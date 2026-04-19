@extends('layouts.dashboard')
@section('page-title', 'Appareils non récupérés')

@section('content')
<div class="space-y-6" x-data="{ openVente: null, openDate: null }">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Appareils non récupérés</h1>
            <p class="text-gray-500 text-sm mt-1">Réparations terminées sans récupération depuis plus de {{ $delai }} jours</p>
        </div>
        <form method="GET" action="{{ route('abandons.index') }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-600">Délai :</label>
            <select name="delai" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                @foreach([15, 30, 45, 60, 90] as $d)
                <option value="{{ $d }}" {{ $delai == $d ? 'selected' : '' }}>{{ $d }} jours</option>
                @endforeach
            </select>
        </form>
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
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Délai dépassé</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date limite</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($repairs as $repair)
                @php
                    $refDate = $repair->date_terminee ?? $repair->date_creation;
                    $joursRetard = $refDate ? (int) \Carbon\Carbon::parse($refDate)->diffInDays(now()) : 0;
                @endphp
                <tr class="hover:bg-gray-50">
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
                        {{ $repair->date_terminee?->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold {{ $joursRetard >= 60 ? 'text-red-600' : 'text-orange-500' }}">
                        {{ $joursRetard }} j
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($repair->date_limite_recuperation)
                        <span class="{{ $repair->date_limite_recuperation->isPast() ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                            {{ $repair->date_limite_recuperation->format('d/m/Y') }}
                        </span>
                        @else
                        <button @click="openDate = openDate === '{{ $repair->id }}' ? null : '{{ $repair->id }}'"
                            class="text-blue-600 hover:underline text-xs">Fixer</button>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($repair->mis_en_vente)
                        <span class="bg-purple-100 text-purple-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            Mis en vente
                        </span>
                        @else
                        <span class="bg-orange-100 text-orange-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                            En attente
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if(!$repair->mis_en_vente)
                        <button @click="openVente = openVente === '{{ $repair->id }}' ? null : '{{ $repair->id }}'"
                            class="text-sm text-purple-600 hover:text-purple-800 font-medium whitespace-nowrap">
                            <i class="fas fa-tag mr-1"></i>Mettre en vente
                        </button>
                        @endif
                    </td>
                </tr>

                {{-- Formulaire date limite --}}
                <tr x-show="openDate === '{{ $repair->id }}'" x-transition>
                    <td colspan="8" class="px-6 py-3 bg-blue-50 border-t border-blue-100">
                        <form method="POST" action="{{ route('abandons.date-limite', $repair->id) }}"
                              class="flex items-center gap-3">
                            @csrf
                            <span class="text-sm text-blue-800 font-medium">Date limite de récupération :</span>
                            <input type="date" name="date_limite_recuperation" required
                                min="{{ now()->addDay()->toDateString() }}"
                                class="border border-blue-300 rounded-lg px-3 py-1.5 text-sm">
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg text-sm">
                                Enregistrer
                            </button>
                            <button type="button" @click="openDate = null" class="text-gray-400 hover:text-gray-600 text-sm">
                                Annuler
                            </button>
                        </form>
                    </td>
                </tr>

                {{-- Formulaire mise en vente --}}
                <tr x-show="openVente === '{{ $repair->id }}'" x-transition>
                    <td colspan="8" class="px-6 py-4 bg-purple-50 border-t border-purple-100">
                        <form method="POST" action="{{ route('abandons.mettre-en-vente', $repair->id) }}"
                              class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                            @csrf
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nom article <span class="text-red-500">*</span></label>
                                <input type="text" name="nom_article" required maxlength="200"
                                    value="{{ $repair->appareil_marque_modele }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Catégorie <span class="text-red-500">*</span></label>
                                <select name="categorie" required
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="telephone">Téléphone</option>
                                    <option value="accessoire">Accessoire</option>
                                    <option value="piece_detachee">Pièce détachée</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Prix de vente (cfa) <span class="text-red-500">*</span></label>
                                <input type="number" name="prix_vente" required min="0"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                    <i class="fas fa-tag mr-1"></i> Créer l'article
                                </button>
                                <button type="button" @click="openVente = null"
                                    class="text-gray-500 hover:text-gray-700 text-sm border border-gray-300 px-3 py-2 rounded-lg">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-check-circle text-3xl mb-3 block text-green-400"></i>
                        Aucun appareil en attente de récupération depuis plus de {{ $delai }} jours.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
