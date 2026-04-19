<?php

namespace Tests\Feature;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Reapprovisionnement;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private User $patron;
    private User $caissiere;
    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patron = User::create([
            'id' => Str::random(25), 'email' => 'patron@supplier.test',
            'password' => Hash::make('password123'), 'nom' => 'Patron', 'prenom' => 'Test', 'role' => 'patron',
        ]);
        $this->caissiere = User::create([
            'id' => Str::random(25), 'email' => 'caissiere@supplier.test',
            'password' => Hash::make('password123'), 'nom' => 'Caissiere', 'prenom' => 'Test', 'role' => 'caissiere',
        ]);
        $this->shop = Shop::create([
            'id' => Str::random(25), 'nom' => 'Boutique Test', 'createdBy' => $this->patron->id,
        ]);
        $this->caissiere->shops()->attach($this->shop->id);
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

    // ── Fournisseurs ─────────────────────────────────────────────────────────

    public function test_patron_peut_creer_fournisseur(): void
    {
        $this->loginAs($this->patron);

        $response = $this->post('/dashboard/fournisseurs', [
            'nom'                   => 'TechParts SARL',
            'contact_nom'           => 'Amadou Diallo',
            'telephone'             => '0700000099',
            'email'                 => 'contact@techparts.com',
            'delai_livraison_jours' => 7,
            'conditions_paiement'   => 'Net 30 jours',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('suppliers', [
            'nom'    => 'TechParts SARL',
            'shopId' => $this->shop->id,
        ]);
    }

    public function test_caissiere_ne_peut_pas_creer_fournisseur(): void
    {
        $this->loginAs($this->caissiere);

        $response = $this->post('/dashboard/fournisseurs', [
            'nom' => 'Fournisseur Interdit',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('suppliers', ['nom' => 'Fournisseur Interdit']);
    }

    public function test_suppression_fournisseur_avec_reappros_bloquee(): void
    {
        $this->loginAs($this->patron);

        $supplier = Supplier::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Fournisseur Lié',
        ]);

        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Article',
            'categorie' => 'accessoire', 'quantite' => 10, 'prixAchat' => 1000, 'prixVente' => 1500,
        ]);

        Reapprovisionnement::create([
            'id' => Str::random(25), 'stockId' => $stock->id, 'shopId' => $this->shop->id,
            'quantite' => 10, 'prixAchatUnitaire' => 1000, 'ancienPrixAchat' => 900,
            'nouveauPrixAchat' => 1000, 'ancienneQuantite' => 0, 'nouvelleQuantite' => 10,
            'supplier_id' => $supplier->id,
        ]);

        $response = $this->delete("/dashboard/fournisseurs/{$supplier->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    // ── Factures fournisseurs ────────────────────────────────────────────────

    public function test_creation_facture_fournisseur(): void
    {
        $this->loginAs($this->patron);

        $supplier = Supplier::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Fournisseur A',
        ]);

        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Écran iPhone',
            'categorie' => 'piece_detachee', 'quantite' => 5, 'prixAchat' => 4000, 'prixVente' => 7000,
        ]);

        $response = $this->post('/dashboard/factures-fournisseurs', [
            'supplier_id'  => $supplier->id,
            'date_facture' => now()->toDateString(),
            'date_echeance'=> now()->addDays(30)->toDateString(),
            'lignes' => [
                [
                    'stock_id'      => $stock->id,
                    'designation'   => 'Écran iPhone 12',
                    'quantite'      => 10,
                    'prix_unitaire' => 4000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $invoice = PurchaseInvoice::withoutGlobalScopes()
            ->where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(40000, $invoice->montant_total);
        $this->assertEquals('en_attente', $invoice->statut);
        $this->assertDatabaseHas('purchase_invoice_lines', [
            'purchase_invoice_id' => $invoice->id,
            'quantite'            => 10,
            'total'               => 40000,
        ]);
    }

    public function test_paiement_partiel_facture_fournisseur(): void
    {
        $this->loginAs($this->patron);

        $supplier = Supplier::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Fournisseur B',
        ]);

        $invoice = PurchaseInvoice::create([
            'id' => Str::random(25), 'numero' => 'FA-TEST-0001',
            'shopId' => $this->shop->id, 'supplier_id' => $supplier->id,
            'montant_total' => 50000, 'montant_paye' => 0, 'reste_a_payer' => 50000,
            'statut' => 'en_attente', 'date_facture' => now()->toDateString(),
            'created_by' => $this->patron->id,
        ]);

        $response = $this->post("/dashboard/factures-fournisseurs/{$invoice->id}/paiement", [
            'montant' => 20000,
        ]);

        $response->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(20000, $invoice->montant_paye);
        $this->assertEquals(30000, $invoice->reste_a_payer);
        $this->assertEquals('partiellement_payee', $invoice->statut);
    }

    public function test_paiement_total_solde_facture(): void
    {
        $this->loginAs($this->patron);

        $supplier = Supplier::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Fournisseur C',
        ]);

        $invoice = PurchaseInvoice::create([
            'id' => Str::random(25), 'numero' => 'FA-TEST-0002',
            'shopId' => $this->shop->id, 'supplier_id' => $supplier->id,
            'montant_total' => 10000, 'montant_paye' => 0, 'reste_a_payer' => 10000,
            'statut' => 'en_attente', 'date_facture' => now()->toDateString(),
            'created_by' => $this->patron->id,
        ]);

        $this->post("/dashboard/factures-fournisseurs/{$invoice->id}/paiement", ['montant' => 10000]);

        $invoice->refresh();
        $this->assertEquals('soldee', $invoice->statut);
        $this->assertEquals(0, $invoice->reste_a_payer);
    }

    // ── Alertes stock ────────────────────────────────────────────────────────

    public function test_stock_en_alerte_detecte(): void
    {
        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Batterie Samsung', 'categorie' => 'piece_detachee',
            'quantite' => 2, 'seuil_alerte' => 5,
            'prixAchat' => 2000, 'prixVente' => 3500,
        ]);

        $this->assertTrue($stock->isEnAlerte());
    }

    public function test_stock_au_dessus_seuil_pas_alerte(): void
    {
        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Écran Samsung', 'categorie' => 'piece_detachee',
            'quantite' => 10, 'seuil_alerte' => 5,
            'prixAchat' => 3000, 'prixVente' => 5000,
        ]);

        $this->assertFalse($stock->isEnAlerte());
    }

    public function test_stock_sans_seuil_jamais_alerte(): void
    {
        $stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => 'Chargeur', 'categorie' => 'accessoire',
            'quantite' => 0, 'seuil_alerte' => 0,
            'prixAchat' => 500, 'prixVente' => 1000,
        ]);

        $this->assertFalse($stock->isEnAlerte());
    }
}
