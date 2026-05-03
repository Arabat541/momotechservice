<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function __construct(private NotificationService $notificationService) {}

    public function enregistrerDette(Sale $sale, Client $client, float $montantCredit, string $createdBy): CreditTransaction
    {
        if (!$client->isRevendeur()) {
            throw new \RuntimeException('Seuls les revendeurs peuvent acheter à crédit.');
        }

        if ($montantCredit > $client->creditDisponible()) {
            throw new \RuntimeException(
                "Crédit insuffisant. Disponible : {$client->creditDisponible()} — Demandé : {$montantCredit}"
            );
        }

        return DB::transaction(function () use ($sale, $client, $montantCredit, $createdBy) {
            $soldeApres = $client->solde_credit + $montantCredit;

            $transaction = CreditTransaction::create([
                'client_id'   => $client->id,
                'shopId'      => $client->shopId,
                'sale_id'     => $sale->id,
                'montant'     => $montantCredit,
                'type'        => 'dette',
                'created_by'  => $createdBy,
                'solde_apres' => $soldeApres,
            ]);

            $client->increment('solde_credit', $montantCredit);

            if ($soldeApres > $client->credit_limite) {
                $this->notificationService->notifierCreditDepasse($client->fresh());
            }

            return $transaction;
        });
    }

    public function enregistrerAvoir(Client $client, float $montant, string $createdBy, ?string $notes = null): CreditTransaction
    {
        return DB::transaction(function () use ($client, $montant, $createdBy, $notes) {
            $transaction = CreditTransaction::create([
                'client_id'  => $client->id,
                'shopId'     => $client->shopId,
                'montant'    => $montant,
                'type'       => 'avoir',
                'notes'      => $notes,
                'created_by' => $createdBy,
            ]);

            // Un avoir augmente le solde crédit disponible (diminue la dette)
            $client->decrement('solde_credit', min($montant, $client->solde_credit));

            return $transaction;
        });
    }

    public function enregistrerRemboursement(Client $client, float $montant, string $createdBy, ?string $notes = null): CreditTransaction
    {
        if ($montant > $client->solde_credit) {
            throw new \RuntimeException(
                "Remboursement ({$montant}) supérieur au solde dû ({$client->solde_credit})."
            );
        }

        return DB::transaction(function () use ($client, $montant, $createdBy, $notes) {
            $soldeApres = max(0, $client->solde_credit - $montant);

            $transaction = CreditTransaction::create([
                'client_id'   => $client->id,
                'shopId'      => $client->shopId,
                'montant'     => $montant,
                'type'        => 'remboursement',
                'notes'       => $notes,
                'created_by'  => $createdBy,
                'solde_apres' => $soldeApres,
            ]);

            $client->decrement('solde_credit', $montant);

            return $transaction;
        });
    }
}
