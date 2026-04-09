<?php

namespace App\Http\Controllers;

use App\Models\Reapprovisionnement;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $query = Stock::where('shopId', $shopId);

        if ($search = $request->input('search')) {
            $query->where('nom', 'like', '%' . $search . '%');
        }

        $stocks = $query->paginate(20)->withQueryString();

        return view('dashboard.stocks', compact('stocks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'quantite' => 'required|integer|min:0',
            'prixAchat' => 'required|numeric|min:0',
            'prixVente' => 'required|numeric|min:0',
        ]);

        $shopId = $request->attributes->get('shopId');

        Stock::create([
            'shopId' => $shopId,
            'nom' => $request->nom,
            'quantite' => $request->quantite,
            'prixAchat' => $request->prixAchat,
            'prixVente' => $request->prixVente,
            'beneficeNetAttendu' => $request->prixVente - $request->prixAchat,
        ]);

        return back()->with('success', 'Article ajouté au stock.');
    }

    public function update(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $stock = Stock::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $request->validate([
            'nom' => 'required|string',
            'quantite' => 'required|integer|min:0',
            'prixAchat' => 'required|numeric|min:0',
            'prixVente' => 'required|numeric|min:0',
        ]);

        $stock->update([
            'nom' => $request->nom,
            'quantite' => $request->quantite,
            'prixAchat' => $request->prixAchat,
            'prixVente' => $request->prixVente,
            'beneficeNetAttendu' => $request->prixVente - $request->prixAchat,
        ]);

        return back()->with('success', 'Article mis à jour.');
    }

    public function destroy(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $stock = Stock::where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $stock->delete();

        return back()->with('success', 'Article supprimé.');
    }

    public function reapprovisionner(Request $request, string $id)
    {
        $request->validate([
            'quantite' => 'required|integer|min:1',
            'prixAchatUnitaire' => 'required|numeric|min:0',
            'fournisseur' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);

        $shopId = $request->attributes->get('shopId');
        $stock = Stock::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $ancienneQuantite = $stock->quantite;
        $ancienPrixAchat = $stock->prixAchat;
        $nouvelleQuantite = $ancienneQuantite + $request->quantite;

        // CMP = (Ancien stock × Ancien PA + Qté reçue × Nouveau PA) / (Ancien stock + Qté reçue)
        $nouveauPrixAchat = $ancienneQuantite + $request->quantite > 0
            ? ($ancienneQuantite * $ancienPrixAchat + $request->quantite * $request->prixAchatUnitaire) / $nouvelleQuantite
            : $request->prixAchatUnitaire;
        $nouveauPrixAchat = round($nouveauPrixAchat, 2);

        // Enregistrer l'historique
        Reapprovisionnement::create([
            'stockId' => $stock->id,
            'shopId' => $shopId,
            'quantite' => $request->quantite,
            'prixAchatUnitaire' => $request->prixAchatUnitaire,
            'ancienPrixAchat' => $ancienPrixAchat,
            'nouveauPrixAchat' => $nouveauPrixAchat,
            'ancienneQuantite' => $ancienneQuantite,
            'nouvelleQuantite' => $nouvelleQuantite,
            'fournisseur' => $request->fournisseur,
            'note' => $request->note,
        ]);

        // Mettre à jour le stock avec le CMP
        $stock->update([
            'quantite' => $nouvelleQuantite,
            'prixAchat' => $nouveauPrixAchat,
            'beneficeNetAttendu' => $stock->prixVente - $nouveauPrixAchat,
        ]);

        return back()->with('success', "Réapprovisionnement effectué : +{$request->quantite} unités. Nouveau CMP : " . number_format($nouveauPrixAchat, 0, ',', ' ') . ' cfa.');
    }

    public function historiqueReappro(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $stock = Stock::where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $historique = Reapprovisionnement::where('stockId', $id)
            ->where('shopId', $shopId)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'stock' => $stock,
            'historique' => $historique,
        ]);
    }
}
