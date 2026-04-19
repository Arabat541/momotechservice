<?php

namespace App\Services;

use App\Models\InventoryLine;
use App\Models\InventorySession;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function ouvrir(?string $shopId, string $userId, ?string $notes = null): InventorySession
    {
        if (!$shopId) {
            throw new \RuntimeException('Aucune boutique sélectionnée.');
        }

        $enCours = InventorySession::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->where('statut', 'en_cours')
            ->exists();

        if ($enCours) {
            throw new \RuntimeException('Un inventaire est déjà en cours pour cette boutique.');
        }

        return DB::transaction(function () use ($shopId, $userId, $notes) {
            $session = InventorySession::create([
                'shopId'     => $shopId,
                'created_by' => $userId,
                'statut'     => 'en_cours',
                'notes'      => $notes,
            ]);

            // Snapshot des quantités théoriques au moment de l'ouverture
            Stock::withoutGlobalScopes()
                ->where('shopId', $shopId)
                ->each(function (Stock $stock) use ($session) {
                    InventoryLine::create([
                        'inventory_session_id' => $session->id,
                        'stock_id'             => $stock->id,
                        'quantite_theorique'   => $stock->quantite,
                    ]);
                });

            return $session;
        });
    }

    public function saisirLigne(InventoryLine $line, int $quantiteComptee, ?string $notes = null): InventoryLine
    {
        $ecart = $quantiteComptee - $line->quantite_theorique;
        $line->update([
            'quantite_comptee' => $quantiteComptee,
            'ecart'            => $ecart,
            'notes'            => $notes,
        ]);
        return $line;
    }

    public function cloturer(InventorySession $session, string $userId, bool $appliquerAjustements = true): InventorySession
    {
        if (!$session->isEnCours()) {
            throw new \RuntimeException('Cet inventaire est déjà terminé.');
        }

        return DB::transaction(function () use ($session, $userId, $appliquerAjustements) {
            if ($appliquerAjustements) {
                $session->lines()
                    ->whereNotNull('quantite_comptee')
                    ->where('ecart', '!=', 0)
                    ->with('stock')
                    ->each(function (InventoryLine $line) {
                        if ($line->stock) {
                            $line->stock->update(['quantite' => $line->quantite_comptee]);
                        }
                    });
            }

            $session->update([
                'statut'    => 'termine',
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            return $session->fresh();
        });
    }
}
