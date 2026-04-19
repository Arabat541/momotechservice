@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white p-8 max-w-2xl mx-auto text-gray-900">
    {{-- En-tête entreprise --}}
    <div class="flex justify-between items-start mb-8">
        <div>
            @if(isset($settings) && !empty($settings->companyInfo['nom']))
            <h2 class="text-xl font-bold">{{ $settings->companyInfo['nom'] }}</h2>
            <p class="text-sm text-gray-500">{{ $settings->companyInfo['adresse'] ?? '' }}</p>
            <p class="text-sm text-gray-500">Tél : {{ $settings->companyInfo['telephone'] ?? '' }}</p>
            @else
            <h2 class="text-xl font-bold">MOMO TECH SERVICE</h2>
            @endif
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold text-gray-800">FACTURE</div>
            <div class="text-lg font-mono text-blue-700 mt-1">{{ $invoice->numero_facture }}</div>
            <div class="text-sm text-gray-500 mt-1">{{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}</div>
        </div>
    </div>

    <hr class="border-gray-300 mb-6">

    {{-- Client --}}
    <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-2">Client</div>
            @if($invoice->client)
            <div class="font-semibold">{{ $invoice->client->nom }}</div>
            <div class="text-gray-600">{{ $invoice->client->telephone }}</div>
            @else
            <div class="text-gray-400">Non renseigné</div>
            @endif
        </div>
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-2">Réparation</div>
            @if($invoice->repair)
            <div class="font-mono font-semibold">{{ $invoice->repair->numeroReparation }}</div>
            <div class="text-gray-600">{{ $invoice->repair->appareil_marque_modele }}</div>
            @endif
        </div>
    </div>

    {{-- Détail pannes --}}
    @if($invoice->repair && !empty($invoice->repair->pannes_services))
    <div class="mb-6">
        <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-2">Prestations</div>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="text-left px-3 py-2 border border-gray-300">Description</th>
                    <th class="text-right px-3 py-2 border border-gray-300">Prix</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->repair->pannes_services as $panne)
                <tr>
                    <td class="px-3 py-2 border border-gray-200">{{ $panne['nom'] ?? $panne }}</td>
                    <td class="px-3 py-2 border border-gray-200 text-right">{{ isset($panne['prix']) ? number_format($panne['prix'], 0, ',', ' ') . ' F' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Totaux --}}
    <div class="ml-auto w-64 text-sm">
        <div class="divide-y divide-gray-200 border border-gray-200 rounded">
            <div class="flex justify-between px-4 py-2">
                <span class="text-gray-600">Montant total</span>
                <span class="font-semibold">{{ number_format($invoice->montant_final ?? $invoice->montant_estime, 0, ',', ' ') }} F</span>
            </div>
            <div class="flex justify-between px-4 py-2">
                <span class="text-gray-600">Acompte versé</span>
                <span class="text-green-600">{{ number_format($invoice->montant_paye, 0, ',', ' ') }} F</span>
            </div>
            <div class="flex justify-between px-4 py-3 font-bold text-base {{ $invoice->reste_a_payer > 0 ? 'bg-red-50' : 'bg-green-50' }}">
                <span>Reste à payer</span>
                <span class="{{ $invoice->reste_a_payer > 0 ? 'text-red-700' : 'text-green-700' }}">{{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} F</span>
            </div>
        </div>
    </div>

    {{-- Statut --}}
    <div class="mt-6 text-center">
        @if($invoice->statut === 'soldee')
        <span class="text-green-600 font-bold text-lg border-2 border-green-600 px-4 py-1 rounded">SOLDÉE</span>
        @elseif($invoice->statut === 'partielle')
        <span class="text-yellow-600 font-bold text-lg border-2 border-yellow-600 px-4 py-1 rounded">PAIEMENT PARTIEL</span>
        @else
        <span class="text-red-600 font-bold text-lg border-2 border-red-600 px-4 py-1 rounded">EN ATTENTE DE PAIEMENT</span>
        @endif
    </div>

    @if(isset($settings) && !empty($settings->warranty['conditions']))
    <div class="mt-8 text-xs text-gray-500 border-t pt-4">
        <strong>Conditions de garantie :</strong> {{ $settings->warranty['conditions'] }}
    </div>
    @endif

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i> Imprimer
        </button>
    </div>
</div>
@endsection
