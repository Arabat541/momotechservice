@extends('layouts.dashboard')
@section('page-title', 'Facture')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('invoices.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $invoice->numero_facture }}</h1>
                <p class="text-gray-500 text-sm">Émise le {{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 text-sm font-medium rounded-full
                {{ $invoice->statut === 'soldee' ? 'bg-green-100 text-green-700' : ($invoice->statut === 'annulee' ? 'bg-red-100 text-red-700' : ($invoice->statut === 'partielle' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600')) }}">
                {{ ['soldee' => 'Soldée', 'partielle' => 'Partielle', 'en_attente' => 'En attente', 'annulee' => 'Annulée'][$invoice->statut] ?? $invoice->statut }}
            </span>
            <a href="{{ route('invoices.print', $invoice->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-print mr-1"></i> Imprimer
            </a>
            @if(session('user_role') === 'patron' && $invoice->statut !== 'annulee')
            <form method="POST" action="{{ route('invoices.rembourser', $invoice->id) }}" class="relative"
                  x-data="{ open: false }">
                @csrf
                <button type="button" @click="open = !open"
                    class="text-red-600 hover:text-red-800 text-sm font-medium border border-red-200 px-3 py-1.5 rounded-lg hover:bg-red-50">
                    <i class="fas fa-undo-alt mr-1"></i> Annuler / Avoir
                </button>
                <div x-show="open" x-transition class="absolute z-10 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-72 right-0">
                    <p class="text-sm text-gray-700 mb-3">Annuler cette facture et créer un avoir de <strong>{{ number_format($invoice->montant_paye, 0, ',', ' ') }} cfa</strong> pour le client ?</p>
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Notes (optionnel)</label>
                        <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded-lg text-sm font-medium">
                            Confirmer
                        </button>
                        <button type="button" @click="open = false" class="text-gray-500 text-sm border border-gray-300 px-3 py-1.5 rounded-lg">
                            Annuler
                        </button>
                    </div>
                </div>
            </form>
            @endif
        </div>
    </div>

    {{-- Infos client / réparation --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-3 font-semibold">Client</div>
            @if($invoice->client)
            <div class="font-semibold text-gray-900">{{ $invoice->client->nom }}</div>
            <div class="text-sm text-gray-500">{{ $invoice->client->telephone }}</div>
            @else
            <div class="text-gray-400 text-sm">Client non renseigné</div>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-3 font-semibold">Réparation</div>
            @if($invoice->repair)
            <a href="{{ route('reparations.show', $invoice->repair->id) }}" class="font-mono text-blue-600 hover:underline font-semibold">{{ $invoice->repair->numeroReparation }}</a>
            <div class="text-sm text-gray-500 mt-1">{{ $invoice->repair->appareil_marque_modele }}</div>
            @else
            <div class="text-gray-400 text-sm">—</div>
            @endif
        </div>
    </div>

    {{-- Montants --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="divide-y divide-gray-100">
            <div class="flex justify-between py-3 text-sm">
                <span class="text-gray-600">Montant estimé (acompte reçu)</span>
                <span>{{ number_format($invoice->montant_estime, 0, ',', ' ') }} F</span>
            </div>
            <div class="flex justify-between py-3 text-sm">
                <span class="text-gray-600">Montant final</span>
                <span class="font-semibold">{{ number_format($invoice->montant_final ?? $invoice->montant_estime, 0, ',', ' ') }} F</span>
            </div>
            <div class="flex justify-between py-3 text-sm">
                <span class="text-gray-600">Montant payé</span>
                <span class="text-green-600 font-semibold">{{ number_format($invoice->montant_paye, 0, ',', ' ') }} F</span>
            </div>
            <div class="flex justify-between py-3 text-base font-bold">
                <span>Reste à payer</span>
                <span class="{{ $invoice->reste_a_payer > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} F
                </span>
            </div>
        </div>
    </div>

    {{-- Formulaire paiement --}}
    @if($invoice->statut !== 'soldee' && $invoice->reste_a_payer > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center gap-2 text-green-700 font-medium">
            <i class="fas fa-money-bill-wave"></i> Enregistrer un paiement
            <i class="fas fa-chevron-down text-xs" :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-transition class="mt-4">
            <form method="POST" action="{{ route('invoices.paiement', $invoice->id) }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant (F CFA)</label>
                    <input type="number" name="montant" min="1" max="{{ $invoice->reste_a_payer }}" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-44 focus:ring-2 focus:ring-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Montant final (si différent)</label>
                    <input type="number" name="montant_final" value="{{ $invoice->montant_final ?? $invoice->montant_estime }}" min="0"
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-44 focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    Confirmer le paiement
                </button>
            </form>
        </div>
    </div>
    @endif

    @if($invoice->cashSession)
    <div class="text-sm text-gray-500">
        Session de caisse : <a href="{{ route('caisse.show', $invoice->cashSession->id) }}" class="text-blue-600 hover:underline">{{ \Carbon\Carbon::parse($invoice->cashSession->date)->format('d/m/Y') }}</a>
    </div>
    @endif
</div>
@endsection
