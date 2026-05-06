<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->string('sale_id', 25);
            $table->foreign('sale_id')->references('id')->on('Sale')->onDelete('cascade');
            $table->double('montant');
            $table->enum('moyen', ['especes', 'orange_money', 'moov_money', 'wave', 'mtn_money']);
            $table->string('created_by', 25);
            $table->foreign('created_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
