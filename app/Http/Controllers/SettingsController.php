<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user = $request->attributes->get('user');

        $settings = Settings::firstOrCreate(
            ['shopId' => $shopId],
            [
                'companyInfo' => ['nom' => '', 'adresse' => '', 'telephone' => '', 'slogan' => ''],
                'warranty' => ['duree' => '7', 'conditions' => ''],
            ]
        );

        $users = [];
        if ($user->role === 'patron') {
            $users = \App\Models\User::with('shops')->get();
        }

        $shops = $user->role === 'patron'
            ? \App\Models\Shop::with('users')->get()
            : $user->shops()->with('users')->get();

        return view('dashboard.parametres', compact('settings', 'users', 'user', 'shops'));
    }

    public function update(Request $request)
    {
        $shopId = $request->attributes->get('shopId');

        $settings = Settings::where('shopId', $shopId)->first();
        if (!$settings) {
            $settings = Settings::create([
                'shopId' => $shopId,
                'companyInfo' => [],
                'warranty' => [],
            ]);
        }

        $settings->update([
            'companyInfo' => [
                'nom' => $request->input('nomEntreprise', ''),
                'adresse' => $request->input('adresse', ''),
                'telephone' => $request->input('telephone', ''),
                'slogan' => $request->input('slogan', ''),
            ],
            'warranty' => [
                'duree' => $request->input('duree_garantie', '7'),
                'conditions' => $request->input('message_garantie', ''),
            ],
        ]);

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
