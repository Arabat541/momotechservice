<?php

namespace App\Models;

class Settings extends BaseModel
{
    protected $table = 'settings';
    protected $fillable = ['id', 'shopId', 'companyInfo', 'warranty'];

    protected $casts = [
        'companyInfo' => 'array',
        'warranty' => 'array',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }
}
