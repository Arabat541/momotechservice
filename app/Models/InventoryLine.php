<?php

namespace App\Models;

class InventoryLine extends BaseModel
{
    protected $table = 'inventory_lines';
    public $timestamps = false;

    protected $fillable = [
        'id', 'inventory_session_id', 'stock_id',
        'quantite_theorique', 'quantite_comptee', 'ecart', 'notes',
    ];

    protected $casts = [
        'quantite_theorique' => 'integer',
        'quantite_comptee'   => 'integer',
        'ecart'              => 'integer',
    ];

    public function inventorySession()
    {
        return $this->belongsTo(InventorySession::class, 'inventory_session_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}
