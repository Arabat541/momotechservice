<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Models\PasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email ou mot de passe incorrect.'])->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Email ou mot de passe incorrect.'])->withInput();
        }

        // 2FA: si activé pour ce patron, stocker l'ID en session et rediriger vers vérification
        if ($user->two_factor_enabled && $user->google2fa_secret) {
            session()->put('2fa_user_id', $user->id);
            return redirect()->route('two-factor.verify');
        }

        session()->regenerate(true);

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

        return redirect()->route('dashboard');
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
            'nom'      => 'required|string|max:100',
            'prenom'   => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8|max:255',
            'role'     => 'nullable|in:caissiere',
        ]);

        $user = User::create([
            'nom'      => $request->nom,
            'prenom'   => $request->prenom,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->input('role', 'caissiere'),
        ]);

        // Assign to current shop if available
        $shopId = session('current_shop_id');
        if ($shopId) {
            $user->shops()->attach($shopId);
        }

        return back()->with('success', 'Utilisateur créé avec succès.');
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

        Mail::to($user->email)->send(new PasswordResetMail($code, $user->prenom));

        return back()->with('success', 'Un code de réinitialisation a été envoyé à votre adresse email.');
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

    public function apiLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|max:255',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Email ou mot de passe incorrect'], 401);
        }

        // Révoquer les anciens tokens de l'appareil si nom fourni
        $deviceName = $request->input('device_name', 'api');
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName, ['*'], now()->addMinutes(config('sanctum.expiration', 60)));

        return response()->json([
            'token' => $token->plainTextToken,
            'user'  => [
                'id'     => $user->id,
                'email'  => $user->email,
                'nom'    => $user->nom,
                'prenom' => $user->prenom,
                'role'   => $user->role,
            ],
        ]);
    }

    public function apiLogout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'Déconnecté.']);
    }
}
