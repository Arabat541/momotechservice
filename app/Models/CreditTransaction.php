<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class CreditTransaction extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'credit_transactions';

    protected $fillable = [
        'id', 'client_id', 'shopId', 'sale_id', 'montant', 'type', 'solde_apres', 'notes', 'created_by',
    ];

    protected $casts = [
        'montant'     => 'float',
        'solde_apres' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
