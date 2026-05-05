<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $shopId   = $request->attributes->get('shopId');
        $user     = $request->attributes->get('user');
        $periode  = $request->query('periode', 'mois'); // mois | trimestre | annee
        $shopIds  = $this->shopIds($user, $shopId);

        [$debut, $fin] = $this->periode($periode);

        // ── CA réparations ──────────────────────────────────────────────────
        $caReparations = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->whereBetween('date_creation', [$debut, $fin])
            ->sum('total_reparation');

        $caVentes = Sale::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->whereBetween('date', [$debut, $fin])
            ->sum('total');

        $encaisseReparations = Invoice::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->whereBetween('created_at', [$debut, $fin])
            ->sum('montant_paye');

        // ── Évolution CA mensuel (12 mois glissants) — 2 requêtes groupées ─
        $startOf12Months = Carbon::now()->subMonths(11)->startOfMonth();

        $repParMois = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('date_creation', '>=', $startOf12Months)
            ->selectRaw("DATE_FORMAT(date_creation, '%Y-%m') as mois, SUM(total_reparation) as ca")
            ->groupBy('mois')
            ->pluck('ca', 'mois');

        $venteParMois = Sale::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('date', '>=', $startOf12Months)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as mois, SUM(total) as ca")
            ->groupBy('mois')
            ->pluck('ca', 'mois');

        $caParMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key  = $date->format('Y-m');
            $rep  = (float) ($repParMois[$key] ?? 0);
            $ven  = (float) ($venteParMois[$key] ?? 0);
            $caParMois[] = [
                'mois'        => $key,
                'mois_label'  => $date->translatedFormat('M Y'),
                'reparations' => $rep,
                'ventes'      => $ven,
                'total'       => $rep + $ven,
            ];
        }

        // ── Top 5 pannes ────────────────────────────────────────────────────
        $topPannes = Repair::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->whereBetween('date_creation', [$debut, $fin])
            ->get()
            ->flatMap(fn($r) => collect($r->pannes_services)->pluck('description'))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn($count, $desc) => ['panne' => $desc, 'total' => $count])
            ->values();

        // ── Top 5 articles vendus ────────────────────────────────────────────
        $topArticles = Sale::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->whereBetween('date', [$debut, $fin])
            ->select('nom', DB::raw('SUM(quantite) as quantite'), DB::raw('SUM(total) as total'))
            ->groupBy('nom')
            ->orderByDesc('quantite')
            ->limit(5)
            ->get();

        // ── Taux réparations soldées ─────────────────────────────────────────
        $totalRep    = Repair::withoutGlobalScopes()->whereIn('shopId', $shopIds)->whereBetween('date_creation', [$debut, $fin])->count();
        $soldeesRep  = Repair::withoutGlobalScopes()->whereIn('shopId', $shopIds)->whereBetween('date_creation', [$debut, $fin])->where('etat_paiement', 'Soldé')->count();
        $tauxSoldees = $totalRep > 0 ? round($soldeesRep / $totalRep * 100, 1) : 0;

        // ── Stocks en alerte ─────────────────────────────────────────────────
        $stocksAlerte = Stock::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('seuil_alerte', '>', 0)
            ->whereColumn('quantite', '<=', 'seuil_alerte')
            ->orderByRaw('quantite - seuil_alerte ASC')
            ->get();

        // ── Encours crédit revendeurs ────────────────────────────────────────
        $encoursCreditClients = Client::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('type', 'revendeur')
            ->where('solde_credit', '>', 0)
            ->orderByDesc('solde_credit')
            ->limit(10)
            ->get();

        $encoursCredit = Client::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('type', 'revendeur')
            ->where('solde_credit', '>', 0)
            ->sum('solde_credit');

        $totalCredit = Client::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('type', 'revendeur')
            ->count();

        // ── Charges fournisseurs à payer (groupées par fournisseur) ──────────
        $chargesDues = PurchaseInvoice::withoutGlobalScopes()
            ->with('supplier')
            ->whereIn('shopId', $shopIds)
            ->whereIn('statut', ['en_attente', 'partiellement_payee'])
            ->get()
            ->groupBy('supplier_id')
            ->map(function ($group) {
                return [
                    'fournisseur' => optional($group->first()->supplier)->nom ?? '—',
                    'nb_factures' => $group->count(),
                    'total'       => $group->sum('reste_a_payer'),
                ];
            })
            ->values();

        // ── Caisse : recap dernière session fermée ───────────────────────────
        $derniereCaisse = CashSession::withoutGlobalScopes()
            ->whereIn('shopId', $shopIds)
            ->where('statut', 'fermee')
            ->latest('date')
            ->first();

        return view('analytics.index', compact(
            'periode', 'debut', 'fin',
            'caReparations', 'caVentes', 'encaisseReparations',
            'caParMois', 'topPannes', 'topArticles',
            'totalRep', 'soldeesRep', 'tauxSoldees',
            'stocksAlerte', 'encoursCredit', 'encoursCreditClients', 'totalCredit',
            'chargesDues', 'derniereCaisse'
        ));
    }

    private function shopIds(mixed $user, ?string $shopId): array
    {
        if ($user->role === 'patron') {
            return Shop::pluck('id')->toArray();
        }
        return $shopId ? [$shopId] : [];
    }

    private function periode(string $periode): array
    {
        return match ($periode) {
            'trimestre' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'annee'     => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            // Default: last 3 months to avoid full-table scans on large datasets
            default     => [Carbon::now()->subMonths(3)->startOfDay(), Carbon::now()->endOfDay()],
        };
    }
}
