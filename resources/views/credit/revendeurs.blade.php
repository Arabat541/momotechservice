@extends('layouts.dashboard')
@section('page-title', 'Revendeurs — Crédits')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Revendeurs</h1>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex flex-wrap gap-2">
            @foreach(['tous' => 'Tous', 'depassement' => 'Dépassement limite', 'solde_zero' => 'Solde = 0'] as $val => $label)
            <a href="{{ route('credits.revendeurs', ['filtre' => $val]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
                      {{ $filtre === $val ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Limite (F)</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Solde dû (F)</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Disponible (F)</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Points</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Bonus (%)</th>
                    <th class="text-center px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($revendeurs as $client)
                @php
                    $depasse = $client->solde_credit > $client->credit_limite;
                    $points  = $client->revendeur?->points_fidelite ?? 0;
                    $taux    = $client->revendeur?->bonus_annuel_taux ?? 0;
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('clients.show', $client->id) }}" class="text-blue-600 hover:underline font-medium text-sm">
                            {{ $client->nom }}
                        </a>
                        @if($client->nom_boutique)
                        <div class="text-xs text-gray-400">{{ $client->nom_boutique }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-700">
                        {{ number_format($client->credit_limite, 0, ',', ' ') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-semibold {{ $depasse ? 'text-red-600' : ($client->solde_credit > 0 ? 'text-amber-600' : 'text-gray-500') }}">
                        {{ number_format($client->solde_credit, 0, ',', ' ') }}
                        @if($depasse)
                        <span class="ml-1 px-1.5 py-0.5 bg-red-100 text-red-700 text-xs rounded font-medium">!</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm {{ $client->creditDisponible() > 0 ? 'text-green-600' : 'text-gray-400' }}">
                        {{ number_format($client->creditDisponible(), 0, ',', ' ') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-indigo-700 font-medium">
                        {{ number_format($points, 0, ',', ' ') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-600">
                        {{ $taux > 0 ? number_format($taux, 2, ',', '.') . ' %' : '—' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('credits.releve-pdf', ['client' => $client->id]) }}"
                               target="_blank"
                               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1">
                                <i class="fas fa-file-pdf text-red-500"></i> Relevé PDF
                            </a>
                            @if($client->solde_credit > 0)
                            <a href="{{ route('clients.show', $client->id) }}#remboursement"
                               class="bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1">
                                <i class="fas fa-hand-holding-usd"></i> Rembourser
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-3xl mb-3 block"></i>
                        Aucun revendeur trouvé.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($revendeurs->count() > 0)
            <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                <tr>
                    <td class="px-6 py-3 text-sm font-semibold text-gray-700">Total ({{ $revendeurs->count() }})</td>
                    <td class="px-6 py-3 text-right text-sm font-semibold text-gray-700">
                        {{ number_format($revendeurs->sum('credit_limite'), 0, ',', ' ') }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm font-semibold text-red-600">
                        {{ number_format($revendeurs->sum('solde_credit'), 0, ',', ' ') }}
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
