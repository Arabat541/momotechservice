<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingSaleLine extends Model
{
    protected $fillable = [
        'pending_sale_id', 'stock_id', 'stock_nom', 'quantite', 'prix_unitaire', 'palier',
    ];

    protected $casts = [
        'prix_unitaire' => 'float',
        'quantite'      => 'integer',
    ];

    public function pendingSale()
    {
        return $this->belongsTo(PendingSale::class, 'pending_sale_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stock_id')->withoutGlobalScopes();
    }

    public function sousTotal(): float
    {
        return $this->prix_unitaire * $this->quantite;
    }
}
