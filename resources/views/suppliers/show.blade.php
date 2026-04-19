@extends('layouts.dashboard')
@section('page-title', 'Fournisseur')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('suppliers.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $supplier->nom }}</h1>
                @if(!$supplier->actif)
                <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-500">Inactif</span>
                @endif
            </div>
        </div>
        <a href="{{ route('suppliers.edit', $supplier->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-edit mr-1"></i> Modifier
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h2 class="font-semibold text-gray-800 text-sm uppercase tracking-wide">Coordonnées</h2>
            @if($supplier->contact_nom)
            <div class="flex gap-2 text-sm"><span class="text-gray-400 w-20">Contact</span> <span class="text-gray-800">{{ $supplier->contact_nom }}</span></div>
            @endif
            @if($supplier->telephone)
            <div class="flex gap-2 text-sm"><span class="text-gray-400 w-20">Tél.</span> <span class="text-gray-800">{{ $supplier->telephone }}</span></div>
            @endif
            @if($supplier->email)
            <div class="flex gap-2 text-sm"><span class="text-gray-400 w-20">Email</span> <span class="text-gray-800">{{ $supplier->email }}</span></div>
            @endif
            @if($supplier->adresse)
            <div class="flex gap-2 text-sm"><span class="text-gray-400 w-20">Adresse</span> <span class="text-gray-800">{{ $supplier->adresse }}</span></div>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h2 class="font-semibold text-gray-800 text-sm uppercase tracking-wide">Conditions commerciales</h2>
            @if($supplier->delai_livraison_jours)
            <div class="flex gap-2 text-sm"><span class="text-gray-400 w-28">Délai livraison</span> <span class="text-gray-800">{{ $supplier->delai_livraison_jours }} jours</span></div>
            @endif
            @if($supplier->conditions_paiement)
            <div class="flex gap-2 text-sm"><span class="text-gray-400 w-28">Conditions</span> <span class="text-gray-800">{{ $supplier->conditions_paiement }}</span></div>
            @endif
        </div>
    </div>

    {{-- Factures fournisseur --}}
    @if($supplier->purchaseInvoices && $supplier->purchaseInvoices->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Factures fournisseur</h2>
            <a href="{{ route('purchase-invoices.create', ['supplier_id' => $supplier->id]) }}" class="text-blue-600 text-sm hover:underline">+ Nouvelle facture</a>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Reste</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($supplier->purchaseInvoices as $inv)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('purchase-invoices.show', $inv->id) }}" class="text-blue-600 hover:underline font-mono text-sm">{{ $inv->numero }}</a>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($inv->date_facture)->format('d/m/Y') }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $inv->statut === 'soldee' ? 'bg-green-100 text-green-700' : ($inv->statut === 'partiellement_payee' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ ['soldee'=>'Soldée','partiellement_payee'=>'Partielle','en_attente'=>'En attente'][$inv->statut] ?? $inv->statut }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right text-sm">{{ number_format($inv->montant_total, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-3 text-right text-sm {{ $inv->reste_a_payer > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ number_format($inv->reste_a_payer, 0, ',', ' ') }} F
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
        <p class="text-gray-400 text-sm mb-3">Aucune facture pour ce fournisseur.</p>
        <a href="{{ route('purchase-invoices.create', ['supplier_id' => $supplier->id]) }}" class="text-blue-600 text-sm hover:underline">
            <i class="fas fa-plus mr-1"></i> Créer une facture
        </a>
    </div>
    @endif

    {{-- Réappros --}}
    @if($supplier->reappros && $supplier->reappros->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Réapprovisionnements liés ({{ $supplier->reappros->count() }})</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Quantité</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Prix unitaire</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($supplier->reappros as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-sm text-gray-800">{{ optional($r->stock)->nom ?? '—' }}</td>
                    <td class="px-6 py-3 text-right text-sm">{{ $r->quantite }}</td>
                    <td class="px-6 py-3 text-right text-sm">{{ $r->prixAchat ? number_format($r->prixAchat, 0, ',', ' ') . ' F' : '—' }}</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500">{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
