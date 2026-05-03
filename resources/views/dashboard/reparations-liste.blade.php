@extends('layouts.dashboard')

@section('page-title', 'Liste des Réparations')

@section('content')
<div class="space-y-6 p-4 sm:p-6 bg-white rounded-xl shadow-2xl">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Liste de Toutes les Réparations</h1>
        <a href="{{ route('reparations.export.csv') }}" class="inline-flex items-center px-4 py-2 text-sm border rounded-md hover:bg-gray-50">
            <i class="fas fa-download mr-2"></i> Exporter CSV
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reparations.liste') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div>
            <label class="text-sm font-medium text-gray-700">Rechercher</label>
            <div class="relative mt-1">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Client, Appareil, N°..."
                       class="pl-10 w-full text-sm py-2 border-gray-300 rounded-md border px-3">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Type</label>
            <select name="type" class="w-full mt-1 text-sm py-2 border-gray-300 rounded-md border px-3">
                <option value="">Tous les types</option>
                <option value="place" {{ $type === 'place' ? 'selected' : '' }}>Sur Place</option>
                <option value="rdv"   {{ $type === 'rdv'   ? 'selected' : '' }}>Sur RDV</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Statut</label>
            <select name="statut" class="w-full mt-1 text-sm py-2 border-gray-300 rounded-md border px-3">
                <option value="">Tous les statuts</option>
                @foreach($allStatuts as $s)
                    <option value="{{ $s }}" {{ $statut === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            <a href="{{ route('reparations.liste') }}" class="px-3 py-2 text-sm border rounded-md hover:bg-gray-100" title="Réinitialiser">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-slate-100 to-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Réparation</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appareil</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total (fcfa)</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Récupéré ?</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($repairs as $repair)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm font-medium text-blue-600">
                        <a href="{{ route('reparations.show', $repair->id) }}" class="hover:underline">{{ $repair->numeroReparation }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $repair->type_reparation === 'place' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $repair->type_reparation === 'place' ? 'Sur Place' : 'Sur RDV' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div>{{ $repair->client_nom }}</div>
                        <div class="text-xs text-gray-500">{{ $repair->client_telephone }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ $repair->appareil_marque_modele }}</td>
                    <td class="px-4 py-3 text-sm text-right font-semibold">{{ number_format($repair->total_reparation, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm">{{ $repair->date_creation ? $repair->date_creation->format('d/m/Y') : 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <form action="{{ route('reparations.update', $repair->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PUT')
                            <select name="statut_reparation" onchange="this.form.submit()"
                                    class="text-xs py-1 px-2 rounded-md border {{ $repairSvc->badgeClasses($repair->statut_reparation) }}">
                                @foreach($allStatuts as $s)
                                    <option value="{{ $s }}" {{ $repair->statut_reparation === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="px-4 py-3">
                        <form action="{{ route('reparations.update', $repair->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PUT')
                            @if($repair->date_retrait)
                                <input type="hidden" name="unmark_retrieved" value="1">
                                <button type="submit" class="text-xs px-2 py-1 rounded-md bg-green-100 text-green-700 border border-green-200 hover:bg-green-200">Oui</button>
                            @else
                                <input type="hidden" name="mark_retrieved" value="1">
                                <button type="submit" class="text-xs px-2 py-1 rounded-md bg-red-100 text-red-700 border border-red-200 hover:bg-red-200">Non</button>
                            @endif
                        </form>
                    </td>
                    <td class="px-4 py-3 text-center space-x-1">
                        <a href="{{ route('reparations.show', $repair->id) }}" class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form action="{{ route('reparations.destroy', $repair->id) }}" method="POST" class="inline"
                              onsubmit="return confirm('Supprimer cette réparation ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-800 hover:bg-red-50 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-gray-500">
                        <i class="fas fa-list text-4xl mb-2 block text-gray-300"></i>
                        Aucune réparation trouvée.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 px-4">
        {{ $repairs->links() }}
    </div>
</div>
@endsection
