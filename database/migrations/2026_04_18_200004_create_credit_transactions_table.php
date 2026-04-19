<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('client_id', 30);
            $table->string('shopId', 30);
            $table->string('sale_id', 191)->nullable();
            $table->double('montant');
            $table->enum('type', ['dette', 'remboursement']);
            $table->text('notes')->nullable();
            $table->string('created_by', 30);
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['client_id', 'created_at']);
            $table->index(['shopId', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
