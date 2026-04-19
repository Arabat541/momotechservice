@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white flex items-center justify-center p-8">
    <div class="text-center space-y-4">
        {{-- Contrôles --}}
        <div class="no-print mb-6 flex gap-3 justify-center">
            <a href="{{ route('reparations.show', $repair->id) }}" class="text-gray-500 hover:text-gray-700 text-sm border border-gray-300 px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-print mr-2"></i> Imprimer
            </button>
        </div>

        {{-- Étiquette --}}
        <div class="border-2 border-gray-800 rounded-xl p-6 inline-block min-w-64">
            {{-- En-tête boutique --}}
            <div class="text-center mb-4">
                @if(isset($settings) && !empty($settings->companyInfo['nom']))
                <div class="text-sm font-bold uppercase tracking-wide">{{ $settings->companyInfo['nom'] }}</div>
                @if(!empty($settings->companyInfo['telephone']))
                <div class="text-xs text-gray-500">{{ $settings->companyInfo['telephone'] }}</div>
                @endif
                @else
                <div class="text-sm font-bold uppercase tracking-wide">MOMO TECH SERVICE</div>
                @endif
            </div>

            <hr class="border-gray-300 mb-4">

            {{-- Info réparation --}}
            <div class="text-left space-y-1 mb-4 text-xs">
                <div class="flex justify-between gap-4">
                    <span class="text-gray-500">N° réparation</span>
                    <span class="font-bold font-mono">{{ $repair->numeroReparation }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-500">Appareil</span>
                    <span class="font-medium text-right max-w-32 truncate">{{ $repair->appareil_marque_modele }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-500">Client</span>
                    <span class="font-medium">{{ $repair->client_nom }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-500">Date</span>
                    <span>{{ \Carbon\Carbon::parse($repair->created_at)->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-500">Statut</span>
                    <span class="font-medium">{{ $repair->statut_reparation }}</span>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="flex justify-center mt-3 mb-2">
                {!! $qrSvg !!}
            </div>
            <div class="text-xs text-gray-400 text-center">Scannez pour suivre votre réparation</div>
        </div>
    </div>
</div>
@endsection
