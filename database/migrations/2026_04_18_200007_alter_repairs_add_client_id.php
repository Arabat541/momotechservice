<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->string('client_id', 30)->nullable()->after('userId');
            $table->string('cash_session_id', 30)->nullable()->after('client_id');
            $table->text('notes_technicien')->nullable()->after('cash_session_id');

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('cash_session_id')->references('id')->on('cash_sessions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['cash_session_id']);
            $table->dropColumn(['client_id', 'cash_session_id', 'notes_technicien']);
        });
    }
};
