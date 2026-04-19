@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white flex items-center justify-center p-8">
    <div class="max-w-lg w-full border-4 border-gray-800 rounded-2xl p-8 text-gray-900">

        {{-- En-tête --}}
        <div class="text-center mb-6">
            @if(isset($settings) && !empty($settings->companyInfo['nom']))
            <h1 class="text-2xl font-bold uppercase tracking-wide">{{ $settings->companyInfo['nom'] }}</h1>
            @else
            <h1 class="text-2xl font-bold uppercase tracking-wide">MOMO TECH SERVICE</h1>
            @endif
            <div class="text-sm text-gray-500 mt-1">{{ optional($settings)->companyInfo['adresse'] ?? '' }}</div>
            <div class="mt-4 border-t-2 border-b-2 border-gray-800 py-2">
                <h2 class="text-xl font-bold uppercase tracking-widest text-gray-700">Certificat de Garantie</h2>
            </div>
        </div>

        {{-- Désignation --}}
        <div class="text-center mb-6">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Article garanti</div>
            <div class="text-2xl font-bold text-gray-900">{{ $warranty->designation }}</div>
        </div>

        {{-- Client --}}
        @if($warranty->client)
        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-sm">
            <div class="font-semibold text-gray-800">{{ $warranty->client->nom }}</div>
            <div class="text-gray-500">{{ $warranty->client->telephone }}</div>
            @if($warranty->client->nom_boutique)
            <div class="text-gray-500">{{ $warranty->client->nom_boutique }}</div>
            @endif
        </div>
        @endif

        {{-- Période --}}
        <div class="grid grid-cols-3 gap-4 mb-6 text-center">
            <div class="border border-gray-200 rounded-lg p-3">
                <div class="text-xs text-gray-500 mb-1">Début</div>
                <div class="font-bold text-gray-900">{{ \Carbon\Carbon::parse($warranty->date_debut)->format('d/m/Y') }}</div>
            </div>
            <div class="border-2 border-gray-800 rounded-lg p-3 bg-gray-800 text-white">
                <div class="text-xs opacity-70 mb-1">Durée</div>
                <div class="font-bold text-lg">{{ $warranty->duree_jours }} j</div>
            </div>
            <div class="border border-gray-200 rounded-lg p-3">
                <div class="text-xs text-gray-500 mb-1">Expiration</div>
                <div class="font-bold text-gray-900">{{ \Carbon\Carbon::parse($warranty->date_expiration)->format('d/m/Y') }}</div>
            </div>
        </div>

        {{-- Conditions --}}
        @if($warranty->conditions)
        <div class="border-t border-gray-200 pt-4 mb-6 text-xs text-gray-600">
            <div class="font-semibold uppercase tracking-wide mb-1">Conditions de garantie</div>
            <p>{{ $warranty->conditions }}</p>
        </div>
        @elseif(isset($settings) && !empty($settings->warranty['conditions']))
        <div class="border-t border-gray-200 pt-4 mb-6 text-xs text-gray-600">
            <div class="font-semibold uppercase tracking-wide mb-1">Conditions de garantie</div>
            <p>{{ $settings->warranty['conditions'] }}</p>
        </div>
        @endif

        {{-- Statut --}}
        <div class="text-center">
            @if($warranty->statut === 'active' && $warranty->isActive())
            <span class="inline-block px-6 py-2 border-2 border-green-600 text-green-700 font-bold uppercase rounded-full tracking-widest text-sm">
                ✓ En vigueur
            </span>
            @elseif($warranty->statut === 'utilisee')
            <span class="inline-block px-6 py-2 border-2 border-gray-400 text-gray-500 font-bold uppercase rounded-full tracking-widest text-sm">
                Utilisée
            </span>
            @else
            <span class="inline-block px-6 py-2 border-2 border-red-500 text-red-600 font-bold uppercase rounded-full tracking-widest text-sm">
                Expirée
            </span>
            @endif
        </div>

        {{-- Signature --}}
        <div class="grid grid-cols-2 gap-8 mt-8 text-xs text-gray-500">
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-10">Signature technicien</div>
            </div>
            <div class="text-center">
                <div class="border-t border-gray-400 pt-2 mt-10">Cachet & signature</div>
            </div>
        </div>

        <div class="text-center mt-6 no-print">
            <button onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 text-sm">
                <i class="fas fa-print mr-2"></i> Imprimer le certificat
            </button>
        </div>
    </div>
</div>
@endsection
