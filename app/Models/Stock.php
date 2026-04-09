<?php

namespace App\Models;

class Stock extends BaseModel
{
    protected $table = 'Stock';
    public $timestamps = false;

    protected $fillable = [
        'id', 'shopId', 'nom', 'quantite', 'prixAchat', 'prixVente', 'beneficeNetAttendu',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prixAchat' => 'float',
        'prixVente' => 'float',
        'beneficeNetAttendu' => 'float',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }
}
