<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;
use Illuminate\Database\Eloquent\Model;

class DeviceModel extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $fillable = ['shopId', 'marque', 'modele', 'type'];

    public function panneTemplates()
    {
        return $this->hasMany(PanneTemplate::class, 'device_model_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->marque} {$this->modele}";
    }
}
