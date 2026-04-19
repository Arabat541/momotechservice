@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white p-8 max-w-3xl mx-auto text-gray-900 text-sm">
    <div class="flex justify-between items-start mb-8">
        <div>
            <h2 class="text-xl font-bold">MOMO TECH SERVICE</h2>
        </div>
        <div class="text-right">
            <div class="text-xl font-bold text-gray-800">RAPPORT D'INVENTAIRE</div>
            <div class="text-gray-500 mt-1">{{ \Carbon\Carbon::parse($session->created_at)->format('d/m/Y') }}</div>
            <div class="text-gray-500">Clôturé le {{ $session->closed_at ? \Carbon\Carbon::parse($session->closed_at)->format('d/m/Y H:i') : '—' }}</div>
        </div>
    </div>

    <hr class="border-gray-300 mb-6">

    <div class="grid grid-cols-2 gap-6 mb-8">
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Réalisé par</div>
            <div>{{ optional($session->createdBy)->prenom }} {{ optional($session->createdBy)->nom }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-1">Clôturé par</div>
            <div>{{ optional($session->closedBy)->prenom }} {{ optional($session->closedBy)->nom ?? '—' }}</div>
        </div>
    </div>

    {{-- Résumé écarts --}}
    @php
        $nbEcarts = $session->lines->filter(fn($l) => $l->quantite_comptee !== null && $l->ecart != 0)->count();
        $nbOk = $session->lines->filter(fn($l) => $l->quantite_comptee !== null && $l->ecart == 0)->count();
        $pertes = $session->lines->filter(fn($l) => $l->ecart < 0)->sum('ecart');
        $surplus = $session->lines->filter(fn($l) => $l->ecart > 0)->sum('ecart');
    @endphp
    <div class="grid grid-cols-4 gap-4 mb-6 text-center">
        <div class="border border-gray-200 rounded p-3">
            <div class="text-xl font-bold">{{ $session->lines->count() }}</div>
            <div class="text-xs text-gray-500">Articles</div>
        </div>
        <div class="border border-green-200 rounded p-3 bg-green-50">
            <div class="text-xl font-bold text-green-700">{{ $nbOk }}</div>
            <div class="text-xs text-gray-500">Conformes</div>
        </div>
        <div class="border border-red-200 rounded p-3 bg-red-50">
            <div class="text-xl font-bold text-red-700">{{ abs($pertes) }}</div>
            <div class="text-xs text-gray-500">Pertes (unités)</div>
        </div>
        <div class="border border-yellow-200 rounded p-3 bg-yellow-50">
            <div class="text-xl font-bold text-yellow-700">{{ $surplus }}</div>
            <div class="text-xs text-gray-500">Surplus (unités)</div>
        </div>
    </div>

    <table class="w-full border-collapse mb-6">
        <thead>
            <tr class="bg-gray-100">
                <th class="text-left px-3 py-2 border border-gray-300">Article</th>
                <th class="text-right px-3 py-2 border border-gray-300">Théorique</th>
                <th class="text-right px-3 py-2 border border-gray-300">Compté</th>
                <th class="text-right px-3 py-2 border border-gray-300">Écart</th>
            </tr>
        </thead>
        <tbody>
            @foreach($session->lines as $line)
            @php $ecart = $line->ecart ?? ($line->quantite_comptee !== null ? $line->quantite_comptee - $line->quantite_theorique : null); @endphp
            <tr class="{{ $ecart !== null && $ecart < 0 ? 'bg-red-50' : ($ecart > 0 ? 'bg-yellow-50' : '') }}">
                <td class="px-3 py-2 border border-gray-200">{{ optional($line->stock)->nom ?? '—' }}</td>
                <td class="px-3 py-2 border border-gray-200 text-right">{{ $line->quantite_theorique }}</td>
                <td class="px-3 py-2 border border-gray-200 text-right">
                    {{ $line->quantite_comptee ?? '—' }}
                </td>
                <td class="px-3 py-2 border border-gray-200 text-right font-bold
                    {{ $ecart === null ? 'text-gray-400' : ($ecart < 0 ? 'text-red-700' : ($ecart > 0 ? 'text-yellow-700' : 'text-green-700')) }}">
                    @if($ecart !== null)
                        {{ $ecart > 0 ? '+' : '' }}{{ $ecart }}
                    @else —
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="grid grid-cols-2 gap-8 mt-10">
        <div class="text-center">
            <div class="border-t border-gray-400 pt-2 mt-12">Signature responsable inventaire</div>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-400 pt-2 mt-12">Signature patron</div>
        </div>
    </div>

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i> Imprimer
        </button>
    </div>
</div>
@endsection
