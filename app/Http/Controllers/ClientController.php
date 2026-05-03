<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CreditTransaction;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $type    = $request->query('type');
        $search  = $request->query('search');

        $query = Client::query();

        if ($type) {
            $query->where('type', $type);
        }

        if ($search) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('nom', 'like', "%{$escaped}%")
                  ->orWhere('telephone', 'like', "%{$escaped}%");
            });
        }

        $clients = $query->orderBy('nom')->paginate(20);

        return view('clients.index', compact('clients', 'type', 'search'));
    }

    public function create(Request $request)
    {
        $shops = $request->attributes->get('shopId') === null
            ? \App\Models\Shop::orderBy('nom')->get()
            : null;

        return view('clients.create', compact('shops'));
    }

    public function store(Request $request)
    {
        $shopId = $request->attributes->get('shopId')
            ?? $request->input('shop_id');

        if (!$shopId) {
            return back()->with('error', 'Veuillez sélectionner une boutique.');
        }

        $validated = $request->validate([
            'nom'           => ['required', 'string', 'max:100'],
            'telephone'     => ['required', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'type'          => ['required', 'in:particulier,revendeur'],
            'nom_boutique'  => ['nullable', 'string', 'max:100'],
            'credit_limite' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $client = Client::create(array_merge($validated, ['shopId' => $shopId]));

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Request $request, string $id)
    {
        $client       = Client::findOrFail($id);
        $transactions = $client->creditTransactions()->latest()->take(20)->get();
        $reparations  = $client->repairs()->latest('date_creation')->take(10)->get();

        return view('clients.show', compact('client', 'transactions', 'reparations'));
    }

    public function edit(string $id)
    {
        $client = Client::findOrFail($id);
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'nom'           => ['required', 'string', 'max:100'],
            'telephone'     => ['required', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'nom_boutique'  => ['nullable', 'string', 'max:100'],
            'credit_limite' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client->id)
            ->with('success', 'Client mis à jour.');
    }

    public function dashboard(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        if (!$client->isRevendeur()) {
            return redirect()->route('clients.show', $id);
        }

        $periode = $request->query('periode', 'mois');
        [$debut, $fin] = match ($periode) {
            'trimestre' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'annee'     => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default     => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };

        // Achats sur la période
        $achats = Sale::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->whereBetween('date', [$debut, $fin])
            ->with('stock')
            ->orderByDesc('date')
            ->get();

        $totalAchats    = $achats->sum('total');
        $totalComptant  = $achats->where('mode_paiement', 'comptant')->sum('montant_paye');
        $totalCredit    = $achats->where('mode_paiement', 'credit')->sum('reste_credit');

        // Évolution mensuelle sur 6 mois
        $evolutionMois = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $ca   = Sale::withoutGlobalScopes()
                ->where('client_id', $client->id)
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('total');
            $evolutionMois[] = ['mois' => $date->translatedFormat('M Y'), 'ca' => $ca];
        }

        // Historique transactions crédit
        $transactions = CreditTransaction::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Top articles achetés
        $topArticles = Sale::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->selectRaw('nom, SUM(quantite) as total_qte, SUM(total) as total_ca')
            ->groupBy('nom')
            ->orderByDesc('total_qte')
            ->limit(5)
            ->get();

        return view('clients.dashboard', compact(
            'client', 'periode', 'debut', 'fin',
            'achats', 'totalAchats', 'totalComptant', 'totalCredit',
            'evolutionMois', 'transactions', 'topArticles'
        ));
    }

    public function remboursement(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        if (!$client->isRevendeur()) {
            return back()->with('error', 'Ce client n\'a pas de compte crédit.');
        }

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
            'notes'   => ['nullable', 'string', 'max:500'],
        ]);

        $user   = $request->attributes->get('user');
        $credit = app(\App\Services\CreditService::class);

        try {
            $credit->enregistrerRemboursement($client, $validated['montant'], $user->id, $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Remboursement de {$validated['montant']} enregistré.");
    }

    public function lierCompte(Request $request, string $id)
    {
        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $client->update(['user_id' => $validated['user_id']]);

        return back()->with('success', 'Compte utilisateur lié au client.');
    }

    public function delierCompte(Request $request, string $id)
    {
        $client = Client::findOrFail($id);
        $client->update(['user_id' => null]);

        return back()->with('success', 'Compte utilisateur délié.');
    }
}
