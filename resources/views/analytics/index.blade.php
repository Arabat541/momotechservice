@extends('layouts.dashboard')
@section('page-title', 'Tableau de bord analytique')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Analytique</h1>
        <form method="GET" class="flex items-center gap-2">
            <select name="periode" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="mois" {{ ($periode ?? 'mois') === 'mois' ? 'selected' : '' }}>Ce mois-ci</option>
                <option value="trimestre" {{ ($periode ?? 'mois') === 'trimestre' ? 'selected' : '' }}>Ce trimestre</option>
                <option value="annee" {{ ($periode ?? 'mois') === 'annee' ? 'selected' : '' }}>Cette année</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter"></i>
            </button>
        </form>
    </div>

    {{-- KPIs principaux --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tools text-blue-600 text-sm"></i>
                </div>
                <span class="text-xs text-gray-500 uppercase tracking-wide">CA Réparations</span>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($caReparations ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">Encaissé : {{ number_format($encaisseReparations ?? 0, 0, ',', ' ') }} F</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-green-600 text-sm"></i>
                </div>
                <span class="text-xs text-gray-500 uppercase tracking-wide">CA Ventes</span>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($caVentes ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">F CFA</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-purple-600 text-sm"></i>
                </div>
                <span class="text-xs text-gray-500 uppercase tracking-wide">Taux soldées</span>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ $tauxSoldees ?? 0 }}%</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ $soldeesRep ?? 0 }} / {{ $totalRep ?? 0 }} réparations</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-9 h-9 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-red-600 text-sm"></i>
                </div>
                <span class="text-xs text-gray-500 uppercase tracking-wide">Crédit encours</span>
            </div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($encoursCredit ?? 0, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ $totalCredit ?? 0 }} revendeur(s)</div>
        </div>
    </div>

    {{-- Évolution CA 12 mois --}}
    @if(!empty($caParMois))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-5">Évolution du CA (12 derniers mois)</h2>
        <div class="flex items-end gap-1 h-40">
            @php
                $maxCA = max(array_column($caParMois, 'total') ?: [1]);
            @endphp
            @foreach($caParMois as $mois)
            <div class="flex-1 flex flex-col items-center gap-1 group">
                <div class="text-xs text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                    {{ number_format($mois['total'] / 1000, 0) }}k F
                </div>
                <div class="w-full bg-blue-500 hover:bg-blue-600 rounded-t transition-all cursor-pointer"
                    style="height: {{ max(4, ($maxCA > 0 ? ($mois['total'] / $maxCA) : 0) * 140) }}px"
                    title="{{ $mois['mois_label'] }} : {{ number_format($mois['total'], 0, ',', ' ') }} F"></div>
                <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($mois['mois'] . '-01')->format('M') }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Top 5 pannes --}}
        @if(!empty($topPannes))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Top 5 pannes</h2>
            <div class="space-y-3">
                @foreach($topPannes as $panne)
                @php $maxP = $topPannes[0]['total'] ?: 1; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 truncate">{{ $panne['panne'] }}</span>
                        <span class="font-semibold text-gray-900 ml-2">{{ $panne['total'] }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ ($panne['total'] / $maxP) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Top 5 articles --}}
        @if(!empty($topArticles))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Top 5 articles vendus</h2>
            <div class="space-y-3">
                @foreach($topArticles as $article)
                @php $maxA = $topArticles[0]['total'] ?: 1; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 truncate">{{ $article['nom'] }}</span>
                        <span class="font-semibold text-gray-900 ml-2">{{ $article['quantite'] }} unités</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ ($article['total'] / $maxA) * 100 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Stocks en alerte --}}
        @if(!empty($stocksAlerte) && count($stocksAlerte) > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-orange-500"></i> Stocks en alerte ({{ count($stocksAlerte) }})
            </h2>
            <div class="space-y-2">
                @foreach($stocksAlerte as $stock)
                <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                    <span class="text-sm text-gray-800">{{ $stock->nom }}</span>
                    <div class="text-right">
                        <span class="text-sm font-bold text-red-600">{{ $stock->quantite }}</span>
                        <span class="text-xs text-gray-400 ml-1">/ seuil {{ $stock->seuil_alerte }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Charges fournisseurs --}}
        @if(!empty($chargesDues))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-red-500"></i> Charges fournisseurs dues
            </h2>
            <div class="space-y-2">
                @foreach($chargesDues as $charge)
                <div class="flex items-center justify-between py-1.5 border-b border-gray-50 last:border-0">
                    <div>
                        <div class="text-sm font-medium text-gray-800">{{ $charge['fournisseur'] }}</div>
                        <div class="text-xs text-gray-400">{{ $charge['nb_factures'] }} facture(s)</div>
                    </div>
                    <span class="text-sm font-bold text-red-600">{{ number_format($charge['total'], 0, ',', ' ') }} F</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Dernière caisse --}}
    @if(isset($derniereCaisse) && $derniereCaisse)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">Dernière session de caisse</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($derniereCaisse->date)->format('d/m/Y') }}</p>
            </div>
            <div class="text-right">
                <div class="text-xl font-bold {{ ($derniereCaisse->ecart ?? 0) < 0 ? 'text-red-600' : 'text-green-600' }}">
                    Écart : {{ $derniereCaisse->ecart !== null ? (($derniereCaisse->ecart > 0 ? '+' : '') . number_format($derniereCaisse->ecart, 0, ',', ' ') . ' F') : 'N/A' }}
                </div>
                <a href="{{ route('caisse.show', $derniereCaisse->id) }}" class="text-blue-600 text-sm hover:underline">Voir la session →</a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
