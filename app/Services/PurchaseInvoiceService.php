<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function creer(array $validated, ?string $shopId, string $createdBy): PurchaseInvoice
    {
        if (!$shopId) {
            throw new \RuntimeException('Aucune boutique sélectionnée.');
        }
        return DB::transaction(function () use ($validated, $shopId, $createdBy) {
            $lines  = $this->buildLines($validated['lignes'] ?? []);
            $total  = array_sum(array_column($lines, 'total'));

            $invoice = PurchaseInvoice::create([
                'numero'          => $this->genererNumero($shopId),
                'shopId'          => $shopId,
                'supplier_id'     => $validated['supplier_id'],
                'montant_total'   => $total,
                'montant_paye'    => 0,
                'reste_a_payer'   => $total,
                'statut'          => 'en_attente',
                'date_facture'    => $validated['date_facture'],
                'date_echeance'   => $validated['date_echeance'] ?? null,
                'notes'           => $validated['notes'] ?? null,
                'created_by'      => $createdBy,
            ]);

            foreach ($lines as $line) {
                PurchaseInvoiceLine::create(array_merge($line, [
                    'purchase_invoice_id' => $invoice->id,
                ]));
            }

            return $invoice->load('lines');
        });
    }

    public function enregistrerPaiement(PurchaseInvoice $invoice, float $montant): PurchaseInvoice
    {
        if ($montant > $invoice->reste_a_payer) {
            throw new \RuntimeException(
                "Paiement ({$montant}) supérieur au reste dû ({$invoice->reste_a_payer})."
            );
        }

        $invoice->enregistrerPaiement($montant);
        return $invoice->fresh();
    }

    public function lierReappro(PurchaseInvoice $invoice, string $reapproId): void
    {
        \App\Models\Reapprovisionnement::withoutGlobalScopes()
            ->where('id', $reapproId)
            ->update(['purchase_invoice_id' => $invoice->id]);
    }

    private function buildLines(array $lignes): array
    {
        $result = [];
        foreach ($lignes as $ligne) {
            $qte   = intval($ligne['quantite'] ?? 0);
            $prix  = floatval($ligne['prix_unitaire'] ?? 0);
            if ($qte <= 0 || $prix <= 0) continue;

            $result[] = [
                'stock_id'      => $ligne['stock_id'] ?? null,
                'designation'   => $ligne['designation'] ?? '',
                'quantite'      => $qte,
                'prix_unitaire' => $prix,
                'total'         => $qte * $prix,
                'reappro_id'    => $ligne['reappro_id'] ?? null,
            ];
        }
        return $result;
    }

    private function genererNumero(string $shopId): string
    {
        $annee = now()->format('Y');
        $mois  = now()->format('m');
        $count = PurchaseInvoice::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->whereYear('created_at', $annee)
            ->whereMonth('created_at', $mois)
            ->count() + 1;

        return sprintf('FA-%s%s-%04d', $annee, $mois, $count);
    }
}
