<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ShopMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->attributes->get('user');

        // Patron : voit toutes les boutiques sans filtre
        if ($user->role === 'patron') {
            $request->attributes->set('shopId', null);
            return $next($request);
        }

        // Caissière : boutique unique auto-détectée depuis son compte
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
