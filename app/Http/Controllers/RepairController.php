<?php

namespace App\Http\Controllers;

use App\Jobs\EnvoyerSmsJob;
use App\Models\Client;
use App\Models\Repair;
use App\Models\Settings;
use App\Models\Stock;
use App\Services\CashSessionService;
use App\Services\InvoiceService;
use App\Services\RepairService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RepairController extends Controller
{
    public function __construct(
        private RepairService $repairService,
        private InvoiceService $invoiceService,
        private CashSessionService $cashSessionService,
    ) {}

    public function index(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $search  = $request->input('search', '');
        $statut  = $request->input('statut', '');
        $type    = $request->input('type', '');

        $query = Repair::where('shopId', $shopId);

        if ($statut) {
            $query->where('statut_reparation', $statut);
        }
        if ($type) {
            $query->where('type_reparation', $type);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numeroReparation', 'like', "%{$search}%")
                  ->orWhere('appareil_marque_modele', 'like', "%{$search}%")
                  ->orWhere('client_nom', 'like', "%{$search}%")
                  ->orWhere('client_telephone', 'like', "%{$search}%");
            });
        }

        $repairs = $query->orderBy('date_creation', 'desc')->paginate(20)->withQueryString();

        return view('dashboard.reparations-liste', compact('repairs', 'search', 'statut', 'type'));
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
        if (!$request->attributes->get('shopId')) {
            return back()->with('error', 'Aucune boutique sélectionnée.');
        }

        $validated = $request->validate([
            'type_reparation'        => 'required|in:place,rdv',
            'client_nom'             => 'required|string|max:150',
            'client_telephone'       => ['required', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'appareil_marque_modele' => 'required|string|max:200',
            'panne_description'      => 'nullable|array|max:20',
            'panne_description.*'    => 'nullable|string|max:255',
            'panne_montant'          => 'nullable|array|max:20',
            'panne_montant.*'        => 'nullable|numeric|min:0|max:99999999',
            'piece_stock_id'         => 'nullable|array|max:20',
            'piece_stock_id.*'       => 'nullable|exists:stocks,id',
            'piece_quantite'         => 'nullable|array|max:20',
            'piece_quantite.*'       => 'nullable|integer|min:1|max:9999',
            'montant_paye'           => 'nullable|numeric|min:0|max:99999999',
            'statut_reparation'      => 'nullable|in:En attente,En cours,Terminé,Récupéré,En attente de pièces,Annulé',
            'date_rendez_vous'       => 'nullable|date',
            'numeroReparation'       => 'nullable|string|max:30',
        ]);

        $shopId  = $request->attributes->get('shopId');
        $user    = $request->attributes->get('user');
        $session = $this->cashSessionService->sessionOuverte($shopId);

        if (!$session) {
            return back()->with('error', 'La caisse doit être ouverte avant d\'enregistrer une réparation.');
        }

        // Retrouver ou créer le client
        $client = Client::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->where('telephone', $validated['client_telephone'])
            ->first();

        if (!$client) {
            $client = Client::create([
                'shopId'    => $shopId,
                'nom'       => $validated['client_nom'],
                'telephone' => $validated['client_telephone'],
                'type'      => 'particulier',
            ]);
        }

        $validated['client_id']       = $client->id;
        $validated['cash_session_id'] = $session?->id;

        $repair = $this->repairService->create($validated, $shopId, $user->id);

        // Créer la facture immédiatement si une caisse est ouverte
        if ($session) {
            $this->invoiceService->creerDepuisReparation(
                $repair,
                floatval($validated['montant_paye'] ?? 0),
                $session->id,
                $user->id
            );
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'numero' => $repair->numeroReparation]);
        }

        $route = $validated['type_reparation'] === 'rdv' ? 'reparations.rdv' : 'reparations.place';
        return redirect()->route($route)->with('success', "Réparation {$repair->numeroReparation} enregistrée.");
    }

    // Réservé au technicien : diagnostic uniquement (pannes, pièces, statut, notes)
    public function updateDiagnostic(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $validated = $request->validate([
            'statut_reparation'        => 'sometimes|in:En diagnostic,En cours,Terminé,En attente de pièces',
            'panne_description'        => 'nullable|array|max:20',
            'panne_description.*'      => 'nullable|string|max:255',
            'panne_montant'            => 'nullable|array|max:20',
            'panne_montant.*'          => 'nullable|numeric|min:0|max:99999999',
            'piece_stock_id'           => 'nullable|array|max:20',
            'piece_stock_id.*'         => 'nullable|exists:stocks,id',
            'piece_quantite'           => 'nullable|array|max:20',
            'piece_quantite.*'         => 'nullable|integer|min:1|max:9999',
            'notes_technicien'         => 'nullable|string|max:1000',
        ]);

        $data = [];

        if (isset($validated['statut_reparation'])) {
            $data['statut_reparation'] = $validated['statut_reparation'];
        }

        if (isset($validated['notes_technicien'])) {
            $data['notes_technicien'] = $validated['notes_technicien'];
        }

        if (!empty($validated['panne_description'])) {
            $pannes = $this->repairService->buildPannes(
                $validated['panne_description'],
                $validated['panne_montant'] ?? []
            );
            // Restore previous stock quantities before reprocessing so we don't double-decrement.
            $this->repairService->restorePiecesStock(
                $repair->pieces_rechange_utilisees ?? [],
                $shopId
            );
            $pieces = $this->repairService->buildPieces(
                $validated['piece_stock_id'] ?? [],
                $validated['piece_quantite'] ?? [],
                $shopId
            );
            $totals = $this->repairService->computeTotals($pannes, $pieces, $repair->montant_paye);

            $data['pannes_services']           = $pannes;
            $data['pieces_rechange_utilisees'] = $pieces;
            $data['total_reparation']          = $totals['total'];
            $data['reste_a_payer']             = $totals['reste'];
            $data['etat_paiement']             = $totals['etat_paiement'];

            // Mettre à jour la facture liée
            if ($repair->invoice) {
                $repair->invoice->update([
                    'montant_final' => $totals['total'],
                    'reste_a_payer' => max(0, $totals['total'] - $repair->invoice->montant_paye),
                ]);
            }
        }

        $ancienStatut = $repair->statut_reparation;
        $repair->update($data);

        // Envoyer SMS + marquer date_terminee si la réparation passe à "Terminé"
        if (
            isset($data['statut_reparation'])
            && $data['statut_reparation'] === 'Terminé'
            && $ancienStatut !== 'Terminé'
        ) {
            $repair->update(['date_terminee' => now(), 'relance_count' => 0, 'derniere_relance' => null]);
            $telephone = $repair->client?->telephone ?? $repair->client_telephone;
            if ($telephone) {
                EnvoyerSmsJob::dispatch('notification', $telephone, $repair->numeroReparation, $repair->shopId);
            }
        }

        return back()->with('success', 'Diagnostic mis à jour.');
    }

    public function show(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::with('photos')->where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $stocks = Stock::where('shopId', $shopId)->get();

        return view('dashboard.reparation-detail', compact('repair', 'stocks'));
    }

    public function update(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $validated = $request->validate([
            'client_nom'           => 'sometimes|string|max:150',
            'client_telephone'     => ['sometimes', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'appareil_marque_modele' => 'sometimes|string|max:200',
            'statut_reparation'    => 'sometimes|in:En attente,En cours,Terminé,Récupéré,En attente de pièces,Annulé',
            'montant_paye'         => 'sometimes|numeric|min:0|max:99999999',
            'date_rendez_vous'     => 'nullable|date',
            'date_retrait'         => 'nullable|date',
        ]);

        $data = array_intersect_key($validated, array_flip([
            'client_nom', 'client_telephone', 'appareil_marque_modele',
            'statut_reparation', 'montant_paye', 'date_rendez_vous', 'date_retrait',
        ]));

        if (isset($data['montant_paye'])) {
            $data = array_merge($data, $this->repairService->applyPayment($repair, $data['montant_paye']));
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
        Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail()->delete();

        return redirect()->route('reparations.liste')->with('success', 'Réparation supprimée.');
    }

    public function printReceipt(Request $request, string $id)
    {
        $shopId  = $request->attributes->get('shopId');
        $repair  = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $settings = Settings::where('shopId', $shopId)->first();

        return view('dashboard.receipt', compact('repair', 'settings'));
    }

    public function exportCsv(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
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
                '"' . ($r->date_creation?->format('d/m/Y H:i') ?? '') . '"',
                '"' . $r->etat_paiement . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reparations.csv"',
        ]);
    }
}
