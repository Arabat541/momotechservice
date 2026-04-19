<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->index('createdBy');
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->index('userId');
            $table->foreign('userId')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('savs', function (Blueprint $table) {
            $table->index('userId');
            $table->foreign('userId')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropForeign(['userId']);
        });

        Schema::table('savs', function (Blueprint $table) {
            $table->dropForeign(['userId']);
            $table->dropIndex(['userId']);
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropForeign(['userId']);
            $table->dropIndex(['userId']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['createdBy']);
            $table->dropIndex(['createdBy']);
        });
    }
};
