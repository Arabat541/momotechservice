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

        if (!$shopId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Shop ID requis'], 400);
            }
            return redirect()->route('dashboard')->with('error', 'Veuillez sélectionner une boutique.');
        }

        // Patron bypasses shop membership check
        if ($user->role === 'patron') {
            $shop = Shop::find($shopId);
            if (!$shop) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Boutique introuvable'], 404);
                }
                return redirect()->route('dashboard')->with('error', 'Boutique introuvable.');
            }
            $request->attributes->set('shopId', $shopId);
            return $next($request);
        }

        // Check employee has access to this shop
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
