@extends('layouts.dashboard')
@section('page-title', 'Bon de commande')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase-orders.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $order->numero }}</h1>
                <p class="text-gray-500 text-sm">{{ optional($order->supplier)->nom }} — {{ \Carbon\Carbon::parse($order->date_commande)->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @php
                $badgeClass = ['brouillon'=>'bg-gray-100 text-gray-600','envoye'=>'bg-blue-100 text-blue-700','partiellement_recu'=>'bg-yellow-100 text-yellow-700','recu'=>'bg-green-100 text-green-700','annule'=>'bg-red-100 text-red-600'][$order->statut] ?? 'bg-gray-100 text-gray-600';
                $label = ['brouillon'=>'Brouillon','envoye'=>'Envoyé','partiellement_recu'=>'Partiellement reçu','recu'=>'Reçu','annule'=>'Annulé'][$order->statut] ?? $order->statut;
            @endphp
            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $badgeClass }}">{{ $label }}</span>
            <a href="{{ route('purchase-orders.print', $order->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-print mr-1"></i> Imprimer
            </a>
        </div>
    </div>

    {{-- Dates --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Date commande</div>
            <div class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($order->date_commande)->format('d/m/Y') }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Livraison prévue</div>
            <div class="font-semibold text-gray-900">{{ $order->date_livraison_prevue ? \Carbon\Carbon::parse($order->date_livraison_prevue)->format('d/m/Y') : '—' }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total estimé</div>
            <div class="font-bold text-lg text-gray-900">{{ number_format($order->montant_total, 0, ',', ' ') }} F</div>
        </div>
    </div>

    {{-- Lignes --}}
    @if($order->lines && $order->lines->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Articles commandés</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Désignation</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Qté</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Reçu</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Prix unit.</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($order->lines as $line)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-sm text-gray-600">{{ optional($line->stock)->nom ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-gray-800">{{ $line->designation }}</td>
                    <td class="px-6 py-3 text-right text-sm">{{ $line->quantite }}</td>
                    <td class="px-6 py-3 text-right text-sm {{ $line->quantite_recue < $line->quantite ? 'text-orange-600' : 'text-green-600' }}">
                        {{ $line->quantite_recue ?? 0 }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm">{{ $line->prix_unitaire ? number_format($line->prix_unitaire, 0, ',', ' ') . ' F' : '—' }}</td>
                    <td class="px-6 py-3 text-right text-sm font-medium">
                        {{ $line->prix_unitaire ? number_format($line->quantite * $line->prix_unitaire, 0, ',', ' ') . ' F' : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">
        @if($order->statut === 'brouillon')
        <form method="POST" action="{{ route('purchase-orders.envoyer', $order->id) }}">
            @csrf
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-paper-plane mr-1"></i> Marquer comme envoyé
            </button>
        </form>
        @endif

        @if(in_array($order->statut, ['envoye', 'partiellement_recu']))
        <div x-data="{ open: false }">
            <button @click="open = !open" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-box-open mr-1"></i> Enregistrer une réception
            </button>
            <div x-show="open" x-transition class="mt-3 bg-white rounded-xl border border-gray-200 p-4">
                <form method="POST" action="{{ route('purchase-orders.reception', $order->id) }}" class="space-y-3">
                    @csrf
                    <h3 class="font-medium text-gray-800 text-sm">Quantités reçues</h3>
                    @foreach($order->lines as $line)
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-700 flex-1">{{ $line->designation }}</span>
                        <span class="text-xs text-gray-400">Commandé : {{ $line->quantite }}</span>
                        <input type="number" name="lignes[{{ $line->id }}]" min="0" max="{{ $line->quantite }}"
                            value="{{ $line->quantite - ($line->quantite_recue ?? 0) }}"
                            class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm w-20 focus:ring-2 focus:ring-green-500">
                    </div>
                    @endforeach
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        Confirmer réception
                    </button>
                </form>
            </div>
        </div>
        @endif

        @if(!in_array($order->statut, ['recu', 'annule']))
        <form method="POST" action="{{ route('purchase-orders.annuler', $order->id) }}"
            onsubmit="return confirm('Annuler ce bon de commande ?')">
            @csrf
            <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-ban mr-1"></i> Annuler
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
