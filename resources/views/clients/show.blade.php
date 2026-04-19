@extends('layouts.dashboard')
@section('page-title', 'Client')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('clients.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $client->nom }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">
                    @if($client->type === 'revendeur')
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700">Revendeur</span>
                    @else
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Particulier</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($client->type === 'revendeur')
            <a href="{{ route('clients.dashboard', $client->id) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            @endif
            <a href="{{ route('clients.edit', $client->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-edit"></i> Modifier
            </a>
        </div>
    </div>

    {{-- Info cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Téléphone</div>
            <div class="text-lg font-semibold text-gray-900">{{ $client->telephone }}</div>
        </div>
        @if($client->nom_boutique)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Boutique</div>
            <div class="text-lg font-semibold text-gray-900">{{ $client->nom_boutique }}</div>
        </div>
        @endif
        @if($client->type === 'revendeur')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Solde crédit</div>
            <div class="text-lg font-semibold {{ $client->solde_credit > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ number_format($client->solde_credit, 0, ',', ' ') }} F CFA
            </div>
            <div class="text-xs text-gray-400 mt-0.5">Limite : {{ number_format($client->credit_limite, 0, ',', ' ') }} F</div>
        </div>
        @endif
    </div>

    {{-- Remboursement crédit --}}
    @if($client->type === 'revendeur' && $client->solde_credit > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 text-green-700 font-medium text-sm">
            <i class="fas fa-hand-holding-usd"></i>
            Enregistrer un remboursement
            <i class="fas fa-chevron-down text-xs transition-transform" :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-transition class="mt-4">
            <form method="POST" action="{{ route('clients.remboursement', $client->id) }}" class="flex gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant (F CFA)</label>
                    <input type="number" name="montant" min="1" max="{{ $client->solde_credit }}" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 w-44">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
                    <input type="text" name="notes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    Confirmer
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Réparations --}}
    @if(isset($reparations) && $reparations->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Réparations ({{ $reparations->count() }})</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Appareil</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($reparations as $rep)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('reparations.show', $rep->id) }}" class="text-blue-600 hover:underline text-sm font-mono">{{ $rep->numeroReparation }}</a>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-700">{{ $rep->appareil_marque_modele }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full
                            {{ $rep->statut_reparation === 'Terminé' ? 'bg-green-100 text-green-700' : ($rep->statut_reparation === 'En cours' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                            {{ $rep->statut_reparation }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right text-sm">{{ number_format($rep->total_reparation, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500">{{ \Carbon\Carbon::parse($rep->created_at)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Transactions crédit --}}
    @if(isset($transactions) && $transactions->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Historique crédit ({{ $transactions->count() }})</h2>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Notes</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Montant</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Solde après</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($transactions as $tx)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-sm text-gray-500">{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $tx->type === 'dette' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                            {{ $tx->type === 'dette' ? 'Crédit accordé' : 'Remboursement' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ $tx->notes ?? '—' }}</td>
                    <td class="px-6 py-3 text-right text-sm font-medium {{ $tx->type === 'dette' ? 'text-red-600' : 'text-green-600' }}">
                        {{ $tx->type === 'dette' ? '+' : '-' }}{{ number_format($tx->montant, 0, ',', ' ') }} F
                    </td>
                    <td class="px-6 py-3 text-right text-sm text-gray-700">{{ number_format($tx->solde_apres, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
