<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Settings;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $repairs = Repair::where('shopId', $shopId)
            ->orderBy('date_creation', 'desc')
            ->paginate(20);

        return view('dashboard.reparations-liste', compact('repairs'));
    }

    public function createPlace(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $stocks = Stock::where('shopId', $shopId)->where('quantite', '>', 0)->get();
        $numero = 'REP-' . strtoupper(Str::random(8));
        $settings = Settings::where('shopId', $shopId)->first();

        return view('dashboard.reparations-place', compact('stocks', 'numero', 'settings'));
    }

    public function createRdv(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $stocks = Stock::where('shopId', $shopId)->where('quantite', '>', 0)->get();
        $numero = 'REP-' . strtoupper(Str::random(8));
        $settings = Settings::where('shopId', $shopId)->first();

        return view('dashboard.reparations-rdv', compact('stocks', 'numero', 'settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_reparation' => 'required|in:place,rdv',
            'client_nom' => 'required|string',
            'client_telephone' => 'required|string',
            'appareil_marque_modele' => 'required|string',
        ]);

        $shopId = $request->attributes->get('shopId');
        $user = $request->attributes->get('user');

        // Build pannes_services array
        $pannes = [];
        $descriptions = $request->input('panne_description', []);
        $montants = $request->input('panne_montant', []);
        foreach ($descriptions as $i => $desc) {
            if ($desc) {
                $pannes[] = [
                    'description' => $desc,
                    'montant' => floatval($montants[$i] ?? 0),
                ];
            }
        }

        // Build pieces_rechange array and update stock
        $pieces = [];
        $pieceIds = $request->input('piece_stock_id', []);
        $pieceQtes = $request->input('piece_quantite', []);
        foreach ($pieceIds as $i => $stockId) {
            if ($stockId) {
                $qte = intval($pieceQtes[$i] ?? 1);
                $stock = Stock::find($stockId);
                if ($stock && $stock->quantite >= $qte) {
                    $stock->decrement('quantite', $qte);
                    $pieces[] = [
                        'stockId' => $stockId,
                        'nom' => $stock->nom,
                        'quantiteUtilisee' => $qte,
                    ];
                }
            }
        }

        $totalPannes = array_sum(array_column($pannes, 'montant'));
        $totalPieces = 0;
        foreach ($pieces as $p) {
            $stock = Stock::find($p['stockId']);
            $totalPieces += ($stock->prixVente ?? 0) * $p['quantiteUtilisee'];
        }
        $total = $totalPannes + $totalPieces;
        $paye = floatval($request->input('montant_paye', 0));
        $reste = $total - $paye;

        $repair = Repair::create([
            'shopId' => $shopId,
            'numeroReparation' => $request->input('numeroReparation', 'REP-' . strtoupper(Str::random(8))),
            'type_reparation' => $request->type_reparation,
            'client_nom' => $request->client_nom,
            'client_telephone' => $request->client_telephone,
            'appareil_marque_modele' => $request->appareil_marque_modele,
            'pannes_services' => $pannes,
            'pieces_rechange_utilisees' => $pieces,
            'total_reparation' => $total,
            'montant_paye' => $paye,
            'reste_a_payer' => $reste,
            'statut_reparation' => $request->input('statut_reparation', 'En cours'),
            'date_creation' => now(),
            'date_mise_en_reparation' => now(),
            'date_rendez_vous' => $request->input('date_rendez_vous'),
            'etat_paiement' => $reste <= 0 ? 'Soldé' : 'Non soldé',
            'userId' => $user->id,
        ]);

        $route = $request->type_reparation === 'rdv' ? 'reparations.rdv' : 'reparations.place';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'numero' => $repair->numeroReparation]);
        }

        return redirect()->route($route)->with('success', "Réparation {$repair->numeroReparation} enregistrée.");
    }

    public function show(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $stocks = Stock::where('shopId', $shopId)->get();

        return view('dashboard.reparation-detail', compact('repair', 'stocks'));
    }

    public function update(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $data = $request->only([
            'client_nom', 'client_telephone', 'appareil_marque_modele',
            'statut_reparation', 'montant_paye', 'date_rendez_vous', 'date_retrait',
        ]);

        if (isset($data['montant_paye'])) {
            $data['montant_paye'] = floatval($data['montant_paye']);
            $data['reste_a_payer'] = $repair->total_reparation - $data['montant_paye'];
            $data['etat_paiement'] = $data['reste_a_payer'] <= 0 ? 'Soldé' : 'Non soldé';
        }

        if ($request->has('mark_retrieved')) {
            $data['date_retrait'] = now();
        }
        if ($request->has('unmark_retrieved')) {
            $data['date_retrait'] = null;
        }

        $repair->update($data);

        return back()->with('success', 'Réparation mise à jour.');
    }

    public function destroy(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $repair->delete();

        return redirect()->route('reparations.liste')->with('success', 'Réparation supprimée.');
    }

    public function printReceipt(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $settings = \App\Models\Settings::where('shopId', $shopId)->first();

        return view('dashboard.receipt', compact('repair', 'settings'));
    }

    public function exportCsv(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $repairs = Repair::where('shopId', $shopId)->orderBy('date_creation', 'desc')->get();

        $headers = ["N° Réparation", "Type", "Client", "Téléphone", "Appareil", "Total", "Payé", "Reste", "Statut", "Date Création", "État Paiement"];
        $csv = implode(',', $headers) . "\n";

        foreach ($repairs as $r) {
            $csv .= implode(',', [
                '"' . $r->numeroReparation . '"',
                '"' . $r->type_reparation . '"',
                '"' . $r->client_nom . '"',
                '"' . $r->client_telephone . '"',
                '"' . $r->appareil_marque_modele . '"',
                $r->total_reparation,
                $r->montant_paye,
                $r->reste_a_payer,
                '"' . $r->statut_reparation . '"',
                '"' . ($r->date_creation ? $r->date_creation->format('d/m/Y H:i') : '') . '"',
                '"' . $r->etat_paiement . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reparations.csv"',
        ]);
    }
}
