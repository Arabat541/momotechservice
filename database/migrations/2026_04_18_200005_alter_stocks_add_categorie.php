<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->enum('categorie', ['telephone', 'accessoire', 'piece_detachee'])->default('accessoire')->after('nom');
            $table->double('prixGros')->nullable()->after('prixVente');
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn(['categorie', 'prixGros']);
        });
    }
};
