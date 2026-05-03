<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\PurchaseInvoice;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\Stock;
use App\Services\RepairService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    //  VENTES
    // ─────────────────────────────────────────────────────────────

    public function ventes(Request $request)
    {
        $data           = $this->buildVentesData($request);
        $data['shops']  = Shop::orderBy('nom')->get();
        return view('reports.ventes', $data);
    }

    public function ventesPdf(Request $request)
    {
        $data                  = $this->buildVentesData($request);
        $data['companyInfo']   = $this->getCompanyInfo($request->input('boutique_id'));
        $data['logoBase64']    = $this->getLogoBase64();
        $pdf = Pdf::loadView('reports.ventes-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('rapport-ventes-' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildVentesData(Request $request): array
    {
        $debut      = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin        = $request->input('fin', now()->toDateString());
        $boutiqueId = $request->input('boutique_id');

        $ventes = Sale::query()
            ->whereBetween('date', [$debut . ' 00:00:00', $fin . ' 23:59:59'])
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->with('shop')
            ->orderByDesc('date')
            ->get();

        $totalCA       = $ventes->sum('total');
        $totalEncaisse = $ventes->sum('montant_paye');
        $totalCredit   = $ventes->sum('reste_credit');
        $nbVentes      = $ventes->count();

        $top10 = $ventes
            ->groupBy('nom')
            ->map(fn($g) => [
                'nom'      => $g->first()->nom,
                'quantite' => $g->sum('quantite'),
                'ca'       => $g->sum('total'),
            ])
            ->sortByDesc('ca')
            ->take(10)
            ->values();

        return compact('debut', 'fin', 'boutiqueId', 'ventes', 'totalCA', 'totalEncaisse', 'totalCredit', 'nbVentes', 'top10');
    }

    // ─────────────────────────────────────────────────────────────
    //  RÉPARATIONS
    // ─────────────────────────────────────────────────────────────

    public function reparations(Request $request)
    {
        $data            = $this->buildReparationsData($request);
        $data['shops']   = Shop::orderBy('nom')->get();
        $data['statuts'] = array_keys(RepairService::STATUTS);
        return view('reports.reparations', $data);
    }

    public function reparationsPdf(Request $request)
    {
        $data                = $this->buildReparationsData($request);
        $data['companyInfo'] = $this->getCompanyInfo($request->input('boutique_id'));
        $data['logoBase64']  = $this->getLogoBase64();
        $pdf = Pdf::loadView('reports.reparations-pdf', $data)->setPaper('a4', 'landscape');
        return $pdf->stream('rapport-reparations-' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildReparationsData(Request $request): array
    {
        $debut      = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin        = $request->input('fin', now()->toDateString());
        $statut     = $request->input('statut');
        $boutiqueId = $request->input('boutique_id');

        $repairs = Repair::query()
            ->whereBetween('date_creation', [$debut . ' 00:00:00', $fin . ' 23:59:59'])
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->when($statut, fn($q) => $q->where('statut_reparation', $statut))
            ->with('shop')
            ->orderByDesc('date_creation')
            ->get();

        $total       = $repairs->count();
        $clotures    = $repairs->whereIn('statut_reparation', ['Livré', 'Irréparable'])->count();
        $tauxCloture = $total > 0 ? round($clotures / $total * 100, 1) : 0;

        $livrees    = $repairs->filter(fn($r) => $r->date_retrait && $r->date_creation);
        $delaiMoyen = $livrees->count() > 0
            ? round($livrees->avg(fn($r) => Carbon::parse($r->date_creation)->diffInDays(Carbon::parse($r->date_retrait))), 1)
            : 0;

        $repartition = $repairs
            ->groupBy('statut_reparation')
            ->map(fn($g) => $g->count())
            ->sortDesc();

        $topPannes = [];
        foreach ($repairs as $repair) {
            foreach ($repair->pannes_services ?? [] as $panne) {
                $desc = trim($panne['description'] ?? '');
                if ($desc) {
                    $topPannes[$desc] = ($topPannes[$desc] ?? 0) + 1;
                }
            }
        }
        arsort($topPannes);
        $topPannes = array_slice($topPannes, 0, 10, true);

        $totalCA      = $repairs->sum('total_reparation');
        $totalPaye    = $repairs->sum('montant_paye');
        $totalRestant = $repairs->sum('reste_a_payer');

        return compact(
            'debut', 'fin', 'statut', 'boutiqueId', 'repairs',
            'total', 'tauxCloture', 'delaiMoyen', 'repartition', 'topPannes',
            'totalCA', 'totalPaye', 'totalRestant'
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  STOCK
    // ─────────────────────────────────────────────────────────────

    public function stock(Request $request)
    {
        $data               = $this->buildStockData($request);
        $data['shops']      = Shop::orderBy('nom')->get();
        $data['categories'] = Stock::query()
            ->select('categorie')->distinct()->pluck('categorie')
            ->filter()->sort()->values();
        return view('reports.stock', $data);
    }

    public function stockPdf(Request $request)
    {
        $data                = $this->buildStockData($request);
        $data['companyInfo'] = $this->getCompanyInfo($request->input('boutique_id'));
        $data['logoBase64']  = $this->getLogoBase64();
        $pdf = Pdf::loadView('reports.stock-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('rapport-stock-' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildStockData(Request $request): array
    {
        $boutiqueId = $request->input('boutique_id');
        $categorie  = $request->input('categorie');

        $stocks = Stock::query()
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->when($categorie,  fn($q) => $q->where('categorie', $categorie))
            ->with('shop')
            ->orderBy('nom')
            ->get();

        $valorisation = $stocks->sum(fn($s) => $s->quantite * $s->prixAchat);
        $sousSeuil    = $stocks->filter(fn($s) => $s->seuil_alerte > 0 && $s->quantite <= $s->seuil_alerte);
        $epuises      = $stocks->where('quantite', 0);
        $nbTotal      = $stocks->count();

        return compact('boutiqueId', 'categorie', 'stocks', 'valorisation', 'sousSeuil', 'epuises', 'nbTotal');
    }

    // ─────────────────────────────────────────────────────────────
    //  FINANCIER
    // ─────────────────────────────────────────────────────────────

    public function financier(Request $request)
    {
        $data           = $this->buildFinancierData($request);
        $data['shops']  = Shop::orderBy('nom')->get();
        return view('reports.financier', $data);
    }

    public function financierPdf(Request $request)
    {
        $data                = $this->buildFinancierData($request);
        $data['companyInfo'] = $this->getCompanyInfo($request->input('boutique_id'));
        $data['logoBase64']  = $this->getLogoBase64();
        $pdf = Pdf::loadView('reports.financier-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('rapport-financier-' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildFinancierData(Request $request): array
    {
        $debut      = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin        = $request->input('fin', now()->toDateString());
        $boutiqueId = $request->input('boutique_id');

        $recettesReparations = (float) Repair::query()
            ->whereBetween('date_creation', [$debut . ' 00:00:00', $fin . ' 23:59:59'])
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->sum('montant_paye');

        $recettesVentes = (float) Sale::query()
            ->whereBetween('date', [$debut . ' 00:00:00', $fin . ' 23:59:59'])
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->sum('montant_paye');

        $totalRecettes = $recettesReparations + $recettesVentes;

        $depensesTotal = (float) PurchaseInvoice::query()
            ->whereBetween('date_facture', [$debut, $fin])
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->sum('montant_paye');

        $chargesDues = PurchaseInvoice::query()
            ->whereBetween('date_facture', [$debut, $fin])
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->where('statut', '!=', 'soldee')
            ->with('supplier')
            ->orderByDesc('date_echeance')
            ->get();

        $beneficeBrut = $totalRecettes - $depensesTotal;

        $sessions = CashSession::query()
            ->whereBetween('date', [$debut, $fin])
            ->when($boutiqueId, fn($q) => $q->where('shopId', $boutiqueId))
            ->with('shop')
            ->orderByDesc('date')
            ->get();

        $totalOuverture = $sessions->sum('montant_ouverture');
        $totalFermeture = $sessions->whereNotNull('montant_fermeture_reel')->sum('montant_fermeture_reel');
        $totalEcart     = $sessions->whereNotNull('ecart')->sum('ecart');

        return compact(
            'debut', 'fin', 'boutiqueId',
            'recettesReparations', 'recettesVentes', 'totalRecettes',
            'depensesTotal', 'chargesDues', 'beneficeBrut',
            'sessions', 'totalOuverture', 'totalFermeture', 'totalEcart'
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────

    private function getCompanyInfo(?string $shopId): array
    {
        $settings = $shopId
            ? Settings::withoutGlobalScopes()->where('shopId', $shopId)->first()
            : Settings::withoutGlobalScopes()->first();

        $default = ['nom' => 'MOMO TECH SERVICE', 'adresse' => '', 'telephone' => '', 'email' => ''];
        return array_merge($default, $settings?->companyInfo ?? []);
    }

    private function getLogoBase64(): ?string
    {
        foreach (['logo-receipt.png', 'logo-app.png'] as $file) {
            $path = public_path('images/' . $file);
            if (file_exists($path)) {
                return base64_encode(file_get_contents($path));
            }
        }
        return null;
    }
}
