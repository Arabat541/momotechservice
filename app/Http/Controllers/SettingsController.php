<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user = $request->attributes->get('user');

        if ($shopId) {
            $settings = Cache::remember("shop_{$shopId}_settings", 300, fn () =>
                Settings::firstOrCreate(
                    ['shopId' => $shopId],
                    [
                        'companyInfo' => ['nom' => '', 'adresse' => '', 'telephone' => '', 'slogan' => '', 'email' => '', 'whatsapp' => '', 'facebook' => '', 'instagram' => ''],
                        'warranty' => ['duree' => '7', 'conditions' => ''],
                    ]
                )
            );
        } else {
            $settings = new Settings(['companyInfo' => [], 'warranty' => [], 'sms_config' => []]);
        }

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

        $request->validate([
            'nomEntreprise'    => 'nullable|string|max:150',
            'adresse'          => 'nullable|string|max:255',
            'telephone'        => ['nullable', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{0,30}$/'],
            'slogan'           => 'nullable|string|max:255',
            'email'            => 'nullable|email|max:255',
            'whatsapp'         => ['nullable', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{0,30}$/'],
            'facebook'         => 'nullable|url|max:500',
            'instagram'        => 'nullable|url|max:500',
            'duree_garantie'   => 'nullable|integer|min:0|max:3650',
            'message_garantie' => 'nullable|string|max:1000',
        ]);

        $settings->update([
            'companyInfo' => [
                'nom'       => $request->input('nomEntreprise', ''),
                'adresse'   => $request->input('adresse', ''),
                'telephone' => $request->input('telephone', ''),
                'slogan'    => $request->input('slogan', ''),
                'email'     => $request->input('email', ''),
                'whatsapp'  => $request->input('whatsapp', ''),
                'facebook'  => $request->input('facebook', ''),
                'instagram' => $request->input('instagram', ''),
            ],
            'warranty' => [
                'duree' => $request->input('duree_garantie', '7'),
                'conditions' => $request->input('message_garantie', ''),
            ],
        ]);

        Cache::forget("shop_{$shopId}_settings");

        return back()->with('success', 'Paramètres enregistrés.');
    }

    public function updateSms(Request $request)
    {
        $shopId = $request->attributes->get('shopId');

        $validated = $request->validate([
            'sms_enabled'   => ['nullable', 'boolean'],
            'sms_provider'  => ['required', 'in:twilio,orange'],
            'sms_api_key'   => ['required', 'string', 'max:255'],
            'sms_sender'    => ['required', 'string', 'max:30'],
            'twilio_sid'    => ['nullable', 'string', 'max:255'],
        ]);

        $settings = Settings::firstOrCreate(['shopId' => $shopId], [
            'companyInfo' => [], 'warranty' => [],
        ]);

        $settings->update([
            'sms_config' => [
                'enabled'    => (bool) ($validated['sms_enabled'] ?? false),
                'provider'   => $validated['sms_provider'],
                'api_key'    => $validated['sms_api_key'],
                'sender'     => $validated['sms_sender'],
                'twilio_sid' => $validated['twilio_sid'] ?? null,
            ],
        ]);

        Cache::forget("shop_{$shopId}_settings");

        return back()->with('success', 'Configuration SMS enregistrée.');
    }
}
