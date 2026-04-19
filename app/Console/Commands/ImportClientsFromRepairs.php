<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Repair;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportClientsFromRepairs extends Command
{
    protected $signature = 'app:import-clients
                            {--dry-run : Simuler sans écrire en base}
                            {--link : Lier les réparations et ventes au client créé}';

    protected $description = 'Importe les clients depuis repairs/sales vers la table clients';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $link   = $this->option('link');

        $this->info($dryRun ? '--- MODE SIMULATION (aucune écriture) ---' : '--- IMPORT EN BASE ---');

        $this->info('Lecture des réparations...');
        $repairs = Repair::withoutGlobalScopes()
            ->whereNotNull('client_telephone')
            ->get(['id', 'shopId', 'client_nom', 'client_telephone', 'client_id']);

        // Dédupliquer par (shopId + telephone)
        $byPhone = [];
        foreach ($repairs as $repair) {
            $tel = $repair->client_telephone; // déchiffré automatiquement par le cast encrypted
            $nom = $repair->client_nom;
            if (!$tel || !$repair->shopId) continue;
            $key = $repair->shopId . '|' . $tel;
            if (!isset($byPhone[$key])) {
                $byPhone[$key] = ['shopId' => $repair->shopId, 'nom' => $nom, 'telephone' => $tel];
            }
        }

        $this->info(count($byPhone) . ' client(s) unique(s) trouvé(s).');
        $this->newLine();

        $created = 0;
        $skipped = 0;
        $linked  = 0;

        DB::transaction(function () use ($byPhone, $dryRun, $link, &$created, &$skipped, &$linked) {
            foreach ($byPhone as $data) {
                $existing = Client::withoutGlobalScopes()
                    ->where('shopId', $data['shopId'])
                    ->where('telephone', $data['telephone'])
                    ->first();

                if ($existing) {
                    $skipped++;
                    $clientId = $existing->id;
                    $this->line("  = Existant : {$data['nom']} ({$data['telephone']})");
                } else {
                    $this->line("  + Créer    : {$data['nom']} ({$data['telephone']})");
                    $clientId = null;
                    if (!$dryRun) {
                        $client   = Client::create([
                            'id'        => Str::random(25),
                            'shopId'    => $data['shopId'],
                            'nom'       => $data['nom'],
                            'telephone' => $data['telephone'],
                            'type'      => 'particulier',
                        ]);
                        $clientId = $client->id;
                    }
                    $created++;
                }

                // Lier les réparations existantes au client
                if ($link && $clientId && !$dryRun) {
                    $count = Repair::withoutGlobalScopes()
                        ->where('shopId', $data['shopId'])
                        ->where('client_telephone', $data['telephone'])
                        ->whereNull('client_id')
                        ->update(['client_id' => $clientId]);
                    $linked += $count;
                }
            }
        });

        $this->newLine();
        $this->info("✔ Clients créés   : {$created}");
        $this->info("  Déjà existants  : {$skipped}");
        if ($link) {
            $this->info("  Réparations liées : {$linked}");
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('Simulation terminée — aucune modification en base.');
        } else {
            $this->info('Import terminé avec succès.');
        }

        return self::SUCCESS;
    }
}
