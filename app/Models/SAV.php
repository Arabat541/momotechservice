<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

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

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class, 'repairId');
    }
}
