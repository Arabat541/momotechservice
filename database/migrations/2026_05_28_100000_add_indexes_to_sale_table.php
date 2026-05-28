<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            // Index composite pour les rapports filtrés par boutique + période
            // (whereBetween date + where shopId présents dans tous les rapports et analytics)
            $table->index(['shopId', 'date'], 'sale_shopid_date_index');

            // Index sur client_id pour les requêtes du dashboard revendeur
            $table->index('client_id', 'sale_client_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            $table->dropIndex('sale_shopid_date_index');
            $table->dropIndex('sale_client_id_index');
        });
    }
};
