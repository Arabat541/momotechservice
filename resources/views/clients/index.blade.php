@extends('layouts.dashboard')
@section('page-title', 'Clients')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $clients->total() }} client(s) enregistré(s)</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.export.pdf') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Exporter PDF
            </a>
            <a href="{{ route('clients.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-plus"></i> Nouveau client
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">Recherche</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Nom, téléphone..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous</option>
                    <option value="particulier" {{ ($type ?? '') === 'particulier' ? 'selected' : '' }}>Particulier</option>
                    <option value="revendeur" {{ ($type ?? '') === 'revendeur' ? 'selected' : '' }}>Revendeur</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-search mr-1"></i> Filtrer
            </button>
            @if($search || $type)
            <a href="{{ route('clients.index') }}" class="text-gray-500 hover:text-gray-700 px-4 py-2 rounded-lg text-sm border border-gray-300">
                <i class="fas fa-times mr-1"></i> Réinitialiser
            </a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nom</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Téléphone</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Boutique</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Solde crédit</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($clients as $client)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $client->nom }}</td>
                    <td class="px-6 py-4 text-gray-600 text-sm">{{ $client->telephone }}</td>
                    <td class="px-6 py-4">
                        @if($client->type === 'revendeur')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700">Revendeur</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Particulier</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500 text-sm">{{ $client->nom_boutique ?? '—' }}</td>
                    <td class="px-6 py-4 text-right">
                        @if($client->solde_credit > 0)
                            <span class="text-red-600 font-medium text-sm">{{ number_format($client->solde_credit, 0, ',', ' ') }} F</span>
                        @else
                            <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('clients.show', $client->id) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="Détail"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('clients.edit', $client->id) }}" class="text-gray-500 hover:text-gray-700 text-sm" title="Modifier"><i class="fas fa-edit"></i></a>
                            @if($client->type === 'revendeur')
                            <a href="{{ route('clients.dashboard', $client->id) }}" class="text-purple-600 hover:text-purple-800 text-sm" title="Dashboard revendeur"><i class="fas fa-chart-line"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-3xl mb-3 block"></i>
                        Aucun client trouvé.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($clients->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $clients->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
