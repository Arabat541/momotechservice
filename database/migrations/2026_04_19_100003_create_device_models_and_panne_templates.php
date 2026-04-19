<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_models', function (Blueprint $table) {
            $table->id();
            $table->string('shopId', 30);
            $table->string('marque');
            $table->string('modele');
            $table->string('type')->default('smartphone'); // smartphone, tablette, ordinateur, autre
            $table->timestamps();

            $table->foreign('shopId')->references('id')->on('shops')->onDelete('cascade');
        });

        Schema::create('panne_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_model_id');
            $table->string('description');
            $table->decimal('prix_estime', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('device_model_id')->references('id')->on('device_models')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panne_templates');
        Schema::dropIfExists('device_models');
    }
};
