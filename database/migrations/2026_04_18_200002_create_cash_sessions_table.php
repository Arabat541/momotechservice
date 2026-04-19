<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30);
            $table->string('userId', 30);
            $table->date('date');
            $table->double('montant_ouverture')->default(0);
            $table->double('montant_fermeture_attendu')->default(0);
            $table->double('montant_fermeture_reel')->nullable();
            $table->double('ecart')->nullable();
            $table->enum('statut', ['ouverte', 'fermee'])->default('ouverte');
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['shopId', 'date']);
            $table->index(['shopId', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
