<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class SyncInvoicesFromRepairs extends Command
{
    protected $signature   = 'invoices:sync-from-repairs';
    protected $description = 'Synchronise montant_paye et statut des factures depuis les réparations liées';

    public function handle(): int
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->whereNotNull('repair_id')
            ->with('repair')
            ->get();

        $this->info("Factures à vérifier : {$invoices->count()}");

        $updated = 0;

        foreach ($invoices as $invoice) {
            $repair = $invoice->repair;

            if (!$repair) {
                $this->warn("  {$invoice->numero_facture} → réparation introuvable, ignorée");
                continue;
            }

            $montantPaye = (float) $repair->montant_paye;
            $resteApayer = max(0.0, ($invoice->montant_final ?? $invoice->montant_estime) - $montantPaye);
            $statut      = $resteApayer <= 0
                ? 'soldee'
                : ($montantPaye > 0 ? 'partielle' : 'en_attente');

            if (
                (float) $invoice->montant_paye  !== $montantPaye ||
                (float) $invoice->reste_a_payer !== $resteApayer ||
                $invoice->statut                !== $statut
            ) {
                $invoice->update([
                    'montant_paye'  => $montantPaye,
                    'reste_a_payer' => $resteApayer,
                    'statut'        => $statut,
                ]);

                $this->line("  <info>{$invoice->numero_facture}</info> → <comment>{$statut}</comment> (payé : {$montantPaye} / reste : {$resteApayer})");
                $updated++;
            }
        }

        $this->info("Terminé — {$updated} facture(s) mise(s) à jour sur {$invoices->count()}.");

        return Command::SUCCESS;
    }
}
