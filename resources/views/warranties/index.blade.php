@extends('layouts.dashboard')
@section('page-title', 'Garanties')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Garanties pièces détachées</h1>
        <a href="#new-warranty" x-data @click.prevent="$dispatch('open-warranty-modal')"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nouvelle garantie
        </a>
    </div>

    {{-- Filtres statut --}}
    <div class="flex gap-2 flex-wrap">
        @foreach(['', 'active', 'expiree', 'utilisee'] as $s)
        <a href="{{ route('warranties.index', array_merge(request()->query(), ['statut' => $s])) }}"
            class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
            {{ request('statut', '') === $s ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
            {{ [''=>'Toutes','active'=>'Actives','expiree'=>'Expirées','utilisee'=>'Utilisées'][$s] }}
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Désignation</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Début</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Expiration</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($warranties as $warranty)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900 text-sm">{{ $warranty->designation }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ optional($warranty->client)->nom ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($warranty->statut === 'active')
                            @if($warranty->isActive())
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Expirée</span>
                            @endif
                        @elseif($warranty->statut === 'utilisee')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Utilisée</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-600">Expirée</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($warranty->date_debut)->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm {{ \Carbon\Carbon::parse($warranty->date_expiration)->isPast() && $warranty->statut === 'active' ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                        {{ \Carbon\Carbon::parse($warranty->date_expiration)->format('d/m/Y') }}
                        @if($warranty->statut === 'active' && !\Carbon\Carbon::parse($warranty->date_expiration)->isPast())
                        <span class="text-xs text-gray-400 ml-1">({{ \Carbon\Carbon::parse($warranty->date_expiration)->diffForHumans() }})</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('warranties.show', $warranty->id) }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('warranties.print', $warranty->id) }}" class="text-gray-400 hover:text-gray-700 text-sm" title="Imprimer certificat"><i class="fas fa-print"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-shield-alt text-3xl mb-3 block"></i>
                        Aucune garantie enregistrée.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($warranties, 'hasPages') && $warranties->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $warranties->withQueryString()->links() }}</div>
        @endif
    </div>

    {{-- Modal nouvelle garantie --}}
    <div x-data="{ open: false }" @open-warranty-modal.window="open = true">
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div @click.outside="open = false" class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-bold text-gray-900">Nouvelle garantie</h2>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="{{ route('warranties.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vente (ID) <span class="text-red-500">*</span></label>
                        <input type="text" name="sale_id" required placeholder="ID de la vente concernée"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 text-sm">
                        <p class="text-xs text-gray-400 mt-1">La vente doit être une pièce détachée.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Durée (jours) <span class="text-red-500">*</span></label>
                        <input type="number" name="duree_jours" min="1" max="3650" required value="90"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Conditions</label>
                        <textarea name="conditions" rows="2" placeholder="Ex: Hors casse, défaut fabrication uniquement..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 text-sm"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-save mr-2"></i> Enregistrer
                        </button>
                        <button type="button" @click="open = false" class="text-gray-600 px-5 py-2 rounded-lg border border-gray-300 text-sm">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
