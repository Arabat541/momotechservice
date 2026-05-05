<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            $table->string('numeroVente', 30)->nullable()->unique()->after('id');
            $table->double('remise')->default(0)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('Sale', function (Blueprint $table) {
            $table->dropUnique(['numeroVente']);
            $table->dropColumn(['numeroVente', 'remise']);
        });
    }
};
