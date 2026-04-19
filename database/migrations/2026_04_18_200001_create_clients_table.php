<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30);
            $table->string('nom');
            $table->string('telephone')->unique();
            $table->enum('type', ['particulier', 'revendeur'])->default('particulier');
            $table->string('nom_boutique')->nullable();
            $table->double('credit_limite')->default(0);
            $table->double('solde_credit')->default(0);
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->index(['shopId', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
