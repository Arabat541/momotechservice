<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE repairs MODIFY COLUMN mode_paiement ENUM('especes','orange_money','wave','mtn_money','cheque','virement','moov_money') NULL");
        DB::statement("ALTER TABLE `Sale` MODIFY COLUMN moyen_paiement ENUM('especes','orange_money','wave','mtn_money','moov_money','mixte') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE repairs MODIFY COLUMN mode_paiement ENUM('especes','orange_money','wave','mtn_money','cheque','virement') NULL");
        DB::statement("ALTER TABLE `Sale` MODIFY COLUMN moyen_paiement ENUM('especes','orange_money','wave','mtn_money') NULL");
    }
};
