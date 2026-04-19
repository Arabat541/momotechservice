<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->timestamp('date_terminee')->nullable()->after('date_retrait');
            $table->unsignedTinyInteger('relance_count')->default(0)->after('date_terminee');
            $table->timestamp('derniere_relance')->nullable()->after('relance_count');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(['date_terminee', 'relance_count', 'derniere_relance']);
        });
    }
};
