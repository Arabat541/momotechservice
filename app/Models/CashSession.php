<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class CashSession extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'cash_sessions';

    protected $fillable = [
        'id', 'shopId', 'userId', 'date',
        'montant_ouverture', 'montant_fermeture_attendu', 'montant_fermeture_reel', 'ecart', 'statut',
    ];

    protected $casts = [
        'date'                      => 'date',
        'montant_ouverture'         => 'float',
        'montant_fermeture_attendu' => 'float',
        'montant_fermeture_reel'    => 'float',
        'ecart'                     => 'float',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'cash_session_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'cash_session_id');
    }

    public function isOuverte(): bool
    {
        return $this->statut === 'ouverte';
    }
}
