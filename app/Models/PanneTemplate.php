<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanneTemplate extends Model
{
    protected $fillable = ['device_model_id', 'description', 'prix_estime'];

    protected $casts = ['prix_estime' => 'float'];

    public function deviceModel()
    {
        return $this->belongsTo(DeviceModel::class, 'device_model_id');
    }
}
