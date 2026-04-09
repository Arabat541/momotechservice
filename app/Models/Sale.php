<?php

namespace App\Models;

class Sale extends BaseModel
{
    protected $table = 'Sale';
    public $timestamps = false;

    protected $fillable = [
        'id', 'nom', 'quantite', 'client', 'prixVente', 'total', 'stockId', 'shopId', 'date',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prixVente' => 'float',
        'total' => 'float',
        'date' => 'datetime',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stockId');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }
}
