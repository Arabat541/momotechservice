<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE credit_transactions MODIFY COLUMN type ENUM('dette','remboursement','avoir') NOT NULL");
    }

    public function down(): void
    {
        // Supprime les lignes 'avoir' avant de rétrécir l'enum pour éviter une erreur
        DB::statement("UPDATE credit_transactions SET type = 'remboursement' WHERE type = 'avoir'");
        DB::statement("ALTER TABLE credit_transactions MODIFY COLUMN type ENUM('dette','remboursement') NOT NULL");
    }
};
