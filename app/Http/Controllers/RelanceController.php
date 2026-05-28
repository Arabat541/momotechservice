<?php

namespace App\Http\Controllers;

use App\Jobs\EnvoyerSmsJob;
use App\Models\Repair;
use App\Traits\PdfHelperTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RelanceController extends Controller
{
    use PdfHelperTrait;


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

    public function exportPdf(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $repairs = Repair::when($shopId, fn($q) => $q->where('shopId', $shopId))
            ->where('statut_reparation', 'Terminé')
            ->whereNull('date_retrait')
            ->orderBy('date_terminee')
            ->paginate(500);

        $companyInfo = $this->getCompanyInfo($shopId);
        $logoBase64  = $this->getLogoBase64();

        return Pdf::loadView('exports.relances-pdf', compact('repairs', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'landscape')
            ->download('relances-' . now()->format('Y-m-d') . '.pdf');
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
