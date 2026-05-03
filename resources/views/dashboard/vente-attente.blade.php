@extends('layouts.dashboard')

@section('page-title', 'Ventes en attente')

@section('content')
@php
    $stocksJson     = $stocks->map(fn($s) => [
        'id'             => $s->id,
        'nom'            => $s->nom,
        'quantite'       => $s->quantite,
        'prixVente'      => $s->prixVente,
        'prix_revendeur' => $s->prix_revendeur,
        'prix_demi_gros' => $s->prix_demi_gros,
        'prixGros'       => $s->prixGros,
    ])->values()->toJson();

    $revendeursJson = $revendeurs->map(fn($r) => [
        'id'                => $r->id,
        'nom'               => $r->nom,
        'nom_boutique'      => $r->nom_boutique,
        'credit_limite'     => (float) $r->credit_limite,
        'solde_credit'      => (float) $r->solde_credit,
        'credit_disponible' => max(0, $r->credit_limite - $r->solde_credit),
    ])->values()->toJson();

    $palierLabels = [
        'normal'    => ['label' => 'Normal',     'class' => 'bg-gray-100 text-gray-600'],
        'revendeur' => ['label' => 'Revendeur',  'class' => 'bg-blue-100 text-blue-700'],
        'demi_gros' => ['label' => 'Demi-gros',  'class' => 'bg-indigo-100 text-indigo-700'],
        'gros'      => ['label' => 'Gros 10+',   'class' => 'bg-purple-100 text-purple-700'],
    ];
@endphp

<div class="space-y-6" x-data="pendingPage()" x-init="init()">

    {{-- ── En-tête ── --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Ventes en attente</h2>
            <p class="text-sm text-gray-500 mt-0.5">Ouvrez un onglet par revendeur, ajoutez des articles au fil de la journée, validez quand vous êtes prêt.</p>
        </div>
        <button @click="showNewForm = !showNewForm"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 gap-2">
            <i class="fas fa-plus"></i> Nouvelle vente en attente
        </button>
    </div>

    @if(session('success'))
        <div class="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- ── Formulaire de création ── --}}
    <div x-show="showNewForm" x-cloak x-transition
         class="bg-white border border-blue-200 rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 mb-4">
            <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Ouvrir une vente en attente
        </h3>
        <form action="{{ route('pending-sales.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-600 block mb-1">Revendeur <span class="text-red-500">*</span></label>
                <select name="client_id" required
                        x-model="newClientId"
                        @change="updateNewCredit()"
                        class="w-full text-sm border-gray-300 rounded-md border px-3 py-2">
                    <option value="">-- Choisir un revendeur --</option>
                    @foreach($revendeurs as $r)
                        <option value="{{ $r->id }}">{{ $r->nom }}{{ $r->nom_boutique ? ' ('.$r->nom_boutique.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600 block mb-1">Mode de paiement</label>
                <select name="mode_paiement" x-model="newMode" class="w-full text-sm border-gray-300 rounded-md border px-3 py-2">
                    <option value="credit">Crédit revendeur</option>
                    <option value="comptant">Comptant</option>
                </select>
                <p x-show="newMode === 'credit' && newClientId" class="text-xs text-blue-600 mt-1">
                    Crédit disponible : <strong x-text="fmt(newCreditDispo)"></strong> F
                </p>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    <i class="fas fa-folder-open mr-1"></i> Ouvrir
                </button>
                <button type="button" @click="showNewForm = false"
                        class="px-4 py-2 border text-sm rounded-md hover:bg-gray-50">
                    Annuler
                </button>
            </div>
        </form>
    </div>

    {{-- ── Cartes des ventes en attente ── --}}
    @if($pendingSales->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-16 text-center text-gray-400">
            <i class="fas fa-clock text-4xl mb-4 block"></i>
            <p class="font-medium">Aucune vente en attente</p>
            <p class="text-sm mt-1">Cliquez sur « Nouvelle vente en attente » pour commencer.</p>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            @foreach($pendingSales as $ps)
            @php
                $total = $ps->lines->sum(fn($l) => $l->prix_unitaire * $l->quantite);
                $client = $ps->client;
                $creditDispo = $client ? max(0, $client->credit_limite - $client->solde_credit) : 0;
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
                 x-data="cardPage('{{ $ps->id }}', {{ $stocksJson }}, '{{ $ps->mode_paiement }}', {{ $total }})">

                {{-- En-tête de carte --}}
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-800 text-base">{{ $client?->nom ?? '—' }}</span>
                            @if($client?->nom_boutique)
                                <span class="text-xs text-gray-500">· {{ $client->nom_boutique }}</span>
                            @endif
                            @if($ps->mode_paiement === 'credit')
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700">Crédit</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Comptant</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">Ouverte le {{ $ps->created_at->format('d/m/Y à H:i') }}</p>
                        @if($ps->mode_paiement === 'credit')
                            <p class="text-xs mt-1">
                                Crédit disponible :
                                <span class="font-semibold {{ $creditDispo <= 0 ? 'text-red-600' : 'text-blue-600' }}">
                                    {{ number_format($creditDispo, 0, ',', ' ') }} F
                                </span>
                                <span class="text-gray-400">/ limite {{ number_format($client?->credit_limite ?? 0, 0, ',', ' ') }} F</span>
                            </p>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-900" x-text="fmt(cardTotal) + ' F'">{{ number_format($total, 0, ',', ' ') }} F</div>
                        <div class="text-xs text-gray-400">{{ $ps->lines->count() }} article(s)</div>
                    </div>
                </div>

                {{-- Liste des lignes --}}
                <div class="divide-y divide-gray-50">
                    @forelse($ps->lines as $line)
                    @php $palier = $palierLabels[$line->palier] ?? $palierLabels['normal']; @endphp
                    <div class="px-5 py-3 flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $line->stock_nom }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-500">{{ $line->quantite }} × {{ number_format($line->prix_unitaire, 0, ',', ' ') }} F</span>
                                <span class="px-1.5 py-0.5 text-xs rounded-full {{ $palier['class'] }}">{{ $palier['label'] }}</span>
                            </div>
                        </div>
                        <div class="text-sm font-semibold text-gray-700 whitespace-nowrap">
                            {{ number_format($line->prix_unitaire * $line->quantite, 0, ',', ' ') }} F
                        </div>
                        <form action="{{ route('pending-sales.remove-line', [$ps->id, $line->id]) }}" method="POST"
                              onsubmit="return confirm('Retirer cet article ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-300 hover:text-red-500 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                    @empty
                    <p class="px-5 py-6 text-center text-sm text-gray-400 italic">Aucun article encore — ajoutez-en ci-dessous.</p>
                    @endforelse
                </div>

                {{-- Formulaire d'ajout d'article --}}
                <div class="px-5 py-4 border-t border-dashed border-gray-200 bg-gray-50">
                    <form action="{{ route('pending-sales.add-line', $ps->id) }}" method="POST"
                          class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @csrf
                        <div class="sm:col-span-2">
                            <select name="stock_id" required
                                    @change="onStockChange($event.target.value)"
                                    class="w-full text-sm border-gray-300 rounded-md border px-3 py-2">
                                <option value="">-- Choisir un article --</option>
                                @foreach($stocks as $stock)
                                    <option value="{{ $stock->id }}">{{ $stock->nom }} (dispo : {{ $stock->quantite }})</option>
                                @endforeach
                            </select>
                            <p x-show="selectedStock" class="text-xs text-gray-500 mt-1">
                                Prix actuel :
                                <span x-text="fmt(previewPrice) + ' F'"></span>
                                <span x-show="previewPalier !== 'normal'" class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700" x-text="palierLabel(previewPalier)"></span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <input type="number" name="quantite" min="1" value="1"
                                   x-model.number="addQty"
                                   @input="calcPreview()"
                                   class="w-20 text-sm border-gray-300 rounded-md border px-3 py-2 no-spinner">
                            <button type="submit"
                                    class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                <i class="fas fa-plus mr-1"></i>Ajouter
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Actions de validation / annulation --}}
                <div class="px-5 py-4 border-t border-gray-100 flex items-center gap-3 flex-wrap">
                    {{-- Bouton valider → ouvre le panneau inline --}}
                    <button @click="showValidate = !showValidate"
                            :class="showValidate ? 'bg-green-700' : 'bg-green-600'"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 text-white text-sm font-medium rounded-md hover:bg-green-700">
                        <i class="fas fa-check-circle"></i>
                        <span x-text="showValidate ? 'Annuler' : 'Valider la vente'"></span>
                    </button>

                    <form action="{{ route('pending-sales.annuler', $ps->id) }}" method="POST"
                          onsubmit="return confirm('Annuler définitivement cette vente en attente ?')">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 border border-red-200 text-red-500 text-sm rounded-md hover:bg-red-50">
                            <i class="fas fa-trash-alt mr-1"></i>Annuler
                        </button>
                    </form>
                </div>

                {{-- Panneau de validation --}}
                <div x-show="showValidate" x-cloak x-transition
                     class="px-5 py-4 border-t border-green-200 bg-green-50">
                    <form action="{{ route('pending-sales.valider', $ps->id) }}" method="POST">
                        @csrf
                        <p class="text-sm font-semibold text-gray-700 mb-3">
                            Total à encaisser : <span class="text-green-700" x-text="fmt(cardTotal) + ' F'"></span>
                        </p>

                        @if($ps->mode_paiement === 'credit')
                        <div class="mb-3">
                            <label class="text-sm font-medium text-gray-600 block mb-1">Acompte versé maintenant (0 = tout en crédit)</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="montant_paye" min="0" step="any"
                                       x-model.number="acompte"
                                       @input="calcReste()"
                                       placeholder="0"
                                       class="flex-1 text-sm border-gray-300 rounded-md border px-3 py-2 no-spinner">
                                <span class="text-sm text-gray-500">F</span>
                            </div>
                            <div class="mt-2 text-sm flex gap-4">
                                <span class="text-green-700">Payé : <strong x-text="fmt(acompte) + ' F'"></strong></span>
                                <span class="text-purple-700">Crédit : <strong x-text="fmt(resteCredit) + ' F'"></strong></span>
                            </div>
                        </div>
                        @else
                        <input type="hidden" name="montant_paye" :value="cardTotal">
                        <p class="text-sm text-gray-600 mb-3">Paiement comptant — le montant total sera encaissé.</p>
                        @endif

                        <button type="submit"
                                class="w-full py-2.5 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700">
                            <i class="fas fa-check mr-2"></i>Confirmer la vente
                        </button>
                    </form>
                </div>

            </div>
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
<script>
const __stocks     = @json($stocks->map(fn($s) => ['id'=>$s->id,'nom'=>$s->nom,'quantite'=>$s->quantite,'prixVente'=>$s->prixVente,'prix_revendeur'=>$s->prix_revendeur,'prix_demi_gros'=>$s->prix_demi_gros,'prixGros'=>$s->prixGros])->values());
const __revendeurs = @json($revendeurs->map(fn($r) => ['id'=>$r->id,'nom'=>$r->nom,'credit_limite'=>(float)$r->credit_limite,'solde_credit'=>(float)$r->solde_credit])->values());

function pendingPage() {
    return {
        showNewForm:    false,
        newClientId:    '',
        newMode:        'credit',
        newCreditDispo: 0,
        init() {},
        updateNewCredit() {
            const r = __revendeurs.find(x => x.id === this.newClientId);
            this.newCreditDispo = r ? Math.max(0, r.credit_limite - r.solde_credit) : 0;
        },
        fmt(n) {
            return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
    };
}

function cardPage(saleId, stocks, mode, initialTotal) {
    return {
        saleId,
        stocks,
        mode,
        cardTotal:      initialTotal,
        showValidate:   false,
        selectedStock:  null,
        addQty:         1,
        previewPrice:   0,
        previewPalier:  'normal',
        acompte:        0,
        resteCredit:    initialTotal,

        init() {
            this.resteCredit = this.cardTotal;
        },

        onStockChange(stockId) {
            this.selectedStock = this.stocks.find(s => s.id === stockId) || null;
            this.calcPreview();
        },

        resolvePrice(stock, qty) {
            if (this.mode === 'credit') {
                if (qty >= 10 && stock.prixGros)       return { price: stock.prixGros,      palier: 'gros' };
                if (qty >= 3  && stock.prix_demi_gros) return { price: stock.prix_demi_gros, palier: 'demi_gros' };
                if (stock.prix_revendeur)              return { price: stock.prix_revendeur, palier: 'revendeur' };
            }
            return { price: stock.prixVente, palier: 'normal' };
        },

        calcPreview() {
            if (!this.selectedStock) { this.previewPrice = 0; this.previewPalier = 'normal'; return; }
            const { price, palier } = this.resolvePrice(this.selectedStock, this.addQty || 1);
            this.previewPrice  = price;
            this.previewPalier = palier;
        },

        calcReste() {
            this.resteCredit = Math.max(0, this.cardTotal - (this.acompte || 0));
        },

        palierLabel(p) {
            return { normal: 'Normal', revendeur: 'Revendeur', demi_gros: 'Demi-gros', gros: 'Gros 10+' }[p] || p;
        },

        fmt(n) {
            return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
    };
}
</script>
@endpush
@endsection
