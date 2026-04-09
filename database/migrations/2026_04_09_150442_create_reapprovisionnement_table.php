<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('Reapprovisionnement', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('stockId', 191);
            $table->string('shopId', 191);
            $table->integer('quantite');
            $table->double('prixAchatUnitaire');
            $table->double('ancienPrixAchat');
            $table->double('nouveauPrixAchat');
            $table->integer('ancienneQuantite');
            $table->integer('nouvelleQuantite');
            $table->string('fournisseur')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('date')->useCurrent();

            $table->index('stockId');
            $table->index('shopId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Reapprovisionnement');
    }
};
