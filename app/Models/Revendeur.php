<?php

namespace App\Models;

class Revendeur extends BaseModel
{
    protected $table = 'revendeurs';

    protected $fillable = [
        'id', 'client_id', 'points_fidelite', 'bonus_annuel_taux', 'annee_debut_fidelite', 'notes_internes',
    ];

    protected $casts = [
        'points_fidelite'   => 'integer',
        'bonus_annuel_taux' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
