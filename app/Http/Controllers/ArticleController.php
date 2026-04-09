<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $stocks = Stock::where('shopId', $shopId)->where('quantite', '>', 0)->get();
        $ventes = Sale::where('shopId', $shopId)->orderByDesc('date')->get();

        // Pièces de rechange sorties pour réparations
        $sortiesRechange = [];
        $repairs = Repair::where('shopId', $shopId)
            ->where('pieces_rechange_utilisees', '!=', '[]')
            ->where('pieces_rechange_utilisees', '!=', '')
            ->orderByDesc('date_mise_en_reparation')
            ->get();

        foreach ($repairs as $repair) {
            $pieces = $repair->pieces_rechange_utilisees;
            if (is_array($pieces)) {
                foreach ($pieces as $piece) {
                    $sortiesRechange[] = [
                        'nom' => $piece['nom'] ?? '',
                        'quantite' => $piece['quantiteUtilisee'] ?? 1,
                        'client' => $repair->numeroReparation ?? '-',
                        'type' => 'Pièce de réchange',
                        'date' => $repair->date_mise_en_reparation,
                    ];
                }
            }
        }

        return view('dashboard.article', compact('stocks', 'ventes', 'sortiesRechange'));
    }

    public function vendre(Request $request)
    {
        $request->validate([
            'article_id' => 'required|string',
            'quantite' => 'required|integer|min:1',
            'client' => 'required|string',
        ]);

        $shopId = $request->attributes->get('shopId');
        $stock = Stock::where('id', $request->article_id)->where('shopId', $shopId)->firstOrFail();

        if ($stock->quantite < $request->quantite) {
            return back()->with('error', 'Stock insuffisant.');
        }

        $stock->decrement('quantite', $request->quantite);

        Sale::create([
            'nom' => $stock->nom,
            'quantite' => $request->quantite,
            'client' => $request->client,
            'prixVente' => $stock->prixVente,
            'total' => $stock->prixVente * $request->quantite,
            'stockId' => $stock->id,
            'shopId' => $shopId,
            'date' => now(),
        ]);

        return back()->with('success', "Vente de {$request->quantite}x {$stock->nom} enregistrée.");
    }

    public function annuler(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $vente = Sale::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $stock = Stock::find($vente->stockId);
        if ($stock) {
            $stock->increment('quantite', $vente->quantite);
        }

        $vente->delete();

        return back()->with('success', 'Vente annulée, stock restauré.');
    }
}
