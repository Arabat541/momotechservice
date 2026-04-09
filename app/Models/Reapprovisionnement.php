<?php

namespace App\Models;

class Reapprovisionnement extends BaseModel
{
    protected $table = 'Reapprovisionnement';
    public $timestamps = false;

    protected $fillable = [
        'id', 'stockId', 'shopId', 'quantite', 'prixAchatUnitaire',
        'ancienPrixAchat', 'nouveauPrixAchat', 'ancienneQuantite',
        'nouvelleQuantite', 'fournisseur', 'note', 'date',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prixAchatUnitaire' => 'float',
        'ancienPrixAchat' => 'float',
        'nouveauPrixAchat' => 'float',
        'ancienneQuantite' => 'integer',
        'nouvelleQuantite' => 'integer',
        'date' => 'datetime',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'stockId');
    }
}
