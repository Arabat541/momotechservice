<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\SAV;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role  = session('user_role', 'caissiere');
        $user  = User::find(session('user_id'));
        $today = Carbon::today();

        if ($role === 'patron') {
            $shops   = Shop::all();
            $shopIds = $shops->pluck('id')->toArray();
        } else {
            // Caissière / réparateur : uniquement la boutique courante
            $currentShopId = session('current_shop_id');
            $shop          = $currentShopId ? Shop::find($currentShopId) : null;
            $shops         = $shop ? collect([$shop]) : collect();
            $shopIds       = $shop ? [$shop->id] : [];
        }

        if (empty($shopIds)) {
            return view('dashboard.home', $this->emptyStats($role, $shops));
        }

        // --- Stats réparations en une seule requête groupée ---
        $repairStats = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(statut_reparation = 'En cours')   as en_cours,
                SUM(statut_reparation = 'En attente') as en_attente,
                SUM(statut_reparation = 'Terminé')    as terminees,
                SUM(statut_reparation = 'Annulé')     as annulees,
                SUM(DATE(date_creation) = ?)           as today,
                SUM(MONTH(date_creation) = ? AND YEAR(date_creation) = ?) as ce_mois,
                SUM(total_reparation)  as ca_total,
                SUM(CASE WHEN MONTH(date_creation) = ? AND YEAR(date_creation) = ? THEN total_reparation ELSE 0 END) as ca_mois,
                SUM(montant_paye)      as total_paye,
                SUM(reste_a_payer)     as total_restant,
                SUM(etat_paiement = 'Non soldé') as non_soldes
            ", [
                $today->toDateString(),
                $today->month, $today->year,
                $today->month, $today->year,
            ])
            ->first();

        $totalRepairs     = $repairStats->total       ?? 0;
        $repairsEnCours   = $repairStats->en_cours    ?? 0;
        $repairsEnAttente = $repairStats->en_attente  ?? 0;
        $repairsTerminees = $repairStats->terminees   ?? 0;
        $repairsAnnulees  = $repairStats->annulees    ?? 0;
        $repairsToday     = $repairStats->today       ?? 0;
        $repairsMonth     = $repairStats->ce_mois     ?? 0;
        $caTotal          = $repairStats->ca_total    ?? 0;
        $caMois           = $repairStats->ca_mois     ?? 0;
        $totalPaye        = $repairStats->total_paye  ?? 0;
        $totalRestant     = $repairStats->total_restant ?? 0;
        $nonSoldes        = $repairStats->non_soldes  ?? 0;

        // --- Stats ventes en une requête ---
        $saleStats = Sale::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->selectRaw("
                COUNT(*) as count,
                SUM(total) as total,
                SUM(CASE WHEN MONTH(date) = ? AND YEAR(date) = ? THEN total ELSE 0 END) as mois
            ", [$today->month, $today->year])
            ->first();

        $ventesTotal = $saleStats->total ?? 0;
        $ventesMois  = $saleStats->mois  ?? 0;
        $ventesCount = $saleStats->count ?? 0;

        // --- Stats stock en une requête ---
        $stockStats = Stock::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->selectRaw("
                COUNT(*) as total,
                SUM(quantite = 0) as epuises,
                SUM(quantite > 0 AND quantite <= 3) as faibles,
                SUM(quantite * prixAchat) as valeur
            ")
            ->first();

        $totalStocks  = $stockStats->total   ?? 0;
        $stocksEpuises = $stockStats->epuises ?? 0;
        $stocksFaibles = $stockStats->faibles ?? 0;
        $valeurStock   = $stockStats->valeur  ?? 0;

        $savCount = SAV::withoutGlobalScopes()->whereIn('shopId', $shopIds)->count();

        // --- Dernières réparations ---
        $dernieresReparations = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->orderByDesc('date_creation')->limit(5)->get();

        $reparationsNonSoldees = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('etat_paiement', 'Non soldé')->where('reste_a_payer', '>', 0)
            ->orderByDesc('reste_a_payer')->limit(5)->get();

        // --- CA par mois (6 mois) en une requête ---
        $caRaw = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('date_creation', '>=', Carbon::now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(date_creation, '%Y-%m') as mois, SUM(total_reparation) as ca")
            ->groupBy('mois')
            ->pluck('ca', 'mois');

        $caParMois = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key  = $date->format('Y-m');
            $caParMois[] = [
                'mois' => $date->translatedFormat('M Y'),
                'ca'   => $caRaw[$key] ?? 0,
            ];
        }

        // --- Stats par boutique (groupées) ---
        $repairByShop = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->selectRaw("
                shopId,
                COUNT(*) as reparations,
                SUM(statut_reparation = 'En cours') as en_cours,
                SUM(statut_reparation = 'Terminé')  as terminees,
                SUM(total_reparation) as ca,
                SUM(montant_paye)     as paye,
                SUM(reste_a_payer)    as restant
            ")
            ->groupBy('shopId')
            ->get()->keyBy('shopId');

        $saleByShop = Sale::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->selectRaw("shopId, SUM(total) as total")
            ->groupBy('shopId')
            ->get()->keyBy('shopId');

        $stockByShop = Stock::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->selectRaw("shopId, COUNT(*) as total, SUM(quantite * prixAchat) as valeur")
            ->groupBy('shopId')
            ->get()->keyBy('shopId');

        $savByShop = SAV::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->selectRaw("shopId, COUNT(*) as total")
            ->groupBy('shopId')
            ->get()->keyBy('shopId');

        $shopStats = [];
        foreach ($shops as $shop) {
            $sid = $shop->id;
            $r   = $repairByShop[$sid] ?? null;
            $shopStats[] = [
                'nom'        => $shop->nom,
                'reparations'=> $r->reparations ?? 0,
                'enCours'    => $r->en_cours    ?? 0,
                'terminees'  => $r->terminees   ?? 0,
                'ca'         => $r->ca          ?? 0,
                'paye'       => $r->paye        ?? 0,
                'restant'    => $r->restant      ?? 0,
                'ventes'     => $saleByShop[$sid]->total  ?? 0,
                'stocks'     => $stockByShop[$sid]->total ?? 0,
                'valeurStock'=> $stockByShop[$sid]->valeur ?? 0,
                'sav'        => $savByShop[$sid]->total   ?? 0,
            ];
        }

        return view('dashboard.home', compact(
            'role', 'shops', 'shopStats',
            'totalRepairs', 'repairsEnCours', 'repairsEnAttente', 'repairsTerminees', 'repairsAnnulees',
            'repairsToday', 'repairsMonth',
            'caTotal', 'caMois', 'totalPaye', 'totalRestant', 'nonSoldes',
            'ventesTotal', 'ventesMois', 'ventesCount',
            'totalStocks', 'stocksEpuises', 'stocksFaibles', 'valeurStock',
            'savCount',
            'dernieresReparations', 'reparationsNonSoldees',
            'caParMois'
        ));
    }

    private function emptyStats(string $role, $shops): array
    {
        return compact('role', 'shops') + [
            'shopStats' => [], 'totalRepairs' => 0, 'repairsEnCours' => 0,
            'repairsEnAttente' => 0, 'repairsTerminees' => 0, 'repairsAnnulees' => 0,
            'repairsToday' => 0, 'repairsMonth' => 0, 'caTotal' => 0, 'caMois' => 0,
            'totalPaye' => 0, 'totalRestant' => 0, 'nonSoldes' => 0,
            'ventesTotal' => 0, 'ventesMois' => 0, 'ventesCount' => 0,
            'totalStocks' => 0, 'stocksEpuises' => 0, 'stocksFaibles' => 0,
            'valeurStock' => 0, 'savCount' => 0,
            'dernieresReparations' => collect(), 'reparationsNonSoldees' => collect(),
            'caParMois' => [],
        ];
    }
}
