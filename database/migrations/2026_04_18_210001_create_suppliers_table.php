<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30);
            $table->string('nom');
            $table->string('contact_nom')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->integer('delai_livraison_jours')->default(0);
            $table->text('conditions_paiement')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->index(['shopId', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
