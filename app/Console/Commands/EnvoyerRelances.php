<?php

namespace App\Console\Commands;

use App\Models\Repair;
use App\Services\SmsService;
use Illuminate\Console\Command;

class EnvoyerRelances extends Command
{
    protected $signature   = 'app:envoyer-relances';
    protected $description = 'Envoie des relances SMS aux clients dont la réparation est terminée mais non récupérée.';

    public function __construct(private SmsService $smsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $envoyes = 0;

        // Première relance : Terminé depuis >= 7 jours, pas encore relancé
        $repairs = Repair::withoutGlobalScopes()
            ->where('statut_reparation', 'Terminé')
            ->whereNull('date_retrait')
            ->whereNotNull('date_terminee')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    // Premier relance : relance_count = 0 et terminé depuis 7 jours
                    $q2->where('relance_count', 0)
                       ->where('date_terminee', '<=', now()->subDays(7));
                })->orWhere(function ($q2) {
                    // Deuxième relance : relance_count = 1 et dernière relance > 7 jours
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

            $sent = $this->smsService->envoyerRelance(
                $telephone,
                $repair->numeroReparation,
                $repair->relance_count,
                $repair->shopId
            );

            if ($sent) {
                $repair->update([
                    'relance_count'   => $repair->relance_count + 1,
                    'derniere_relance' => now(),
                ]);
                $envoyes++;
                $this->line("Relance envoyée : {$repair->numeroReparation}");
            }
        }

        $this->info("{$envoyes} relance(s) envoyée(s).");
        return self::SUCCESS;
    }
}
