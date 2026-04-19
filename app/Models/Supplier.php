<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Supplier extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'suppliers';

    protected $fillable = [
        'id', 'shopId', 'nom', 'contact_nom', 'telephone', 'email',
        'adresse', 'delai_livraison_jours', 'conditions_paiement', 'notes', 'actif',
    ];

    protected $casts = [
        'actif'                  => 'boolean',
        'delai_livraison_jours'  => 'integer',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function reappros()
    {
        return $this->hasMany(Reapprovisionnement::class, 'supplier_id');
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function totalDu(): float
    {
        return (float) $this->purchaseInvoices()
            ->whereIn('statut', ['en_attente', 'partiellement_payee'])
            ->sum('reste_a_payer');
    }
}
