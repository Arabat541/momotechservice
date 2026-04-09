<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Settings;
use App\Models\Repair;

class StorefrontController extends Controller
{
    public function index()
    {
        $shops = Shop::all()->map(function ($shop) {
            $settings = Settings::where('shopId', $shop->id)->first();
            return [
                'id' => $shop->id,
                'nom' => $shop->nom,
                'adresse' => $shop->adresse,
                'telephone' => $shop->telephone,
                'companyInfo' => $settings?->companyInfo,
            ];
        });

        return view('storefront.index', compact('shops'));
    }

    public function trackRepair()
    {
        return view('storefront.track');
    }

    public function trackRepairSearch(\Illuminate\Http\Request $request)
    {
        $numero = $request->input('numero');
        $repair = Repair::where('numeroReparation', $numero)->first();

        if (!$repair) {
            return view('storefront.track', ['error' => 'Aucune réparation trouvée avec ce numéro.']);
        }

        $result = [
            'numeroReparation' => $repair->numeroReparation,
            'appareil' => $repair->appareil_marque_modele,
            'statut' => $repair->statut_reparation,
            'date_creation' => $repair->date_creation?->format('d/m/Y'),
            'date_retrait' => $repair->date_retrait?->format('d/m/Y'),
        ];

        return view('storefront.track', ['repair' => $result, 'numero' => $numero]);
    }
}
