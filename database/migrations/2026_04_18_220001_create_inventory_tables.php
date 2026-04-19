<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sessions', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('shopId', 30);
            $table->string('created_by', 30);
            $table->string('closed_by', 30)->nullable();
            $table->enum('statut', ['en_cours', 'termine'])->default('en_cours');
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['shopId', 'statut']);
        });

        Schema::create('inventory_lines', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('inventory_session_id', 30);
            $table->string('stock_id', 30);
            $table->integer('quantite_theorique');
            $table->integer('quantite_comptee')->nullable();
            $table->integer('ecart')->nullable();
            $table->text('notes')->nullable();

            $table->foreign('inventory_session_id')->references('id')->on('inventory_sessions')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            $table->unique(['inventory_session_id', 'stock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lines');
        Schema::dropIfExists('inventory_sessions');
    }
};
