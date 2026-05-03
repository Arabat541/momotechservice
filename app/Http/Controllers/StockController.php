<?php

namespace App\Http\Controllers;

use App\Models\Reapprovisionnement;
use App\Models\Shop;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $filtre  = $shopId ?? $request->input('boutique'); // patron peut filtrer par boutique
        $query   = Stock::query();
        $shops   = $shopId ? null : Shop::orderBy('nom')->get(); // pour le sélecteur patron

        if ($filtre) {
            $query->where('shopId', $filtre);
        }

        if ($search = $request->input('search')) {
            $search  = substr($search, 0, 100);
            $escaped = str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $search);
            $query->where('nom', 'like', '%' . $escaped . '%');
        }

        if ($categorie = $request->input('categorie')) {
            $query->where('categorie', $categorie);
        }

        if ($request->input('alerte') === '1') {
            $query->whereNotNull('seuil_alerte')->whereColumn('quantite', '<=', 'seuil_alerte');
        }

        $stocks = $query->orderBy('nom')->paginate(20)->withQueryString();

        $statsRaw = Stock::query()
            ->when($filtre, fn($q) => $q->where('shopId', $filtre))
            ->selectRaw('
                COUNT(*) as articles,
                SUM(quantite * prixVente) as valeur,
                SUM((prixVente - prixAchat) * quantite) as benefice,
                SUM(quantite < 10) as stock_faible
            ')
            ->first();

        $statsArticles    = $statsRaw->articles    ?? 0;
        $statsValeur      = $statsRaw->valeur       ?? 0;
        $statsBenefice    = $statsRaw->benefice     ?? 0;
        $statsStockFaible = $statsRaw->stock_faible ?? 0;

        return view('dashboard.stocks', compact(
            'stocks', 'statsArticles', 'statsValeur', 'statsStockFaible', 'statsBenefice',
            'shops', 'shopId', 'filtre'
        ));
    }

    public function store(Request $request)
    {
        $shopId = $request->attributes->get('shopId')
            ?? $request->input('shop_id'); // patron passe shop_id dans le formulaire

        if (!$shopId) {
            return back()->with('error', 'Veuillez sélectionner une boutique.');
        }

        $validated = $request->validate([
            'nom'            => 'required|string|max:255',
            'quantite'       => 'required|integer|min:0|max:9999999',
            'prixAchat'      => 'required|numeric|min:0|max:99999999',
            'prixVente'      => 'required|numeric|min:0|max:99999999',
            'prix_revendeur' => 'nullable|numeric|min:0|max:99999999',
            'prix_demi_gros' => 'nullable|numeric|min:0|max:99999999',
            'prixGros'       => 'nullable|numeric|min:0|max:99999999',
        ]);

        Stock::create([
            'shopId'             => $shopId,
            'nom'                => $validated['nom'],
            'quantite'           => $validated['quantite'],
            'prixAchat'          => $validated['prixAchat'],
            'prixVente'          => $validated['prixVente'],
            'prix_revendeur'     => $validated['prix_revendeur'] ?: null,
            'prix_demi_gros'     => $validated['prix_demi_gros'] ?: null,
            'prixGros'           => $validated['prixGros'] ?: null,
            'beneficeNetAttendu' => $validated['prixVente'] - $validated['prixAchat'],
        ]);

        return back()->with('success', 'Article ajouté au stock.');
    }

    public function update(Request $request, string $id)
    {
        $stock = Stock::findOrFail($id);

        $validated = $request->validate([
            'nom'            => 'required|string|max:255',
            'quantite'       => 'required|integer|min:0|max:9999999',
            'prixAchat'      => 'required|numeric|min:0|max:99999999',
            'prixVente'      => 'required|numeric|min:0|max:99999999',
            'prix_revendeur' => 'nullable|numeric|min:0|max:99999999',
            'prix_demi_gros' => 'nullable|numeric|min:0|max:99999999',
            'prixGros'       => 'nullable|numeric|min:0|max:99999999',
        ]);

        $stock->update([
            'nom'                => $validated['nom'],
            'quantite'           => $validated['quantite'],
            'prixAchat'          => $validated['prixAchat'],
            'prixVente'          => $validated['prixVente'],
            'prix_revendeur'     => $validated['prix_revendeur'] ?: null,
            'prix_demi_gros'     => $validated['prix_demi_gros'] ?: null,
            'prixGros'           => $validated['prixGros'] ?: null,
            'beneficeNetAttendu' => $validated['prixVente'] - $validated['prixAchat'],
        ]);

        return back()->with('success', 'Article mis à jour.');
    }

    public function destroy(Request $request, string $id)
    {
        Stock::findOrFail($id)->delete();
        return back()->with('success', 'Article supprimé.');
    }

    public function reapprovisionner(Request $request, string $id)
    {
        $validated = $request->validate([
            'quantite'          => 'required|integer|min:1|max:9999999',
            'prixAchatUnitaire' => 'required|numeric|min:0|max:99999999',
            'fournisseur'       => 'nullable|string|max:255',
            'note'              => 'nullable|string|max:500',
        ]);

        $stock = Stock::findOrFail($id);

        $stock = $this->stockService->reapprovisionner(
            $stock,
            $validated['quantite'],
            $validated['prixAchatUnitaire'],
            $stock->shopId,
            $validated['fournisseur'] ?? null,
            $validated['note'] ?? null,
        );

        $cmp = number_format($stock->prixAchat, 0, ',', ' ');
        return back()->with('success', "Réapprovisionnement effectué : +{$validated['quantite']} unités. Nouveau CMP : {$cmp} cfa.");
    }

    public function historiqueReappro(Request $request, string $id)
    {
        $stock     = Stock::findOrFail($id);
        $historique = Reapprovisionnement::where('stockId', $id)
            ->where('shopId', $stock->shopId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['stock' => $stock, 'historique' => $historique]);
    }
}
