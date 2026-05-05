<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Stock;

class PricingService
{
    public static function resolvePrix(Stock $stock, int $quantite, ?Client $client): float
    {
        if ($client?->isRevendeur()) {
            if ($quantite >= 10 && $stock->prixGros !== null) {
                return $stock->prixGros;
            }
            if ($quantite >= 3 && $stock->prix_demi_gros !== null) {
                return $stock->prix_demi_gros;
            }
            if ($stock->prix_revendeur !== null) {
                return $stock->prix_revendeur;
            }
        }
        return $stock->prixVente;
    }
}
