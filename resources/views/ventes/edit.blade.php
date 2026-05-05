@extends('layouts.dashboard')
@section('page-title', 'Modifier la vente')

@section('content')
@php
    $revendeursJson = $revendeurs->map(fn($r) => [
        'id'  => $r->id,
        'nom' => $r->nom . ($r->nom_boutique ? ' — ' . $r->nom_boutique : ''),
    ])->values()->toJson();

    $dateFormatted = $vente->date
        ? \Carbon\Carbon::parse($vente->date)->format('Y-m-d\TH:i')
        : now()->format('Y-m-d\TH:i');
@endphp

<div class="max-w-2xl mx-auto space-y-6"
     x-data="venteEdit({{ $vente->quantite }}, {{ $vente->prixVente }}, '{{ $vente->mode_paiement }}', {{ $vente->montant_paye }}, {{ $vente->remise ?? 0 }})">

    {{-- En-tête --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('article') }}"
           class="text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Modifier la vente</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $vente->nom }}
                <span class="text-gray-300 mx-1">·</span>
                <span class="font-mono text-xs text-gray-400">{{ $vente->id }}</span>
            </p>
        </div>
    </div>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-red-500"></i> {{ session('error') }}
    </div>
    @endif

    <form action="{{ route('article.update', $vente->id) }}" method="POST"
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf
        @method('PUT')

        {{-- Article (lecture seule) --}}
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-0.5">Article</p>
                <p class="font-semibold text-gray-900">{{ $vente->nom }}</p>
            </div>
            @if($stock)
            <div class="text-right flex-shrink-0">
                <p class="text-xs text-gray-500">Stock disponible</p>
                <p class="font-bold {{ $stock->quantite <= 0 ? 'text-red-600' : 'text-green-700' }}">
                    {{ $stock->quantite }} unité(s)
                </p>
                <p class="text-xs text-gray-400">(hors cette vente)</p>
            </div>
            @endif
        </div>

        {{-- Quantité + Prix unitaire --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Quantité <span class="text-red-500">*</span>
                </label>
                <input type="number" name="quantite" min="1" max="9999" required
                       x-model.number="quantite" @input="calcTotal()"
                       value="{{ old('quantite', $vente->quantite) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('quantite') border-red-400 @enderror">
                @error('quantite')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @if($stock)
                <p x-show="quantite > {{ $vente->quantite }} && (quantite - {{ $vente->quantite }}) > {{ $stock->quantite }}"
                   class="mt-1 text-xs text-red-600">
                    Stock insuffisant — seulement {{ $stock->quantite }} unité(s) dispo pour augmenter
                </p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Prix unitaire (F CFA) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="prixVente" min="0" step="any" required
                       x-model.number="prixUnitaire" @input="calcTotal()"
                       value="{{ old('prixVente', $vente->prixVente) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('prixVente') border-red-400 @enderror">
                @error('prixVente')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Remise --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Remise (F CFA)</label>
            <input type="number" name="remise" min="0" step="any"
                   x-model.number="remise" @input="calcTotal()"
                   value="{{ old('remise', $vente->remise ?? 0) }}"
                   placeholder="0"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            <p x-show="remise > 0 && remise >= sousTotal"
               class="mt-1 text-xs text-red-600">La remise doit être inférieure au sous-total (<span x-text="fmt(sousTotal)"></span> F CFA).</p>
        </div>

        {{-- Total calculé --}}
        <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
            <template x-if="remise > 0 && remise < sousTotal">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-blue-500">Sous-total</span>
                    <span class="text-sm text-blue-500 line-through" x-text="fmt(sousTotal) + ' F CFA'"></span>
                </div>
            </template>
            <div class="flex items-center justify-between">
                <span class="text-sm text-blue-700 font-medium">Total</span>
                <span class="text-xl font-bold text-blue-800" x-text="fmt(total) + ' F CFA'"></span>
            </div>
        </div>

        {{-- Mode paiement --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mode de paiement</label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="mode_paiement" value="comptant"
                           x-model="modePaiement" @change="onModeChange()"
                           class="text-blue-600">
                    <span class="text-sm font-medium">Comptant</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="mode_paiement" value="credit"
                           x-model="modePaiement" @change="onModeChange()"
                           class="text-purple-600">
                    <span class="text-sm font-medium">À crédit</span>
                </label>
            </div>
        </div>

        {{-- Champs conditionnels selon le mode --}}
        <template x-if="modePaiement === 'comptant'">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du client</label>
                <input type="text" name="client" maxlength="150"
                       value="{{ old('client', $vente->mode_paiement === 'comptant' ? $vente->client : '') }}"
                       placeholder="Anonyme"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <input type="hidden" name="client_id" value="">
            </div>
        </template>

        <template x-if="modePaiement === 'credit'">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Revendeur <span class="text-red-500">*</span>
                    </label>
                    <select name="client_id" :required="modePaiement === 'credit'"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                        <option value="">Sélectionner un revendeur…</option>
                        @foreach($revendeurs as $rev)
                        <option value="{{ $rev->id }}"
                            {{ old('client_id', $vente->client_id) === $rev->id ? 'selected' : '' }}>
                            {{ $rev->nom }}{{ $rev->nom_boutique ? ' — ' . $rev->nom_boutique : '' }}
                        </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="client" value="">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Acompte versé (F CFA)
                    </label>
                    <input type="number" name="montant_paye" min="0" step="any"
                           x-model.number="montantPaye" @input="calcTotal()"
                           :max="total"
                           value="{{ old('montant_paye', $vente->mode_paiement === 'credit' ? $vente->montant_paye : 0) }}"
                           placeholder="0 = tout à crédit"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                    <p class="mt-1 text-xs text-gray-500">
                        Reste à crédit : <span class="font-semibold text-purple-700" x-text="fmt(resteCredit) + ' F CFA'"></span>
                    </p>
                </div>
            </div>
        </template>

        {{-- Pour le mode comptant, montant_paye = total (tout payé) --}}
        <template x-if="modePaiement === 'comptant'">
            <input type="hidden" name="montant_paye" :value="total">
        </template>

        {{-- Date --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date de vente</label>
            <input type="datetime-local" name="date" required
                   value="{{ old('date', $dateFormatted) }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 @error('date') border-red-400 @enderror">
            @error('date')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-2 border-t border-gray-100">
            <a href="{{ route('article') }}"
               class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-times mr-1"></i> Annuler
            </a>
            <button type="submit"
                    :disabled="remise > 0 && remise >= sousTotal"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-semibold text-sm transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                <i class="fas fa-save mr-2"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function venteEdit(initQte, initPrix, initMode, initMontantPaye, initRemise) {
    const initSousTotal = initPrix * initQte;
    const initRemiseCap = Math.min(initRemise || 0, Math.max(0, initSousTotal - 0.01));
    return {
        quantite:     initQte,
        prixUnitaire: initPrix,
        modePaiement: initMode,
        montantPaye:  initMontantPaye,
        remise:       initRemise || 0,
        sousTotal:    initSousTotal,
        total:        initSousTotal - initRemiseCap,
        resteCredit:  Math.max(0, initSousTotal - initRemiseCap - initMontantPaye),

        onModeChange() {
            this.montantPaye = 0;
            this.calcTotal();
        },
        calcTotal() {
            this.sousTotal   = (this.prixUnitaire || 0) * (this.quantite || 0);
            const remiseCap  = Math.min(this.remise || 0, Math.max(0, this.sousTotal - 0.01));
            this.total       = Math.max(0, this.sousTotal - remiseCap);
            this.resteCredit = Math.max(0, this.total - (this.montantPaye || 0));
        },
        fmt(n) {
            return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
    };
}
</script>
@endpush
@endsection
