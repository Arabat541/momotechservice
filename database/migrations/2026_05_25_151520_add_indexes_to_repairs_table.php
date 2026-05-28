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
        Schema::table('repairs', function (Blueprint $table) {
            $table->index('client_id',          'idx_repairs_client_id');
            $table->index('statut_reparation',  'idx_repairs_statut');
            $table->index('etat_paiement',      'idx_repairs_etat_paiement');
            $table->index('userId',             'idx_repairs_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropIndex('idx_repairs_client_id');
            $table->dropIndex('idx_repairs_statut');
            $table->dropIndex('idx_repairs_etat_paiement');
            $table->dropIndex('idx_repairs_user_id');
        });
    }
};
