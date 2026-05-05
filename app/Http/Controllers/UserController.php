<?php

namespace App\Http\Controllers;

use App\Models\User;
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

    public function exportCsv(Request $request)
    {
        $users = User::with('shops')->get();

        $csv = "Nom,Prénom,Email,Rôle,Boutiques\n";
        foreach ($users as $u) {
            $shopNames = $u->shops->pluck('nom')->implode('; ');
            $csv .= "\"{$u->nom}\",\"{$u->prenom}\",\"{$u->email}\",\"{$u->role}\",\"{$shopNames}\"\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="utilisateurs.csv"',
        ]);
    }
}
