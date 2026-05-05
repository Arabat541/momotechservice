<?php

namespace App\Http\Controllers;

use App\Jobs\EnvoyerSmsJob;
use App\Models\Client;
use App\Models\Repair;
use App\Models\Settings;
use App\Models\Stock;
use App\Services\CashSessionService;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\RepairService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RepairController extends Controller
{
    public function __construct(
        private RepairService $repairService,
        private InvoiceService $invoiceService,
        private CashSessionService $cashSessionService,
        private NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $search  = $request->input('search', '');
        $statut  = $request->input('statut', '');
        $type    = $request->input('type', '');

        $query = Repair::query();

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

        $allStatuts  = array_keys(RepairService::STATUTS);
        $repairSvc   = $this->repairService;

        return view('dashboard.reparations-liste', compact('repairs', 'search', 'statut', 'type', 'allStatuts', 'repairSvc'));
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
            'mode_paiement'          => 'nullable|in:especes,orange_money,wave,mtn_money,cheque,virement',
            'statut_reparation'      => 'nullable|in:En attente,En attente de paiement,En cours,En attente de pièces,Terminé,Prêt pour retrait,Irréparable,Livré,Annulé',
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
            return response()->json(['success' => true, 'id' => $repair->id, 'numero' => $repair->numeroReparation]);
        }

        $route = $validated['type_reparation'] === 'rdv' ? 'reparations.rdv' : 'reparations.place';
        return redirect()->route($route)->with('success', "Réparation {$repair->numeroReparation} enregistrée.");
    }

    // Mise à jour du diagnostic : pannes, pièces, statut technique, notes réparateur
    public function updateDiagnostic(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $validated = $request->validate([
            'statut_reparation'        => 'sometimes|in:En cours,En attente de pièces,Terminé,Prêt pour retrait,Irréparable',
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
            if ($validated['statut_reparation'] !== $repair->statut_reparation) {
                $role    = $request->attributes->get('userRole', session('user_role', 'caissiere'));
                $allowed = $this->repairService->allowedTransitions($repair->statut_reparation, $role);
                if (!in_array($validated['statut_reparation'], $allowed)) {
                    return back()->with('error', "Transition vers « {$validated['statut_reparation']} » non autorisée depuis « {$repair->statut_reparation} ».");
                }
            }
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

        // Dates automatiques selon le nouveau statut
        if (isset($data['statut_reparation']) && $data['statut_reparation'] !== $ancienStatut) {
            $data = array_merge($data, $this->repairService->autoDateFields($data['statut_reparation']));
        }

        $repair->update($data);

        $nouveauStatut = $data['statut_reparation'] ?? null;

        // SMS + notifications internes lors des transitions de statut
        if ($nouveauStatut && $nouveauStatut !== $ancienStatut) {
            $telephone = $repair->client?->telephone ?? $repair->client_telephone;

            if ($nouveauStatut === 'Terminé') {
                $repair->update(['relance_count' => 0, 'derniere_relance' => null]);
                if ($telephone) {
                    EnvoyerSmsJob::dispatch('notification', $telephone, $repair->numeroReparation, $repair->shopId);
                }
            } elseif ($nouveauStatut === 'Prêt pour retrait') {
                if ($telephone) {
                    EnvoyerSmsJob::dispatch('retrait', $telephone, $repair->numeroReparation, $repair->shopId);
                }
            }

            $this->repairService->onStatutChange($repair, $nouveauStatut, $this->notificationService);
        }

        return back()->with('success', 'Diagnostic mis à jour.');
    }

    public function show(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::with('photos')->findOrFail($id);
        $stocks = Stock::query()->get();

        $userRole    = $request->attributes->get('userRole');
        $allStatuts  = array_keys(RepairService::STATUTS);
        $repairSvc   = $this->repairService;
        $diagStatuts = ['En cours', 'En attente de pièces', 'Terminé', 'Prêt pour retrait', 'Irréparable'];

        return view('dashboard.reparation-detail', compact(
            'repair', 'stocks', 'allStatuts', 'diagStatuts', 'repairSvc', 'userRole'
        ));
    }

    public function update(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $validated = $request->validate([
            'client_nom'           => 'sometimes|string|max:150',
            'client_telephone'     => ['sometimes', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'appareil_marque_modele' => 'sometimes|string|max:200',
            'statut_reparation'    => 'sometimes|in:En attente,En attente de paiement,En cours,En attente de pièces,Terminé,Prêt pour retrait,Irréparable,Livré,Annulé',
            'montant_paye'         => 'sometimes|numeric|min:0|max:99999999',
            'mode_paiement'        => 'nullable|in:especes,orange_money,wave,mtn_money,cheque,virement',
            'date_rendez_vous'     => 'nullable|date',
            'date_retrait'         => 'nullable|date',
        ]);

        $data = array_intersect_key($validated, array_flip([
            'client_nom', 'client_telephone', 'appareil_marque_modele',
            'statut_reparation', 'montant_paye', 'mode_paiement', 'date_rendez_vous', 'date_retrait',
        ]));

        if (isset($data['statut_reparation']) && $data['statut_reparation'] !== $repair->statut_reparation) {
            $role    = $request->attributes->get('userRole', session('user_role', 'caissiere'));
            $allowed = $this->repairService->allowedTransitions($repair->statut_reparation, $role);
            if (!in_array($data['statut_reparation'], $allowed)) {
                return back()->with('error', "Transition vers « {$data['statut_reparation']} » non autorisée depuis « {$repair->statut_reparation} ».");
            }
        }

        if (isset($data['montant_paye'])) {
            $data = array_merge($data, $this->repairService->applyPayment($repair, $data['montant_paye']));
        }

        if ($request->has('mark_retrieved')) {
            $data['date_retrait'] = now();
            $data['statut_reparation'] = 'Livré';
        }
        if ($request->has('unmark_retrieved')) {
            $data['date_retrait'] = null;
        }

        // Dates auto pour les transitions de statut
        if (isset($data['statut_reparation']) && $data['statut_reparation'] !== $repair->statut_reparation) {
            $data = array_merge($data, $this->repairService->autoDateFields($data['statut_reparation']));
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
        $repair   = Repair::findOrFail($id);
        $settings = Settings::where('shopId', $repair->shopId)->first();

        $trackUrl = route('track') . '?numero=' . urlencode($repair->numeroReparation);
        $qrCode   = QrCode::format('svg')->size(120)->errorCorrection('M')->generate($trackUrl);

        return view('dashboard.receipt', compact('repair', 'settings', 'qrCode'));
    }

    public function enregistrerPaiement(Request $request, string $id)
    {
        $repair = Repair::findOrFail($id);

        $validated = $request->validate([
            'montant'       => ['required', 'numeric', 'min:0.01', 'max:' . max(0.01, (float) $repair->reste_a_payer)],
            'mode_paiement' => 'required|in:especes,orange_money,wave,mtn_money,cheque,virement',
        ]);

        DB::transaction(function () use ($repair, $validated) {
            $nouveauMontantPaye = $repair->montant_paye + floatval($validated['montant']);
            $nouveauReste       = max(0.0, $repair->total_reparation - $nouveauMontantPaye);

            $repair->montant_paye  = $nouveauMontantPaye;
            $repair->reste_a_payer = $nouveauReste;
            $repair->mode_paiement = $validated['mode_paiement'];

            if ($nouveauReste <= 0) {
                $repair->etat_paiement = 'Soldé';
                $repair->reste_a_payer = 0;
            }

            $repair->save();
        });

        $montantFormate = number_format(floatval($validated['montant']), 0, ',', ' ');
        return back()->with('success', "Paiement de {$montantFormate} cfa enregistré.");
    }

    public function exportPdf(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $repairs = Repair::when($shopId, fn($q) => $q->where('shopId', $shopId))
            ->orderBy('date_creation', 'desc')
            ->get();

        $companyInfo = $this->getCompanyInfo($shopId);
        $logoBase64  = $this->getLogoBase64();

        return Pdf::loadView('exports.reparations-pdf', compact('repairs', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'landscape')
            ->download('reparations-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getCompanyInfo(?string $shopId): array
    {
        $settings = $shopId
            ? Settings::withoutGlobalScopes()->where('shopId', $shopId)->first()
            : Settings::withoutGlobalScopes()->first();
        $default = ['nom' => 'MOMO TECH SERVICE', 'adresse' => '', 'telephone' => ''];
        return array_merge($default, $settings?->companyInfo ?? []);
    }

    private function getLogoBase64(): ?string
    {
        foreach (['logo-receipt.png', 'logo-app.png'] as $file) {
            $path = public_path('images/' . $file);
            if (file_exists($path)) {
                return base64_encode(file_get_contents($path));
            }
        }
        return null;
    }
}
