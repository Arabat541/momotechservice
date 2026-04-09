<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('user_id')) {
            $user = User::find(session('user_id'));
            if ($user) {
                return redirect()->route('dashboard');
            }
            session()->flush();
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email ou mot de passe incorrect.'])->withInput();
        }

        // Handle $2b$ (Node.js bcryptjs) hashes by converting to $2y$ (PHP bcrypt)
        $hash = $user->password;
        if (str_starts_with($hash, '$2b$')) {
            $hash = '$2y$' . substr($hash, 4);
        }

        if (!Hash::check($request->password, $hash)) {
            return back()->withErrors(['email' => 'Email ou mot de passe incorrect.'])->withInput();
        }

        // Store user in session
        session([
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_email' => $user->email,
            'user_nom' => $user->nom,
            'user_prenom' => $user->prenom,
        ]);

        // Set first shop as current if not set
        $firstShop = $user->role === 'patron'
            ? \App\Models\Shop::first()
            : $user->shops()->first();

        if ($firstShop) {
            session(['current_shop_id' => $firstShop->id]);
        }

        return redirect()->route('reparations.place');
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('login');
    }

    public function register(Request $request)
    {
        $authUser = $request->attributes->get('user');
        if (!$authUser || $authUser->role !== 'patron') {
            return back()->with('error', 'Seul le patron peut créer des comptes.');
        }

        $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'employé',
        ]);

        // Assign to current shop if available
        $shopId = session('current_shop_id');
        if ($shopId) {
            $user->shops()->attach($shopId);
        }

        return back()->with('success', 'Employé créé avec succès.');
    }

    public function showResetPassword()
    {
        return view('auth.reset-password');
    }

    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Aucun compte avec cet email.']);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete old tokens
        PasswordResetToken::where('userId', $user->id)->delete();

        PasswordResetToken::create([
            'userId' => $user->id,
            'token' => Hash::make($code),
            'expiresAt' => now()->addMinutes(15),
        ]);

        // In production, send email. For now, log it.
        \Log::info("Code de réinitialisation pour {$user->email}: {$code}");

        return back()->with('success', "Code envoyé. Vérifiez les logs serveur. Code: {$code}");
    }

    public function confirmReset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|min:8',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Utilisateur introuvable.']);
        }

        $resetToken = PasswordResetToken::where('userId', $user->id)
            ->where('expiresAt', '>', now())
            ->latest('expiresAt')
            ->first();

        if (!$resetToken || !Hash::check($request->code, $resetToken->token)) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.']);
        }

        $user->update(['password' => Hash::make($request->password)]);
        PasswordResetToken::where('userId', $user->id)->delete();

        return redirect()->route('login')->with('success', 'Mot de passe réinitialisé. Connectez-vous.');
    }

    // API login (for mobile/external access)
    public function apiLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        $payload = [
            'id' => $user->id,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + (config('jwt.ttl') * 60),
        ];

        $token = JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'nom' => $user->nom,
                'prenom' => $user->prenom,
                'role' => $user->role,
            ],
        ]);
    }
}
