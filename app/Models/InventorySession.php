<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class InventorySession extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'inventory_sessions';

    protected $fillable = [
        'id', 'shopId', 'created_by', 'closed_by', 'statut', 'notes', 'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function lines()
    {
        return $this->hasMany(InventoryLine::class, 'inventory_session_id');
    }

    public function isEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    public function totalEcart(): int
    {
        return $this->lines->whereNotNull('ecart')->sum('ecart');
    }

    public function lignesEnEcart(): \Illuminate\Support\Collection
    {
        return $this->lines->where('ecart', '!=', 0)->whereNotNull('ecart');
    }
}
