<?php

namespace App\Console\Commands;

use App\Jobs\EnvoyerSmsJob;
use App\Models\Repair;
use Illuminate\Console\Command;

class EnvoyerRelances extends Command
{
    protected $signature   = 'app:envoyer-relances';
    protected $description = 'Programme les relances SMS aux clients dont la réparation est terminée mais non récupérée.';

    public function handle(): int
    {
        $dispatches = 0;

        $repairs = Repair::withoutGlobalScopes()
            ->where('statut_reparation', 'Terminé')
            ->whereNull('date_retrait')
            ->whereNotNull('date_terminee')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('relance_count', 0)
                       ->where('date_terminee', '<=', now()->subDays(7));
                })->orWhere(function ($q2) {
                    $q2->where('relance_count', 1)
                       ->where('derniere_relance', '<=', now()->subDays(7));
                });
            })
            ->where('relance_count', '<', 2)
            ->get();

        foreach ($repairs as $repair) {
            $telephone = $repair->client?->telephone ?? $repair->client_telephone;

            if (!$telephone) {
                continue;
            }

            EnvoyerSmsJob::dispatch('relance', $telephone, $repair->numeroReparation, $repair->shopId, $repair->relance_count);

            $repair->update([
                'relance_count'    => $repair->relance_count + 1,
                'derniere_relance' => now(),
            ]);

            $dispatches++;
            $this->line("Relance programmée : {$repair->numeroReparation}");
        }

        $this->info("{$dispatches} relance(s) programmée(s) en queue.");
        return self::SUCCESS;
    }
}
