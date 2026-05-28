<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_payments', function (Blueprint $table) {
            // moyen peut être null quand aucun mode de paiement n'est précisé
            // (ex: acompte global saisi sans détail de moyen lors de la création d'une réparation)
            $table->enum('moyen', ['especes', 'orange_money', 'moov_money', 'wave', 'mtn_money', 'cheque', 'virement'])
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('repair_payments', function (Blueprint $table) {
            $table->enum('moyen', ['especes', 'orange_money', 'moov_money', 'wave', 'mtn_money', 'cheque', 'virement'])
                ->nullable(false)
                ->change();
        });
    }
};
