<?php

namespace Tests\Feature;

use App\Models\InventoryLine;
use App\Models\InventorySession;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $patron;
    private User $caissiere;
    private Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patron = User::create([
            'id' => Str::random(25), 'email' => 'patron@inv.test',
            'password' => Hash::make('password123'), 'nom' => 'Patron', 'prenom' => 'Test', 'role' => 'patron',
        ]);
        $this->caissiere = User::create([
            'id' => Str::random(25), 'email' => 'caissiere@inv.test',
            'password' => Hash::make('password123'), 'nom' => 'Caissiere', 'prenom' => 'Test', 'role' => 'caissiere',
        ]);
        $this->shop = Shop::create([
            'id' => Str::random(25), 'nom' => 'Boutique Inv', 'createdBy' => $this->patron->id,
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

    private function createStock(string $nom, int $quantite, int $seuil = 0): Stock
    {
        return Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id,
            'nom' => $nom, 'categorie' => 'accessoire',
            'quantite' => $quantite, 'seuil_alerte' => $seuil,
            'prixAchat' => 1000, 'prixVente' => 1500,
        ]);
    }

    // ── Inventaires ──────────────────────────────────────────────────────────

    public function test_ouverture_inventaire_cree_snapshot_stock(): void
    {
        $stock1 = $this->createStock('Article A', 10);
        $stock2 = $this->createStock('Article B', 5);

        $this->loginAs($this->patron);
        $response = $this->post('/dashboard/inventaires/ouvrir', []);

        $response->assertRedirect();
        $session = InventorySession::withoutGlobalScopes()->where('shopId', $this->shop->id)->first();
        $this->assertNotNull($session);
        $this->assertEquals('en_cours', $session->statut);
        $this->assertDatabaseHas('inventory_lines', ['stock_id' => $stock1->id, 'quantite_theorique' => 10]);
        $this->assertDatabaseHas('inventory_lines', ['stock_id' => $stock2->id, 'quantite_theorique' => 5]);
    }

    public function test_double_inventaire_impossible(): void
    {
        $this->loginAs($this->patron);
        $this->post('/dashboard/inventaires/ouvrir', []);
        $response = $this->post('/dashboard/inventaires/ouvrir', []);

        $response->assertSessionHas('error');
        $this->assertEquals(1, InventorySession::withoutGlobalScopes()->where('shopId', $this->shop->id)->count());
    }

    public function test_saisie_ligne_calcule_ecart(): void
    {
        $stock = $this->createStock('Article C', 10);
        $this->loginAs($this->patron);
        $this->post('/dashboard/inventaires/ouvrir', []);

        $session = InventorySession::withoutGlobalScopes()->where('shopId', $this->shop->id)->first();
        $line    = InventoryLine::where('stock_id', $stock->id)->first();

        $response = $this->post("/dashboard/inventaires/{$session->id}/lignes/{$line->id}", [
            'quantite_comptee' => 8,
        ]);

        $response->assertRedirect();
        $line->refresh();
        $this->assertEquals(8, $line->quantite_comptee);
        $this->assertEquals(-2, $line->ecart);
    }

    public function test_cloture_ajuste_les_stocks(): void
    {
        $stock = $this->createStock('Article D', 10);
        $this->loginAs($this->patron);
        $this->post('/dashboard/inventaires/ouvrir', []);

        $session = InventorySession::withoutGlobalScopes()->where('shopId', $this->shop->id)->first();
        $line    = InventoryLine::where('stock_id', $stock->id)->first();

        // Saisir 7 au lieu de 10
        $this->post("/dashboard/inventaires/{$session->id}/lignes/{$line->id}", ['quantite_comptee' => 7]);
        $this->post("/dashboard/inventaires/{$session->id}/cloturer", ['appliquer_ajustements' => '1']);

        $stock->refresh();
        $this->assertEquals(7, $stock->quantite);
        $this->assertEquals('termine', InventorySession::withoutGlobalScopes()->find($session->id)->statut);
    }

    public function test_cloture_sans_ajustement_preserve_stocks(): void
    {
        $stock = $this->createStock('Article E', 10);
        $this->loginAs($this->patron);
        $this->post('/dashboard/inventaires/ouvrir', []);

        $session = InventorySession::withoutGlobalScopes()->where('shopId', $this->shop->id)->first();
        $line    = InventoryLine::where('stock_id', $stock->id)->first();

        $this->post("/dashboard/inventaires/{$session->id}/lignes/{$line->id}", ['quantite_comptee' => 3]);
        $this->post("/dashboard/inventaires/{$session->id}/cloturer", ['appliquer_ajustements' => '0']);

        $stock->refresh();
        $this->assertEquals(10, $stock->quantite); // inchangé
    }

    // ── Bons de commande ─────────────────────────────────────────────────────

    public function test_creation_bon_commande(): void
    {
        $supplier = Supplier::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Fournisseur BC',
        ]);
        $stock = $this->createStock('Pièce X', 2, 5);

        $this->loginAs($this->patron);
        $response = $this->post('/dashboard/bons-commande', [
            'supplier_id'          => $supplier->id,
            'date_commande'        => now()->toDateString(),
            'date_livraison_prevue'=> now()->addDays(7)->toDateString(),
            'lignes' => [[
                'stock_id'              => $stock->id,
                'designation'           => 'Pièce X',
                'quantite_commandee'    => 20,
                'prix_unitaire_estime'  => 500,
            ]],
        ]);

        $response->assertRedirect();
        $order = PurchaseOrder::withoutGlobalScopes()->where('supplier_id', $supplier->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('brouillon', $order->statut);
        $this->assertEquals(10000, $order->montant_total);
    }

    public function test_reception_partielle_incremente_stock(): void
    {
        $supplier = Supplier::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Fourn Reception',
        ]);
        $stock = $this->createStock('Pièce Y', 5);

        $this->loginAs($this->patron);
        $this->post('/dashboard/bons-commande', [
            'supplier_id'   => $supplier->id,
            'date_commande' => now()->toDateString(),
            'lignes' => [[
                'stock_id'            => $stock->id,
                'designation'         => 'Pièce Y',
                'quantite_commandee'  => 10,
                'prix_unitaire_estime'=> 0,
            ]],
        ]);

        $order = PurchaseOrder::withoutGlobalScopes()->where('supplier_id', $supplier->id)->first();
        $line  = $order->lines->first();

        // Marquer comme envoyé puis recevoir 6
        $this->post("/dashboard/bons-commande/{$order->id}/envoyer");
        $this->post("/dashboard/bons-commande/{$order->id}/reception", [
            'receptions' => [$line->id => 6],
        ]);

        $stock->refresh();
        $order->refresh();
        $this->assertEquals(11, $stock->quantite); // 5 + 6
        $this->assertEquals('partiellement_recu', $order->statut);
    }

    public function test_reception_totale_cloture_commande(): void
    {
        $supplier = Supplier::create([
            'id' => Str::random(25), 'shopId' => $this->shop->id, 'nom' => 'Fourn Total',
        ]);
        $stock = $this->createStock('Pièce Z', 0);

        $this->loginAs($this->patron);
        $this->post('/dashboard/bons-commande', [
            'supplier_id'   => $supplier->id,
            'date_commande' => now()->toDateString(),
            'lignes' => [[
                'stock_id'            => $stock->id,
                'designation'         => 'Pièce Z',
                'quantite_commandee'  => 5,
                'prix_unitaire_estime'=> 0,
            ]],
        ]);

        $order = PurchaseOrder::withoutGlobalScopes()->where('supplier_id', $supplier->id)->first();
        $line  = $order->lines->first();

        $this->post("/dashboard/bons-commande/{$order->id}/envoyer");
        $this->post("/dashboard/bons-commande/{$order->id}/reception", [
            'receptions' => [$line->id => 5],
        ]);

        $order->refresh();
        $this->assertEquals('recu', $order->statut);
    }
}
