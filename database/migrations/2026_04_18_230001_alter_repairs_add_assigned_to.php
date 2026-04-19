<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->string('assigned_to', 30)->nullable()->after('userId');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->index(['shopId', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['shopId', 'assigned_to']);
            $table->dropColumn('assigned_to');
        });
    }
};
