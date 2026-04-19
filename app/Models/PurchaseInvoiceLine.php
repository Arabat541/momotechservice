<?php

namespace App\Models;

class PurchaseInvoiceLine extends BaseModel
{
    protected $table = 'purchase_invoice_lines';
    public $timestamps = false;

    protected $fillable = [
        'id', 'purchase_invoice_id', 'stock_id', 'designation',
        'quantite', 'prix_unitaire', 'total', 'reappro_id',
    ];

    protected $casts = [
        'quantite'      => 'integer',
        'prix_unitaire' => 'float',
        'total'         => 'float',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
