<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Services\SmsService;
use Illuminate\Http\Request;

class RelanceController extends Controller
{
    public function __construct(private SmsService $smsService) {}

    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');

        $repairs = Repair::where('shopId', $shopId)
            ->where('statut_reparation', 'Terminé')
            ->whereNull('date_retrait')
            ->orderBy('date_terminee')
            ->paginate(25);

        return view('relances.index', compact('repairs'));
    }

    public function relancer(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        if ($repair->date_retrait || $repair->statut_reparation !== 'Terminé') {
            return back()->with('error', 'Cette réparation n\'est pas en attente de récupération.');
        }

        $telephone = $repair->client?->telephone ?? $repair->client_telephone;

        if (!$telephone) {
            return back()->with('error', 'Aucun numéro de téléphone pour ce client.');
        }

        $sent = $this->smsService->envoyerRelance(
            $telephone,
            $repair->numeroReparation,
            $repair->relance_count,
            $repair->shopId
        );

        if ($sent) {
            $repair->update([
                'relance_count'    => $repair->relance_count + 1,
                'derniere_relance' => now(),
            ]);
            return back()->with('success', "Relance envoyée à {$telephone}.");
        }

        return back()->with('error', 'Envoi SMS échoué. Vérifiez la configuration SMS dans les paramètres.');
    }
}
