@extends('layouts.base')

@section('body')
@php
    $company = $settings?->companyInfo ?? [];
    $warranty = $settings?->warranty ?? [];
    $pannes = is_array($repair->pannes_services) ? $repair->pannes_services : [];
    $pieces = is_array($repair->pieces_rechange_utilisees) ? $repair->pieces_rechange_utilisees : [];
    $isRdv = $repair->type_reparation === 'rdv';
@endphp

<div class="flex justify-center p-4 print:p-0">
    <div class="bg-white text-gray-800 text-sm w-[302px] p-3 shadow-lg border border-gray-300 print:shadow-none print:border-0">
        {{-- Header --}}
        <div class="text-center mb-2">
            <h2 class="text-lg font-bold uppercase">{{ $company['nom'] ?? 'MOMO TECH SERVICE' }}</h2>
            <p class="text-xs">{{ $company['adresse'] ?? '' }}</p>
            <p class="text-xs">Tél: {{ $company['telephone'] ?? '' }}</p>
            <p class="text-xs italic">{{ $company['slogan'] ?? '' }}</p>
        </div>

        {{-- Repair info --}}
        <div class="mb-2">
            <div class="flex justify-between">
                <span class="font-semibold">N° Réparation:</span>
                <span>{{ $repair->numeroReparation }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-semibold">Date création:</span>
                <span>{{ $repair->date_creation ? $repair->date_creation->format('d/m/Y') : 'N/A' }}</span>
            </div>
            @if($isRdv && $repair->date_rendez_vous)
            <div class="flex justify-between">
                <span class="font-semibold">Date RDV:</span>
                <span>{{ $repair->date_rendez_vous->format('d/m/Y') }}</span>
            </div>
            @endif
        </div>

        {{-- Client --}}
        <div class="mb-2 border-t border-b border-dashed border-gray-400 py-1">
            <p><span class="font-semibold">Client:</span> {{ $repair->client_nom }}</p>
            <p><span class="font-semibold">Téléphone:</span> {{ $repair->client_telephone }}</p>
            <p><span class="font-semibold">Appareil:</span> {{ $repair->appareil_marque_modele }}</p>
        </div>

        {{-- Pannes --}}
        @if(count($pannes) > 0)
        <div class="mb-2">
            <p class="font-semibold underline mb-0.5">Pannes / Services:</p>
            @foreach($pannes as $panne)
                @if(!empty($panne['description']) && ($panne['montant'] ?? 0) > 0)
                <div class="flex justify-between text-xs">
                    <span>- {{ $panne['description'] }}</span>
                    <span>{{ number_format($panne['montant'], 0, ',', ' ') }} cfa</span>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        {{-- Pièces --}}
        @if(count($pieces) > 0)
        <div class="mb-2">
            <p class="font-semibold underline mb-0.5">Pièces de rechange:</p>
            @foreach($pieces as $piece)
                @if(!empty($piece['nom']) && ($piece['quantiteUtilisee'] ?? 0) > 0)
                <div class="flex justify-between text-xs">
                    <span>- {{ $piece['nom'] }} (x{{ $piece['quantiteUtilisee'] }})</span>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        {{-- Totals --}}
        <div class="border-t border-gray-400 pt-1 mb-2">
            <div class="flex justify-between font-bold text-base">
                <span>TOTAL:</span>
                <span>{{ number_format($repair->total_reparation, 0, ',', ' ') }} cfa</span>
            </div>
            <div class="flex justify-between text-xs">
                <span>Payé:</span>
                <span>{{ number_format($repair->montant_paye, 0, ',', ' ') }} cfa</span>
            </div>
            <div class="flex justify-between text-xs font-semibold">
                <span>Reste à payer:</span>
                <span>{{ number_format($repair->reste_a_payer, 0, ',', ' ') }} cfa</span>
            </div>
        </div>

        {{-- Barcode area --}}
        <div class="text-center mb-2 py-2 border border-dashed border-gray-300">
            <span class="text-xs font-mono tracking-widest">{{ $repair->numeroReparation }}</span>
        </div>

        {{-- Footer --}}
        <div class="text-xs text-center border-t border-dashed border-gray-400 pt-1">
            <p class="font-semibold">Merci pour votre confiance!</p>
            <p>{{ $warranty['conditions'] ?? '' }} (Durée: {{ $warranty['duree'] ?? '7' }} jours)</p>
            <p class="font-bold mt-1">Statut: {{ $repair->statut_reparation }} | Paiement: {{ $repair->etat_paiement }}</p>
        </div>
    </div>
</div>

{{-- Auto print --}}
<script>window.onload = function() { window.print(); }</script>
@endsection
