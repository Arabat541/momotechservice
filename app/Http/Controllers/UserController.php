<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Models\Shop;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->attributes->get('user');

        $request->validate([
            'nom' => 'sometimes|string|max:100',
            'prenom' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:8|max:255',
        ]);

        $user->update($request->only('nom', 'prenom', 'email'));

        if ($request->filled('password')) {
            $user->update(['password' => bcrypt($request->password)]);
        }

        session([
            'user_email' => $user->email,
            'user_nom' => $user->nom,
            'user_prenom' => $user->prenom,
        ]);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function resetPassword(Request $request, string $id)
    {
        $request->validate(['password' => 'required|min:8|max:255']);

        $target = User::findOrFail($id);
        if ($target->role === 'patron') {
            return back()->with('error', 'Impossible de réinitialiser le mot de passe d\'un patron.');
        }

        $target->update(['password' => bcrypt($request->password)]);

        return back()->with('success', "Mot de passe de {$target->email} réinitialisé.");
    }

    public function destroy(Request $request, string $id)
    {
        $authUser = $request->attributes->get('user');
        if ($authUser->id === $id) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user = User::findOrFail($id);
        if ($user->role === 'patron') {
            return back()->with('error', 'Impossible de supprimer un patron.');
        }

        $user->shops()->detach();

        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Impossible de supprimer cet utilisateur : il est lié à des réparations ou d\'autres données.');
        }

        return back()->with('success', 'Utilisateur supprimé.');
    }

    public function exportPdf(Request $request)
    {
        $shopIds = Shop::pluck('id')->toArray();
        $users   = User::with('shops')
            ->whereHas('shops', fn($q) => $q->whereIn('shops.id', $shopIds))
            ->orWhere('role', 'patron')
            ->orderBy('nom')
            ->get();

        $companyInfo = $this->getCompanyInfo(null);
        $logoBase64  = $this->getLogoBase64();

        return Pdf::loadView('exports.utilisateurs-pdf', compact('users', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'portrait')
            ->download('utilisateurs-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getCompanyInfo(?string $shopId): array
    {
        $settings = $shopId
            ? Settings::withoutGlobalScopes()->where('shopId', $shopId)->first()
            : Settings::withoutGlobalScopes()->first();
        $default = ['nom' => 'MOMO TECH SERVICE', 'adresse' => '', 'telephone' => ''];
        return array_merge($default, $settings?->companyInfo ?? []);
    }

    private function getLogoBase64(): ?string
    {
        foreach (['logo-receipt.png', 'logo-app.png'] as $file) {
            $path = public_path('images/' . $file);
            if (file_exists($path)) {
                return base64_encode(file_get_contents($path));
            }
        }
        return null;
    }
}
