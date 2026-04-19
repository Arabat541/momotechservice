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

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $role = session('user_role', 'technicien');
        $user = User::find(session('user_id'));
        $today = Carbon::today();

        if ($role === 'patron') {
            $shops   = Shop::all();
            $shopIds = $shops->pluck('id')->toArray();
        } else {
            // Caissière / technicien : uniquement la boutique courante
            $currentShopId = session('current_shop_id');
            $shop          = $currentShopId ? Shop::find($currentShopId) : null;
            $shops         = $shop ? collect([$shop]) : collect();
            $shopIds       = $shop ? [$shop->id] : [];
        }

        // --- Stats globales (toutes boutiques) ---
        $totalRepairs = Repair::whereIn('shopId', $shopIds)->count();
        $repairsEnCours = Repair::whereIn('shopId', $shopIds)->where('statut_reparation', 'En cours')->count();
        $repairsEnAttente = Repair::whereIn('shopId', $shopIds)->where('statut_reparation', 'En attente')->count();
        $repairsTerminees = Repair::whereIn('shopId', $shopIds)->where('statut_reparation', 'Terminé')->count();
        $repairsAnnulees = Repair::whereIn('shopId', $shopIds)->where('statut_reparation', 'Annulé')->count();
        $repairsToday = Repair::whereIn('shopId', $shopIds)->whereDate('date_creation', $today)->count();
        $repairsMonth = Repair::whereIn('shopId', $shopIds)
            ->whereMonth('date_creation', $today->month)
            ->whereYear('date_creation', $today->year)->count();

        $caTotal = Repair::whereIn('shopId', $shopIds)->sum('total_reparation');
        $caMois = Repair::whereIn('shopId', $shopIds)
            ->whereMonth('date_creation', $today->month)
            ->whereYear('date_creation', $today->year)->sum('total_reparation');
        $totalPaye = Repair::whereIn('shopId', $shopIds)->sum('montant_paye');
        $totalRestant = Repair::whereIn('shopId', $shopIds)->sum('reste_a_payer');
        $nonSoldes = Repair::whereIn('shopId', $shopIds)->where('etat_paiement', 'Non soldé')->count();

        $ventesTotal = Sale::whereIn('shopId', $shopIds)->sum('total');
        $ventesMois = Sale::whereIn('shopId', $shopIds)
            ->whereMonth('date', $today->month)
            ->whereYear('date', $today->year)->sum('total');
        $ventesCount = Sale::whereIn('shopId', $shopIds)->count();

        $totalStocks = Stock::whereIn('shopId', $shopIds)->count();
        $stocksEpuises = Stock::whereIn('shopId', $shopIds)->where('quantite', 0)->count();
        $stocksFaibles = Stock::whereIn('shopId', $shopIds)->where('quantite', '>', 0)->where('quantite', '<=', 3)->count();
        $valeurStock = Stock::whereIn('shopId', $shopIds)->selectRaw('SUM(quantite * prixAchat) as valeur')->value('valeur') ?? 0;
        $savCount = SAV::whereIn('shopId', $shopIds)->count();

        // --- Dernières réparations (toutes boutiques) ---
        $dernieresReparations = Repair::whereIn('shopId', $shopIds)
            ->orderByDesc('date_creation')->limit(5)->get();

        $reparationsNonSoldees = Repair::whereIn('shopId', $shopIds)
            ->where('etat_paiement', 'Non soldé')->where('reste_a_payer', '>', 0)
            ->orderByDesc('reste_a_payer')->limit(5)->get();

        // --- CA par mois (6 derniers mois) ---
        $caParMois = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $ca = Repair::whereIn('shopId', $shopIds)
                ->whereMonth('date_creation', $date->month)
                ->whereYear('date_creation', $date->year)
                ->sum('total_reparation');
            $caParMois[] = [
                'mois' => $date->translatedFormat('M Y'),
                'ca' => $ca,
            ];
        }

        // --- Stats PAR BOUTIQUE ---
        $shopStats = [];
        foreach ($shops as $shop) {
            $sid = $shop->id;
            $shopStats[] = [
                'nom' => $shop->nom,
                'reparations' => Repair::where('shopId', $sid)->count(),
                'enCours' => Repair::where('shopId', $sid)->where('statut_reparation', 'En cours')->count(),
                'terminees' => Repair::where('shopId', $sid)->where('statut_reparation', 'Terminé')->count(),
                'ca' => Repair::where('shopId', $sid)->sum('total_reparation'),
                'paye' => Repair::where('shopId', $sid)->sum('montant_paye'),
                'restant' => Repair::where('shopId', $sid)->sum('reste_a_payer'),
                'ventes' => Sale::where('shopId', $sid)->sum('total'),
                'stocks' => Stock::where('shopId', $sid)->count(),
                'valeurStock' => Stock::where('shopId', $sid)->selectRaw('SUM(quantite * prixAchat) as v')->value('v') ?? 0,
                'sav' => SAV::where('shopId', $sid)->count(),
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
}
