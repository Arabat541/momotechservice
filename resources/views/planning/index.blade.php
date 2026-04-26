@extends('layouts.dashboard')
@section('page-title', 'Planning réparateurs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Planning réparateurs</h1>
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Réparateur</label>
                <select name="technicien" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    @foreach($techniciens as $t)
                    <option value="{{ $t->id }}" {{ ($techId ?? '') === $t->id ? 'selected' : '' }}>{{ $t->prenom }} {{ $t->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Semaine du</label>
                <input type="date" name="semaine" value="{{ $debut->toDateString() }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
        </form>
    </div>

    {{-- Non assignées --}}
    @if(isset($nonAssignees) && $nonAssignees->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-orange-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-orange-100 bg-orange-50 flex items-center gap-2">
            <i class="fas fa-exclamation-circle text-orange-500"></i>
            <h2 class="font-semibold text-orange-800">Réparations non assignées ({{ $nonAssignees->count() }})</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Appareil</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($nonAssignees as $repair)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('reparations.show', $repair->id) }}" class="text-blue-600 hover:underline font-mono text-sm">{{ $repair->numeroReparation }}</a>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-700">{{ $repair->appareil_marque_modele }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">{{ $repair->statut_reparation }}</span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($repair->created_at)->format('d/m/Y') }}</td>
                    <td class="px-6 py-3">
                        <form method="POST" action="{{ route('planning.assigner', $repair->id) }}" class="flex items-center gap-2">
                            @csrf
                            <select name="assigned_to" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500">
                                <option value="">Assigner à...</option>
                                @foreach($techniciens as $tech)
                                <option value="{{ $tech->id }}">{{ $tech->prenom }} {{ $tech->nom }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1.5 rounded text-xs font-medium">OK</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Par réparateur --}}
    @if(!empty($planning))
    @foreach($planning as $techEntryId => $data)
    @php
        $tech = $data['technicien'];
        $reparations = $data['reparations'];
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 font-bold text-sm">
                    {{ strtoupper(substr($tech->prenom ?? '?', 0, 1)) }}
                </div>
                <h2 class="font-semibold text-gray-900">{{ $tech->prenom }} {{ $tech->nom }}</h2>
            </div>
            <span class="text-sm text-gray-500">{{ $reparations->count() }} réparation(s)</span>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Appareil</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($reparations as $repair)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('reparations.show', $repair->id) }}" class="text-blue-600 hover:underline font-mono text-sm">{{ $repair->numeroReparation }}</a>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-700">{{ $repair->appareil_marque_modele }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $repair->statut_reparation === 'Terminé' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $repair->statut_reparation }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($repair->created_at)->format('d/m/Y') }}</td>
                    <td class="px-6 py-3">
                        <form method="POST" action="{{ route('planning.assigner', $repair->id) }}" class="flex items-center gap-2 justify-end">
                            @csrf
                            <select name="assigned_to" class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-500">
                                <option value="">Désassigner</option>
                                @foreach($techniciens as $t)
                                <option value="{{ $t->id }}" {{ $repair->assigned_to === $t->id ? 'selected' : '' }}>{{ $t->prenom }} {{ $t->nom }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1.5 rounded text-xs font-medium">Changer</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @elseif(!isset($nonAssignees) || $nonAssignees->count() === 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
        <i class="fas fa-calendar-check text-3xl mb-3 block"></i>
        Aucune réparation trouvée pour cette période.
    </div>
    @endif
</div>
@endsection
