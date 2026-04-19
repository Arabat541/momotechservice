@extends('layouts.base')

@section('body')
<div class="bg-white min-h-screen p-6">
    {{-- Contrôles --}}
    <div class="no-print mb-6 flex gap-3 items-center">
        <button onclick="history.back()" class="text-gray-500 hover:text-gray-700 text-sm border border-gray-300 px-4 py-2 rounded-lg">
            <i class="fas fa-arrow-left mr-1"></i> Retour
        </button>
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 text-sm">
            <i class="fas fa-print mr-2"></i> Imprimer les {{ count($items) }} étiquettes
        </button>
        <span class="text-sm text-gray-500">{{ count($items) }} étiquette(s)</span>
    </div>

    {{-- Grille d'étiquettes --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 print:grid-cols-3 print:gap-3">
        @foreach($items as $item)
        <div class="border-2 border-gray-700 rounded-xl p-4 text-center break-inside-avoid">
            {{-- En-tête boutique --}}
            @if(isset($settings) && !empty($settings->companyInfo['nom']))
            <div class="text-xs font-bold uppercase tracking-wide">{{ $settings->companyInfo['nom'] }}</div>
            @else
            <div class="text-xs font-bold uppercase tracking-wide">MOMO TECH SERVICE</div>
            @endif

            <hr class="border-gray-300 my-2">

            {{-- Info réparation --}}
            <div class="text-left space-y-0.5 mb-3 text-xs">
                <div class="flex justify-between gap-2">
                    <span class="text-gray-500">N°</span>
                    <span class="font-bold font-mono">{{ $item['repair']->numeroReparation }}</span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-gray-500">Appareil</span>
                    <span class="font-medium text-right max-w-28 truncate text-xs">{{ $item['repair']->appareil_marque_modele }}</span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-gray-500">Client</span>
                    <span class="font-medium truncate max-w-28 text-xs">{{ $item['repair']->client_nom }}</span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-gray-500">Date</span>
                    <span class="text-xs">{{ \Carbon\Carbon::parse($item['repair']->created_at)->format('d/m/Y') }}</span>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="flex justify-center">
                {!! $item['qrSvg'] !!}
            </div>
            <div class="text-xs text-gray-400 mt-1">Suivi en ligne</div>
        </div>
        @endforeach
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { margin: 0; padding: 10mm; }
    .grid { grid-template-columns: repeat(3, 1fr); }
}
</style>
@endsection
