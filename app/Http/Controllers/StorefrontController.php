<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\Stock;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    public function index(Request $request)
    {
        $search    = $request->query('search');
        $categorie = $request->query('categorie');

        // Boutiques avec leurs infos settings
        $shopsById = Shop::all()->keyBy('id')->map(function ($shop) {
            $settings = Settings::where('shopId', $shop->id)->first();
            $info = $settings?->companyInfo ?? [];
            return [
                'id'        => $shop->id,
                'nom'       => $info['nom'] ?? $shop->nom,
                'adresse'   => $info['adresse'] ?? $shop->adresse ?? '',
                'telephone' => $info['telephone'] ?? $shop->telephone ?? '',
                'email'     => $info['email'] ?? '',
                'whatsapp'  => $info['whatsapp'] ?? '',
                'facebook'  => $info['facebook'] ?? '',
                'instagram' => $info['instagram'] ?? '',
                'slogan'    => $info['slogan'] ?? '',
            ];
        });

        // Contact principal = première boutique avec des infos
        $contactPrincipal = $shopsById->first(fn($s) => !empty($s['telephone']) || !empty($s['email'])) ?? $shopsById->first();

        // Stocks disponibles
        $query = Stock::withoutGlobalScopes()->where('quantite', '>', 0);

        if ($search) {
            $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where('nom', 'like', "%{$esc}%");
        }
        if ($categorie) {
            $query->where('categorie', $categorie);
        }

        $stocks = $query->orderBy('nom')->get();

        // Regrouper par nom (insensible à la casse)
        $produits = $stocks
            ->groupBy(fn($s) => mb_strtolower(trim($s->nom)))
            ->map(function ($groupe) use ($shopsById) {
                $prix = $groupe->pluck('prixVente')->filter()->unique()->sort()->values();
                return [
                    'nom'         => $groupe->first()->nom,
                    'categorie'   => $groupe->first()->categorie ?? null,
                    'prix_min'    => $prix->first() ?? 0,
                    'prix_max'    => $prix->last() ?? 0,
                    'stock_total' => $groupe->sum('quantite'),
                    'boutiques'   => $groupe->map(fn($s) => [
                        'shop'     => $shopsById[$s->shopId] ?? ['nom' => '—', 'adresse' => '', 'telephone' => ''],
                        'quantite' => $s->quantite,
                        'prix'     => $s->prixVente,
                    ])->values(),
                ];
            })
            ->sortBy('nom')
            ->values();

        // Catégories avec compteurs (tous stocks, sans filtre)
        $categoriesCount = Stock::withoutGlobalScopes()
            ->where('quantite', '>', 0)
            ->whereNotNull('categorie')
            ->get()
            ->groupBy(fn($s) => mb_strtolower(trim($s->nom)))
            ->map(fn($g) => $g->first()->categorie)
            ->groupBy(fn($cat) => $cat)
            ->map->count();

        $totalProduits = Stock::withoutGlobalScopes()
            ->where('quantite', '>', 0)
            ->get()
            ->groupBy(fn($s) => mb_strtolower(trim($s->nom)))
            ->count();

        // Derniers arrivages (8 produits récents, sans filtre catégorie/search)
        $derniersArrivages = Stock::withoutGlobalScopes()
            ->where('quantite', '>', 0)
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn($s) => mb_strtolower(trim($s->nom)))
            ->map(function ($groupe) use ($shopsById) {
                $prix = $groupe->pluck('prixVente')->filter()->unique()->sort()->values();
                return [
                    'nom'         => $groupe->first()->nom,
                    'categorie'   => $groupe->first()->categorie ?? null,
                    'prix_min'    => $prix->first() ?? 0,
                    'prix_max'    => $prix->last() ?? 0,
                    'stock_total' => $groupe->sum('quantite'),
                    'boutiques'   => $groupe->map(fn($s) => [
                        'shop'     => $shopsById[$s->shopId] ?? ['nom' => '—', 'adresse' => '', 'telephone' => ''],
                        'quantite' => $s->quantite,
                        'prix'     => $s->prixVente,
                    ])->values(),
                ];
            })
            ->take(8)
            ->values();

        $shops = $shopsById->values();

        return view('storefront.index', compact(
            'produits', 'shops', 'categoriesCount', 'totalProduits',
            'derniersArrivages', 'search', 'categorie', 'contactPrincipal'
        ));
    }

    public function trackRepair()
    {
        return view('storefront.track');
    }

    public function trackRepairSearch(Request $request)
    {
        $request->validate([
            'numero' => 'required|string|max:30|regex:/^[A-Za-z0-9\-]+$/',
        ]);

        $numero = $request->input('numero');
        $repair = Repair::withoutGlobalScopes()->where('numeroReparation', $numero)->first();

        if (!$repair) {
            return view('storefront.track', ['error' => 'Aucune réparation trouvée avec ce numéro.']);
        }

        return view('storefront.track', [
            'repair' => [
                'numeroReparation' => $repair->numeroReparation,
                'appareil'         => $repair->appareil_marque_modele,
                'statut'           => $repair->statut_reparation,
                'date_creation'    => $repair->date_creation?->format('d/m/Y'),
                'date_retrait'     => $repair->date_retrait?->format('d/m/Y'),
            ],
            'numero' => $numero,
        ]);
    }
}
