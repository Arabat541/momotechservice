<?php

namespace App\Http\Controllers;

use App\Jobs\EnvoyerSmsJob;
use App\Models\Repair;
use Illuminate\Http\Request;

class RelanceController extends Controller
{

    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');

        $repairs = Repair::query()
            ->where('statut_reparation', 'Terminé')
            ->whereNull('date_retrait')
            ->orderBy('date_terminee')
            ->paginate(25);

        return view('relances.index', compact('repairs'));
    }

    public function relancer(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::findOrFail($id);

        if ($repair->date_retrait || $repair->statut_reparation !== 'Terminé') {
            return back()->with('error', 'Cette réparation n\'est pas en attente de récupération.');
        }

        $telephone = $repair->client?->telephone ?? $repair->client_telephone;

        if (!$telephone) {
            return back()->with('error', 'Aucun numéro de téléphone pour ce client.');
        }

        EnvoyerSmsJob::dispatch('relance', $telephone, $repair->numeroReparation, $repair->shopId, $repair->relance_count);

        $repair->update([
            'relance_count'    => $repair->relance_count + 1,
            'derniere_relance' => now(),
        ]);

        return back()->with('success', "Relance programmée pour {$telephone}.");
    }
}
