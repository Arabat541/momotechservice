<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ShopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->attributes->get('user');

        // Patron : si une boutique est sélectionnée en session (ex: navigation sur une boutique
        // spécifique), l'utiliser pour les opérations d'écriture. Sinon null = toutes boutiques.
        if ($user->role === 'patron') {
            $shopId = $request->session()->get('current_shop_id');
            $request->attributes->set('shopId', $shopId ?: null);
            return $next($request);
        }

        // Caissière : réutiliser la boutique déjà mise en session pour éviter
        // une requête DB sur chaque requête HTTP.
        $cachedShopId = $request->session()->get('current_shop_id');
        if ($cachedShopId) {
            $request->attributes->set('shopId', $cachedShopId);
            return $next($request);
        }

        $shop = $user->shops()->first();

        if (!$shop) {
            $request->session()->flush();
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Aucune boutique assignée'], 400);
            }
            return redirect()->route('login')
                ->with('error', 'Aucune boutique assignée à votre compte. Contactez le patron.');
        }

        $request->attributes->set('shopId', $shop->id);
        $request->session()->put('current_shop_id', $shop->id);

        return $next($request);
    }
}
