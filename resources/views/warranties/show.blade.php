@extends('layouts.dashboard')
@section('page-title', 'Garantie')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('warranties.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $warranty->designation }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">Créée le {{ \Carbon\Carbon::parse($warranty->created_at)->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($warranty->statut === 'active' && $warranty->isActive())
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-700">Active</span>
            @elseif($warranty->statut === 'utilisee')
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-gray-100 text-gray-600">Utilisée</span>
            @else
                <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-600">Expirée</span>
            @endif
            <a href="{{ route('warranties.print', $warranty->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-print mr-1"></i> Certificat
            </a>
        </div>
    </div>

    {{-- Infos --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-3 font-semibold">Client</div>
            @if($warranty->client)
            <div class="font-semibold text-gray-900">{{ $warranty->client->nom }}</div>
            <div class="text-sm text-gray-500">{{ $warranty->client->telephone }}</div>
            @else
            <div class="text-gray-400 text-sm">Non renseigné</div>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-3 font-semibold">Article</div>
            @if($warranty->sale && $warranty->sale->stock)
            <div class="font-semibold text-gray-900">{{ $warranty->sale->stock->nom }}</div>
            <div class="text-sm text-gray-500">{{ optional($warranty->sale->stock)->categorie }}</div>
            @else
            <div class="text-gray-400 text-sm">—</div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
        <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Début</div>
                <div class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($warranty->date_debut)->format('d/m/Y') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Expiration</div>
                <div class="font-semibold {{ \Carbon\Carbon::parse($warranty->date_expiration)->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                    {{ \Carbon\Carbon::parse($warranty->date_expiration)->format('d/m/Y') }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Durée</div>
                <div class="font-semibold text-gray-900">{{ $warranty->duree_jours }} jours</div>
            </div>
        </div>

        @if($warranty->conditions)
        <div class="border-t border-gray-100 pt-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Conditions</div>
            <p class="text-sm text-gray-700">{{ $warranty->conditions }}</p>
        </div>
        @endif

        @if($warranty->notes_utilisation)
        <div class="border-t border-gray-100 pt-4">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Notes d'utilisation</div>
            <p class="text-sm text-gray-700">{{ $warranty->notes_utilisation }}</p>
        </div>
        @endif
    </div>

    {{-- Utiliser --}}
    @if($warranty->statut === 'active' && $warranty->isActive())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 text-orange-700 font-medium">
            <i class="fas fa-tools"></i> Utiliser cette garantie (remplacement)
            <i class="fas fa-chevron-down text-xs" :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-transition class="mt-4">
            <form method="POST" action="{{ route('warranties.utiliser', $warranty->id) }}" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes sur l'utilisation</label>
                    <textarea name="notes" rows="2" required placeholder="Décrivez le défaut constaté..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-500"></textarea>
                </div>
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
                    onclick="return confirm('Marquer cette garantie comme utilisée ? Cette action est irréversible.')">
                    <i class="fas fa-check mr-1"></i> Confirmer l'utilisation
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
