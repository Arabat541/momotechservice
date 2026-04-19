<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Reapprovisionnement', function (Blueprint $table) {
            $table->string('supplier_id', 30)->nullable()->after('fournisseur');
            $table->string('purchase_invoice_id', 30)->nullable()->after('supplier_id');

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('purchase_invoice_id')->references('id')->on('purchase_invoices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('Reapprovisionnement', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['purchase_invoice_id']);
            $table->dropColumn(['supplier_id', 'purchase_invoice_id']);
        });
    }
};
