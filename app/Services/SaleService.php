<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private CreditService $creditService) {}

    public function vendre(
        Stock $stock,
        int $quantite,
        string $shopId,
        string $createdBy,
        ?Client $client = null,
        ?string $cashSessionId = null,
        string $modePaiement = 'comptant',
        ?float $montantPaye = null,
    ): Sale {
        if ($stock->quantite < $quantite) {
            throw new \RuntimeException("Stock insuffisant. Disponible : {$stock->quantite}");
        }

        if ($modePaiement === 'credit') {
            if (!$client || !$client->isRevendeur()) {
                throw new \RuntimeException('Le paiement à crédit est réservé aux revendeurs.');
            }
            if (!$stock->isPieceDetachee()) {
                throw new \RuntimeException('Le crédit s\'applique uniquement aux pièces détachées.');
            }
        }

        return DB::transaction(function () use ($stock, $quantite, $shopId, $createdBy, $client, $cashSessionId, $modePaiement, $montantPaye) {
            $prixUnitaire = ($modePaiement === 'credit' && $client?->isRevendeur() && $stock->prixGros !== null)
                ? $stock->prixGros
                : $stock->prixVente;

            $total        = $prixUnitaire * $quantite;
            $montantPaye  = $montantPaye ?? ($modePaiement === 'comptant' ? $total : 0);
            $resteCredit  = max(0, $total - $montantPaye);

            $stock->decrement('quantite', $quantite);

            $sale = Sale::create([
                'nom'             => $stock->nom,
                'quantite'        => $quantite,
                'client'          => $client?->nom ?? 'Anonyme',
                'prixVente'       => $prixUnitaire,
                'total'           => $total,
                'stockId'         => $stock->id,
                'shopId'          => $shopId,
                'date'            => now(),
                'client_id'       => $client?->id,
                'cash_session_id' => $cashSessionId,
                'mode_paiement'   => $modePaiement,
                'montant_paye'    => $montantPaye,
                'reste_credit'    => $resteCredit,
                'statut'          => $resteCredit > 0 ? 'credit' : 'soldee',
            ]);

            if ($modePaiement === 'credit' && $resteCredit > 0) {
                $this->creditService->enregistrerDette($sale, $client, $resteCredit, $createdBy);
            }

            return $sale;
        });
    }

    public function annuler(Sale $vente): void
    {
        DB::transaction(function () use ($vente) {
            $stock = Stock::withoutGlobalScopes()->find($vente->stockId);
            if ($stock) {
                $stock->increment('quantite', $vente->quantite);
            }

            if ($vente->statut === 'credit' && $vente->client_id) {
                $client = $vente->client()->withoutGlobalScopes()->first();
                if ($client && $vente->reste_credit > 0) {
                    $client->decrement('solde_credit', $vente->reste_credit);
                }
                $vente->creditTransactions()->delete();
            }

            $vente->delete();
        });
    }
}
