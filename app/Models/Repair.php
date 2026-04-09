<?php

namespace App\Models;

class Repair extends BaseModel
{
    protected $table = 'repairs';
    public $timestamps = false;

    protected $fillable = [
        'id', 'shopId', 'numeroReparation', 'type_reparation',
        'client_nom', 'client_telephone', 'appareil_marque_modele',
        'pannes_services', 'pieces_rechange_utilisees',
        'total_reparation', 'montant_paye', 'reste_a_payer',
        'statut_reparation', 'date_creation', 'date_mise_en_reparation',
        'date_rendez_vous', 'date_retrait', 'etat_paiement', 'userId',
    ];

    protected $casts = [
        'pannes_services' => 'array',
        'pieces_rechange_utilisees' => 'array',
        'total_reparation' => 'float',
        'montant_paye' => 'float',
        'reste_a_payer' => 'float',
        'date_creation' => 'datetime',
        'date_mise_en_reparation' => 'datetime',
        'date_rendez_vous' => 'datetime',
        'date_retrait' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function savs()
    {
        return $this->hasMany(SAV::class, 'repairId');
    }
}
