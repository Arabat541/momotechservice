<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class JwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Auth web — session
        if ($request->session()->has('user_id')) {
            $user = User::find($request->session()->get('user_id'));
            if ($user) {
                $this->setUserOnRequest($request, $user);
                return $next($request);
            }
        }

        // Auth API — Sanctum Bearer token
        $bearerToken = $request->bearerToken();
        if (!$bearerToken) {
            return $this->unauthorized($request);
        }

        $accessToken = PersonalAccessToken::findToken($bearerToken);
        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            return $this->unauthorized($request);
        }

        $user = $accessToken->tokenable;
        if (!$user instanceof User) {
            return $this->unauthorized($request);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();
        $this->setUserOnRequest($request, $user);

        return $next($request);
    }

    private function setUserOnRequest(Request $request, User $user): void
    {
        $request->attributes->set('user', $user);
        $request->attributes->set('userId', $user->id);
        $request->attributes->set('userRole', $user->role);
    }

    private function unauthorized(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }
        return redirect()->route('login');
    }
}
