@extends('layouts.dashboard')
@section('page-title', 'Rapport de marge')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rapport de marge</h1>
            <p class="text-gray-500 text-sm mt-1">Coût pièces vs CA facturé sur les réparations soldées</p>
        </div>
        <a href="{{ route('margin.index', array_merge(request()->query(), ['export' => 'csv'])) }}"
           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-file-csv"></i> Exporter CSV
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" action="{{ route('margin.index') }}" class="flex flex-wrap gap-3 items-end">
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
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
        </form>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">CA total</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totaux['ca'], 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">cfa</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Coût pièces</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($totaux['cout_pieces'], 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">cfa</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Marge brute</p>
            <p class="text-2xl font-bold {{ $totaux['marge'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($totaux['marge'], 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">cfa</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Taux de marge</p>
            <p class="text-2xl font-bold {{ $totaux['taux_marge'] >= 50 ? 'text-green-600' : ($totaux['taux_marge'] >= 20 ? 'text-orange-500' : 'text-red-600') }}">
                {{ $totaux['taux_marge'] }}%
            </p>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N° Réparation</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Appareil</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">CA (cfa)</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Coût pièces</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Marge</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Taux</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($lignes as $l)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-3">
                        <a href="{{ route('reparations.show', $l['repair']->id) }}"
                           class="text-blue-600 hover:underline font-mono text-sm">{{ $l['repair']->numeroReparation }}</a>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-700">{{ $l['repair']->client_nom }}</td>
                    <td class="px-6 py-3 text-sm text-gray-700">{{ $l['repair']->appareil_marque_modele }}</td>
                    <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">{{ number_format($l['ca'], 0, ',', ' ') }}</td>
                    <td class="px-6 py-3 text-right text-sm text-red-600">{{ number_format($l['cout_pieces'], 0, ',', ' ') }}</td>
                    <td class="px-6 py-3 text-right text-sm font-semibold {{ $l['marge'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        {{ number_format($l['marge'], 0, ',', ' ') }}
                    </td>
                    <td class="px-6 py-3 text-right">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $l['taux_marge'] >= 50 ? 'bg-green-100 text-green-700' : ($l['taux_marge'] >= 20 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700') }}">
                            {{ $l['taux_marge'] }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-chart-bar text-3xl mb-3 block"></i>
                        Aucune réparation soldée sur cette période.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($lignes->count() > 0)
            <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr>
                    <td colspan="3" class="px-6 py-3 text-sm font-bold text-gray-700">TOTAL ({{ $lignes->count() }} réparations)</td>
                    <td class="px-6 py-3 text-right text-sm font-bold text-gray-900">{{ number_format($totaux['ca'], 0, ',', ' ') }}</td>
                    <td class="px-6 py-3 text-right text-sm font-bold text-red-600">{{ number_format($totaux['cout_pieces'], 0, ',', ' ') }}</td>
                    <td class="px-6 py-3 text-right text-sm font-bold {{ $totaux['marge'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($totaux['marge'], 0, ',', ' ') }}</td>
                    <td class="px-6 py-3 text-right">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                            {{ $totaux['taux_marge'] >= 50 ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                            {{ $totaux['taux_marge'] }}%
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
