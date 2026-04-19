<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'id' => \Illuminate\Support\Str::random(25),
            'email' => 'patron@test.com',
            'password' => Hash::make('password123'),
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'role' => 'patron',
        ], $attrs));
    }

    public function test_login_avec_bons_credentials(): void
    {
        $this->makeUser();

        $response = $this->post('/connexion', [
            'email' => 'patron@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertNotNull(session('user_id'));
    }

    public function test_login_avec_mauvais_mot_de_passe(): void
    {
        $this->makeUser();

        $response = $this->post('/connexion', [
            'email' => 'patron@test.com',
            'password' => 'mauvaispassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('user_id'));
    }

    public function test_login_email_inexistant(): void
    {
        $response = $this->post('/connexion', [
            'email' => 'inconnu@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_password_trop_long_rejete(): void
    {
        $response = $this->post('/connexion', [
            'email' => 'patron@test.com',
            'password' => str_repeat('a', 300),
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_logout_vide_la_session(): void
    {
        $this->makeUser();
        $this->post('/connexion', ['email' => 'patron@test.com', 'password' => 'password123']);

        $this->post('/deconnexion');

        $this->assertNull(session('user_id'));
    }

    public function test_throttle_login_bloque_apres_5_tentatives(): void
    {
        $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['email' => 'patron@test.com', 'password' => 'faux']);
        }

        $response = $this->post('/connexion', ['email' => 'patron@test.com', 'password' => 'password123']);

        $response->assertStatus(429);
    }

    public function test_reset_password_email_inexistant(): void
    {
        $response = $this->post('/mot-de-passe-oublie', ['email' => 'inconnu@test.com']);

        // Le controller retourne back()->withErrors() — l'erreur est dans le bag global
        $response->assertRedirect();
        $this->assertNotEmpty($response->baseResponse->getSession()->get('errors'));
    }

    public function test_dashboard_redirige_si_non_authentifie(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/connexion');
    }
}
