<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'User';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'email', 'password', 'nom', 'prenom', 'role'];
    protected $hidden = ['password'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = \Illuminate\Support\Str::random(25);
            }
        });
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, '_UserShops', 'A', 'B');
    }
}
