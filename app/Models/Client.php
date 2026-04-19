<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Client extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'clients';

    protected $fillable = [
        'id', 'shopId', 'user_id', 'nom', 'telephone', 'type', 'nom_boutique', 'credit_limite', 'solde_credit',
    ];

    protected $casts = [
        'credit_limite' => 'float',
        'solde_credit'  => 'float',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class, 'client_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'client_id');
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class, 'client_id');
    }

    public function isRevendeur(): bool
    {
        return $this->type === 'revendeur';
    }

    public function creditDisponible(): float
    {
        return max(0, $this->credit_limite - $this->solde_credit);
    }
}
