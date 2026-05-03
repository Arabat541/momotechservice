@extends('layouts.dashboard')
@section('page-title', 'Rapport Ventes')

@section('content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rapport des ventes</h1>
            <p class="text-gray-500 text-sm mt-1">Ventes d'articles par période</p>
        </div>
        <a href="{{ route('reports.ventes.pdf', request()->query()) }}" target="_blank"
           class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-file-pdf"></i> Exporter PDF
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Du</label>
                <input type="date" name="debut" value="{{ $debut }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Au</label>
                <input type="date" name="fin" value="{{ $fin }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Boutique</label>
                <select name="boutique_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Toutes les boutiques</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ $boutiqueId == $shop->id ? 'selected' : '' }}>{{ $shop->nom }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter mr-1"></i>Filtrer
            </button>
        </form>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">CA total</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCA, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Encaissé</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($totalEncaisse, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">En attente crédit</p>
            <p class="text-2xl font-bold {{ $totalCredit > 0 ? 'text-orange-500' : 'text-gray-400' }}">{{ number_format($totalCredit, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Nb ventes</p>
            <p class="text-2xl font-bold text-blue-600">{{ $nbVentes }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top 10 articles --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Top 10 articles vendus</h3>
            </div>
            @if($top10->isEmpty())
                <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucune vente sur cette période.</p>
            @else
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Article</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qté</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">CA (F)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($top10 as $i => $art)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $art['nom'] }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $art['quantite'] }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">{{ number_format($art['ca'], 0, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Répartition comptant / crédit --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Répartition par mode de paiement</h3>
            @php
                $comptant = $ventes->where('mode_paiement', 'comptant')->count();
                $credit   = $ventes->where('mode_paiement', 'credit')->count();
                $caComptant = $ventes->where('mode_paiement', 'comptant')->sum('total');
                $caCredit   = $ventes->where('mode_paiement', 'credit')->sum('total');
            @endphp
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-sm font-medium text-gray-700">Comptant</span>
                        <span class="text-xs text-gray-400">({{ $comptant }} ventes)</span>
                    </div>
                    <span class="font-bold text-green-700">{{ number_format($caComptant, 0, ',', ' ') }} F</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                        <span class="text-sm font-medium text-gray-700">Crédit revendeur</span>
                        <span class="text-xs text-gray-400">({{ $credit }} ventes)</span>
                    </div>
                    <span class="font-bold text-purple-700">{{ number_format($caCredit, 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau détaillé --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Détail des ventes</h3>
            <span class="text-xs text-gray-400">{{ $ventes->count() }} vente(s)</span>
        </div>
        @if($ventes->isEmpty())
            <p class="px-6 py-12 text-center text-gray-400"><i class="fas fa-receipt text-3xl mb-3 block"></i>Aucune vente sur cette période.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Article</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mode</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qté</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Encaissé (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Crédit (F)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($ventes as $v)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $v->nom }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $v->client ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if($v->mode_paiement === 'credit')
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700">Crédit</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Comptant</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $v->quantite }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($v->total, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700">{{ number_format($v->montant_paye, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right {{ $v->reste_credit > 0 ? 'text-orange-600 font-medium' : 'text-gray-300' }}">
                            {{ $v->reste_credit > 0 ? number_format($v->reste_credit, 0, ',', ' ') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $v->shop?->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $v->date->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700">TOTAL</td>
                        <td class="px-4 py-3 text-sm text-right font-bold">{{ number_format($totalCA, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-green-700">{{ number_format($totalEncaisse, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-orange-600">{{ number_format($totalCredit, 0, ',', ' ') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
