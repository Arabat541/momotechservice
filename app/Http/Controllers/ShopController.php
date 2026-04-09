<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function switchShop(Request $request)
    {
        $shopId = $request->input('shop_id');
        if ($shopId) {
            session(['current_shop_id' => $shopId]);
        }
        return back();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string',
        ]);

        $user = $request->attributes->get('user');

        $shop = DB::transaction(function () use ($request, $user) {
            $shop = Shop::create([
                'nom' => $request->nom,
                'adresse' => $request->input('adresse', ''),
                'telephone' => $request->input('telephone', ''),
                'createdBy' => $user->id,
            ]);

            Settings::create([
                'shopId' => $shop->id,
                'companyInfo' => ['nom' => $request->nom, 'adresse' => $request->input('adresse', ''), 'telephone' => $request->input('telephone', ''), 'slogan' => ''],
                'warranty' => ['duree' => '7', 'conditions' => ''],
            ]);

            $user->shops()->attach($shop->id);

            return $shop;
        });

        session(['current_shop_id' => $shop->id]);

        return back()->with('success', "Boutique \"{$shop->nom}\" créée.");
    }

    public function update(Request $request, string $id)
    {
        $shop = Shop::findOrFail($id);

        $request->validate([
            'nom' => 'required|string',
            'adresse' => 'nullable|string',
            'telephone' => 'nullable|string',
        ]);

        $shop->update($request->only('nom', 'adresse', 'telephone'));

        return back()->with('success', 'Boutique mise à jour.');
    }

    public function destroy(string $id)
    {
        $shop = Shop::findOrFail($id);

        DB::transaction(function () use ($shop) {
            $shop->savs()->delete();
            $shop->repairs()->delete();
            $shop->stocks()->delete();
            if ($shop->settings) {
                $shop->settings->delete();
            }
            $shop->users()->detach();
            $shop->delete();
        });

        // If deleted shop was current, switch to another
        if (session('current_shop_id') === $id) {
            $nextShop = Shop::first();
            session(['current_shop_id' => $nextShop?->id]);
        }

        return back()->with('success', 'Boutique supprimée.');
    }

    public function addUser(Request $request, string $id)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $shop = Shop::findOrFail($id);
        $shop->users()->syncWithoutDetaching([$request->user_id]);

        return back()->with('success', 'Utilisateur ajouté à la boutique.');
    }

    public function removeUser(Request $request, string $id)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $shop = Shop::findOrFail($id);
        $shop->users()->detach($request->user_id);

        return back()->with('success', 'Utilisateur retiré de la boutique.');
    }
}
