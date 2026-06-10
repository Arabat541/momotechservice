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

    public function enregistrerPaiementFinal(Invoice $invoice, float $montant, string $cashSessionId, ?string $moyen = null, ?string $createdBy = null): Invoice
    {
        if (!$createdBy) {
            throw new \RuntimeException('Utilisateur requis pour enregistrer un paiement.');
        }

        return DB::transaction(function () use ($invoice, $montant, $cashSessionId, $moyen, $createdBy) {
            // Verrou pour éviter le surpaiement concurrent
            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);

            if ($montant > $invoice->reste_a_payer + 0.01) {
                throw new \RuntimeException(
                    'Le montant (' . number_format($montant, 0, ',', ' ') . ' cfa) dépasse le reste à payer ('
                    . number_format($invoice->reste_a_payer, 0, ',', ' ') . ' cfa).'
                );
            }

            $invoice->montant_paye  += $montant;
            $invoice->reste_a_payer  = max(0, $invoice->montant_final - $invoice->montant_paye);
            $invoice->statut         = $invoice->reste_a_payer <= 0 ? 'soldee' : 'partielle';
            $invoice->moyen_paiement = $moyen;
            // cash_session_id conserve la session d'origine de la facture — ne pas écraser
            $invoice->save();

            // Tracer ce paiement comme RepairPayment pour l'imputation à la session courante
            if ($invoice->repair_id) {
                \App\Models\RepairPayment::create([
                    'repair_id'       => $invoice->repair_id,
                    'montant'         => $montant,
                    'moyen'           => $moyen,
                    'created_by'      => $createdBy,
                    'cash_session_id' => $cashSessionId,
                ]);
            }

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
        $annee = now()->format('Y');
        $mois  = now()->format('m');

        // Numéro séquentiel GLOBAL (toutes boutiques) : la contrainte d'unicité sur
        // numero_facture est globale et le numéro ne contient pas d'identifiant boutique.
        $lastNum = Invoice::withoutGlobalScopes()
            ->whereYear('created_at', $annee)
            ->whereMonth('created_at', $mois)
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(numero_facture, '-', -1) AS UNSIGNED)) as last_num")
            ->value('last_num');

        $count = ($lastNum ?? 0) + 1;

        return sprintf('FAC-%s%s-%04d', $annee, $mois, $count);
    }
}
