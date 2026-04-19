@extends('layouts.dashboard')
@section('page-title', 'Dashboard revendeur')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('clients.show', $client->id) }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $client->nom }}</h1>
                <p class="text-gray-500 text-sm">{{ $client->nom_boutique ?? 'Revendeur' }} — Dashboard analytique</p>
            </div>
        </div>
        <form method="GET" class="flex gap-2 items-center">
            <select name="periode" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="mois" {{ ($periode ?? 'mois') === 'mois' ? 'selected' : '' }}>Ce mois</option>
                <option value="trimestre" {{ ($periode ?? 'mois') === 'trimestre' ? 'selected' : '' }}>Ce trimestre</option>
                <option value="annee" {{ ($periode ?? 'mois') === 'annee' ? 'selected' : '' }}>Cette année</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm">
                <i class="fas fa-filter"></i>
            </button>
        </form>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">CA Total</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($totalAchats ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400">F CFA</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Comptant</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($totalComptant ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400">F CFA encaissé</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Crédit accordé</div>
            <div class="text-2xl font-bold text-red-500">{{ number_format($totalCredit ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400">F CFA</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Solde dû</div>
            <div class="text-2xl font-bold {{ ($client->solde_credit ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($client->solde_credit ?? 0, 0, ',', ' ') }}
            </div>
            <div class="text-xs text-gray-400">Limite : {{ number_format($client->credit_limite ?? 0, 0, ',', ' ') }} F</div>
        </div>
    </div>

    {{-- Évolution CA --}}
    @if(!empty($evolutionMois))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Évolution du CA (6 derniers mois)</h2>
        <div class="flex items-end gap-2 h-32">
            @php $max = max(array_column($evolutionMois, 'ca') ?: [1]); @endphp
            @foreach($evolutionMois as $mois)
            <div class="flex-1 flex flex-col items-center gap-1">
                <div class="text-xs text-gray-500">{{ number_format($mois['ca'] / 1000, 0) }}k</div>
                <div class="w-full bg-blue-500 rounded-t" style="height: {{ max(4, ($max > 0 ? ($mois['ca'] / $max) : 0) * 100) }}px"></div>
                <div class="text-xs text-gray-400">{{ $mois['mois'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Top articles --}}
        @if(!empty($topArticles) && count($topArticles) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Top articles achetés</h2>
            <div class="space-y-3">
                @foreach($topArticles as $article)
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-medium text-gray-800">{{ $article->nom }}</div>
                        <div class="text-xs text-gray-400">{{ $article->total_qte }} unité(s)</div>
                    </div>
                    <div class="text-sm font-semibold text-blue-600">{{ number_format($article->total_ca, 0, ',', ' ') }} F</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Dernières transactions --}}
        @if(isset($transactions) && $transactions->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Dernières transactions crédit</h2>
            <div class="space-y-2">
                @foreach($transactions->take(8) as $tx)
                <div class="flex items-center justify-between py-1 border-b border-gray-50 last:border-0">
                    <div>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $tx->type === 'dette' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                            {{ $tx->type === 'dette' ? 'Crédit' : 'Remb.' }}
                        </span>
                        <span class="text-xs text-gray-400 ml-2">{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y') }}</span>
                    </div>
                    <div class="text-sm font-medium {{ $tx->type === 'dette' ? 'text-red-600' : 'text-green-600' }}">
                        {{ $tx->type === 'dette' ? '+' : '-' }}{{ number_format($tx->montant, 0, ',', ' ') }} F
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Achats récents --}}
    @if(isset($achats) && count($achats) > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Achats récents</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Qté</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Mode paiement</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($achats as $achat)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-sm font-medium text-gray-800">{{ $achat->nom }}</td>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ $achat->quantite }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $achat->mode_paiement === 'credit' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                            {{ $achat->mode_paiement === 'credit' ? 'Crédit' : 'Comptant' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right text-sm font-medium">{{ number_format($achat->total, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500">{{ \Carbon\Carbon::parse($achat->created_at)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
