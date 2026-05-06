<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_payments', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->string('repair_id', 25);
            $table->foreign('repair_id')->references('id')->on('repairs')->onDelete('cascade');
            $table->double('montant');
            $table->enum('moyen', ['especes', 'orange_money', 'moov_money', 'wave', 'mtn_money', 'cheque', 'virement']);
            $table->string('notes', 200)->nullable();
            $table->string('created_by', 25);
            $table->foreign('created_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_payments');
    }
};
