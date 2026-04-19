<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->boolean('mis_en_vente')->default(false)->after('derniere_relance');
            $table->date('date_limite_recuperation')->nullable()->after('mis_en_vente');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(['mis_en_vente', 'date_limite_recuperation']);
        });
    }
};
