<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('numero')->unique();
            $table->string('shopId', 30);
            $table->string('supplier_id', 30);
            $table->enum('statut', ['brouillon', 'envoye', 'partiellement_recu', 'recu', 'annule'])->default('brouillon');
            $table->date('date_commande');
            $table->date('date_livraison_prevue')->nullable();
            $table->date('date_livraison_reelle')->nullable();
            $table->double('montant_total')->default(0);
            $table->text('notes')->nullable();
            $table->string('created_by', 30);
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['shopId', 'statut']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('purchase_order_id', 30);
            $table->string('stock_id', 30)->nullable();
            $table->string('designation');
            $table->integer('quantite_commandee');
            $table->integer('quantite_recue')->default(0);
            $table->double('prix_unitaire_estime')->default(0);
            $table->double('total_estime')->default(0);

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('set null');
            $table->index('purchase_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
    }
};
