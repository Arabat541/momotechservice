<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class PurchaseInvoice extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'purchase_invoices';

    protected $fillable = [
        'id', 'numero', 'shopId', 'supplier_id',
        'montant_total', 'montant_paye', 'reste_a_payer',
        'statut', 'date_facture', 'date_echeance', 'notes', 'created_by',
    ];

    protected $casts = [
        'montant_total'   => 'float',
        'montant_paye'    => 'float',
        'reste_a_payer'   => 'float',
        'date_facture'    => 'date',
        'date_echeance'   => 'date',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseInvoiceLine::class, 'purchase_invoice_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reappros()
    {
        return $this->hasMany(Reapprovisionnement::class, 'purchase_invoice_id');
    }

    public function enregistrerPaiement(float $montant): void
    {
        $this->montant_paye  += $montant;
        $this->reste_a_payer  = max(0, $this->montant_total - $this->montant_paye);
        $this->statut         = match(true) {
            $this->reste_a_payer <= 0       => 'soldee',
            $this->montant_paye > 0         => 'partiellement_payee',
            default                          => 'en_attente',
        };
        $this->save();
    }

    public function isEnRetard(): bool
    {
        return $this->date_echeance
            && $this->date_echeance->isPast()
            && $this->statut !== 'soldee';
    }
}
