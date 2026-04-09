<?php

namespace App\Models;

class PasswordResetToken extends BaseModel
{
    protected $table = 'password_reset_tokens';
    public $timestamps = false;
    protected $fillable = ['id', 'userId', 'token', 'expiresAt'];

    protected $casts = [
        'expiresAt' => 'datetime',
    ];
}
