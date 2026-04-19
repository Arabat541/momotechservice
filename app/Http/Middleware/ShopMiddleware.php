<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;

class ShopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->attributes->get('user');
        $shopId = $request->session()->get('current_shop_id')
            ?? $request->header('X-Shop-Id')
            ?? $request->input('shop_id');

        // Patron : accès à toutes les boutiques
        if ($user->role === 'patron') {
            // Auto-sélection de la première boutique si aucune en session
            if (!$shopId) {
                $first = Shop::first();
                if ($first) {
                    $shopId = $first->id;
                    $request->session()->put('current_shop_id', $shopId);
                } else {
                    // Aucune boutique créée — laisser passer pour qu'il puisse en créer une
                    $request->attributes->set('shopId', null);
                    return $next($request);
                }
            }

            $shop = Shop::find($shopId);
            if (!$shop) {
                // Boutique supprimée — choisir la première disponible
                $first = Shop::first();
                if ($first) {
                    $shopId = $first->id;
                    $request->session()->put('current_shop_id', $shopId);
                } else {
                    $request->attributes->set('shopId', null);
                    return $next($request);
                }
            }

            $request->attributes->set('shopId', $shopId);
            return $next($request);
        }

        // Employé sans boutique en session → déconnexion propre
        if (!$shopId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Shop ID requis'], 400);
            }
            $request->session()->flush();
            return redirect()->route('login')->with('error', 'Aucune boutique assignée. Contactez le patron.');
        }

        // Vérifier que l'employé appartient à cette boutique
        $hasAccess = $user->shops()->where('shops.id', $shopId)->exists();
        if (!$hasAccess) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Accès refusé à cette boutique'], 403);
            }
            abort(403, 'Accès refusé à cette boutique');
        }

        $request->attributes->set('shopId', $shopId);
        return $next($request);
    }
}
