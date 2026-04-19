<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

class FixSerializedClients extends Command
{
    protected $signature = 'app:fix-serialized-clients
                            {--dry-run : Simuler sans écriture}';

    protected $description = 'Corrige les clients dont nom/telephone sont stockés en format PHP sérialisé (s:N:"...";)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? '--- MODE SIMULATION ---' : '--- CORRECTION EN BASE ---');

        $clients = Client::withoutGlobalScopes()->get(['id', 'nom', 'telephone']);

        $fixed = 0;

        foreach ($clients as $client) {
            $newNom = $this->clean($client->nom);
            $newTel = $this->clean($client->telephone);

            if ($newNom !== $client->nom || $newTel !== $client->telephone) {
                $this->line("  Fix: [{$client->id}] {$client->nom} → {$newNom} | {$client->telephone} → {$newTel}");
                if (!$dryRun) {
                    Client::withoutGlobalScopes()
                        ->where('id', $client->id)
                        ->update(['nom' => $newNom, 'telephone' => $newTel]);
                }
                $fixed++;
            }
        }

        $this->newLine();
        $this->info("Clients corrigés : {$fixed}");
        if ($dryRun) {
            $this->warn('Simulation — aucune écriture.');
        }

        return self::SUCCESS;
    }

    private function clean(mixed $value): string
    {
        if (is_string($value) && str_starts_with($value, 's:')) {
            $unserialized = @unserialize($value);
            if ($unserialized !== false) {
                return (string) $unserialized;
            }
        }
        return (string) ($value ?? '');
    }
}
