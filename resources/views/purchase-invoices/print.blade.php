@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white p-8 max-w-2xl mx-auto text-gray-900 text-sm">
    <div class="flex justify-between items-start mb-8">
        <div>
            <h2 class="text-xl font-bold">MOMO TECH SERVICE</h2>
        </div>
        <div class="text-right">
            <div class="text-xl font-bold text-gray-800">BON DE RÉCEPTION / FACTURE</div>
            <div class="font-mono text-blue-700 mt-1">{{ $invoice->numero }}</div>
            <div class="text-gray-500 mt-1">{{ \Carbon\Carbon::parse($invoice->date_facture)->format('d/m/Y') }}</div>
        </div>
    </div>

    <hr class="border-gray-300 mb-6">

    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-2">Fournisseur</div>
            <div class="font-semibold">{{ optional($invoice->supplier)->nom }}</div>
            @if($invoice->supplier && $invoice->supplier->telephone)
            <div class="text-gray-600">{{ $invoice->supplier->telephone }}</div>
            @endif
            @if($invoice->supplier && $invoice->supplier->contact_nom)
            <div class="text-gray-600">Contact : {{ $invoice->supplier->contact_nom }}</div>
            @endif
        </div>
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-2">Échéance</div>
            <div class="{{ $invoice->date_echeance && $invoice->statut !== 'soldee' && \Carbon\Carbon::parse($invoice->date_echeance)->isPast() ? 'font-bold text-red-700' : '' }}">
                {{ $invoice->date_echeance ? \Carbon\Carbon::parse($invoice->date_echeance)->format('d/m/Y') : '—' }}
            </div>
        </div>
    </div>

    @if($invoice->lines && $invoice->lines->count() > 0)
    <table class="w-full border-collapse mb-6">
        <thead>
            <tr class="bg-gray-100">
                <th class="text-left px-3 py-2 border border-gray-300">Désignation</th>
                <th class="text-right px-3 py-2 border border-gray-300">Qté</th>
                <th class="text-right px-3 py-2 border border-gray-300">Prix unit.</th>
                <th class="text-right px-3 py-2 border border-gray-300">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $line)
            <tr>
                <td class="px-3 py-2 border border-gray-200">{{ $line->designation }}</td>
                <td class="px-3 py-2 border border-gray-200 text-right">{{ $line->quantite }}</td>
                <td class="px-3 py-2 border border-gray-200 text-right">{{ number_format($line->prix_unitaire, 0, ',', ' ') }} F</td>
                <td class="px-3 py-2 border border-gray-200 text-right font-medium">{{ number_format($line->quantite * $line->prix_unitaire, 0, ',', ' ') }} F</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 font-bold">
                <td colspan="3" class="px-3 py-2 border border-gray-300 text-right">TOTAL</td>
                <td class="px-3 py-2 border border-gray-300 text-right">{{ number_format($invoice->montant_total, 0, ',', ' ') }} F</td>
            </tr>
            <tr>
                <td colspan="3" class="px-3 py-2 border border-gray-200 text-right text-green-700">Payé</td>
                <td class="px-3 py-2 border border-gray-200 text-right text-green-700">{{ number_format($invoice->montant_paye, 0, ',', ' ') }} F</td>
            </tr>
            <tr class="font-bold {{ $invoice->reste_a_payer > 0 ? 'bg-red-50' : 'bg-green-50' }}">
                <td colspan="3" class="px-3 py-2 border border-gray-300 text-right">RESTE À PAYER</td>
                <td class="px-3 py-2 border border-gray-300 text-right {{ $invoice->reste_a_payer > 0 ? 'text-red-700' : 'text-green-700' }}">
                    {{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} F
                </td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i> Imprimer
        </button>
    </div>
</div>
@endsection
