<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class PendingSale extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $fillable = [
        'id', 'shopId', 'client_id', 'created_by', 'cash_session_id',
        'statut', 'mode_paiement', 'montant_paye', 'notes', 'validated_at',
    ];

    protected $casts = [
        'montant_paye'  => 'float',
        'validated_at'  => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id')->withoutGlobalScopes();
    }

    public function lines()
    {
        return $this->hasMany(PendingSaleLine::class, 'pending_sale_id');
    }

    public function total(): float
    {
        return $this->lines->sum(fn($l) => $l->prix_unitaire * $l->quantite);
    }
}
