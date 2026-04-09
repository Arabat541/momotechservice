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
        Schema::create('Sale', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('nom', 191);
            $table->integer('quantite');
            $table->string('client', 191);
            $table->double('prixVente')->default(0);
            $table->double('total')->default(0);
            $table->string('stockId', 191);
            $table->string('shopId', 191);
            $table->timestamp('date')->useCurrent();

            $table->index('shopId');
            $table->index('stockId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Sale');
    }
};
