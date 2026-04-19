<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase5FinalTest extends TestCase
{
    use RefreshDatabase;

    private User $patron;
    private User $caissiere;
    private User $technicien;
    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patron = User::create([
            'id' => Str::random(25), 'email' => 'patron@phase5.test',
            'password' => Hash::make('password123'), 'nom' => 'Patron', 'prenom' => 'P', 'role' => 'patron',
        ]);
        $this->caissiere = User::create([
            'id' => Str::random(25), 'email' => 'caiss@phase5.test',
            'password' => Hash::make('password123'), 'nom' => 'Caissiere', 'prenom' => 'C', 'role' => 'caissiere',
        ]);
        $this->technicien = User::create([
            'id' => Str::random(25), 'email' => 'tech@phase5.test',
            'password' => Hash::make('password123'), 'nom' => 'Tech', 'prenom' => 'T', 'role' => 'technicien',
        ]);
        $this->shop = Shop::create([
            'id' => Str::random(25), 'nom' => 'Boutique P5', 'createdBy' => $this->patron->id,
        ]);
        $this->caissiere->shops()->attach($this->shop->id);
        $this->technicien->shops()->attach($this->shop->id);
    }

    private function loginAs(User $user): void
    {
        $this->withSession([
            'user_id'         => $user->id,
            'user_role'       => $user->role,
            'user_email'      => $user->email,
            'user_nom'        => $user->nom,
            'user_prenom'     => $user->prenom,
            'current_shop_id' => $this->shop->id,
        ]);
    }

    // ── Dashboard revendeur ──────────────────────────────────────────────────

    public function test_dashboard_revendeur_accessible(): void
    {
        $revendeur = Client::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Revendeur Test', 'telephone' => '0700001111',
            'type' => 'revendeur', 'credit_limite' => 100000,
        ]);

        $this->loginAs($this->caissiere);
        $response = $this->get("/dashboard/clients/{$revendeur->id}/dashboard");
        $response->assertOk();
    }

    public function test_dashboard_particulier_redirige_vers_show(): void
    {
        $particulier = Client::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Client Normal', 'telephone' => '0700001112', 'type' => 'particulier',
        ]);

        $this->loginAs($this->caissiere);
        $response = $this->get("/dashboard/clients/{$particulier->id}/dashboard");
        $response->assertRedirect(route('clients.show', $particulier->id));
    }

    // ── Planning technicien ──────────────────────────────────────────────────

    public function test_planning_accessible_tous_roles(): void
    {
        $this->loginAs($this->technicien);
        $response = $this->get('/dashboard/planning');
        $response->assertOk();
    }

    public function test_assignation_reparation_a_technicien(): void
    {
        $repair = Repair::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'numeroReparation' => 'REP-PLAN-001', 'type_reparation' => 'place',
            'client_nom' => 'Client', 'client_telephone' => '0600000001',
            'appareil_marque_modele' => 'iPhone', 'pannes_services' => [],
            'pieces_rechange_utilisees' => [], 'total_reparation' => 0,
            'montant_paye' => 0, 'reste_a_payer' => 0,
            'statut_reparation' => 'En cours', 'etat_paiement' => 'Non soldé',
            'userId' => $this->caissiere->id,
        ]);

        $this->loginAs($this->caissiere);
        $response = $this->post("/dashboard/planning/{$repair->id}/assigner", [
            'assigned_to' => $this->technicien->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repairs', [
            'id'          => $repair->id,
            'assigned_to' => $this->technicien->id,
        ]);
    }

    public function test_desassignation_reparation(): void
    {
        $repair = Repair::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'numeroReparation' => 'REP-PLAN-002', 'type_reparation' => 'place',
            'client_nom' => 'Client2', 'client_telephone' => '0600000002',
            'appareil_marque_modele' => 'Samsung', 'pannes_services' => [],
            'pieces_rechange_utilisees' => [], 'total_reparation' => 0,
            'montant_paye' => 0, 'reste_a_payer' => 0,
            'statut_reparation' => 'En cours', 'etat_paiement' => 'Non soldé',
            'userId' => $this->caissiere->id, 'assigned_to' => $this->technicien->id,
        ]);

        $this->loginAs($this->patron);
        $this->post("/dashboard/planning/{$repair->id}/assigner", ['assigned_to' => null]);

        $this->assertDatabaseHas('repairs', ['id' => $repair->id, 'assigned_to' => null]);
    }

    // ── Garanties ────────────────────────────────────────────────────────────

    public function test_creation_garantie_piece_detachee(): void
    {
        $revendeur = Client::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Revendeur G', 'telephone' => '0700001113',
            'type' => 'revendeur', 'credit_limite' => 50000,
        ]);

        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Écran iPhone 13', 'categorie' => 'piece_detachee',
            'quantite' => 10, 'prixAchat' => 5000, 'prixVente' => 8000,
        ]);

        $sale = Sale::create([
            'id' => Str::random(25), 'nom' => 'Écran iPhone 13', 'quantite' => 2,
            'client' => 'Revendeur G', 'prixVente' => 8000, 'total' => 16000,
            'stockId' => $stock->id, 'shopId' => $this->shop->id,
            'client_id' => $revendeur->id, 'mode_paiement' => 'comptant',
            'montant_paye' => 16000, 'reste_credit' => 0, 'statut' => 'soldee',
        ]);

        $this->loginAs($this->caissiere);
        $response = $this->post('/dashboard/garanties', [
            'sale_id'     => $sale->id,
            'duree_jours' => 90,
            'conditions'  => 'Garantie pièce uniquement, hors casse.',
        ]);

        $response->assertRedirect();
        $warranty = Warranty::withoutGlobalScopes()->where('sale_id', $sale->id)->first();
        $this->assertNotNull($warranty);
        $this->assertEquals(90, $warranty->duree_jours);
        $this->assertEquals('active', $warranty->statut);
        $this->assertTrue($warranty->isActive());
    }

    public function test_garantie_accessoire_refusee(): void
    {
        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Coque iPhone', 'categorie' => 'accessoire',
            'quantite' => 5, 'prixAchat' => 500, 'prixVente' => 1000,
        ]);

        $sale = Sale::create([
            'id' => Str::random(25), 'nom' => 'Coque iPhone', 'quantite' => 1,
            'client' => 'Client', 'prixVente' => 1000, 'total' => 1000,
            'stockId' => $stock->id, 'shopId' => $this->shop->id,
            'mode_paiement' => 'comptant', 'montant_paye' => 1000, 'reste_credit' => 0, 'statut' => 'soldee',
        ]);

        $this->loginAs($this->caissiere);
        $response = $this->post('/dashboard/garanties', [
            'sale_id'     => $sale->id,
            'duree_jours' => 30,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, Warranty::withoutGlobalScopes()->count());
    }

    public function test_utilisation_garantie(): void
    {
        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Batterie', 'categorie' => 'piece_detachee',
            'quantite' => 5, 'prixAchat' => 2000, 'prixVente' => 3500,
        ]);

        $sale = Sale::create([
            'id' => Str::random(25), 'nom' => 'Batterie', 'quantite' => 1,
            'client' => 'Client', 'prixVente' => 3500, 'total' => 3500,
            'stockId' => $stock->id, 'shopId' => $this->shop->id,
            'mode_paiement' => 'comptant', 'montant_paye' => 3500, 'reste_credit' => 0, 'statut' => 'soldee',
        ]);

        $warranty = Warranty::create([
            'id' => Str::random(25), 'sale_id' => $sale->id,
            'shopId' => $this->shop->id, 'designation' => 'Batterie',
            'duree_jours' => 30, 'date_debut' => now()->toDateString(),
            'date_expiration' => now()->addDays(30)->toDateString(),
            'statut' => 'active', 'created_by' => $this->caissiere->id,
        ]);

        $this->loginAs($this->caissiere);
        $this->post("/dashboard/garanties/{$warranty->id}/utiliser", [
            'notes' => 'Remplacement défaut fabrication.',
        ]);

        $warranty->refresh();
        $this->assertEquals('utilisee', $warranty->statut);
    }

    // ── Config SMS ───────────────────────────────────────────────────────────

    public function test_patron_peut_configurer_sms(): void
    {
        $this->loginAs($this->patron);
        $response = $this->post('/dashboard/parametres/sms', [
            'sms_enabled'  => '1',
            'sms_provider' => 'twilio',
            'sms_api_key'  => 'token_test_123',
            'sms_sender'   => '+22500000000',
            'twilio_sid'   => 'AC_test_sid',
        ]);

        $response->assertRedirect();
        $settings = Settings::withoutGlobalScopes()->where('shopId', $this->shop->id)->first();
        $this->assertNotNull($settings);
        $this->assertEquals('twilio', $settings->sms_config['provider']);
        $this->assertTrue($settings->sms_config['enabled']);
    }
}
