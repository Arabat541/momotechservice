<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Stock extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'stocks';
    public $timestamps = false;

    protected $fillable = [
        'id', 'shopId', 'nom', 'categorie', 'quantite', 'seuil_alerte',
        'prixAchat', 'prixVente', 'prix_revendeur', 'prix_demi_gros', 'prixGros', 'beneficeNetAttendu',
    ];

    protected $casts = [
        'quantite'           => 'integer',
        'seuil_alerte'       => 'integer',
        'prixAchat'          => 'float',
        'prixVente'          => 'float',
        'prix_revendeur'     => 'float',
        'prix_demi_gros'     => 'float',
        'prixGros'           => 'float',
        'beneficeNetAttendu' => 'float',
    ];

    public function isPieceDetachee(): bool
    {
        return $this->categorie === 'piece_detachee';
    }

    public function isEnAlerte(): bool
    {
        return $this->seuil_alerte > 0 && $this->quantite <= $this->seuil_alerte;
    }

    public function reappros()
    {
        return $this->hasMany(Reapprovisionnement::class, 'stockId');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }
}
