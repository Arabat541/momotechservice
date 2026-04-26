<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptLegacyPii extends Command
{
    protected $signature = 'app:encrypt-legacy-pii {--dry-run : Afficher sans modifier}';
    protected $description = 'Chiffre les données PII (client_nom, client_telephone) déjà en clair en base';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '[DRY RUN] Simulation — aucune donnée modifiée.' : 'Chiffrement des données PII en cours...');

        $total = 0;

        foreach (['repairs', 'savs'] as $table) {
            $rows = DB::table($table)->select('id', 'client_nom', 'client_telephone')->get();

            $bar = $this->output->createProgressBar($rows->count());
            $bar->start();

            foreach ($rows as $row) {
                // Détecter si la valeur est déjà chiffrée (commence par 'eyJ')
                $nomDejaCrypte = str_starts_with((string) $row->client_nom, 'eyJ');
                $telDejaCrypte = str_starts_with((string) $row->client_telephone, 'eyJ');

                if ($nomDejaCrypte && $telDejaCrypte) {
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    DB::table($table)->where('id', $row->id)->update([
                        'client_nom'       => $nomDejaCrypte ? $row->client_nom : \Illuminate\Support\Facades\Crypt::encryptString($row->client_nom),
                        'client_telephone' => $telDejaCrypte ? $row->client_telephone : \Illuminate\Support\Facades\Crypt::encryptString($row->client_telephone),
                    ]);
                }

                $total++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->line("  Table {$table} : {$rows->count()} lignes traitées.");
        }

        $this->info($dryRun
            ? "{$total} lignes seraient chiffrées."
            : "{$total} lignes chiffrées avec succès."
        );

        return Command::SUCCESS;
    }
}
