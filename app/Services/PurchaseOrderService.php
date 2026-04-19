<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function creer(array $validated, ?string $shopId, string $createdBy): PurchaseOrder
    {
        if (!$shopId) {
            throw new \RuntimeException('Aucune boutique sélectionnée.');
        }
        return DB::transaction(function () use ($validated, $shopId, $createdBy) {
            $lines = $this->buildLines($validated['lignes'] ?? []);
            $total = array_sum(array_column($lines, 'total_estime'));

            $order = PurchaseOrder::create([
                'numero'                 => $this->genererNumero($shopId),
                'shopId'                 => $shopId,
                'supplier_id'            => $validated['supplier_id'],
                'statut'                 => 'brouillon',
                'date_commande'          => $validated['date_commande'],
                'date_livraison_prevue'  => $validated['date_livraison_prevue'] ?? null,
                'montant_total'          => $total,
                'notes'                  => $validated['notes'] ?? null,
                'created_by'             => $createdBy,
            ]);

            foreach ($lines as $line) {
                PurchaseOrderLine::create(array_merge($line, ['purchase_order_id' => $order->id]));
            }

            return $order->load('lines');
        });
    }

    public function envoyer(PurchaseOrder $order): PurchaseOrder
    {
        if (!$order->isEditable()) {
            throw new \RuntimeException('Seul un bon de commande en brouillon peut être envoyé.');
        }
        $order->update(['statut' => 'envoye']);
        return $order;
    }

    public function recevoirPartiel(PurchaseOrder $order, array $receptions): PurchaseOrder
    {
        if (in_array($order->statut, ['recu', 'annule'])) {
            throw new \RuntimeException('Ce bon de commande est déjà clôturé.');
        }

        return DB::transaction(function () use ($order, $receptions) {
            foreach ($receptions as $lineId => $qteRecue) {
                $line = $order->lines()->find($lineId);
                if (!$line) continue;

                $qte = max(0, intval($qteRecue));
                $line->update(['quantite_recue' => $line->quantite_recue + $qte]);

                // Mise à jour du stock si lié
                if ($line->stock_id && $qte > 0) {
                    Stock::withoutGlobalScopes()->where('id', $line->stock_id)
                        ->increment('quantite', $qte);
                }
            }

            $order->load('lines');
            $toutRecu = $order->lines->every(fn($l) => $l->resteARecevoir() === 0);
            $unRecu   = $order->lines->some(fn($l) => $l->quantite_recue > 0);

            $order->update([
                'statut'               => $toutRecu ? 'recu' : ($unRecu ? 'partiellement_recu' : $order->statut),
                'date_livraison_reelle'=> $toutRecu ? now()->toDateString() : null,
            ]);

            return $order->fresh();
        });
    }

    public function annuler(PurchaseOrder $order): PurchaseOrder
    {
        if (in_array($order->statut, ['recu', 'annule'])) {
            throw new \RuntimeException('Ce bon de commande ne peut pas être annulé.');
        }
        $order->update(['statut' => 'annule']);
        return $order;
    }

    private function buildLines(array $lignes): array
    {
        $result = [];
        foreach ($lignes as $ligne) {
            $qte  = intval($ligne['quantite_commandee'] ?? 0);
            $prix = floatval($ligne['prix_unitaire_estime'] ?? 0);
            if ($qte <= 0) continue;

            $result[] = [
                'stock_id'              => $ligne['stock_id'] ?? null,
                'designation'           => $ligne['designation'] ?? '',
                'quantite_commandee'    => $qte,
                'quantite_recue'        => 0,
                'prix_unitaire_estime'  => $prix,
                'total_estime'          => $qte * $prix,
            ];
        }
        return $result;
    }

    private function genererNumero(string $shopId): string
    {
        $annee = now()->format('Y');
        $mois  = now()->format('m');
        $count = PurchaseOrder::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->whereYear('created_at', $annee)
            ->whereMonth('created_at', $mois)
            ->count() + 1;

        return sprintf('BC-%s%s-%04d', $annee, $mois, $count);
    }
}
