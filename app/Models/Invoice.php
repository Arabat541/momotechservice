<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Invoice extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'invoices';

    protected $fillable = [
        'id', 'numero_facture', 'shopId', 'repair_id', 'client_id', 'cash_session_id',
        'montant_estime', 'montant_final', 'montant_paye', 'reste_a_payer', 'statut', 'created_by',
    ];

    protected $casts = [
        'montant_estime'  => 'float',
        'montant_final'   => 'float',
        'montant_paye'    => 'float',
        'reste_a_payer'   => 'float',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enregistrerPaiement(float $montant): void
    {
        $this->montant_paye    += $montant;
        $this->reste_a_payer   = max(0, $this->montant_final - $this->montant_paye);
        $this->statut = $this->reste_a_payer <= 0 ? 'soldee' : 'partielle';
        $this->save();
    }
}
