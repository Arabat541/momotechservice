@extends('layouts.dashboard')
@section('page-title', 'Bons de commande')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Bons de commande</h1>
            @if($enAlerte > 0)
            <p class="text-orange-600 text-sm mt-1"><i class="fas fa-exclamation-triangle mr-1"></i> {{ $enAlerte }} article(s) en alerte de stock</p>
            @endif
        </div>
        <a href="{{ route('purchase-orders.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nouveau bon de commande
        </a>
    </div>

    {{-- Filtres statut --}}
    <div class="flex gap-2 flex-wrap">
        @foreach(['', 'brouillon', 'envoye', 'partiellement_recu', 'recu', 'annule'] as $s)
        <a href="{{ route('purchase-orders.index', array_merge(request()->query(), ['statut' => $s])) }}"
            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
            {{ request('statut', '') === $s ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
            {{ [''=>'Tous','brouillon'=>'Brouillon','envoye'=>'Envoyé','partiellement_recu'=>'Partiel','recu'=>'Reçu','annule'=>'Annulé'][$s] }}
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Fournisseur</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date commande</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Livraison prévue</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                @php
                    $retard = $order->date_livraison_prevue
                        && !in_array($order->statut, ['recu','annule'])
                        && \Carbon\Carbon::parse($order->date_livraison_prevue)->isPast();
                @endphp
                <tr class="hover:bg-gray-50 transition-colors {{ $retard ? 'bg-orange-50/30' : '' }}">
                    <td class="px-6 py-4">
                        <a href="{{ route('purchase-orders.show', $order->id) }}" class="text-blue-600 hover:underline font-mono text-sm">{{ $order->numero }}</a>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ optional($order->supplier)->nom }}</td>
                    <td class="px-6 py-4">
                        @php
                            $badgeClass = ['brouillon'=>'bg-gray-100 text-gray-600','envoye'=>'bg-blue-100 text-blue-700','partiellement_recu'=>'bg-yellow-100 text-yellow-700','recu'=>'bg-green-100 text-green-700','annule'=>'bg-red-100 text-red-600'][$order->statut] ?? 'bg-gray-100 text-gray-600';
                            $label = ['brouillon'=>'Brouillon','envoye'=>'Envoyé','partiellement_recu'=>'Partiellement reçu','recu'=>'Reçu','annule'=>'Annulé'][$order->statut] ?? $order->statut;
                        @endphp
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badgeClass }}">{{ $label }}</span>
                        @if($retard)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-orange-100 text-orange-700 font-medium">Retard</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($order->date_commande)->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm {{ $retard ? 'text-orange-600 font-medium' : 'text-gray-600' }}">
                        {{ $order->date_livraison_prevue ? \Carbon\Carbon::parse($order->date_livraison_prevue)->format('d/m/Y') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">{{ number_format($order->montant_total, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('purchase-orders.show', $order->id) }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('purchase-orders.print', $order->id) }}" class="text-gray-400 hover:text-gray-700 text-sm"><i class="fas fa-print"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-shopping-cart text-3xl mb-3 block"></i>
                        Aucun bon de commande.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($orders, 'hasPages') && $orders->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $orders->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
