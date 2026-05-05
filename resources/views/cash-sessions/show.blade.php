@extends('layouts.dashboard')
@section('page-title', 'Session de caisse')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('caisse.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Session du {{ \Carbon\Carbon::parse($session->date)->format('d/m/Y') }}</h1>
                <p class="text-gray-500 text-sm">{{ optional($session->user)->prenom }} {{ optional($session->user)->nom }}</p>
                @php
                    $opened = $session->opened_at ?? $session->created_at;
                    $closed = $session->closed_at;
                    $duree  = ($opened && $closed) ? $opened->diff($closed) : null;
                @endphp
                <p class="text-gray-400 text-xs mt-0.5">
                    Ouverte à {{ $opened?->format('H\hi') ?? '—' }}
                    @if($closed)
                        — Fermée à {{ $closed->format('H\hi') }}
                        @if($duree)
                            <span class="ml-1">({{ $duree->h }}h{{ str_pad($duree->i, 2, '0', STR_PAD_LEFT) }} de service)</span>
                        @endif
                    @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('caisse.detail.pdf', $session->id) }}" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-file-pdf mr-1"></i> PDF Détail
            </a>
            @if($session->statut === 'fermee')
            <a href="{{ route('caisse.z-report', $session->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-print mr-1"></i> Rapport Z
            </a>
            @endif
            @if($session->statut === 'ouverte')
                <span class="flex items-center gap-2 text-green-600 font-medium text-sm">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Ouverte
                </span>
            @else
                <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Fermée</span>
            @endif
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Fonds ouverture</div>
            <div class="text-xl font-bold text-gray-900">{{ number_format($session->montant_ouverture, 0, ',', ' ') }} F</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Attendu fermeture</div>
            <div class="text-xl font-bold text-blue-600">{{ number_format($session->montant_fermeture_attendu ?? 0, 0, ',', ' ') }} F</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Réel fermeture</div>
            <div class="text-xl font-bold text-gray-700">
                {{ $session->montant_fermeture_reel !== null ? number_format($session->montant_fermeture_reel, 0, ',', ' ') . ' F' : '—' }}
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Écart</div>
            <div class="text-xl font-bold {{ ($session->ecart ?? 0) < 0 ? 'text-red-600' : (($session->ecart ?? 0) > 0 ? 'text-yellow-600' : 'text-green-600') }}">
                {{ $session->ecart !== null ? (($session->ecart > 0 ? '+' : '') . number_format($session->ecart, 0, ',', ' ') . ' F') : '—' }}
            </div>
        </div>
    </div>

    {{-- Fermeture --}}
    @if($session->statut === 'ouverte')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 text-red-700 font-medium">
            <i class="fas fa-lock"></i> Fermer la caisse
            <i class="fas fa-chevron-down text-xs" :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-transition class="mt-4">
            <form method="POST" action="{{ route('caisse.fermer', $session->id) }}" class="flex gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant en caisse (F CFA)</label>
                    <input type="number" name="montant_fermeture_reel" min="0" step="100" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-red-500">
                </div>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
                    onclick="return confirm('Confirmer la fermeture de la caisse ?')">
                    Confirmer la fermeture
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Ventes --}}
    @if($session->sales && $session->sales->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Ventes articles ({{ $session->sales->count() }})</h2>
            <span class="text-sm font-medium text-blue-600">
                Total : {{ number_format($session->sales->sum('total'), 0, ',', ' ') }} F
            </span>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Mode</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($session->sales as $sale)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-sm font-medium text-gray-800">{{ $sale->nom }}</td>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ $sale->client ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $sale->mode_paiement === 'credit' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                            {{ $sale->mode_paiement === 'credit' ? 'Crédit' : 'Comptant' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right text-sm font-medium">{{ number_format($sale->total, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Factures réparations --}}
    @if($session->invoices && $session->invoices->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Factures réparations ({{ $session->invoices->count() }})</h2>
            <span class="text-sm font-medium text-blue-600">
                Encaissé : {{ number_format($session->invoices->sum('montant_paye'), 0, ',', ' ') }} F
            </span>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N° Facture</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Réparation</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Payé</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Reste</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($session->invoices as $invoice)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('invoices.show', $invoice->id) }}" class="text-blue-600 hover:underline text-sm font-mono">{{ $invoice->numero_facture }}</a>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-600">{{ optional($invoice->repair)->numeroReparation ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs rounded-full
                            {{ $invoice->statut === 'soldee' ? 'bg-green-100 text-green-700' : ($invoice->statut === 'partielle' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                            {{ ['soldee' => 'Soldée', 'partielle' => 'Partielle', 'en_attente' => 'En attente'][$invoice->statut] ?? $invoice->statut }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right text-sm font-medium text-green-700">{{ number_format($invoice->montant_paye, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-3 text-right text-sm {{ $invoice->reste_a_payer > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        {{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} F
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
