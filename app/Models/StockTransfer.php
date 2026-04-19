<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id', 'numero', 'shop_from_id', 'shop_to_id', 'created_by',
        'validated_by_sender', 'validated_by_receiver',
        'statut', 'notes', 'validated_sender_at', 'validated_receiver_at',
    ];

    protected $casts = [
        'validated_sender_at'   => 'datetime',
        'validated_receiver_at' => 'datetime',
    ];

    public function shopFrom()            { return $this->belongsTo(Shop::class, 'shop_from_id'); }
    public function shopTo()              { return $this->belongsTo(Shop::class, 'shop_to_id'); }
    public function createdBy()           { return $this->belongsTo(User::class, 'created_by'); }
    public function validatedBySender()   { return $this->belongsTo(User::class, 'validated_by_sender'); }
    public function validatedByReceiver() { return $this->belongsTo(User::class, 'validated_by_receiver'); }
    public function lines()               { return $this->hasMany(StockTransferLine::class); }

    public function impliqueBoutique(string $shopId): bool
    {
        return $this->shop_from_id === $shopId || $this->shop_to_id === $shopId;
    }
}
