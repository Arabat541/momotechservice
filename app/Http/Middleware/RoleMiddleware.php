<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $userRole = $request->attributes->get('userRole');

        if (!in_array($userRole, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Accès refusé'], 403);
            }
            abort(403, 'Accès refusé');
        }

        return $next($request);
    }
}
