<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            $table->enum('moyen_paiement', [
                'especes', 'orange_money', 'wave', 'mtn_money',
            ])->nullable()->after('mode_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            $table->dropColumn('moyen_paiement');
        });
    }
};
