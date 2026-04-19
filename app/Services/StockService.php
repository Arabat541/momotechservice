<?php

namespace App\Services;

use App\Models\Reapprovisionnement;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function calculateCMP(int $ancienneQuantite, float $ancienPrix, int $nouvelleQte, float $nouveauPrix): float
    {
        $total = $ancienneQuantite + $nouvelleQte;
        if ($total === 0) {
            return $nouveauPrix;
        }
        return round(($ancienneQuantite * $ancienPrix + $nouvelleQte * $nouveauPrix) / $total, 2);
    }

    public function reapprovisionner(Stock $stock, int $qte, float $prixUnitaire, string $shopId, ?string $fournisseur, ?string $note): Stock
    {
        return DB::transaction(function () use ($stock, $qte, $prixUnitaire, $shopId, $fournisseur, $note) {
            $ancienneQte   = $stock->quantite;
            $ancienPrix    = $stock->prixAchat;
            $nouvelleQte   = $ancienneQte + $qte;
            $nouveauPrix   = $this->calculateCMP($ancienneQte, $ancienPrix, $qte, $prixUnitaire);

            Reapprovisionnement::create([
                'stockId'          => $stock->id,
                'shopId'           => $shopId,
                'quantite'         => $qte,
                'prixAchatUnitaire'=> $prixUnitaire,
                'ancienPrixAchat'  => $ancienPrix,
                'nouveauPrixAchat' => $nouveauPrix,
                'ancienneQuantite' => $ancienneQte,
                'nouvelleQuantite' => $nouvelleQte,
                'fournisseur'      => $fournisseur,
                'note'             => $note,
            ]);

            $stock->update([
                'quantite'           => $nouvelleQte,
                'prixAchat'          => $nouveauPrix,
                'beneficeNetAttendu' => $stock->prixVente - $nouveauPrix,
            ]);

            return $stock->fresh();
        });
    }
}
