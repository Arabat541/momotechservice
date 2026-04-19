<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Warranty;
use Illuminate\Support\Facades\DB;

class WarrantyService
{
    public function creer(Sale $sale, int $dureeJours, string $createdBy, ?string $conditions = null): Warranty
    {
        if (!$sale->stock?->isPieceDetachee()) {
            throw new \RuntimeException('Les garanties s\'appliquent uniquement aux pièces détachées.');
        }

        return DB::transaction(function () use ($sale, $dureeJours, $createdBy, $conditions) {
            $debut      = now()->toDateString();
            $expiration = now()->addDays($dureeJours)->toDateString();

            return Warranty::create([
                'sale_id'          => $sale->id,
                'client_id'        => $sale->client_id,
                'shopId'           => $sale->shopId,
                'designation'      => $sale->nom,
                'duree_jours'      => $dureeJours,
                'date_debut'       => $debut,
                'date_expiration'  => $expiration,
                'conditions'       => $conditions,
                'statut'           => 'active',
                'created_by'       => $createdBy,
            ]);
        });
    }

    public function utiliser(Warranty $warranty, string $notes): Warranty
    {
        if (!$warranty->isActive()) {
            throw new \RuntimeException('Cette garantie est expirée ou déjà utilisée.');
        }

        $warranty->update(['statut' => 'utilisee', 'notes' => $notes]);
        return $warranty->fresh();
    }

    public function expirerAnciennes(): int
    {
        return Warranty::withoutGlobalScopes()
            ->where('statut', 'active')
            ->where('date_expiration', '<', now()->toDateString())
            ->update(['statut' => 'expiree']);
    }
}
