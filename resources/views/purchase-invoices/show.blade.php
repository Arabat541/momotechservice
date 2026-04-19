@extends('layouts.dashboard')
@section('page-title', 'Facture fournisseur')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase-invoices.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $invoice->numero }}</h1>
                <p class="text-gray-500 text-sm">{{ optional($invoice->supplier)->nom }} — {{ \Carbon\Carbon::parse($invoice->date_facture)->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 text-sm font-medium rounded-full
                {{ $invoice->statut === 'soldee' ? 'bg-green-100 text-green-700' : ($invoice->statut === 'partiellement_payee' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                {{ ['soldee'=>'Soldée','partiellement_payee'=>'Partielle','en_attente'=>'En attente'][$invoice->statut] ?? $invoice->statut }}
            </span>
            <a href="{{ route('purchase-invoices.print', $invoice->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-print mr-1"></i> Imprimer
            </a>
        </div>
    </div>

    {{-- Dates --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Date facture</div>
            <div class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($invoice->date_facture)->format('d/m/Y') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Échéance</div>
            <div class="font-semibold {{ $invoice->date_echeance && $invoice->statut !== 'soldee' && \Carbon\Carbon::parse($invoice->date_echeance)->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                {{ $invoice->date_echeance ? \Carbon\Carbon::parse($invoice->date_echeance)->format('d/m/Y') : '—' }}
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Reste à payer</div>
            <div class="font-bold text-lg {{ $invoice->reste_a_payer > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} F
            </div>
        </div>
    </div>

    {{-- Lignes --}}
    @if($invoice->lines && $invoice->lines->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Lignes de facture</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Désignation</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Qté</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Prix unit.</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Sous-total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($invoice->lines as $line)
                <tr>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ optional($line->stock)->nom ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-gray-800">{{ $line->designation }}</td>
                    <td class="px-6 py-3 text-right text-sm">{{ $line->quantite }}</td>
                    <td class="px-6 py-3 text-right text-sm">{{ number_format($line->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-3 text-right text-sm font-medium">{{ number_format($line->quantite * $line->prix_unitaire, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr>
                    <td colspan="4" class="px-6 py-3 text-right font-semibold text-gray-700">Total</td>
                    <td class="px-6 py-3 text-right font-bold text-gray-900">{{ number_format($invoice->montant_total, 0, ',', ' ') }} F</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Paiement --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between mb-4">
            <div class="text-sm text-gray-600">Payé : <span class="font-semibold text-green-700">{{ number_format($invoice->montant_paye, 0, ',', ' ') }} F</span></div>
            <div class="text-sm text-gray-600">Total : <span class="font-semibold">{{ number_format($invoice->montant_total, 0, ',', ' ') }} F</span></div>
        </div>
        @if($invoice->statut !== 'soldee')
        <form method="POST" action="{{ route('purchase-invoices.paiement', $invoice->id) }}" class="flex gap-3 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payer (F CFA)</label>
                <input type="number" name="montant" min="1" max="{{ $invoice->reste_a_payer }}" required
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-44 focus:ring-2 focus:ring-green-500"
                    placeholder="Montant">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date paiement</label>
                <input type="date" name="date_paiement" value="{{ date('Y-m-d') }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                Confirmer paiement
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
