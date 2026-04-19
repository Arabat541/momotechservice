<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    private User  $patron;
    private User  $caissSource;
    private User  $caissDest;
    private Shop  $shopSource;
    private Shop  $shopDest;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patron = User::create([
            'id' => Str::random(25), 'email' => 'patron@transfer.test',
            'password' => Hash::make('password'), 'nom' => 'Patron', 'prenom' => 'P', 'role' => 'patron',
        ]);
        $this->caissSource = User::create([
            'id' => Str::random(25), 'email' => 'source@transfer.test',
            'password' => Hash::make('password'), 'nom' => 'Source', 'prenom' => 'S', 'role' => 'caissiere',
        ]);
        $this->caissDest = User::create([
            'id' => Str::random(25), 'email' => 'dest@transfer.test',
            'password' => Hash::make('password'), 'nom' => 'Dest', 'prenom' => 'D', 'role' => 'caissiere',
        ]);

        $this->shopSource = Shop::create([
            'id' => Str::random(25), 'nom' => 'Boutique Source', 'createdBy' => $this->patron->id,
        ]);
        $this->shopDest = Shop::create([
            'id' => Str::random(25), 'nom' => 'Boutique Dest', 'createdBy' => $this->patron->id,
        ]);

        $this->caissSource->shops()->attach($this->shopSource->id);
        $this->caissDest->shops()->attach($this->shopDest->id);

        $this->stock = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shopSource->id,
            'nom' => 'Écran iPhone 14', 'categorie' => 'piece_detachee',
            'quantite' => 10, 'prixAchat' => 5000, 'prixVente' => 8000,
        ]);
    }

    private function loginAs(User $user, Shop $shop): void
    {
        $this->withSession([
            'user_id'         => $user->id,
            'user_role'       => $user->role,
            'user_email'      => $user->email,
            'user_nom'        => $user->nom,
            'user_prenom'     => $user->prenom,
            'current_shop_id' => $shop->id,
        ]);
    }

    // ── Création ────────────────────────────────────────────────────────────

    public function test_patron_peut_creer_un_transfert(): void
    {
        $this->loginAs($this->patron, $this->shopSource);

        $response = $this->post('/dashboard/transferts', [
            'shop_from_id' => $this->shopSource->id,
            'shop_to_id'   => $this->shopDest->id,
            'lignes'       => [['stock_id' => $this->stock->id, 'quantite' => 3]],
        ]);

        $response->assertRedirect();
        $transfer = StockTransfer::withoutGlobalScopes()->first();
        $this->assertNotNull($transfer);
        $this->assertEquals('en_attente_envoi', $transfer->statut);
        $this->assertEquals(1, $transfer->lines()->count());
        $this->assertEquals(10, $this->stock->fresh()->quantite); // stock non encore débité
    }

    public function test_creation_echoue_si_stock_insuffisant(): void
    {
        $this->loginAs($this->patron, $this->shopSource);

        $response = $this->post('/dashboard/transferts', [
            'shop_from_id' => $this->shopSource->id,
            'shop_to_id'   => $this->shopDest->id,
            'lignes'       => [['stock_id' => $this->stock->id, 'quantite' => 99]],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, StockTransfer::withoutGlobalScopes()->count());
    }

    public function test_caissiere_ne_peut_pas_creer_un_transfert(): void
    {
        $this->loginAs($this->caissSource, $this->shopSource);

        $response = $this->post('/dashboard/transferts', [
            'shop_from_id' => $this->shopSource->id,
            'shop_to_id'   => $this->shopDest->id,
            'lignes'       => [['stock_id' => $this->stock->id, 'quantite' => 2]],
        ]);

        $response->assertForbidden();
    }

    // ── Validation envoi ────────────────────────────────────────────────────

    public function test_caissiere_source_peut_valider_envoi(): void
    {
        $transfer = $this->creerTransfert(3);
        $this->loginAs($this->caissSource, $this->shopSource);

        $response = $this->post("/dashboard/transferts/{$transfer->id}/valider-envoi");

        $response->assertRedirect();
        $this->assertEquals('en_attente_reception', $transfer->fresh()->statut);
        $this->assertEquals(7, $this->stock->fresh()->quantite); // stock débité
        $this->assertEquals($this->caissSource->id, $transfer->fresh()->validated_by_sender);
    }

    public function test_caissiere_destination_ne_peut_pas_valider_envoi(): void
    {
        $transfer = $this->creerTransfert(3);
        $this->loginAs($this->caissDest, $this->shopDest);

        $response = $this->post("/dashboard/transferts/{$transfer->id}/valider-envoi");

        $response->assertForbidden();
        $this->assertEquals('en_attente_envoi', $transfer->fresh()->statut);
        $this->assertEquals(10, $this->stock->fresh()->quantite); // stock intact
    }

    // ── Validation réception ────────────────────────────────────────────────

    public function test_caissiere_dest_peut_valider_reception(): void
    {
        $transfer = $this->creerTransfert(4);
        $this->validerEnvoi($transfer);
        $this->loginAs($this->caissDest, $this->shopDest);

        $response = $this->post("/dashboard/transferts/{$transfer->id}/valider-reception");

        $response->assertRedirect();
        $this->assertEquals('completee', $transfer->fresh()->statut);

        // Stock crédité dans la boutique dest
        $destStock = Stock::withoutGlobalScopes()
            ->where('shopId', $this->shopDest->id)
            ->where('nom', 'Écran iPhone 14')
            ->first();
        $this->assertNotNull($destStock);
        $this->assertEquals(4, $destStock->quantite);
    }

    public function test_stock_dest_existant_est_incrementé(): void
    {
        // Pré-existant dans la boutique dest
        $existant = Stock::create([
            'id' => Str::random(25), 'shopId' => $this->shopDest->id,
            'nom' => 'Écran iPhone 14', 'categorie' => 'piece_detachee',
            'quantite' => 2, 'prixAchat' => 5000, 'prixVente' => 8000,
        ]);

        $transfer = $this->creerTransfert(3);
        $this->validerEnvoi($transfer);
        $this->loginAs($this->caissDest, $this->shopDest);
        $this->post("/dashboard/transferts/{$transfer->id}/valider-reception");

        $this->assertEquals(5, $existant->fresh()->quantite); // 2 + 3
    }

    public function test_caissiere_source_ne_peut_pas_valider_reception(): void
    {
        $transfer = $this->creerTransfert(3);
        $this->validerEnvoi($transfer);
        $this->loginAs($this->caissSource, $this->shopSource);

        $response = $this->post("/dashboard/transferts/{$transfer->id}/valider-reception");

        $response->assertForbidden();
        $this->assertEquals('en_attente_reception', $transfer->fresh()->statut);
    }

    // ── Annulation ──────────────────────────────────────────────────────────

    public function test_patron_peut_annuler_transfert_en_attente_envoi(): void
    {
        $transfer = $this->creerTransfert(5);
        $this->loginAs($this->patron, $this->shopSource);

        $this->post("/dashboard/transferts/{$transfer->id}/annuler");

        $this->assertEquals('annulee', $transfer->fresh()->statut);
        $this->assertEquals(10, $this->stock->fresh()->quantite); // stock intact (jamais débité)
    }

    public function test_annulation_apres_envoi_restaure_le_stock(): void
    {
        $transfer = $this->creerTransfert(5);
        $this->validerEnvoi($transfer);

        $this->assertEquals(5, $this->stock->fresh()->quantite); // débité

        $this->loginAs($this->patron, $this->shopSource);
        $this->post("/dashboard/transferts/{$transfer->id}/annuler");

        $this->assertEquals('annulee', $transfer->fresh()->statut);
        $this->assertEquals(10, $this->stock->fresh()->quantite); // restauré
    }

    public function test_transfert_complete_ne_peut_pas_etre_annule(): void
    {
        $transfer = $this->creerTransfert(2);
        $this->validerEnvoi($transfer);
        $this->validerReception($transfer);
        $this->loginAs($this->patron, $this->shopSource);

        $response = $this->post("/dashboard/transferts/{$transfer->id}/annuler");

        $response->assertSessionHas('error');
        $this->assertEquals('completee', $transfer->fresh()->statut);
    }

    // ── Numérotation & liste ─────────────────────────────────────────────────

    public function test_numero_transfert_est_sequentiel(): void
    {
        $this->loginAs($this->patron, $this->shopSource);
        $year = date('Y');

        $this->post('/dashboard/transferts', [
            'shop_from_id' => $this->shopSource->id,
            'shop_to_id'   => $this->shopDest->id,
            'lignes'       => [['stock_id' => $this->stock->id, 'quantite' => 1]],
        ]);
        $this->post('/dashboard/transferts', [
            'shop_from_id' => $this->shopSource->id,
            'shop_to_id'   => $this->shopDest->id,
            'lignes'       => [['stock_id' => $this->stock->id, 'quantite' => 1]],
        ]);

        $numeros = StockTransfer::withoutGlobalScopes()->pluck('numero')->sort()->values();
        $this->assertEquals("TRF-{$year}-0001", $numeros[0]);
        $this->assertEquals("TRF-{$year}-0002", $numeros[1]);
    }

    public function test_caissiere_voit_uniquement_ses_boutiques(): void
    {
        $this->creerTransfert(1);
        $this->loginAs($this->caissSource, $this->shopSource);

        $response = $this->get('/dashboard/transferts');
        $response->assertOk();
        $response->assertSee(optional($this->shopSource)->nom);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function creerTransfert(int $quantite): StockTransfer
    {
        $this->loginAs($this->patron, $this->shopSource);
        $this->post('/dashboard/transferts', [
            'shop_from_id' => $this->shopSource->id,
            'shop_to_id'   => $this->shopDest->id,
            'lignes'       => [['stock_id' => $this->stock->id, 'quantite' => $quantite]],
        ]);
        return StockTransfer::withoutGlobalScopes()->latest()->first();
    }

    private function validerEnvoi(StockTransfer $transfer): void
    {
        $this->loginAs($this->caissSource, $this->shopSource);
        $this->post("/dashboard/transferts/{$transfer->id}/valider-envoi");
    }

    private function validerReception(StockTransfer $transfer): void
    {
        $this->loginAs($this->caissDest, $this->shopDest);
        $this->post("/dashboard/transferts/{$transfer->id}/valider-reception");
    }
}
