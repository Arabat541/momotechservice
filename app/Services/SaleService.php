<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\PricingService;

class SaleService
{
    public function __construct(
        private CreditService $creditService,
        private NotificationService $notificationService,
    ) {}

    public function vendre(
        Stock $stock,
        int $quantite,
        string $shopId,
        string $createdBy,
        ?Client $client = null,
        ?string $cashSessionId = null,
        string $modePaiement = 'comptant',
        ?float $montantPaye = null,
        ?string $clientNom = null,
        float $remise = 0.0,
    ): Sale {
        if ($modePaiement === 'credit') {
            if (!$client || !$client->isRevendeur()) {
                throw new \RuntimeException('Le paiement à crédit est réservé aux revendeurs.');
            }
        }

        return DB::transaction(function () use ($stock, $quantite, $shopId, $createdBy, $client, $cashSessionId, $modePaiement, $montantPaye, $clientNom, $remise) {
            // Verrouillage de la ligne pour prévenir les ventes concurrentes sur le même stock
            $stock = Stock::withoutGlobalScopes()->lockForUpdate()->findOrFail($stock->id);

            if ($stock->quantite < $quantite) {
                throw new \RuntimeException("Stock insuffisant. Disponible : {$stock->quantite}");
            }

            $prixUnitaire = PricingService::resolvePrix($stock, $quantite, $client);

            $sousTotal    = $prixUnitaire * $quantite;
            $remise       = max(0.0, min($remise, $sousTotal - 0.01)); // jamais >= sousTotal
            $total        = $sousTotal - $remise;
            $montantPaye  = $montantPaye ?? ($modePaiement === 'comptant' ? $total : 0);
            $resteCredit  = max(0, $total - $montantPaye);

            $stock->decrement('quantite', $quantite);

            $sale = Sale::create([
                'numeroVente'     => 'VTE-' . strtoupper(Str::random(8)),
                'nom'             => $stock->nom,
                'quantite'        => $quantite,
                'client'          => $client?->nom ?? $clientNom ?? 'Anonyme',
                'prixVente'       => $prixUnitaire,
                'remise'          => $remise,
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

            // Vérifie l'alerte stock après décrément (déclencheur principal)
            $this->notificationService->notifierStockAlerte($stock->fresh());

            return $sale;
        });
    }

    public function annuler(Sale $vente, ?string $userId = null): void
    {
        DB::transaction(function () use ($vente, $userId) {
            $stock = Stock::withoutGlobalScopes()->find($vente->stockId);
            if ($stock) {
                $stock->increment('quantite', $vente->quantite);
            }

            if ($vente->statut === 'credit' && $vente->client_id) {
                $client = $vente->client()->withoutGlobalScopes()->first();
                if ($client) {
                    // Annuler la dette (reste_credit) via un avoir de crédit traçable.
                    if ($vente->reste_credit > 0) {
                        $this->creditService->enregistrerAvoir(
                            $client,
                            $vente->reste_credit,
                            $userId ?? 'system',
                            "Avoir — annulation vente {$vente->numeroVente}"
                        );
                    }

                    // Si un acompte en espèces avait été perçu, l'enregistrer comme avoir
                    // distinct pour que le solde crédit reflète l'intégralité du remboursement dû.
                    if ($vente->montant_paye > 0) {
                        $this->creditService->enregistrerAvoir(
                            $client,
                            $vente->montant_paye,
                            $userId ?? 'system',
                            "Avoir acompte espèces — annulation vente {$vente->numeroVente}"
                        );
                    }
                }
                // Les transactions dette originales sont supprimées ; les avoirs sont la trace d'annulation
                $vente->creditTransactions()->delete();
            }

            $vente->delete();
        });
    }
}
