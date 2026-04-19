<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = ['userId', 'shopId', 'method', 'route', 'ip', 'action'];
}
