<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_sales', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->string('shopId', 25)->index();
            $table->string('client_id', 25)->index();
            $table->string('created_by', 25);
            $table->string('cash_session_id', 25)->nullable();
            $table->string('statut', 20)->default('en_attente'); // en_attente | validee | annulee
            $table->string('mode_paiement', 20)->default('credit');
            $table->double('montant_paye')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique(['shopId', 'client_id', 'statut'], 'unique_open_per_client');
        });

        Schema::create('pending_sale_lines', function (Blueprint $table) {
            $table->id();
            $table->string('pending_sale_id', 25);
            $table->string('stock_id', 25);
            $table->string('stock_nom', 255);
            $table->integer('quantite');
            $table->double('prix_unitaire');
            $table->string('palier', 20)->default('normal'); // normal | revendeur | demi_gros | gros
            $table->timestamps();

            $table->foreign('pending_sale_id')->references('id')->on('pending_sales')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_sale_lines');
        Schema::dropIfExists('pending_sales');
    }
};
