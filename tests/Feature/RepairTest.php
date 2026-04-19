<?php

namespace Tests\Feature;

use App\Models\CashSession;
use App\Models\Repair;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepairTest extends TestCase
{
    use RefreshDatabase;

    private User $patron;
    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patron = User::create([
            'id' => \Illuminate\Support\Str::random(25),
            'email' => 'patron@test.com',
            'password' => Hash::make('password123'),
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'role' => 'patron',
        ]);

        $this->shop = Shop::create([
            'id' => \Illuminate\Support\Str::random(25),
            'nom' => 'Boutique Test',
            'createdBy' => $this->patron->id,
        ]);
    }

    private function loginAs(User $user, Shop $shop): void
    {
        $this->withSession([
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_email' => $user->email,
            'user_nom' => $user->nom,
            'user_prenom' => $user->prenom,
            'current_shop_id' => $shop->id,
        ]);
    }

    public function test_creation_reparation_valide(): void
    {
        CashSession::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'userId' => $this->patron->id,
            'date' => now()->toDateString(), 'montant_ouverture' => 0, 'statut' => 'ouverte',
        ]);

        $this->loginAs($this->patron, $this->shop);

        $response = $this->post('/dashboard/reparations', [
            'type_reparation' => 'place',
            'client_nom' => 'Alice Martin',
            'client_telephone' => '0612345678',
            'appareil_marque_modele' => 'Samsung Galaxy S21',
            'numeroReparation' => 'REP-TEST0001',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repairs', [
            'numeroReparation' => 'REP-TEST0001',
            'shopId' => $this->shop->id,
        ]);
    }

    public function test_creation_reparation_montant_negatif_rejete(): void
    {
        $this->loginAs($this->patron, $this->shop);

        $response = $this->post('/dashboard/reparations', [
            'type_reparation' => 'place',
            'client_nom' => 'Alice Martin',
            'client_telephone' => '0612345678',
            'appareil_marque_modele' => 'Samsung Galaxy S21',
            'montant_paye' => -100,
        ]);

        $response->assertSessionHasErrors('montant_paye');
    }

    public function test_creation_reparation_telephone_invalide_rejete(): void
    {
        $this->loginAs($this->patron, $this->shop);

        $response = $this->post('/dashboard/reparations', [
            'type_reparation' => 'place',
            'client_nom' => 'Alice Martin',
            'client_telephone' => '<script>alert(1)</script>',
            'appareil_marque_modele' => 'Samsung Galaxy S21',
        ]);

        $response->assertSessionHasErrors('client_telephone');
    }

    public function test_isolation_entre_shops(): void
    {
        $autreShop = Shop::create([
            'id' => \Illuminate\Support\Str::random(25),
            'nom' => 'Autre Boutique',
            'createdBy' => $this->patron->id,
        ]);

        // Créer une réparation dans autreShop
        Repair::create([
            'id' => \Illuminate\Support\Str::random(25),
            'shopId' => $autreShop->id,
            'numeroReparation' => 'REP-AUTRE-001',
            'type_reparation' => 'place',
            'client_nom' => 'Bob',
            'client_telephone' => '0600000001',
            'appareil_marque_modele' => 'iPhone 12',
            'pannes_services' => [],
            'pieces_rechange_utilisees' => [],
            'total_reparation' => 0,
            'montant_paye' => 0,
            'reste_a_payer' => 0,
            'statut_reparation' => 'En cours',
            'etat_paiement' => 'Non soldé',
            'userId' => $this->patron->id,
        ]);

        // Connecté sur $this->shop — la réparation de autreShop ne doit pas être visible
        $this->loginAs($this->patron, $this->shop);

        $response = $this->get('/dashboard/liste-reparations');
        $response->assertOk();
        $response->assertDontSee('REP-AUTRE-001');
    }

    public function test_acces_reparation_autre_shop_refuse(): void
    {
        $autreShop = Shop::create([
            'id' => \Illuminate\Support\Str::random(25),
            'nom' => 'Autre Boutique',
            'createdBy' => $this->patron->id,
        ]);

        $repair = Repair::create([
            'id' => 'repair-autre-shop-id-123456',
            'shopId' => $autreShop->id,
            'numeroReparation' => 'REP-AUTRE-002',
            'type_reparation' => 'place',
            'client_nom' => 'Bob',
            'client_telephone' => '0600000002',
            'appareil_marque_modele' => 'iPhone 13',
            'pannes_services' => [],
            'pieces_rechange_utilisees' => [],
            'total_reparation' => 0,
            'montant_paye' => 0,
            'reste_a_payer' => 0,
            'statut_reparation' => 'En cours',
            'etat_paiement' => 'Non soldé',
            'userId' => $this->patron->id,
        ]);

        $this->loginAs($this->patron, $this->shop);

        // Tentative d'accès direct à la réparation d'un autre shop
        $response = $this->get('/dashboard/reparations/' . $repair->id);
        $response->assertStatus(404);
    }

    public function test_non_authentifie_redirige(): void
    {
        $response = $this->post('/dashboard/reparations', [
            'type_reparation' => 'place',
            'client_nom' => 'Test',
            'client_telephone' => '0600000000',
            'appareil_marque_modele' => 'Test Device',
        ]);

        $response->assertRedirect('/connexion');
    }
}
