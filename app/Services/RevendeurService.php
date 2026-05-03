<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\Revendeur;
use Carbon\Carbon;

class RevendeurService
{
    public function ajouterPoints(Client $client, int $points): Revendeur
    {
        $revendeur = Revendeur::firstOrCreate(
            ['client_id' => $client->id],
            ['id' => \Illuminate\Support\Str::random(25)]
        );

        $revendeur->increment('points_fidelite', $points);

        return $revendeur->fresh();
    }

    public function calculerBonus(Client $client): float
    {
        $revendeur = Revendeur::where('client_id', $client->id)->first();
        if (!$revendeur || $revendeur->bonus_annuel_taux <= 0) {
            return 0.0;
        }

        $annee = $revendeur->annee_debut_fidelite ?? Carbon::now()->year;
        $caAnnuel = $client->sales()
            ->whereYear('date', $annee)
            ->sum('total');

        return round($caAnnuel * $revendeur->bonus_annuel_taux / 100, 2);
    }

    public function getRelevéCompte(Client $client, string $debut, string $fin)
    {
        return CreditTransaction::where('client_id', $client->id)
            ->whereBetween('created_at', [
                Carbon::parse($debut)->startOfDay(),
                Carbon::parse($fin)->endOfDay(),
            ])
            ->orderBy('created_at')
            ->get();
    }
}
