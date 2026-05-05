@extends('layouts.dashboard')
@section('page-title', 'Factures fournisseurs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Factures fournisseurs</h1>
        </div>
        <div class="flex items-center gap-2">
            @if(session('user_role') === 'patron')
            <a href="{{ route('export.module', 'factures-fournisseurs') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-csv"></i> CSV
            </a>
            <a href="{{ route('export.pdf', 'factures-fournisseurs') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            @endif
            <a href="{{ route('purchase-invoices.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-plus"></i> Nouvelle facture
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total dû</div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($totalDu ?? 0, 0, ',', ' ') }} F</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">En retard</div>
            <div class="text-2xl font-bold text-orange-500">{{ number_format($totalEnRetard ?? 0, 0, ',', ' ') }} F</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Fournisseur</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date facture</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Échéance</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Reste</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($invoices as $invoice)
                @php
                    $retard = $invoice->date_echeance && $invoice->statut !== 'soldee' && \Carbon\Carbon::parse($invoice->date_echeance)->isPast();
                @endphp
                <tr class="hover:bg-gray-50 transition-colors {{ $retard ? 'bg-red-50/30' : '' }}">
                    <td class="px-6 py-4">
                        <a href="{{ route('purchase-invoices.show', $invoice->id) }}" class="text-blue-600 hover:underline font-mono text-sm">{{ $invoice->numero }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ optional($invoice->supplier)->nom }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $invoice->statut === 'soldee' ? 'bg-green-100 text-green-700' : ($invoice->statut === 'partiellement_payee' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ ['soldee'=>'Soldée','partiellement_payee'=>'Partielle','en_attente'=>'En attente'][$invoice->statut] ?? $invoice->statut }}
                        </span>
                        @if($retard)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-red-100 text-red-700 font-medium">Retard</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($invoice->date_facture)->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm {{ $retard ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                        {{ $invoice->date_echeance ? \Carbon\Carbon::parse($invoice->date_echeance)->format('d/m/Y') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">{{ number_format($invoice->montant_total, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-4 text-right text-sm {{ $invoice->reste_a_payer > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                        {{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} F
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('purchase-invoices.show', $invoice->id) }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('purchase-invoices.print', $invoice->id) }}" class="text-gray-400 hover:text-gray-700 text-sm"><i class="fas fa-print"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-file-invoice-dollar text-3xl mb-3 block"></i>
                        Aucune facture fournisseur.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($invoices, 'hasPages') && $invoices->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $invoices->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
