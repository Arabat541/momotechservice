<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Warranty extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'warranties';

    protected $fillable = [
        'id', 'sale_id', 'client_id', 'shopId', 'designation',
        'duree_jours', 'date_debut', 'date_expiration',
        'conditions', 'statut', 'notes', 'created_by',
    ];

    protected $casts = [
        'duree_jours'     => 'integer',
        'date_debut'      => 'date',
        'date_expiration' => 'date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->statut === 'active' && $this->date_expiration->isFuture();
    }

    public function joursRestants(): int
    {
        return max(0, now()->diffInDays($this->date_expiration, false));
    }
}
