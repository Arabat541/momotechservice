<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_payments', function (Blueprint $table) {
            $table->string('cash_session_id', 36)->nullable()->after('created_by');
            $table->index('cash_session_id', 'idx_rp_cash_session');
        });
    }

    public function down(): void
    {
        Schema::table('repair_payments', function (Blueprint $table) {
            $table->dropIndex('idx_rp_cash_session');
            $table->dropColumn('cash_session_id');
        });
    }
};
