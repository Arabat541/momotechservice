<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Settings;
use App\Models\Stock;
use App\Services\CashSessionService;
use App\Services\CreditService;
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ArticleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private CashSessionService $cashSessionService,
        private CreditService $creditService,
    ) {}

    public function index(Request $request)
    {
        $shopId    = $request->attributes->get('shopId');
        $categorie = $request->query('categorie');

        $stockQuery = Stock::where('quantite', '>', 0);
        if ($categorie) {
            $stockQuery->where('categorie', $categorie);
        }
        $stocks = $stockQuery->get();

        $ventes = Sale::with('client')->orderByDesc('date')->paginate(30);

        $sortiesRechange = [];
        $repairs = Repair::where('pieces_rechange_utilisees', '!=', '[]')
            ->where('pieces_rechange_utilisees', '!=', '')
            ->orderByDesc('date_mise_en_reparation')
            ->get();

        foreach ($repairs as $repair) {
            $pieces = $repair->pieces_rechange_utilisees;
            if (is_array($pieces)) {
                foreach ($pieces as $piece) {
                    $sortiesRechange[] = [
                        'nom'      => $piece['nom'] ?? '',
                        'quantite' => $piece['quantiteUtilisee'] ?? 1,
                        'client'   => $repair->numeroReparation ?? '-',
                        'type'     => 'Pièce de réchange',
                        'date'     => $repair->date_mise_en_reparation,
                    ];
                }
            }
        }

        $revendeurs = Client::where('type', 'revendeur')->orderBy('nom')->get();

        return view('dashboard.article', compact('stocks', 'ventes', 'sortiesRechange', 'revendeurs'));
    }

    public function vendre(Request $request)
    {
        if (!$request->attributes->get('shopId')) {
            return back()->with('error', 'Aucune boutique sélectionnée.');
        }

        $validated = $request->validate([
            'article_id'    => 'required|string|max:30',
            'quantite'      => 'required|integer|min:1|max:9999',
            'client'        => 'nullable|string|max:150',
            'client_id'     => 'nullable|string|max:30|exists:clients,id',
            'mode_paiement'  => 'nullable|in:comptant,credit',
            'montant_paye'   => 'nullable|numeric|min:0|max:99999999',
            'moyen_paiement' => 'nullable|in:especes,orange_money,wave,mtn_money',
            'remise'         => 'nullable|numeric|min:0|max:99999999',
        ]);

        $shopId  = $request->attributes->get('shopId');
        $user    = $request->attributes->get('user');
        $session = $this->cashSessionService->sessionOuverte($shopId);

        if (!$session) {
            return back()->with('error', 'La caisse doit être ouverte avant d\'enregistrer une vente.');
        }

        $stock = Stock::where('id', $validated['article_id'])->where('shopId', $shopId)->firstOrFail();

        $client        = null;
        $modePaiement  = $validated['mode_paiement'] ?? 'comptant';

        if (!empty($validated['client_id'])) {
            $client = Client::withoutGlobalScopes()
                ->where('id', $validated['client_id'])
                ->where('shopId', $shopId)
                ->firstOrFail();
        }

        try {
            $sale = $this->saleService->vendre(
                stock: $stock,
                quantite: $validated['quantite'],
                shopId: $shopId,
                createdBy: $user->id,
                client: $client,
                cashSessionId: $session?->id,
                modePaiement: $modePaiement,
                montantPaye: isset($validated['montant_paye']) ? floatval($validated['montant_paye']) : null,
                clientNom: $validated['client'] ?? null,
                remise: floatval($validated['remise'] ?? 0),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($modePaiement === 'comptant' && !empty($validated['moyen_paiement'])) {
            $sale->update(['moyen_paiement' => $validated['moyen_paiement']]);
        }

        $label = $modePaiement === 'credit'
            ? "Vente à crédit de {$sale->quantite}x {$sale->nom} enregistrée."
            : "Vente de {$sale->quantite}x {$sale->nom} enregistrée.";

        return back()->with('success', $label);
    }

    public function annuler(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $vente  = Sale::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $this->saleService->annuler($vente);

        return back()->with('success', 'Vente annulée, stock restauré.');
    }

    public function edit(Request $request, string $id)
    {
        $role  = $request->attributes->get('userRole', session('user_role'));
        $vente = $role === 'patron'
            ? Sale::withoutGlobalScopes()->findOrFail($id)
            : Sale::findOrFail($id);

        $stock      = Stock::withoutGlobalScopes()->find($vente->stockId);
        $revendeurs = Client::where('type', 'revendeur')->orderBy('nom')->get();

        return view('ventes.edit', compact('vente', 'stock', 'revendeurs'));
    }

    public function update(Request $request, string $id)
    {
        $role  = $request->attributes->get('userRole', session('user_role'));
        $user  = $request->attributes->get('user');
        $vente = $role === 'patron'
            ? Sale::withoutGlobalScopes()->findOrFail($id)
            : Sale::findOrFail($id);

        if (!in_array($vente->statut, ['soldee', 'credit'])) {
            return back()->with('error', 'Cette vente ne peut pas être modifiée.');
        }

        $validated = $request->validate([
            'client'        => 'nullable|string|max:150',
            'client_id'     => 'nullable|string|max:30|exists:clients,id',
            'quantite'      => 'required|integer|min:1|max:9999',
            'prixVente'     => 'required|numeric|min:0|max:99999999',
            'mode_paiement'  => 'required|in:comptant,credit',
            'montant_paye'   => 'required|numeric|min:0|max:99999999',
            'moyen_paiement' => 'nullable|in:especes,orange_money,wave,mtn_money',
            'remise'         => 'nullable|numeric|min:0|max:99999999',
            'date'           => 'required|date',
        ]);

        if ($validated['mode_paiement'] === 'credit' && empty($validated['client_id'])) {
            return back()->withInput()->with('error', 'Un revendeur doit être sélectionné pour une vente à crédit.');
        }

        $stock           = Stock::withoutGlobalScopes()->find($vente->stockId);
        $ancienneQte     = $vente->quantite;
        $nouvelleQte     = (int) $validated['quantite'];
        $deltaQte        = $nouvelleQte - $ancienneQte;

        if ($deltaQte > 0 && (!$stock || $stock->quantite < $deltaQte)) {
            $dispo = $stock?->quantite ?? 0;
            return back()->withInput()->with('error', "Stock insuffisant — seulement {$dispo} unité(s) disponible(s) pour augmenter la quantité.");
        }

        $ancienMode         = $vente->mode_paiement;
        $nouveauMode        = $validated['mode_paiement'];
        $sousTotal          = floatval($validated['prixVente']) * $nouvelleQte;
        $nouvelleRemise     = min(floatval($validated['remise'] ?? 0), max(0.0, $sousTotal - 0.01));
        $nouveauTotal       = $sousTotal - $nouvelleRemise;
        $nouveauMontantPaye = floatval($validated['montant_paye']);
        $nouveauResteCredit = max(0.0, $nouveauTotal - $nouveauMontantPaye);
        $ancienResteCredit  = floatval($vente->reste_credit);

        try {
            DB::transaction(function () use (
                $vente, $stock, $deltaQte, $ancienneQte, $nouvelleQte,
                $nouveauTotal, $nouveauMontantPaye, $nouveauResteCredit,
                $ancienResteCredit, $ancienMode, $nouveauMode, $validated, $user, $nouvelleRemise
            ) {
                // 1. Ajustement stock
                if ($deltaQte > 0 && $stock) {
                    $stock->decrement('quantite', $deltaQte);
                } elseif ($deltaQte < 0 && $stock) {
                    $stock->increment('quantite', abs($deltaQte));
                }

                // 2. Résolution des clients
                $nouveauClientId = $validated['client_id'] ?? null;
                $ancienClientId  = $vente->client_id;

                $nouveauClient = $nouveauClientId
                    ? Client::withoutGlobalScopes()->find($nouveauClientId)
                    : null;
                $ancienClient = $ancienClientId
                    ? Client::withoutGlobalScopes()->find($ancienClientId)
                    : null;

                // 3. Ajustement crédit
                if ($ancienMode === 'comptant' && $nouveauMode === 'credit') {
                    // comptant → crédit : créer la dette
                    if ($nouveauClient && $nouveauResteCredit > 0) {
                        $this->creditService->enregistrerDette($vente, $nouveauClient, $nouveauResteCredit, $user->id);
                    }
                } elseif ($ancienMode === 'credit' && $nouveauMode === 'comptant') {
                    // crédit → comptant : effacer la dette résiduelle via avoir
                    if ($ancienClient && $ancienResteCredit > 0) {
                        $this->creditService->enregistrerAvoir($ancienClient, $ancienResteCredit, $user->id, "Modification vente — passage en comptant");
                    }
                } elseif ($ancienMode === 'credit' && $nouveauMode === 'credit') {
                    $clientChanged = $nouveauClientId && $nouveauClientId !== $ancienClientId;

                    if ($clientChanged) {
                        // Réaffectation : annuler l'ancienne dette, créer une nouvelle
                        if ($ancienClient && $ancienResteCredit > 0) {
                            $this->creditService->enregistrerAvoir($ancienClient, $ancienResteCredit, $user->id, "Réaffectation crédit — modification vente");
                        }
                        if ($nouveauClient && $nouveauResteCredit > 0) {
                            $this->creditService->enregistrerDette($vente, $nouveauClient, $nouveauResteCredit, $user->id);
                        }
                    } else {
                        // Même client : ajustement delta
                        $delta = $nouveauResteCredit - $ancienResteCredit;
                        if ($delta > 0 && $ancienClient) {
                            $this->creditService->enregistrerDette($vente, $ancienClient, $delta, $user->id);
                        } elseif ($delta < 0 && $ancienClient) {
                            $this->creditService->enregistrerAvoir($ancienClient, abs($delta), $user->id, "Ajustement crédit — modification vente");
                        }
                    }
                }

                // 4. Nom client final
                $clientNomFinal = match(true) {
                    $nouveauMode === 'credit' && $nouveauClient !== null => $nouveauClient->nom,
                    !empty($validated['client'])                         => $validated['client'],
                    default                                              => $vente->client,
                };

                // 5. Mise à jour de la vente
                $vente->update([
                    'client'        => $clientNomFinal,
                    'client_id'     => $nouveauMode === 'credit' ? ($nouveauClientId ?? $ancienClientId) : null,
                    'quantite'      => $nouvelleQte,
                    'prixVente'     => floatval($validated['prixVente']),
                    'remise'        => $nouvelleRemise,
                    'total'         => $nouveauTotal,
                    'mode_paiement' => $nouveauMode,
                    'montant_paye'  => $nouveauMontantPaye,
                    'reste_credit'  => $nouveauResteCredit,
                    'statut'         => $nouveauResteCredit > 0 ? 'credit' : 'soldee',
                    'moyen_paiement' => $nouveauMode === 'comptant' ? ($validated['moyen_paiement'] ?? null) : null,
                    'date'           => $validated['date'],
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('article')->with('success', 'Vente modifiée avec succès.');
    }

    public function printReceipt(Request $request, string $id)
    {
        $vente = Sale::with('client')->findOrFail($id);

        $settings = Settings::withoutGlobalScopes()->where('shopId', $vente->shopId)->first();
        $qrCode   = QrCode::format('svg')->size(100)->errorCorrection('M')->generate((string) $vente->id);

        return view('dashboard.sale-receipt', compact('vente', 'settings', 'qrCode'));
    }

    public function exportPdf(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $ventes = Sale::with('client')
            ->when($shopId, fn($q) => $q->where('shopId', $shopId))
            ->orderByDesc('date')
            ->get();

        $companyInfo = $this->getCompanyInfo($shopId);
        $logoBase64  = $this->getLogoBase64();

        return Pdf::loadView('exports.ventes-articles-pdf', compact('ventes', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'landscape')
            ->download('ventes-articles-' . now()->format('Y-m-d') . '.pdf');
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
