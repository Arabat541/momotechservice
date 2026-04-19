<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('sale_id', 191);
            $table->string('client_id', 30)->nullable();
            $table->string('shopId', 30);
            $table->string('designation');
            $table->integer('duree_jours');
            $table->date('date_debut');
            $table->date('date_expiration');
            $table->text('conditions')->nullable();
            $table->enum('statut', ['active', 'expiree', 'utilisee'])->default('active');
            $table->text('notes')->nullable();
            $table->string('created_by', 30);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('Sale')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['shopId', 'statut']);
            $table->index(['client_id', 'date_expiration']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
