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
        Schema::table('stocks', function (Blueprint $table) {
            $table->double('prix_revendeur')->nullable()->after('prixGros');
            $table->double('prix_demi_gros')->nullable()->after('prix_revendeur');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['prix_revendeur', 'prix_demi_gros']);
        });
    }
};
