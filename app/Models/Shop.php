<?php

namespace App\Models;

class Shop extends BaseModel
{
    protected $table = 'Shop';
    protected $fillable = ['id', 'nom', 'adresse', 'telephone', 'createdBy'];

    public function users()
    {
        return $this->belongsToMany(User::class, '_UserShops', 'B', 'A');
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class, 'shopId');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'shopId');
    }

    public function settings()
    {
        return $this->hasOne(Settings::class, 'shopId');
    }

    public function savs()
    {
        return $this->hasMany(SAV::class, 'shopId');
    }
}
