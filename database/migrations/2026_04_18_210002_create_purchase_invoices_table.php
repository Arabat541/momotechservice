<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('numero')->unique();
            $table->string('shopId', 30);
            $table->string('supplier_id', 30);
            $table->double('montant_total')->default(0);
            $table->double('montant_paye')->default(0);
            $table->double('reste_a_payer')->default(0);
            $table->enum('statut', ['en_attente', 'partiellement_payee', 'soldee'])->default('en_attente');
            $table->date('date_facture');
            $table->date('date_echeance')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 30);
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['shopId', 'statut']);
            $table->index(['shopId', 'date_facture']);
        });

        Schema::create('purchase_invoice_lines', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('purchase_invoice_id', 30);
            $table->string('stock_id', 30)->nullable();
            $table->string('designation');
            $table->integer('quantite');
            $table->double('prix_unitaire');
            $table->double('total');
            $table->string('reappro_id', 191)->nullable();

            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('set null');
            $table->index('purchase_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_lines');
        Schema::dropIfExists('purchase_invoices');
    }
};
