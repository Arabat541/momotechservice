<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'reparateur')->update(['role' => 'caissiere']);
    }

    public function down(): void
    {
        // Irreversible — impossible de savoir qui était réparateur
    }
};
