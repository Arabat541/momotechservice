<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Remove the overly-broad global unique so the same phone can exist in different shops.
            $table->dropUnique('clients_telephone_unique');
            // Enforce uniqueness per shop (prevents duplicates within a single shop).
            $table->unique(['shopId', 'telephone'], 'clients_shop_telephone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_shop_telephone_unique');
            $table->unique('telephone', 'clients_telephone_unique');
        });
    }
};
