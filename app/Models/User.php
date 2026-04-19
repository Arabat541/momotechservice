<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'users';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'email', 'password', 'nom', 'prenom', 'role', 'google2fa_secret', 'two_factor_enabled'];
    protected $hidden   = ['password', 'google2fa_secret'];
    protected $casts    = ['two_factor_enabled' => 'boolean'];

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
        return $this->belongsToMany(Shop::class, '_user_shops', 'A', 'B');
    }

    public function skills()
    {
        return $this->hasMany(TechnicianSkill::class, 'user_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'user_id');
    }
}
