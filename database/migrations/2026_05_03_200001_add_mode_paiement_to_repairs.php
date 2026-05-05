<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->enum('mode_paiement', [
                'especes', 'orange_money', 'wave', 'mtn_money', 'cheque', 'virement',
            ])->nullable()->after('etat_paiement');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn('mode_paiement');
        });
    }
};
