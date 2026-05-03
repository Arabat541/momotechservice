<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\PendingSale;
use App\Models\PendingSaleLine;
use App\Models\Stock;
use App\Services\CashSessionService;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingSaleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private CashSessionService $cashSessionService,
    ) {}

    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');

        $pendingSales = PendingSale::with(['client', 'lines'])
            ->where('statut', 'en_attente')
            ->orderBy('created_at')
            ->get();

        $stocks     = Stock::where('shopId', $shopId)->where('quantite', '>', 0)->orderBy('nom')->get();
        $revendeurs = Client::where('type', 'revendeur')->orderBy('nom')->get();

        return view('dashboard.vente-attente', compact('pendingSales', 'stocks', 'revendeurs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'     => 'required|string|max:30|exists:clients,id',
            'mode_paiement' => 'required|in:comptant,credit',
            'notes'         => 'nullable|string|max:500',
        ]);

        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $client = Client::withoutGlobalScopes()
            ->where('id', $validated['client_id'])
            ->where('shopId', $shopId)
            ->firstOrFail();

        if ($validated['mode_paiement'] === 'credit' && !$client->isRevendeur()) {
            return back()->with('error', 'Le paiement à crédit est réservé aux revendeurs.');
        }

        $deja = PendingSale::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->where('client_id', $validated['client_id'])
            ->where('statut', 'en_attente')
            ->exists();

        if ($deja) {
            return back()->with('error', "Une vente en attente est déjà ouverte pour {$client->nom}.");
        }

        PendingSale::create([
            'shopId'        => $shopId,
            'client_id'     => $validated['client_id'],
            'created_by'    => $user->id,
            'mode_paiement' => $validated['mode_paiement'],
            'notes'         => $validated['notes'] ?? null,
            'statut'        => 'en_attente',
        ]);

        return back()->with('success', "Vente en attente ouverte pour {$client->nom}.");
    }

    public function addLine(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');

        $sale = PendingSale::withoutGlobalScopes()
            ->where('id', $id)
            ->where('shopId', $shopId)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        $validated = $request->validate([
            'stock_id' => 'required|string|max:30',
            'quantite' => 'required|integer|min:1|max:9999',
        ]);

        $stock = Stock::withoutGlobalScopes()
            ->where('id', $validated['stock_id'])
            ->where('shopId', $shopId)
            ->firstOrFail();

        $sale->loadMissing('client');
        $client    = $sale->client;
        $quantite  = (int) $validated['quantite'];

        // Si la ligne existe déjà pour cet article, fusionner les quantités
        $existante = $sale->lines()->where('stock_id', $stock->id)->first();
        $qtyFinale = $existante ? $existante->quantite + $quantite : $quantite;

        if ($stock->quantite < $qtyFinale) {
            return back()->with('error', "Stock insuffisant pour « {$stock->nom} » (disponible : {$stock->quantite}).");
        }

        $prix   = $this->resolvePrice($stock, $qtyFinale, $client);
        $palier = $this->resolvePalier($stock, $qtyFinale, $client);

        if ($existante) {
            $existante->update([
                'quantite'      => $qtyFinale,
                'prix_unitaire' => $prix,
                'palier'        => $palier,
            ]);
        } else {
            $sale->lines()->create([
                'stock_id'      => $stock->id,
                'stock_nom'     => $stock->nom,
                'quantite'      => $qtyFinale,
                'prix_unitaire' => $prix,
                'palier'        => $palier,
            ]);
        }

        return back()->with('success', "{$stock->nom} ajouté.");
    }

    public function removeLine(Request $request, string $saleId, int $lineId)
    {
        $shopId = $request->attributes->get('shopId');

        $sale = PendingSale::withoutGlobalScopes()
            ->where('id', $saleId)
            ->where('shopId', $shopId)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        $sale->lines()->where('id', $lineId)->firstOrFail()->delete();

        return back()->with('success', 'Article retiré.');
    }

    public function valider(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $sale = PendingSale::withoutGlobalScopes()
            ->with(['client', 'lines'])
            ->where('id', $id)
            ->where('shopId', $shopId)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        if ($sale->lines->isEmpty()) {
            return back()->with('error', 'Impossible de valider une vente sans articles.');
        }

        $validated = $request->validate([
            'montant_paye' => 'nullable|numeric|min:0|max:99999999',
        ]);

        $session = $this->cashSessionService->sessionOuverte($shopId);
        if (!$session) {
            return back()->with('error', 'La caisse doit être ouverte pour valider une vente.');
        }

        $totalGeneral = $sale->lines->sum(fn($l) => $l->prix_unitaire * $l->quantite);
        $montantPaye  = isset($validated['montant_paye'])
            ? floatval($validated['montant_paye'])
            : ($sale->mode_paiement === 'comptant' ? $totalGeneral : 0);

        DB::transaction(function () use ($sale, $shopId, $user, $session, $montantPaye, $totalGeneral) {
            $restePaye = $montantPaye;

            foreach ($sale->lines as $line) {
                $stock      = Stock::withoutGlobalScopes()->findOrFail($line->stock_id);
                $lineTotal  = $line->prix_unitaire * $line->quantite;
                $linePaye   = min($restePaye, $lineTotal);
                $restePaye  = max(0, $restePaye - $lineTotal);

                $this->saleService->vendre(
                    stock:         $stock,
                    quantite:      $line->quantite,
                    shopId:        $shopId,
                    createdBy:     $user->id,
                    client:        $sale->client,
                    cashSessionId: $session->id,
                    modePaiement:  $sale->mode_paiement,
                    montantPaye:   $linePaye,
                    clientNom:     null,
                );
            }

            $sale->update([
                'statut'       => 'validee',
                'montant_paye' => $montantPaye,
                'validated_at' => now(),
            ]);
        });

        $totalFmt = number_format($totalGeneral, 0, ',', ' ');
        return back()->with('success', "Vente validée — Total : {$totalFmt} F.");
    }

    public function annuler(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');

        $sale = PendingSale::withoutGlobalScopes()
            ->where('id', $id)
            ->where('shopId', $shopId)
            ->where('statut', 'en_attente')
            ->firstOrFail();

        $sale->update(['statut' => 'annulee']);

        return back()->with('success', 'Vente en attente annulée.');
    }

    private function resolvePrice(Stock $stock, int $quantite, ?Client $client): float
    {
        if ($client?->isRevendeur()) {
            if ($quantite >= 10 && $stock->prixGros !== null)       return $stock->prixGros;
            if ($quantite >= 3  && $stock->prix_demi_gros !== null) return $stock->prix_demi_gros;
            if ($stock->prix_revendeur !== null)                    return $stock->prix_revendeur;
        }
        return $stock->prixVente;
    }

    private function resolvePalier(Stock $stock, int $quantite, ?Client $client): string
    {
        if ($client?->isRevendeur()) {
            if ($quantite >= 10 && $stock->prixGros !== null)       return 'gros';
            if ($quantite >= 3  && $stock->prix_demi_gros !== null) return 'demi_gros';
            if ($stock->prix_revendeur !== null)                    return 'revendeur';
        }
        return 'normal';
    }
}
