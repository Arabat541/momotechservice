<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->enum('type', ['stock_alerte', 'reparation_prete', 'credit_depasse']);
            $table->string('titre');
            $table->text('message');
            $table->string('shop_id', 25)->nullable();
            $table->enum('role_cible', ['patron', 'caissiere', 'all']);
            $table->string('entity_type', 50)->nullable();
            $table->string('entity_id', 25)->nullable();
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('set null');
            $table->index(['lu_at', 'shop_id', 'role_cible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
