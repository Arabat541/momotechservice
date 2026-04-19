<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferLine extends Model
{
    protected $fillable = ['stock_transfer_id', 'stock_id', 'quantite'];

    public function transfer() { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function stock()    { return $this->belongsTo(Stock::class); }
}
