<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY COLUMN statut ENUM('en_attente','partielle','soldee','annulee') NOT NULL DEFAULT 'en_attente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY COLUMN statut ENUM('en_attente','partielle','soldee') NOT NULL DEFAULT 'en_attente'");
    }
};
