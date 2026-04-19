<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('userId', 30)->nullable();
            $table->string('shopId', 30)->nullable();
            $table->string('method', 10);
            $table->string('route', 255);
            $table->string('ip', 45)->nullable();
            $table->string('action', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['userId', 'created_at']);
            $table->index(['shopId', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
