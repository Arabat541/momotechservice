<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            $table->string('client_id', 30)->nullable()->after('client');
            $table->string('cash_session_id', 30)->nullable()->after('client_id');
            $table->enum('mode_paiement', ['comptant', 'credit'])->default('comptant')->after('total');
            $table->double('montant_paye')->default(0)->after('mode_paiement');
            $table->double('reste_credit')->default(0)->after('montant_paye');
            $table->enum('statut', ['soldee', 'credit'])->default('soldee')->after('reste_credit');

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('cash_session_id')->references('id')->on('cash_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['cash_session_id']);
            $table->dropColumn(['client_id', 'cash_session_id', 'mode_paiement', 'montant_paye', 'reste_credit', 'statut']);
        });
    }
};
