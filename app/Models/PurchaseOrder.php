<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class PurchaseOrder extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'purchase_orders';

    protected $fillable = [
        'id', 'numero', 'shopId', 'supplier_id', 'statut',
        'date_commande', 'date_livraison_prevue', 'date_livraison_reelle',
        'montant_total', 'notes', 'created_by',
    ];

    protected $casts = [
        'montant_total'          => 'float',
        'date_commande'          => 'date',
        'date_livraison_prevue'  => 'date',
        'date_livraison_reelle'  => 'date',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->statut, ['brouillon']);
    }

    public function estEnRetard(): bool
    {
        return $this->date_livraison_prevue
            && $this->date_livraison_prevue->isPast()
            && !in_array($this->statut, ['recu', 'annule']);
    }
}
