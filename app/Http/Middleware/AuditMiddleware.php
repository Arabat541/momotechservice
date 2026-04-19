<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;

class AuditMiddleware
{
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (in_array($request->method(), self::WRITE_METHODS)) {
            $user = $request->attributes->get('user');
            AuditLog::create([
                'userId' => $user?->id,
                'shopId' => $request->attributes->get('shopId'),
                'method' => $request->method(),
                'route' => $request->path(),
                'ip' => $request->ip(),
                'action' => $this->resolveAction($request),
            ]);
        }

        return $response;
    }

    private function resolveAction(Request $request): string
    {
        $route = $request->route()?->getName() ?? $request->path();
        return substr($route, 0, 100);
    }
}
