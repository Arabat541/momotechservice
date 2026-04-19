<?php

namespace App\Models;

use App\Models\Scopes\ShopScope;

class Repair extends BaseModel
{
    protected static function booted(): void
    {
        static::addGlobalScope(new ShopScope());
    }

    protected $table = 'repairs';
    public $timestamps = false;

    protected $fillable = [
        'id', 'shopId', 'numeroReparation', 'type_reparation',
        'client_nom', 'client_telephone', 'appareil_marque_modele',
        'pannes_services', 'pieces_rechange_utilisees',
        'total_reparation', 'montant_paye', 'reste_a_payer',
        'statut_reparation', 'date_creation', 'date_mise_en_reparation',
        'date_rendez_vous', 'date_retrait', 'etat_paiement', 'userId',
        'client_id', 'cash_session_id', 'notes_technicien', 'assigned_to',
        'date_terminee', 'relance_count', 'derniere_relance',
        'mis_en_vente', 'date_limite_recuperation',
    ];

    protected $casts = [
        'client_nom'               => 'encrypted',
        'client_telephone'         => 'encrypted',
        'pannes_services'          => 'array',
        'pieces_rechange_utilisees'=> 'array',
        'total_reparation'         => 'float',
        'montant_paye'             => 'float',
        'reste_a_payer'            => 'float',
        'date_creation'            => 'datetime',
        'date_mise_en_reparation'  => 'datetime',
        'date_rendez_vous'         => 'datetime',
        'date_retrait'             => 'datetime',
        'date_terminee'             => 'datetime',
        'derniere_relance'          => 'datetime',
        'date_limite_recuperation'  => 'date',
        'mis_en_vente'              => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shopId');
    }

    public function savs()
    {
        return $this->hasMany(SAV::class, 'repairId');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'repair_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function photos()
    {
        return $this->hasMany(RepairPhoto::class, 'repair_id');
    }
}
