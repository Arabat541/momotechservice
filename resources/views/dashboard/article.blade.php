@extends('layouts.dashboard')

@section('page-title', "Vente d'Articles")

@section('content')
@php
    $stocksJson    = $stocks->map(fn($s) => [
        'id'             => $s->id,
        'nom'            => $s->nom,
        'quantite'       => $s->quantite,
        'prixVente'      => $s->prixVente,
        'prix_revendeur' => $s->prix_revendeur,
        'prix_demi_gros' => $s->prix_demi_gros,
        'prixGros'       => $s->prixGros,
    ])->values()->toJson();

    $revendeursJson = $revendeurs->map(fn($r) => [
        'id'               => $r->id,
        'nom'              => $r->nom,
        'nom_boutique'     => $r->nom_boutique,
        'credit_limite'    => $r->credit_limite,
        'solde_credit'     => $r->solde_credit,
        'credit_disponible'=> max(0, $r->credit_limite - $r->solde_credit),
    ])->values()->toJson();
@endphp

<div class="space-y-6" x-data="ventePage()" x-init="init()">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Vente d'articles</h2>
    </div>

    {{-- ── Formulaire de vente (caissière uniquement) ── --}}
    @if(session('user_role') === 'caissiere')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-5">
            <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>Nouvelle vente
        </h3>

        <form action="{{ route('article.vendre') }}" method="POST" @submit="return valider()">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                {{-- Article --}}
                <div class="lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Article <span class="text-red-500">*</span></label>
                    <select name="article_id" required x-model="selectedStockId" @change="onStockChange()"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Choisir un article...</option>
                        @foreach($stocks as $stock)
                        <option value="{{ $stock->id }}">
                            {{ $stock->nom }} — {{ number_format($stock->prixVente, 0, ',', ' ') }} cfa
                            (stock: {{ $stock->quantite }})
                        </option>
                        @endforeach
                    </select>
                    <div x-show="selectedStock" class="mt-1.5 text-xs text-gray-500 flex flex-wrap gap-2">
                        <span>Normal : <strong x-text="fmt(selectedStock?.prixVente)"></strong></span>
                        <template x-if="selectedStock?.prix_revendeur">
                            <span class="text-blue-600">Rev. : <strong x-text="fmt(selectedStock?.prix_revendeur)"></strong></span>
                        </template>
                        <template x-if="selectedStock?.prix_demi_gros">
                            <span class="text-indigo-600">D-gros : <strong x-text="fmt(selectedStock?.prix_demi_gros)"></strong></span>
                        </template>
                        <template x-if="selectedStock?.prixGros">
                            <span class="text-purple-600">Gros : <strong x-text="fmt(selectedStock?.prixGros)"></strong></span>
                        </template>
                    </div>
                </div>

                {{-- Quantité --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantité <span class="text-red-500">*</span></label>
                    <input type="number" name="quantite" min="1" required
                           x-model.number="quantite" @input="calcTotal()"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 no-spinner">
                    <p x-show="selectedStock && quantite > selectedStock.quantite"
                       class="mt-1 text-xs text-red-600">
                        Stock insuffisant (<span x-text="selectedStock?.quantite"></span> dispo)
                    </p>
                </div>

                {{-- Mode paiement --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                    <div class="flex gap-3 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="mode_paiement" value="comptant"
                                   x-model="modePaiement" @change="onModeChange()"
                                   class="text-blue-600">
                            <span class="text-sm">Comptant</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="mode_paiement" value="credit"
                                   x-model="modePaiement" @change="onModeChange()"
                                   :disabled="revendeurs.length === 0"
                                   class="text-purple-600">
                            <span class="text-sm" :class="revendeurs.length === 0 ? 'text-gray-400' : ''">À crédit</span>
                        </label>
                    </div>
                </div>

                {{-- Client comptant --}}
                <template x-if="modePaiement === 'comptant'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom du client</label>
                        <input type="text" name="client" placeholder="Anonyme"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </template>

                {{-- Revendeur (crédit) --}}
                <template x-if="modePaiement === 'credit'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Revendeur <span class="text-red-500">*</span></label>
                        <select name="client_id" x-model="selectedRevendeurId" @change="onRevendeurChange()"
                                :required="modePaiement === 'credit'"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                            <option value="">Sélectionner un revendeur...</option>
                            @foreach($revendeurs as $rev)
                            <option value="{{ $rev->id }}">
                                {{ $rev->nom }}{{ $rev->nom_boutique ? ' — ' . $rev->nom_boutique : '' }}
                            </option>
                            @endforeach
                        </select>
                        <template x-if="selectedRevendeur">
                            <div class="mt-1.5 text-xs space-y-0.5">
                                <p>Crédit disponible :
                                    <strong :class="creditDisponible < total ? 'text-red-600' : 'text-green-600'"
                                            x-text="fmt(creditDisponible) + ' cfa'"></strong>
                                    / limite <span x-text="fmt(selectedRevendeur?.credit_limite) + ' cfa'"></span>
                                </p>
                                <p x-show="selectedRevendeur?.solde_credit > 0" class="text-orange-600">
                                    Dette en cours : <span x-text="fmt(selectedRevendeur?.solde_credit) + ' cfa'"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Acompte (crédit partiel) --}}
                <template x-if="modePaiement === 'credit'">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Acompte versé (cfa)</label>
                        <input type="number" name="montant_paye" step="any" min="0"
                               x-model.number="montantPaye" @input="calcTotal()"
                               :max="total"
                               placeholder="0 = tout à crédit"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 no-spinner">
                    </div>
                </template>
            </div>

            {{-- Résumé total --}}
            <div x-show="selectedStock && quantite > 0" class="mt-5 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex flex-wrap gap-6 items-center">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Prix unitaire</p>
                        <p class="text-lg font-bold text-gray-800" x-text="fmt(prixUnitaire) + ' cfa'"></p>
                        <p class="text-xs mt-0.5" :class="{
                            'text-purple-600': palierActif === 'gros',
                            'text-indigo-600': palierActif === 'demi_gros',
                            'text-blue-600':   palierActif === 'revendeur',
                            'text-gray-400':   palierActif === 'normal'
                        }" x-text="{
                            gros:       'Prix gros (10+ pcs)',
                            demi_gros:  'Prix demi-gros (3-9 pcs)',
                            revendeur:  'Prix revendeur (1-2 pcs)',
                            normal:     'Prix normal'
                        }[palierActif]"></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
                        <p class="text-lg font-bold text-blue-700" x-text="fmt(total) + ' cfa'"></p>
                    </div>
                    <template x-if="modePaiement === 'credit'">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Mis à crédit</p>
                            <p class="text-lg font-bold" :class="resteCredit > creditDisponible ? 'text-red-600' : 'text-purple-700'"
                               x-text="fmt(resteCredit) + ' cfa'"></p>
                        </div>
                    </template>
                    <div x-show="modePaiement === 'credit' && resteCredit > creditDisponible"
                         class="flex-1 bg-red-50 border border-red-200 rounded-lg px-4 py-2 text-sm text-red-700">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Crédit insuffisant — manque <span x-text="fmt(resteCredit - creditDisponible)"></span> cfa
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit"
                        :disabled="!selectedStockId || quantite < 1 || (selectedStock && quantite > selectedStock.quantite) || (modePaiement === 'credit' && resteCredit > creditDisponible)"
                        class="px-6 py-2 bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white rounded-lg font-semibold text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-check mr-2"></i>
                    <span x-text="modePaiement === 'credit' ? 'Valider la vente à crédit' : 'Valider la vente'"></span>
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Historique des ventes ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Historique des ventes</h3>
            <span class="text-xs text-gray-400">{{ $ventes->total() }} vente(s)</span>
        </div>

        @if($ventes->isEmpty())
            <p class="px-6 py-12 text-center text-gray-400">
                <i class="fas fa-receipt text-3xl mb-3 block"></i>
                Aucune vente enregistrée.
            </p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Article</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Qté</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Mode</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Payé</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Reste</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($ventes as $vente)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $vente->nom }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $vente->quantite }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if($vente->client_id)
                                <a href="{{ route('clients.show', $vente->client_id) }}" class="text-blue-600 hover:underline">{{ $vente->client }}</a>
                            @else
                                {{ $vente->client }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($vente->mode_paiement === 'credit')
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700">Crédit</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Comptant</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($vente->total, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700">{{ number_format($vente->montant_paye, 0, ',', ' ') }} F</td>
                        <td class="px-4 py-3 text-sm text-right {{ $vente->reste_credit > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                            {{ $vente->reste_credit > 0 ? number_format($vente->reste_credit, 0, ',', ' ') . ' F' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $vente->date->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if(session('user_role') === 'caissiere')
                            <form action="{{ route('article.annuler', $vente->id) }}" method="POST"
                                  onsubmit="return confirm('Annuler cette vente et restaurer le stock ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $ventes->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function ventePage() {
    const stocks     = {!! $stocksJson !!};
    const revendeurs = {!! $revendeursJson !!};

    return {
        stocks,
        revendeurs,
        selectedStockId: '',
        selectedStock: null,
        selectedRevendeurId: '',
        selectedRevendeur: null,
        quantite: 1,
        modePaiement: 'comptant',
        montantPaye: 0,
        prixUnitaire: 0,
        palierActif: 'normal',
        total: 0,
        resteCredit: 0,
        creditDisponible: 0,

        init() {},

        onStockChange() {
            this.selectedStock = this.stocks.find(s => s.id === this.selectedStockId) || null;
            this.calcTotal();
        },
        onRevendeurChange() {
            this.selectedRevendeur = this.revendeurs.find(r => r.id === this.selectedRevendeurId) || null;
            this.creditDisponible  = this.selectedRevendeur
                ? Math.max(0, this.selectedRevendeur.credit_limite - this.selectedRevendeur.solde_credit)
                : 0;
            this.calcTotal();
        },
        onModeChange() {
            this.montantPaye = 0;
            if (this.modePaiement === 'comptant') {
                this.selectedRevendeurId = '';
                this.selectedRevendeur   = null;
                this.creditDisponible    = 0;
            }
            this.calcTotal();
        },
        resolvePrice(stock, quantite, isRevendeur) {
            if (isRevendeur) {
                if (quantite >= 10 && stock.prixGros)       { this.palierActif = 'gros';      return stock.prixGros; }
                if (quantite >= 3  && stock.prix_demi_gros) { this.palierActif = 'demi_gros'; return stock.prix_demi_gros; }
                if (stock.prix_revendeur)                   { this.palierActif = 'revendeur'; return stock.prix_revendeur; }
            }
            this.palierActif = 'normal';
            return stock.prixVente;
        },
        calcTotal() {
            if (!this.selectedStock) { this.total = 0; this.prixUnitaire = 0; this.palierActif = 'normal'; return; }
            const isRevendeur = this.modePaiement === 'credit' || !!this.selectedRevendeurId;
            this.prixUnitaire = this.resolvePrice(this.selectedStock, this.quantite || 1, isRevendeur);
            this.total        = this.prixUnitaire * (this.quantite || 0);
            this.resteCredit  = Math.max(0, this.total - (this.montantPaye || 0));
        },
        valider() {
            if (this.modePaiement === 'credit' && this.resteCredit > this.creditDisponible) {
                alert('Crédit insuffisant pour ce revendeur.');
                return false;
            }
            return true;
        },
        fmt(n) {
            return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
    };
}
</script>
@endpush
@endsection
