<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;
use Illuminate\Database\Eloquent\Casts\Attribute;

class SAV extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    public $timestamps = false;
    protected $table = 'savs';

    protected $fillable = [
        'id', 'shopId', 'numeroSAV', 'repairId', 'numeroReparationOrigine',
        'client_nom', 'client_telephone', 'appareil_marque_modele',
        'description_probleme', 'sous_garantie', 'date_fin_garantie',
        'statut', 'decision', 'date_creation', 'date_resolution',
        'notes', 'userId',
    ];

    protected $casts = [
        'client_nom'       => 'encrypted',
        'client_telephone' => 'encrypted',
        'sous_garantie'    => 'boolean',
        'date_creation'    => 'datetime',
        'date_fin_garantie'=> 'datetime',
        'date_resolution'  => 'datetime',
    ];

    protected function clientNom(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->unserializeIfSerialized($value),
        );
    }

    protected function clientTelephone(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->unserializeIfSerialized($value),
        );
    }

    private function unserializeIfSerialized(mixed $value): string
    {
        if (is_string($value) && str_starts_with($value, 's:')) {
            $unserialized = @unserialize($value);
            if ($unserialized !== false) {
                return (string) $unserialized;
            }
        }
        return (string) ($value ?? '');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repairId');
    }
}
