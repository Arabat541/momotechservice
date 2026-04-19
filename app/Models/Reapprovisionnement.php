<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Reapprovisionnement extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'Reapprovisionnement';
    public $timestamps = false;

    protected $fillable = [
        'id', 'stockId', 'shopId', 'quantite', 'prixAchatUnitaire',
        'ancienPrixAchat', 'nouveauPrixAchat', 'ancienneQuantite',
        'nouvelleQuantite', 'fournisseur', 'note', 'date',
        'supplier_id', 'purchase_invoice_id',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prixAchatUnitaire' => 'float',
        'ancienPrixAchat' => 'float',
        'nouveauPrixAchat' => 'float',
        'ancienneQuantite' => 'integer',
        'nouvelleQuantite' => 'integer',
        'date' => 'datetime',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stockId');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
