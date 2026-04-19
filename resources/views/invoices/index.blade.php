@extends('layouts.dashboard')
@section('page-title', 'Factures')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Factures réparations</h1>
    </div>

    {{-- Filtres statut --}}
    <div class="flex gap-2 flex-wrap">
        @foreach(['', 'en_attente', 'partielle', 'soldee'] as $s)
        <a href="{{ route('invoices.index', array_merge(request()->query(), ['statut' => $s])) }}"
            class="px-4 py-2 rounded-lg text-sm font-medium border transition-colors
            {{ request('statut', '') === $s ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
            {{ [''=>'Toutes', 'en_attente'=>'En attente', 'partielle'=>'Partielles', 'soldee'=>'Soldées'][$s] }}
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N° Facture</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Réparation</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Payé</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Reste</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($invoices as $invoice)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('invoices.show', $invoice->id) }}" class="text-blue-600 hover:underline font-mono text-sm">{{ $invoice->numero_facture }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ optional($invoice->client)->nom ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($invoice->repair)
                        <a href="{{ route('reparations.show', $invoice->repair->id) }}" class="text-blue-500 hover:underline font-mono">{{ $invoice->repair->numeroReparation }}</a>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $invoice->statut === 'soldee' ? 'bg-green-100 text-green-700' : ($invoice->statut === 'partielle' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ ['soldee' => 'Soldée', 'partielle' => 'Partielle', 'en_attente' => 'En attente'][$invoice->statut] ?? $invoice->statut }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right text-sm">{{ number_format($invoice->montant_final ?? $invoice->montant_estime, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-4 text-right text-sm text-green-700 font-medium">{{ number_format($invoice->montant_paye, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-4 text-right text-sm {{ $invoice->reste_a_payer > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} F
                    </td>
                    <td class="px-6 py-4 text-right text-xs text-gray-500">{{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('invoices.print', $invoice->id) }}" class="text-gray-400 hover:text-gray-700" title="Imprimer"><i class="fas fa-print"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-file-invoice text-3xl mb-3 block"></i>
                        Aucune facture trouvée.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($invoices, 'hasPages') && $invoices->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $invoices->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
