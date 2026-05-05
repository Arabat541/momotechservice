<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Sale extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'Sale';
    public $timestamps = false;

    protected $fillable = [
        'id', 'numeroVente', 'nom', 'quantite', 'client', 'prixVente', 'remise', 'total',
        'stockId', 'shopId', 'date',
        'client_id', 'cash_session_id', 'mode_paiement', 'moyen_paiement', 'montant_paye', 'reste_credit', 'statut',
    ];

    protected $casts = [
        'quantite'     => 'integer',
        'prixVente'    => 'float',
        'remise'       => 'float',
        'total'        => 'float',
        'montant_paye' => 'float',
        'reste_credit' => 'float',
        'date'         => 'datetime',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stockId');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class, 'sale_id');
    }
}
