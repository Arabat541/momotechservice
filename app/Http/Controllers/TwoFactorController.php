<?php

namespace App\Http\Controllers;

use PragmaRX\Google2FALaravel\Support\Authenticator;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->attributes->get('user');

        return view('two-factor.show', compact('user'));
    }

    public function activate(Request $request)
    {
        $user    = $request->attributes->get('user');
        $google2fa = new Google2FA();

        $secret = $google2fa->generateSecretKey();
        $user->update(['google2fa_secret' => $secret]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name', 'MomoTech'),
            $user->email,
            $secret
        );

        $qrSvg = QrCode::format('svg')->size(200)->generate($qrCodeUrl);

        return view('two-factor.activate', compact('user', 'secret', 'qrSvg'));
    }

    public function confirm(Request $request)
    {
        $user      = $request->attributes->get('user');
        $google2fa = new Google2FA();

        $validated = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        if (!$user->google2fa_secret) {
            return back()->with('error', 'Aucun secret 2FA généré. Activez d\'abord le 2FA.');
        }

        $oldTs     = $user->google2fa_ts;
        $newTs     = $google2fa->verifyKeyNewer($user->google2fa_secret, $validated['otp'], $oldTs);

        if ($newTs === false) {
            return back()->with('error', 'Code invalide ou déjà utilisé. Vérifiez votre application et réessayez.');
        }

        $user->update(['two_factor_enabled' => true, 'google2fa_ts' => $newTs]);

        return redirect()->route('two-factor.show')->with('success', '2FA activé avec succès.');
    }

    public function disable(Request $request)
    {
        $user = $request->attributes->get('user');

        $validated = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $google2fa = new Google2FA();
        $oldTs     = $user->google2fa_ts;
        $newTs     = $google2fa->verifyKeyNewer($user->google2fa_secret ?? '', $validated['otp'], $oldTs);

        if ($newTs === false) {
            return back()->with('error', 'Code invalide ou déjà utilisé.');
        }

        $user->update(['two_factor_enabled' => false, 'google2fa_secret' => null, 'google2fa_ts' => null]);

        return redirect()->route('two-factor.show')->with('success', '2FA désactivé.');
    }

    /** Called after password login — verify TOTP code */
    public function verify(Request $request)
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('two-factor.verify');
    }

    public function verifySubmit(Request $request)
    {
        $userId = session('2fa_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $google2fa = new Google2FA();
        $oldTs     = $user->google2fa_ts;
        $newTs     = $google2fa->verifyKeyNewer($user->google2fa_secret ?? '', $validated['otp'], $oldTs);

        if ($newTs === false) {
            return back()->with('error', 'Code invalide ou déjà utilisé. Réessayez.');
        }

        // Mémoriser le timestamp du code utilisé pour prévenir le rejeu
        $user->update(['google2fa_ts' => $newTs]);

        // Complete the login process
        session()->forget('2fa_user_id');
        session()->regenerate(true);
        session([
            'user_id'    => $user->id,
            'user_role'  => $user->role,
            'user_email' => $user->email,
            'user_nom'   => $user->nom,
            'user_prenom'=> $user->prenom,
        ]);

        if ($user->role === 'patron') {
            session(['current_shop_id' => null]);
        } else {
            $firstShop = $user->shops()->orderBy('nom')->first();
            if ($firstShop) {
                session(['current_shop_id' => $firstShop->id]);
            }
        }

        return redirect()->route('dashboard');
    }
}
