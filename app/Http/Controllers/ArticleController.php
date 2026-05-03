<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\CashSessionService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private CashSessionService $cashSessionService,
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
            'mode_paiement' => 'nullable|in:comptant,credit',
            'montant_paye'  => 'nullable|numeric|min:0|max:99999999',
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
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
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
}
