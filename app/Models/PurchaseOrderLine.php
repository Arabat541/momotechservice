<?php

namespace App\Models;

class PurchaseOrderLine extends BaseModel
{
    protected $table = 'purchase_order_lines';
    public $timestamps = false;

    protected $fillable = [
        'id', 'purchase_order_id', 'stock_id', 'designation',
        'quantite_commandee', 'quantite_recue', 'prix_unitaire_estime', 'total_estime',
    ];

    protected $casts = [
        'quantite_commandee'    => 'integer',
        'quantite_recue'        => 'integer',
        'prix_unitaire_estime'  => 'float',
        'total_estime'          => 'float',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function resteARecevoir(): int
    {
        return max(0, $this->quantite_commandee - $this->quantite_recue);
    }
}
