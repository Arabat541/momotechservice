<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revendeurs', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('client_id', 30)->unique();
            $table->integer('points_fidelite')->default(0);
            $table->decimal('bonus_annuel_taux', 5, 2)->default(0);
            $table->year('annee_debut_fidelite')->nullable();
            $table->text('notes_internes')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revendeurs');
    }
};
