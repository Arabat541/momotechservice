<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('repair_photos', function (Blueprint $table) {
            $table->id();
            $table->string('repair_id', 30);
            $table->string('chemin');
            $table->string('legende')->nullable();
            $table->enum('type', ['avant', 'apres'])->default('avant');
            $table->timestamps();

            $table->foreign('repair_id')->references('id')->on('repairs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_photos');
    }
};
