@extends('layouts.dashboard')
@section('page-title', 'Relances — Réparations non récupérées')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Relances clients</h1>
            <p class="text-gray-500 text-sm mt-1">Réparations terminées non récupérées</p>
        </div>
        <span class="bg-orange-100 text-orange-700 text-sm font-semibold px-3 py-1.5 rounded-full">
            {{ $repairs->count() }} en attente
        </span>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N° Réparation</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Appareil</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Terminée le</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Jours d'attente</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Relances</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Dernière relance</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($repairs as $repair)
                @php
                    $joursAttente = $repair->date_terminee ? (int) $repair->date_terminee->diffInDays(now()) : 0;
                    $urgence = $joursAttente >= 14 ? 'text-red-600 font-bold' : ($joursAttente >= 7 ? 'text-orange-600 font-semibold' : 'text-gray-700');
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('reparations.show', $repair->id) }}"
                           class="text-blue-600 hover:underline font-mono text-sm font-semibold">
                            {{ $repair->numeroReparation }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $repair->client_nom }}</div>
                        <div class="text-xs text-gray-500">{{ $repair->client_telephone }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $repair->appareil_marque_modele }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $repair->date_terminee?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm {{ $urgence }}">
                        {{ $joursAttente }} j
                    </td>
                    <td class="px-6 py-4">
                        @if($repair->relance_count === 0)
                            <span class="text-xs text-gray-400">Aucune</span>
                        @else
                            <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                {{ $repair->relance_count }}/2
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $repair->derniere_relance?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($repair->relance_count < 2)
                        <form method="POST" action="{{ route('relances.relancer', $repair->id) }}" class="inline">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('Envoyer un SMS de relance à {{ addslashes($repair->client_nom) }} ?')"
                                class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium px-3 py-1.5 rounded-lg flex items-center gap-1.5 whitespace-nowrap">
                                <i class="fas fa-sms"></i> Relancer
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-gray-400 italic">Max atteint</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-check-circle text-3xl mb-3 block text-green-400"></i>
                        Aucune réparation en attente de récupération.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <i class="fas fa-info-circle mr-2"></i>
        Les relances automatiques sont envoyées chaque matin à 9h (J+7 et J+14 après la fin de réparation).
        Vous pouvez aussi déclencher une relance manuellement depuis cette page.
    </div>
</div>
@endsection
