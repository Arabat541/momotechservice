<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('numero_facture')->unique();
            $table->string('shopId', 30);
            $table->string('repair_id', 30)->nullable();
            $table->string('client_id', 30)->nullable();
            $table->string('cash_session_id', 30)->nullable();
            $table->double('montant_estime')->default(0);
            $table->double('montant_final')->default(0);
            $table->double('montant_paye')->default(0);
            $table->double('reste_a_payer')->default(0);
            $table->enum('statut', ['en_attente', 'partielle', 'soldee'])->default('en_attente');
            $table->string('created_by', 30);
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('repair_id')->references('id')->on('repairs')->onDelete('set null');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('cash_session_id')->references('id')->on('cash_sessions')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['shopId', 'created_at']);
            $table->index(['shopId', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
