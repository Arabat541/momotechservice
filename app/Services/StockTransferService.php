<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use Illuminate\Support\Str;

class StockTransferService
{
    public function creer(array $data, string $createdBy): StockTransfer
    {
        foreach ($data['lignes'] as $ligne) {
            $stock = Stock::withoutGlobalScopes()
                ->where('id', $ligne['stock_id'])
                ->where('shopId', $data['shop_from_id'])
                ->firstOrFail();

            if ($stock->quantite < $ligne['quantite']) {
                throw new \Exception("Stock insuffisant pour « {$stock->nom} » (disponible : {$stock->quantite}).");
            }
        }

        $transfer = StockTransfer::create([
            'id'           => Str::random(25),
            'numero'       => $this->genererNumero(),
            'shop_from_id' => $data['shop_from_id'],
            'shop_to_id'   => $data['shop_to_id'],
            'created_by'   => $createdBy,
            'statut'       => 'en_attente_envoi',
            'notes'        => $data['notes'] ?? null,
        ]);

        foreach ($data['lignes'] as $ligne) {
            StockTransferLine::create([
                'stock_transfer_id' => $transfer->id,
                'stock_id'          => $ligne['stock_id'],
                'quantite'          => $ligne['quantite'],
            ]);
        }

        return $transfer;
    }

    public function validerEnvoi(StockTransfer $transfer, string $userId): void
    {
        if ($transfer->statut !== 'en_attente_envoi') {
            throw new \Exception('Ce transfert ne peut pas être validé à l\'envoi.');
        }

        $transfer->load('lines.stock');

        foreach ($transfer->lines as $line) {
            $stock = Stock::withoutGlobalScopes()->findOrFail($line->stock_id);
            if ($stock->quantite < $line->quantite) {
                throw new \Exception("Stock insuffisant pour « {$stock->nom} » au moment de l'envoi.");
            }
            $stock->decrement('quantite', $line->quantite);
        }

        $transfer->update([
            'statut'               => 'en_attente_reception',
            'validated_by_sender'  => $userId,
            'validated_sender_at'  => now(),
        ]);
    }

    public function validerReception(StockTransfer $transfer, string $userId): void
    {
        if ($transfer->statut !== 'en_attente_reception') {
            throw new \Exception('Ce transfert n\'est pas en attente de réception.');
        }

        $transfer->load('lines.stock');

        foreach ($transfer->lines as $line) {
            $sourceStock = $line->stock;

            // Trouver ou créer le stock correspondant dans la boutique destinataire
            $destStock = Stock::withoutGlobalScopes()
                ->where('shopId', $transfer->shop_to_id)
                ->where('nom', $sourceStock->nom)
                ->first();

            if ($destStock) {
                $destStock->increment('quantite', $line->quantite);
            } else {
                Stock::create([
                    'id'           => Str::random(25),
                    'shopId'       => $transfer->shop_to_id,
                    'nom'          => $sourceStock->nom,
                    'categorie'    => $sourceStock->categorie,
                    'quantite'     => $line->quantite,
                    'prixAchat'       => $sourceStock->prixAchat,
                    'prixVente'       => $sourceStock->prixVente,
                    'prix_revendeur'  => $sourceStock->prix_revendeur,
                    'prix_demi_gros'  => $sourceStock->prix_demi_gros,
                    'prixGros'        => $sourceStock->prixGros,
                    'seuil_alerte' => $sourceStock->seuil_alerte,
                ]);
            }
        }

        $transfer->update([
            'statut'                  => 'completee',
            'validated_by_receiver'   => $userId,
            'validated_receiver_at'   => now(),
        ]);
    }

    public function annuler(StockTransfer $transfer): void
    {
        if (in_array($transfer->statut, ['completee', 'annulee'])) {
            throw new \Exception('Ce transfert ne peut plus être annulé.');
        }

        // Restaurer le stock source si l'envoi avait déjà été validé
        if ($transfer->statut === 'en_attente_reception') {
            $transfer->load('lines.stock');
            foreach ($transfer->lines as $line) {
                Stock::withoutGlobalScopes()
                    ->findOrFail($line->stock_id)
                    ->increment('quantite', $line->quantite);
            }
        }

        $transfer->update(['statut' => 'annulee']);
    }

    private function genererNumero(): string
    {
        $year  = date('Y');
        $count = StockTransfer::withoutGlobalScopes()
            ->where('numero', 'like', "TRF-{$year}-%")
            ->count();

        return sprintf('TRF-%s-%04d', $year, $count + 1);
    }
}
