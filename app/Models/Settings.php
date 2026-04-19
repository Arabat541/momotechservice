<?php

namespace App\Models;

class Settings extends BaseModel
{
    protected $table = 'settings';
    protected $fillable = ['id', 'shopId', 'companyInfo', 'warranty', 'sms_config'];

    protected $casts = [
        'companyInfo' => 'array',
        'warranty'    => 'array',
        'sms_config'  => 'array',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }
}
