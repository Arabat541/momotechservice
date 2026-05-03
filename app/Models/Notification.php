<?php

namespace App\Models;

class Notification extends BaseModel
{
    protected $table = 'notifications';

    protected $fillable = [
        'id', 'type', 'titre', 'message', 'shop_id', 'role_cible',
        'entity_type', 'entity_id', 'lu_at',
    ];

    protected $casts = [
        'lu_at' => 'datetime',
    ];

    public function scopeUnread($query)
    {
        return $query->whereNull('lu_at');
    }

    public function scopeForShop($query, ?string $shopId)
    {
        return $query->where(function ($q) use ($shopId) {
            $q->where('shop_id', $shopId)->orWhereNull('shop_id');
        });
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where(function ($q) use ($role) {
            $q->where('role_cible', $role)->orWhere('role_cible', 'all');
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}
