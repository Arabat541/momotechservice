<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Repair;
use App\Models\CashSession;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function creerDepuisReparation(Repair $repair, float $acompte, string $cashSessionId, string $createdBy): Invoice
    {
        return DB::transaction(function () use ($repair, $acompte, $cashSessionId, $createdBy) {
            $montant = $repair->total_reparation;
            $reste   = max(0, $montant - $acompte);

            $invoice = Invoice::create([
                'numero_facture'  => $this->genererNumero($repair->shopId),
                'shopId'          => $repair->shopId,
                'repair_id'       => $repair->id,
                'client_id'       => $repair->client_id,
                'cash_session_id' => $cashSessionId,
                'montant_estime'  => $montant,
                'montant_final'   => $montant,
                'montant_paye'    => $acompte,
                'reste_a_payer'   => $reste,
                'statut'          => $reste <= 0 ? 'soldee' : ($acompte > 0 ? 'partielle' : 'en_attente'),
                'created_by'      => $createdBy,
            ]);

            $repair->update([
                'montant_paye'  => $acompte,
                'reste_a_payer' => $reste,
                'etat_paiement' => $reste <= 0 ? 'Soldé' : 'Non soldé',
            ]);

            return $invoice;
        });
    }

    public function enregistrerPaiementFinal(Invoice $invoice, float $montant, string $cashSessionId): Invoice
    {
        return DB::transaction(function () use ($invoice, $montant, $cashSessionId) {
            $invoice->montant_paye  += $montant;
            $invoice->reste_a_payer  = max(0, $invoice->montant_final - $invoice->montant_paye);
            $invoice->statut         = $invoice->reste_a_payer <= 0 ? 'soldee' : 'partielle';

            if ($cashSessionId) {
                $invoice->cash_session_id = $cashSessionId;
            }

            $invoice->save();

            if ($invoice->repair) {
                $invoice->repair->update([
                    'montant_paye'  => $invoice->montant_paye,
                    'reste_a_payer' => $invoice->reste_a_payer,
                    'etat_paiement' => $invoice->reste_a_payer <= 0 ? 'Soldé' : 'Non soldé',
                ]);
            }

            return $invoice;
        });
    }

    private function genererNumero(string $shopId): string
    {
        $annee   = now()->format('Y');
        $mois    = now()->format('m');
        $count   = Invoice::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->whereYear('created_at', $annee)
            ->whereMonth('created_at', $mois)
            ->count() + 1;

        return sprintf('FAC-%s%s-%04d', $annee, $mois, $count);
    }
}
