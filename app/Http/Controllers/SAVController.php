<?php

namespace App\Http\Controllers;

use App\Models\SAV;
use App\Models\Repair;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SAVController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $savs = SAV::where('shopId', $shopId)
            ->orderBy('date_creation', 'desc')
            ->paginate(20);

        return view('dashboard.sav', compact('savs'));
    }

    public function store(Request $request)
    {
        if (!$request->attributes->get('shopId')) {
            return back()->with('error', 'Aucune boutique sélectionnée.');
        }

        $request->validate([
            'client_nom' => 'required|string|max:150',
            'client_telephone' => ['required', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'appareil_marque_modele' => 'required|string|max:200',
            'description_probleme' => 'required|string|max:2000',
            'statut' => 'nullable|in:En attente,En cours,Résolu,Refusé',
            'decision' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'numeroReparationOrigine' => 'nullable|string|max:30|regex:/^[A-Za-z0-9\-]*$/',
        ]);

        $shopId = $request->attributes->get('shopId');
        $user = $request->attributes->get('user');

        $data = [
            'shopId' => $shopId,
            'numeroSAV' => 'SAV-' . strtoupper(Str::random(8)),
            'client_nom' => $request->client_nom,
            'client_telephone' => $request->client_telephone,
            'appareil_marque_modele' => $request->appareil_marque_modele,
            'description_probleme' => $request->description_probleme,
            'statut' => $request->input('statut', 'En attente'),
            'decision' => $request->input('decision', ''),
            'notes' => $request->input('notes', ''),
            'sous_garantie' => false,
            'userId' => $user->id,
            'date_creation' => now(),
        ];

        // Link to repair if numero provided
        $numero = $request->input('numeroReparationOrigine');
        if ($numero) {
            $repair = Repair::where('numeroReparation', $numero)->where('shopId', $shopId)->first();
            if ($repair) {
                $data['repairId'] = $repair->id;
                $data['numeroReparationOrigine'] = $numero;
                $data['client_nom'] = $repair->client_nom;
                $data['client_telephone'] = $repair->client_telephone;
                $data['appareil_marque_modele'] = $repair->appareil_marque_modele;

                // Check warranty
                if ($repair->date_retrait) {
                    $settings = Settings::where('shopId', $shopId)->first();
                    $warrantyDays = intval($settings?->warranty['duree'] ?? 7);
                    $warrantyEnd = $repair->date_retrait->copy()->addDays($warrantyDays);
                    $data['sous_garantie'] = now()->lte($warrantyEnd);
                    $data['date_fin_garantie'] = $warrantyEnd;
                }
            }
        }

        SAV::create($data);

        return back()->with('success', 'Dossier SAV créé.');
    }

    public function update(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $sav = SAV::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $request->validate([
            'statut' => 'sometimes|in:En attente,En cours,Résolu,Refusé',
            'decision' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'description_probleme' => 'sometimes|string|max:2000',
        ]);

        $data = $request->only([
            'statut', 'decision', 'notes', 'description_probleme',
        ]);

        if (in_array($request->input('statut'), ['Résolu', 'Refusé']) && !$sav->date_resolution) {
            $data['date_resolution'] = now();
        }

        $sav->update($data);

        return back()->with('success', 'Dossier SAV mis à jour.');
    }

    public function destroy(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $sav = SAV::where('id', $id)->where('shopId', $shopId)->firstOrFail();
        $sav->delete();

        return back()->with('success', 'Dossier SAV supprimé.');
    }

    public function lookupRepair(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $numero = $request->input('numero');

        if (!$numero) {
            return response()->json(['found' => false]);
        }

        $repair = Repair::where('numeroReparation', $numero)
            ->where('shopId', $shopId)
            ->first();

        if (!$repair) {
            return response()->json(['found' => false]);
        }

        $sousGarantie = false;
        $dateFin = null;
        if ($repair->date_retrait) {
            $settings = Settings::where('shopId', $shopId)->first();
            $warrantyDays = intval($settings?->warranty['duree'] ?? 7);
            $warrantyEnd = $repair->date_retrait->copy()->addDays($warrantyDays);
            $sousGarantie = now()->lte($warrantyEnd);
            $dateFin = $warrantyEnd->format('d/m/Y');
        }

        return response()->json([
            'found' => true,
            'client_nom' => $repair->client_nom,
            'client_telephone' => $repair->client_telephone,
            'appareil_marque_modele' => $repair->appareil_marque_modele,
            'sous_garantie' => $sousGarantie,
            'date_fin_garantie' => $dateFin,
        ]);
    }
}
