<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->string('id', 25)->primary();
            $table->string('numero')->unique();
            $table->string('shop_from_id', 25);
            $table->string('shop_to_id', 25);
            $table->string('created_by', 25);
            $table->string('validated_by_sender', 25)->nullable();
            $table->string('validated_by_receiver', 25)->nullable();
            $table->enum('statut', ['en_attente_envoi', 'en_attente_reception', 'completee', 'annulee'])
                  ->default('en_attente_envoi');
            $table->text('notes')->nullable();
            $table->timestamp('validated_sender_at')->nullable();
            $table->timestamp('validated_receiver_at')->nullable();
            $table->timestamps();

            $table->foreign('shop_from_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('shop_to_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('validated_by_sender')->references('id')->on('users');
            $table->foreign('validated_by_receiver')->references('id')->on('users');
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->string('stock_transfer_id', 25);
            $table->string('stock_id', 25);
            $table->integer('quantite');
            $table->timestamps();

            $table->foreign('stock_transfer_id')
                  ->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('stock_id')->references('id')->on('stocks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
    }
};
