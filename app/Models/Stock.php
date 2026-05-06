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
        'id', 'shopId', 'nom', 'categorie', 'quantite', 'seuil_alerte', 'seuil_epuise',
        'prixAchat', 'prixVente', 'prix_revendeur', 'prix_demi_gros', 'prixGros', 'beneficeNetAttendu',
    ];

    protected $casts = [
        'quantite'           => 'integer',
        'seuil_alerte'       => 'integer',
        'seuil_epuise'       => 'integer',
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

    public function isStockFaible(): bool
    {
        return $this->seuil_alerte > 0
            && $this->quantite <= $this->seuil_alerte
            && $this->quantite > ($this->seuil_epuise ?? 0);
    }

    public function isStockEpuise(): bool
    {
        return $this->seuil_epuise > 0 && $this->quantite <= $this->seuil_epuise;
    }

    public function isEnAlerte(): bool
    {
        return $this->isStockFaible() || $this->isStockEpuise();
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
