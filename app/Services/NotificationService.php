<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Notification;
use App\Models\Repair;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    public function notifierStockAlerte(Stock $stock): void
    {
        if (!$stock->isEnAlerte()) {
            return;
        }

        $exists = Notification::whereNull('lu_at')
            ->where('type', 'stock_alerte')
            ->where('entity_id', $stock->id)
            ->exists();

        if ($exists) {
            return;
        }

        Notification::create([
            'type'        => 'stock_alerte',
            'titre'       => "Stock critique : {$stock->nom}",
            'message'     => "Quantité : {$stock->quantite} / Seuil : {$stock->seuil_alerte}",
            'shop_id'     => $stock->shopId,
            'role_cible'  => 'all',
            'entity_type' => 'Stock',
            'entity_id'   => $stock->id,
        ]);
    }

    public function notifierReparationPrete(Repair $repair): void
    {
        $exists = Notification::whereNull('lu_at')
            ->where('type', 'reparation_prete')
            ->where('entity_id', $repair->id)
            ->exists();

        if ($exists) {
            return;
        }

        Notification::create([
            'type'        => 'reparation_prete',
            'titre'       => "Réparation prête : {$repair->client_nom}",
            'message'     => "Appareil prêt pour retrait — {$repair->appareil_marque_modele}",
            'shop_id'     => $repair->shopId,
            'role_cible'  => 'all',
            'entity_type' => 'Repair',
            'entity_id'   => $repair->id,
        ]);
    }

    public function notifierCreditDepasse(Client $client): void
    {
        if ($client->solde_credit <= $client->credit_limite) {
            return;
        }

        $exists = Notification::whereNull('lu_at')
            ->where('type', 'credit_depasse')
            ->where('entity_id', $client->id)
            ->exists();

        if ($exists) {
            return;
        }

        Notification::create([
            'type'        => 'credit_depasse',
            'titre'       => "Limite crédit dépassée : {$client->nom}",
            'message'     => "Solde dû : " . number_format($client->solde_credit, 0, ',', ' ') . " F / Limite : " . number_format($client->credit_limite, 0, ',', ' ') . " F",
            'shop_id'     => $client->shopId,
            'role_cible'  => 'patron',
            'entity_type' => 'Client',
            'entity_id'   => $client->id,
        ]);
    }

    public function getNotificationsForUser(User $user, ?string $shopId): Collection
    {
        $query = Notification::unread()->orderByDesc('created_at');

        if ($user->role !== 'patron') {
            $query->forShop($shopId)->forRole('caissiere');
        }

        return $query->get();
    }
}
