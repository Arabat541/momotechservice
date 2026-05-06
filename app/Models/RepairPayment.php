<?php

namespace App\Models;

class RepairPayment extends BaseModel
{
    protected $table = 'repair_payments';

    protected $fillable = [
        'id', 'repair_id', 'montant', 'moyen', 'notes', 'created_by',
    ];

    protected $casts = [
        'montant' => 'float',
    ];

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repair_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
