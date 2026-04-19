@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white p-8 max-w-2xl mx-auto text-gray-900 text-sm">
    <div class="flex justify-between items-start mb-8">
        <div>
            <h2 class="text-xl font-bold">MOMO TECH SERVICE</h2>
        </div>
        <div class="text-right">
            <div class="text-xl font-bold text-gray-800">BON DE COMMANDE</div>
            <div class="font-mono text-blue-700 mt-1">{{ $order->numero }}</div>
            <div class="text-gray-500 mt-1">{{ \Carbon\Carbon::parse($order->date_commande)->format('d/m/Y') }}</div>
        </div>
    </div>

    <hr class="border-gray-300 mb-6">

    <div class="grid grid-cols-2 gap-8 mb-8">
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-2">Fournisseur</div>
            <div class="font-semibold">{{ optional($order->supplier)->nom }}</div>
            @if($order->supplier && $order->supplier->telephone)
            <div class="text-gray-600">{{ $order->supplier->telephone }}</div>
            @endif
            @if($order->supplier && $order->supplier->contact_nom)
            <div class="text-gray-600">Contact : {{ $order->supplier->contact_nom }}</div>
            @endif
        </div>
        <div>
            <div class="text-xs text-gray-500 uppercase tracking-wide font-semibold mb-2">Livraison prévue</div>
            <div class="font-semibold">{{ $order->date_livraison_prevue ? \Carbon\Carbon::parse($order->date_livraison_prevue)->format('d/m/Y') : 'À définir' }}</div>
            @if($order->notes)
            <div class="text-gray-600 mt-2 text-xs">{{ $order->notes }}</div>
            @endif
        </div>
    </div>

    @if($order->lines && $order->lines->count() > 0)
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
            @foreach($order->lines as $line)
            <tr>
                <td class="px-3 py-2 border border-gray-200">{{ $line->designation }}</td>
                <td class="px-3 py-2 border border-gray-200 text-right">{{ $line->quantite }}</td>
                <td class="px-3 py-2 border border-gray-200 text-right">{{ $line->prix_unitaire ? number_format($line->prix_unitaire, 0, ',', ' ') . ' F' : '—' }}</td>
                <td class="px-3 py-2 border border-gray-200 text-right font-medium">
                    {{ $line->prix_unitaire ? number_format($line->quantite * $line->prix_unitaire, 0, ',', ' ') . ' F' : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 font-bold">
                <td colspan="3" class="px-3 py-2 border border-gray-300 text-right">TOTAL ESTIMÉ</td>
                <td class="px-3 py-2 border border-gray-300 text-right">{{ number_format($order->montant_total, 0, ',', ' ') }} F</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="grid grid-cols-2 gap-8 mt-10">
        <div class="text-center">
            <div class="border-t border-gray-400 pt-2 mt-12">Signature émetteur</div>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-400 pt-2 mt-12">Signature fournisseur</div>
        </div>
    </div>

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i> Imprimer
        </button>
    </div>
</div>
@endsection
