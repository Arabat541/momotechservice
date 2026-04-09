<?php

namespace App\Models;

class PasswordResetToken extends BaseModel
{
    protected $table = 'PasswordResetToken';
    public $timestamps = false;
    protected $fillable = ['id', 'userId', 'token', 'expiresAt'];

    protected $casts = [
        'expiresAt' => 'datetime',
    ];
}
