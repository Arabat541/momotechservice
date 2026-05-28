<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CreditTransaction;
use App\Services\RevendeurService;
use App\Traits\PdfHelperTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CreditController extends Controller
{
    use PdfHelperTrait;

    public function __construct(private RevendeurService $revendeurService) {}
    public function index(Request $request)
    {
        $clientId = $request->query('client_id');
        $query    = CreditTransaction::with('client')->orderByDesc('created_at');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $transactions = $query->paginate(30);
        $clients      = Client::where('type', 'revendeur')->orderBy('nom')->get();

        return view('credit.index', compact('transactions', 'clients'));
    }

    public function revendeurs(Request $request)
    {
        $filtre = $request->query('filtre', 'tous');

        $query = Client::where('type', 'revendeur')->orderBy('nom');

        if ($filtre === 'depassement') {
            $query->whereColumn('solde_credit', '>', 'credit_limite');
        } elseif ($filtre === 'solde_zero') {
            $query->where('solde_credit', 0);
        }

        $revendeurs = $query->with('revendeur')->get();

        return view('credit.revendeurs', compact('revendeurs', 'filtre'));
    }

    public function relevePdf(Request $request, Client $client)
    {
        $debut = $request->query('debut', Carbon::now()->startOfMonth()->toDateString());
        $fin   = $request->query('fin', Carbon::now()->toDateString());

        $transactions = $this->revendeurService->getRelevéCompte($client, $debut, $fin);

        $shopId      = $request->attributes->get('shopId');
        $companyInfo = $this->getCompanyInfo($shopId ?? $client->shopId);
        $logoBase64  = $this->getLogoBase64();

        $pdf = Pdf::loadView('credit.releve-pdf', compact(
            'client', 'transactions', 'debut', 'fin', 'companyInfo', 'logoBase64'
        ))->setPaper('a4', 'portrait');

        $filename = 'releve-' . \Illuminate\Support\Str::slug($client->nom) . '-' . $debut . '.pdf';

        return $pdf->download($filename);
    }

}
