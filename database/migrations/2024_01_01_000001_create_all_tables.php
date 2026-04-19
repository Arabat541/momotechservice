<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('nom');
            $table->string('prenom');
            $table->string('role')->default('technicien');
            $table->timestamps();
        });

        Schema::create('shops', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('nom');
            $table->string('adresse')->default('');
            $table->string('telephone')->default('');
            $table->string('createdBy');
            $table->timestamps();
        });

        Schema::create('_user_shops', function (Blueprint $table) {
            $table->string('A', 30);
            $table->string('B', 30);
            $table->foreign('A')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('B')->references('id')->on('shops')->onDelete('cascade');
            $table->unique(['A', 'B']);
            $table->index('B');
        });

        Schema::create('repairs', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30);
            $table->string('numeroReparation')->unique();
            $table->string('type_reparation');
            $table->string('client_nom');
            $table->string('client_telephone');
            $table->string('appareil_marque_modele');
            $table->json('pannes_services');
            $table->json('pieces_rechange_utilisees');
            $table->double('total_reparation');
            $table->double('montant_paye');
            $table->double('reste_a_payer');
            $table->string('statut_reparation');
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_mise_en_reparation')->nullable();
            $table->timestamp('date_rendez_vous')->nullable();
            $table->timestamp('date_retrait')->nullable();
            $table->string('etat_paiement');
            $table->string('userId');
            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->index(['shopId', 'date_creation']);
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30);
            $table->string('nom');
            $table->integer('quantite');
            $table->double('prixAchat');
            $table->double('prixVente');
            $table->double('beneficeNetAttendu')->default(0);
            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->index('shopId');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30)->unique();
            $table->json('companyInfo');
            $table->json('warranty');
            $table->timestamps();
            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
        });

        Schema::create('savs', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30);
            $table->string('numeroSAV')->unique();
            $table->string('repairId', 30)->nullable();
            $table->string('numeroReparationOrigine')->default('');
            $table->string('client_nom');
            $table->string('client_telephone');
            $table->string('appareil_marque_modele');
            $table->text('description_probleme');
            $table->boolean('sous_garantie')->default(false);
            $table->timestamp('date_fin_garantie')->nullable();
            $table->string('statut')->default('En attente');
            $table->text('decision');
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_resolution')->nullable();
            $table->text('notes');
            $table->string('userId');
            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('repairId')->references('id')->on('repairs')->onDelete('set null');
            $table->index(['shopId', 'date_creation']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('userId');
            $table->string('token')->unique();
            $table->timestamp('expiresAt');
            $table->index('expiresAt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('savs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('repairs');
        Schema::dropIfExists('_user_shops');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('users');
    }
};
