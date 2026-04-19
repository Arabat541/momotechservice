<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CashSession;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessLogicTest extends TestCase
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
            'id' => Str::random(25), 'email' => 'patron@test.com',
            'password' => Hash::make('password123'), 'nom' => 'Patron', 'prenom' => 'Test', 'role' => 'patron',
        ]);
        $this->caissiere = User::create([
            'id' => Str::random(25), 'email' => 'caissiere@test.com',
            'password' => Hash::make('password123'), 'nom' => 'Caissiere', 'prenom' => 'Test', 'role' => 'caissiere',
        ]);
        $this->technicien = User::create([
            'id' => Str::random(25), 'email' => 'tech@test.com',
            'password' => Hash::make('password123'), 'nom' => 'Tech', 'prenom' => 'Test', 'role' => 'technicien',
        ]);

        $this->shop = Shop::create([
            'id' => Str::random(25), 'nom' => 'Boutique Test', 'createdBy' => $this->patron->id,
        ]);

        // Attacher caissière et technicien à la boutique
        $this->caissiere->shops()->attach($this->shop->id);
        $this->technicien->shops()->attach($this->shop->id);
    }

    private function loginAs(User $user, Shop $shop): void
    {
        $this->withSession([
            'user_id'          => $user->id,
            'user_role'        => $user->role,
            'user_email'       => $user->email,
            'user_nom'         => $user->nom,
            'user_prenom'      => $user->prenom,
            'current_shop_id'  => $shop->id,
        ]);
    }

    // ── Caisse ──────────────────────────────────────────────────────────────

    public function test_caissiere_peut_ouvrir_caisse(): void
    {
        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post('/dashboard/caisse/ouvrir', ['montant_ouverture' => 50000]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cash_sessions', [
            'shopId' => $this->shop->id,
            'userId' => $this->caissiere->id,
            'statut' => 'ouverte',
        ]);
    }

    public function test_double_ouverture_caisse_refusee(): void
    {
        $this->loginAs($this->caissiere, $this->shop);

        $this->post('/dashboard/caisse/ouvrir', ['montant_ouverture' => 50000]);
        $response = $this->post('/dashboard/caisse/ouvrir', ['montant_ouverture' => 50000]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, CashSession::withoutGlobalScopes()->where('shopId', $this->shop->id)->count());
    }

    public function test_technicien_ne_peut_pas_ouvrir_caisse(): void
    {
        $this->loginAs($this->technicien, $this->shop);

        $response = $this->post('/dashboard/caisse/ouvrir', ['montant_ouverture' => 50000]);

        $response->assertStatus(403);
    }

    // ── Clients ─────────────────────────────────────────────────────────────

    public function test_creation_client(): void
    {
        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post('/dashboard/clients', [
            'nom'       => 'Moussa Diallo',
            'telephone' => '0700000001',
            'type'      => 'particulier',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['telephone' => '0700000001', 'shopId' => $this->shop->id]);
    }

    public function test_creation_client_revendeur_avec_credit_limite(): void
    {
        $this->loginAs($this->patron, $this->shop);

        $response = $this->post('/dashboard/clients', [
            'nom'           => 'Tech Express',
            'telephone'     => '0700000002',
            'type'          => 'revendeur',
            'nom_boutique'  => 'Tech Express SARL',
            'credit_limite' => 500000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('clients', ['telephone' => '0700000002', 'type' => 'revendeur']);
    }

    // ── Réparation + Facture automatique ────────────────────────────────────

    public function test_creation_reparation_genere_facture_si_caisse_ouverte(): void
    {
        // Ouvrir la caisse
        CashSession::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'userId' => $this->caissiere->id,
            'date' => now()->toDateString(), 'montant_ouverture' => 0, 'statut' => 'ouverte',
        ]);

        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post('/dashboard/reparations', [
            'type_reparation'        => 'place',
            'client_nom'             => 'Alice Martin',
            'client_telephone'       => '0612345678',
            'appareil_marque_modele' => 'Samsung Galaxy S21',
            'numeroReparation'       => 'REP-FACT-001',
            'montant_paye'           => 5000,
        ]);

        $response->assertRedirect();
        $repair = Repair::withoutGlobalScopes()->where('numeroReparation', 'REP-FACT-001')->first();
        $this->assertNotNull($repair);
        $this->assertDatabaseHas('invoices', ['repair_id' => $repair->id, 'montant_paye' => 5000]);
    }

    public function test_creation_reparation_sans_caisse_bloquee(): void
    {
        // Règle métier : caisse obligatoire pour créer une réparation
        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post('/dashboard/reparations', [
            'type_reparation'        => 'place',
            'client_nom'             => 'Bob Dupont',
            'client_telephone'       => '0699999999',
            'appareil_marque_modele' => 'iPhone 12',
            'numeroReparation'       => 'REP-NOFACT-001',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('repairs', ['numeroReparation' => 'REP-NOFACT-001']);
    }

    // ── Diagnostic technicien ────────────────────────────────────────────────

    public function test_technicien_peut_mettre_a_jour_diagnostic(): void
    {
        $repair = Repair::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'numeroReparation' => 'REP-DIAG-001', 'type_reparation' => 'place',
            'client_nom' => 'Test', 'client_telephone' => '0600000000',
            'appareil_marque_modele' => 'Test', 'pannes_services' => [],
            'pieces_rechange_utilisees' => [], 'total_reparation' => 0,
            'montant_paye' => 0, 'reste_a_payer' => 0,
            'statut_reparation' => 'En cours', 'etat_paiement' => 'Non soldé',
            'userId' => $this->caissiere->id,
        ]);

        $this->loginAs($this->technicien, $this->shop);

        $response = $this->put("/dashboard/reparations/{$repair->id}/diagnostic", [
            'statut_reparation' => 'En diagnostic',
            'notes_technicien'  => 'Écran fissuré, batterie faible.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repairs', [
            'id'               => $repair->id,
            'statut_reparation'=> 'En diagnostic',
        ]);
    }

    // ── Vente à crédit ───────────────────────────────────────────────────────

    public function test_vente_credit_revendeur_piece_detachee(): void
    {
        CashSession::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'userId' => $this->caissiere->id,
            'date' => now()->toDateString(), 'montant_ouverture' => 0, 'statut' => 'ouverte',
        ]);

        $revendeur = Client::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Revendeur Test', 'telephone' => '0700000010',
            'type' => 'revendeur', 'credit_limite' => 100000, 'solde_credit' => 0,
        ]);

        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Écran iPhone 12', 'categorie' => 'piece_detachee',
            'quantite' => 10, 'prixAchat' => 5000, 'prixVente' => 8000, 'prixGros' => 7000,
        ]);

        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post('/dashboard/article/vendre', [
            'article_id'    => $stock->id,
            'quantite'      => 2,
            'client_id'     => $revendeur->id,
            'mode_paiement' => 'credit',
            'montant_paye'  => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $revendeur->refresh();
        $this->assertEquals(14000, $revendeur->solde_credit); // 2 × 7000 (prix gros)
        $this->assertEquals(8, Stock::withoutGlobalScopes()->find($stock->id)->quantite);
        $this->assertDatabaseHas('credit_transactions', ['client_id' => $revendeur->id, 'type' => 'dette']);
    }

    public function test_vente_credit_particulier_refusee(): void
    {
        $particulier = Client::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Client Normal', 'telephone' => '0700000011',
            'type' => 'particulier', 'credit_limite' => 0,
        ]);

        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Écran Samsung', 'categorie' => 'piece_detachee',
            'quantite' => 5, 'prixAchat' => 3000, 'prixVente' => 5000,
        ]);

        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post('/dashboard/article/vendre', [
            'article_id'    => $stock->id,
            'quantite'      => 1,
            'client_id'     => $particulier->id,
            'mode_paiement' => 'credit',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('Sale', ['stockId' => $stock->id]);
    }

    public function test_vente_credit_depasse_limite_refusee(): void
    {
        $revendeur = Client::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Revendeur Limite', 'telephone' => '0700000012',
            'type' => 'revendeur', 'credit_limite' => 1000, 'solde_credit' => 0,
        ]);

        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Pièce chère', 'categorie' => 'piece_detachee',
            'quantite' => 5, 'prixAchat' => 1000, 'prixVente' => 2000,
        ]);

        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post('/dashboard/article/vendre', [
            'article_id'    => $stock->id,
            'quantite'      => 1,
            'client_id'     => $revendeur->id,
            'mode_paiement' => 'credit',
            'montant_paye'  => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── Remboursement crédit ─────────────────────────────────────────────────

    public function test_remboursement_credit_met_a_jour_solde(): void
    {
        $revendeur = Client::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Revendeur Rembourse', 'telephone' => '0700000013',
            'type' => 'revendeur', 'credit_limite' => 50000, 'solde_credit' => 20000,
        ]);

        $this->loginAs($this->caissiere, $this->shop);

        $response = $this->post("/dashboard/clients/{$revendeur->id}/remboursement", [
            'montant' => 5000,
            'notes'   => 'Paiement partiel',
        ]);

        $response->assertRedirect();
        $revendeur->refresh();
        $this->assertEquals(15000, $revendeur->solde_credit);
        $this->assertDatabaseHas('credit_transactions', [
            'client_id' => $revendeur->id, 'type' => 'remboursement', 'montant' => 5000,
        ]);
    }
}
