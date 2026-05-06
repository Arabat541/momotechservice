<?php

namespace App\Models;

class SalePayment extends BaseModel
{
    protected $table = 'sale_payments';

    protected $fillable = [
        'id', 'sale_id', 'montant', 'moyen', 'created_by',
    ];

    protected $casts = [
        'montant' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
