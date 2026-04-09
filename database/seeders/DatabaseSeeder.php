<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create patron user
        $userId = Str::random(25);
        \App\Models\User::create([
            'id' => $userId,
            'email' => 'patron@momotech.com',
            'password' => bcrypt('password123'),
            'nom' => 'MOMO',
            'prenom' => 'Admin',
            'role' => 'patron',
        ]);

        // Create first shop
        $shopId = Str::random(25);
        \App\Models\Shop::create([
            'id' => $shopId,
            'nom' => 'MOMO TECH SERVICE',
            'adresse' => 'Face Grande mosquée à côté de moov',
            'telephone' => '0710510157',
            'createdBy' => $userId,
        ]);

        // Attach user to shop
        \Illuminate\Support\Facades\DB::table('_user_shops')->insert([
            'A' => $userId,
            'B' => $shopId,
        ]);

        // Create default settings
        \App\Models\Settings::create([
            'shopId' => $shopId,
            'companyInfo' => [
                'nom' => 'MOMO TECH SERVICE',
                'adresse' => 'Face Grande mosquée à côté de moov',
                'telephone' => '0710510157',
                'slogan' => '[la technologie au bout des doigts...]',
            ],
            'warranty' => [
                'duree' => '7',
                'conditions' => 'Garantit une semaine. Passé ce délai, MOMO TECH SERVICE décline toute responsabilité.',
            ],
        ]);

        $this->command->info('Patron user created: patron@momotech.com / password123');
    }
}
