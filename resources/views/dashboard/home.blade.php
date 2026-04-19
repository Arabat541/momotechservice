@extends('layouts.dashboard')

@section('page-title', 'Tableau de Bord')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            @if($role === 'patron')
            <h3 class="text-lg font-semibold text-gray-600">Toutes les boutiques ({{ $shops->count() }})</h3>
            <p class="text-sm text-gray-400">Aperçu global de votre activité</p>
            @else
            <h3 class="text-lg font-semibold text-gray-600">{{ $shops->first()?->nom ?? 'Ma boutique' }}</h3>
            <p class="text-sm text-gray-400">Activité de votre boutique</p>
            @endif
        </div>
        <span class="text-sm text-gray-400">{{ now()->translatedFormat('l d F Y') }}</span>
    </div>

    {{-- KPI Cards Row 1: Réparations --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Total Réparations</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalRepairs) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-screwdriver-wrench text-blue-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2"><span class="text-blue-600 font-semibold">{{ $repairsToday }}</span> aujourd'hui · <span class="text-blue-600 font-semibold">{{ $repairsMonth }}</span> ce mois</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">En cours</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1">{{ number_format($repairsEnCours) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-spinner text-orange-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2"><span class="text-yellow-600 font-semibold">{{ $repairsEnAttente }}</span> en attente</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Terminées</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ number_format($repairsTerminees) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
            @if($totalRepairs > 0)
            <p class="text-xs text-gray-400 mt-2"><span class="text-green-600 font-semibold">{{ round($repairsTerminees / $totalRepairs * 100) }}%</span> taux de complétion</p>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-medium">Annulées</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ number_format($repairsAnnulees) }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-ban text-red-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2"><span class="text-red-600 font-semibold">{{ $savCount }}</span> dossiers SAV</p>
        </div>
    </div>

    {{-- KPI Cards Row 2: Finances --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase font-medium text-blue-200">CA Total</p>
                    <p class="text-2xl font-bold mt-1">{{ number_format($caTotal, 0, ',', ' ') }}</p>
                    <p class="text-xs text-blue-200">CFA</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-blue-200 mt-2">Ce mois: <span class="text-white font-semibold">{{ number_format($caMois, 0, ',', ' ') }} CFA</span></p>
        </div>

        <div class="bg-gradient-to-br from-green-600 to-emerald-700 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase font-medium text-green-200">Montant Encaissé</p>
                    <p class="text-2xl font-bold mt-1">{{ number_format($totalPaye, 0, ',', ' ') }}</p>
                    <p class="text-xs text-green-200">CFA</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-wallet text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-green-200 mt-2">Ventes articles: <span class="text-white font-semibold">{{ number_format($ventesTotal, 0, ',', ' ') }} CFA</span></p>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase font-medium text-amber-200">Reste à Payer</p>
                    <p class="text-2xl font-bold mt-1">{{ number_format($totalRestant, 0, ',', ' ') }}</p>
                    <p class="text-xs text-amber-200">CFA</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-amber-200 mt-2"><span class="text-white font-semibold">{{ $nonSoldes }}</span> réparations non soldées</p>
        </div>

        <div class="bg-gradient-to-br from-purple-600 to-violet-700 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase font-medium text-purple-200">Ventes Articles</p>
                    <p class="text-2xl font-bold mt-1">{{ number_format($ventesCount) }}</p>
                    <p class="text-xs text-purple-200">ventes</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-purple-200 mt-2">Ce mois: <span class="text-white font-semibold">{{ number_format($ventesMois, 0, ',', ' ') }} CFA</span></p>
        </div>
    </div>

    {{-- Stocks summary --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-boxes-stacked text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Articles en stock</p>
                    <p class="text-lg font-bold text-gray-800">{{ $totalStocks }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-triangle-exclamation text-red-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Stocks épuisés</p>
                    <p class="text-lg font-bold text-red-600">{{ $stocksEpuises }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-battery-quarter text-yellow-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Stocks faibles (≤3)</p>
                    <p class="text-lg font-bold text-yellow-600">{{ $stocksFaibles }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-teal-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Valeur du stock</p>
                    <p class="text-lg font-bold text-teal-700">{{ number_format($valeurStock, 0, ',', ' ') }}<span class="text-xs font-normal ml-1">CFA</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts + Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- CA Graph --}}
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-4"><i class="fas fa-chart-bar text-blue-500 mr-2"></i>Chiffre d'affaires (6 derniers mois)</h3>
            <div class="space-y-2">
                @php $maxCA = max(max(array_column($caParMois, 'ca')), 1); @endphp
                @foreach($caParMois as $mois)
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 w-16 text-right">{{ $mois['mois'] }}</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-end pr-2 transition-all duration-500"
                             style="width: {{ $maxCA > 0 ? max(($mois['ca'] / $maxCA) * 100, 2) : 2 }}%">
                            @if($mois['ca'] > 0)
                            <span class="text-xs text-white font-medium whitespace-nowrap">{{ number_format($mois['ca'], 0, ',', ' ') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Réparations non soldées --}}
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-4"><i class="fas fa-exclamation-triangle text-amber-500 mr-2"></i>Réparations non soldées (top 5)</h3>
            @if($reparationsNonSoldees->isEmpty())
                <p class="text-sm text-gray-400 text-center py-8">Aucune réparation non soldée <i class="fas fa-party-horn"></i></p>
            @else
            <div class="space-y-2">
                @foreach($reparationsNonSoldees as $r)
                <a href="{{ route('reparations.show', $r->id) }}" class="flex items-center justify-between p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $r->numeroReparation }}</p>
                        <p class="text-xs text-gray-500">{{ $r->client_nom }} · {{ $r->appareil_marque_modele }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-amber-700">{{ number_format($r->reste_a_payer, 0, ',', ' ') }} CFA</p>
                        <p class="text-xs text-gray-400">/ {{ number_format($r->total_reparation, 0, ',', ' ') }}</p>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Dernières réparations --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700"><i class="fas fa-clock-rotate-left text-indigo-500 mr-2"></i>Dernières réparations</h3>
            <a href="{{ route('reparations.liste') }}" class="text-xs text-blue-600 hover:underline">Voir tout →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Réparation</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Boutique</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appareil</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($dernieresReparations as $r)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('reparations.show', $r->id) }}'">
                        <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ $r->numeroReparation }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $r->shop->nom ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $r->client_nom }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $r->appareil_marque_modele }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusColors = [
                                    'En cours' => 'bg-orange-100 text-orange-700',
                                    'En attente' => 'bg-yellow-100 text-yellow-700',
                                    'Terminé' => 'bg-green-100 text-green-700',
                                    'Annulé' => 'bg-red-100 text-red-700',
                                ];
                                $color = $statusColors[$r->statut_reparation] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $color }}">{{ $r->statut_reparation }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $r->etat_paiement === 'Soldé' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $r->etat_paiement }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ number_format($r->total_reparation, 0, ',', ' ') }} CFA</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $r->date_creation ? $r->date_creation->format('d/m/Y') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tableau comparatif par boutique (patron uniquement) --}}
    @if($role === 'patron' && count($shopStats) > 0)
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-sm font-semibold text-gray-700"><i class="fas fa-store text-indigo-500 mr-2"></i>Détails par boutique</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Boutique</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Réparations</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">En cours</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Terminées</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">CA Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Encaissé</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reste à payer</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ventes</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stocks</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Valeur stock</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">SAV</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($shopStats as $ss)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                            <i class="fas fa-store text-indigo-400 mr-1"></i>{{ $ss['nom'] }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($ss['reparations']) }}</td>
                        <td class="px-4 py-3 text-sm text-right"><span class="text-orange-600 font-medium">{{ $ss['enCours'] }}</span></td>
                        <td class="px-4 py-3 text-sm text-right"><span class="text-green-600 font-medium">{{ $ss['terminees'] }}</span></td>
                        <td class="px-4 py-3 text-sm text-right font-semibold">{{ number_format($ss['ca'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700">{{ number_format($ss['paye'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-amber-700">{{ number_format($ss['restant'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($ss['ventes'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $ss['stocks'] }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($ss['valeurStock'], 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $ss['sav'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                @if(count($shopStats) > 1)
                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr class="font-bold">
                        <td class="px-4 py-3 text-sm text-gray-700">TOTAL</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($totalRepairs) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-orange-600">{{ $repairsEnCours }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-600">{{ $repairsTerminees }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($caTotal, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700">{{ number_format($totalPaye, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-amber-700">{{ number_format($totalRestant, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($ventesTotal, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $totalStocks }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ number_format($valeurStock, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right">{{ $savCount }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    @endif

    {{-- Statut distribution --}}
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-4"><i class="fas fa-chart-pie text-purple-500 mr-2"></i>Répartition des réparations</h3>
        <div class="flex items-center gap-2 h-8 rounded-full overflow-hidden bg-gray-100">
            @if($totalRepairs > 0)
                @if($repairsTerminees > 0)
                <div class="h-full bg-green-500 flex items-center justify-center" style="width: {{ $repairsTerminees / $totalRepairs * 100 }}%" title="Terminées: {{ $repairsTerminees }}">
                    @if($repairsTerminees / $totalRepairs > 0.08)
                    <span class="text-xs text-white font-medium">{{ $repairsTerminees }}</span>
                    @endif
                </div>
                @endif
                @if($repairsEnCours > 0)
                <div class="h-full bg-orange-500 flex items-center justify-center" style="width: {{ $repairsEnCours / $totalRepairs * 100 }}%" title="En cours: {{ $repairsEnCours }}">
                    @if($repairsEnCours / $totalRepairs > 0.08)
                    <span class="text-xs text-white font-medium">{{ $repairsEnCours }}</span>
                    @endif
                </div>
                @endif
                @if($repairsEnAttente > 0)
                <div class="h-full bg-yellow-500 flex items-center justify-center" style="width: {{ $repairsEnAttente / $totalRepairs * 100 }}%" title="En attente: {{ $repairsEnAttente }}">
                    @if($repairsEnAttente / $totalRepairs > 0.08)
                    <span class="text-xs text-white font-medium">{{ $repairsEnAttente }}</span>
                    @endif
                </div>
                @endif
                @if($repairsAnnulees > 0)
                <div class="h-full bg-red-500 flex items-center justify-center" style="width: {{ $repairsAnnulees / $totalRepairs * 100 }}%" title="Annulées: {{ $repairsAnnulees }}">
                    @if($repairsAnnulees / $totalRepairs > 0.08)
                    <span class="text-xs text-white font-medium">{{ $repairsAnnulees }}</span>
                    @endif
                </div>
                @endif
            @endif
        </div>
        <div class="flex flex-wrap gap-4 mt-3">
            <span class="flex items-center gap-1 text-xs text-gray-600"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Terminées ({{ $repairsTerminees }})</span>
            <span class="flex items-center gap-1 text-xs text-gray-600"><span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span> En cours ({{ $repairsEnCours }})</span>
            <span class="flex items-center gap-1 text-xs text-gray-600"><span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span> En attente ({{ $repairsEnAttente }})</span>
            <span class="flex items-center gap-1 text-xs text-gray-600"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Annulées ({{ $repairsAnnulees }})</span>
        </div>
    </div>
</div>
@endsection
