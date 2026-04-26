<?php

namespace App\Services;

use App\Models\Repair;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RepairService
{
    public function buildPannes(array $descriptions, array $montants): array
    {
        $pannes = [];
        foreach ($descriptions as $i => $desc) {
            if ($desc) {
                $pannes[] = [
                    'description' => $desc,
                    'montant' => floatval($montants[$i] ?? 0),
                ];
            }
        }
        return $pannes;
    }

    public function buildPieces(array $pieceIds, array $pieceQtes, ?string $shopId): array
    {
        $pieces = [];
        foreach ($pieceIds as $i => $stockId) {
            if (!$stockId) continue;

            $qte = intval($pieceQtes[$i] ?? 1);
            $stock = Stock::withoutGlobalScopes()->where('id', $stockId)->where('shopId', $shopId)->first();

            if ($stock && $stock->quantite >= $qte) {
                $stock->decrement('quantite', $qte);
                $pieces[] = [
                    'stockId' => $stockId,
                    'nom' => $stock->nom,
                    'quantiteUtilisee' => $qte,
                    'prixVente' => $stock->prixVente,
                ];
            }
        }
        return $pieces;
    }

    // Restore stock quantities for pieces already used in a repair (call before re-processing pieces on update).
    public function restorePiecesStock(array $oldPieces, ?string $shopId): void
    {
        foreach ($oldPieces as $piece) {
            $stockId = $piece['stockId'] ?? null;
            $qte     = intval($piece['quantiteUtilisee'] ?? 0);
            if (!$stockId || $qte <= 0) continue;

            Stock::withoutGlobalScopes()
                ->where('id', $stockId)
                ->where('shopId', $shopId)
                ->increment('quantite', $qte);
        }
    }

    public function computeTotals(array $pannes, array $pieces, float $montantPaye): array
    {
        $totalPannes = array_sum(array_column($pannes, 'montant'));
        $totalPieces = array_sum(array_map(
            fn($p) => ($p['prixVente'] ?? 0) * ($p['quantiteUtilisee'] ?? 0),
            $pieces
        ));
        $total = $totalPannes + $totalPieces;
        $reste = $total - $montantPaye;

        return [
            'total' => $total,
            'paye' => $montantPaye,
            'reste' => $reste,
            'etat_paiement' => $reste <= 0 ? 'Soldé' : 'Non soldé',
        ];
    }

    public function create(array $validated, ?string $shopId, string $userId): Repair
    {
        if (!$shopId) {
            throw new \RuntimeException('Aucune boutique sélectionnée.');
        }
        return DB::transaction(function () use ($validated, $shopId, $userId) {
            $pannes = $this->buildPannes(
                $validated['panne_description'] ?? [],
                $validated['panne_montant'] ?? []
            );
            $pieces = $this->buildPieces(
                $validated['piece_stock_id'] ?? [],
                $validated['piece_quantite'] ?? [],
                $shopId
            );
            $totals = $this->computeTotals($pannes, $pieces, floatval($validated['montant_paye'] ?? 0));

            return Repair::create([
                'shopId'                   => $shopId,
                'numeroReparation'         => $validated['numeroReparation'] ?? 'REP-' . strtoupper(Str::random(8)),
                'type_reparation'          => $validated['type_reparation'],
                'client_nom'               => $validated['client_nom'],
                'client_telephone'         => $validated['client_telephone'],
                'appareil_marque_modele'   => $validated['appareil_marque_modele'],
                'pannes_services'          => $pannes,
                'pieces_rechange_utilisees'=> $pieces,
                'total_reparation'         => $totals['total'],
                'montant_paye'             => $totals['paye'],
                'reste_a_payer'            => $totals['reste'],
                'statut_reparation'        => $validated['statut_reparation'] ?? 'En cours',
                'date_creation'            => now(),
                'date_mise_en_reparation'  => now(),
                'date_rendez_vous'         => $validated['date_rendez_vous'] ?? null,
                'etat_paiement'            => $totals['etat_paiement'],
                'userId'                   => $userId,
            ]);
        });
    }

    public function applyPayment(Repair $repair, float $montantPaye): array
    {
        $reste = $repair->total_reparation - $montantPaye;
        return [
            'montant_paye'  => $montantPaye,
            'reste_a_payer' => $reste,
            'etat_paiement' => $reste <= 0 ? 'Soldé' : 'Non soldé',
        ];
    }
}
