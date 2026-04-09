<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class JwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check session first (Blade auth via session)
        if ($request->session()->has('user_id')) {
            $user = User::find($request->session()->get('user_id'));
            if ($user) {
                $request->attributes->set('user', $user);
                $request->attributes->set('userId', $user->id);
                $request->attributes->set('userRole', $user->role);
                return $next($request);
            }
        }

        // Fallback to JWT token in header (API compatibility)
        $token = $request->bearerToken();
        if (!$token) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Token manquant'], 401);
            }
            return redirect()->route('login');
        }

        try {
            $decoded = JWT::decode($token, new Key(config('jwt.secret'), config('jwt.algo')));
            $user = User::find($decoded->id);
            if (!$user) {
                return response()->json(['error' => 'Utilisateur introuvable'], 401);
            }
            $request->attributes->set('user', $user);
            $request->attributes->set('userId', $user->id);
            $request->attributes->set('userRole', $user->role);
            return $next($request);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Token invalide'], 401);
            }
            return redirect()->route('login');
        }
    }
}
