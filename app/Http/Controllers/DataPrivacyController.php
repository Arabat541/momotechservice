<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\SAV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataPrivacyController extends Controller
{
    /**
     * Anonymise toutes les données personnelles d'un client
     * identifié par son numéro de téléphone dans le shop courant.
     * Réservé aux patrons — conforme RGPD droit à l'effacement.
     *
     * Note : le filtre se fait en PHP (pas en SQL) car client_telephone est chiffré en base.
     */
    public function anonymize(Request $request)
    {
        $request->validate([
            'telephone' => ['required', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
        ]);

        $shopId    = $request->attributes->get('shopId');
        $telephone = $request->input('telephone');
        $anonyme   = 'CLIENT ANONYMISÉ';
        $telAnonyme = '00000000';
        $count     = 0;

        DB::transaction(function () use ($shopId, $telephone, $anonyme, $telAnonyme, &$count) {
            // Eloquent déchiffre automatiquement — on filtre en PHP après déchiffrement
            Repair::withoutGlobalScopes()->where('shopId', $shopId)->each(function (Repair $repair) use ($telephone, $anonyme, $telAnonyme, &$count) {
                if ($repair->client_telephone === $telephone) {
                    $repair->update(['client_nom' => $anonyme, 'client_telephone' => $telAnonyme]);
                    $count++;
                }
            });

            SAV::withoutGlobalScopes()->where('shopId', $shopId)->each(function (SAV $sav) use ($telephone, $anonyme, $telAnonyme, &$count) {
                if ($sav->client_telephone === $telephone) {
                    $sav->update(['client_nom' => $anonyme, 'client_telephone' => $telAnonyme]);
                    $count++;
                }
            });
        });

        return back()->with('success', "Données anonymisées pour ce numéro ({$count} dossier(s) modifié(s)).");
    }
}
