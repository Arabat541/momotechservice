@extends('layouts.dashboard')
@section('page-title', 'Nouveau transfert')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="transferForm()">
    <div class="flex items-center gap-3">
        <a href="{{ route('transfers.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900">Nouveau transfert intra-boutique</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('transfers.store') }}" class="space-y-6">
            @csrf

            {{-- Boutiques --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Boutique source <span class="text-red-500">*</span>
                    </label>
                    <select name="shop_from_id" x-model="shopFromId" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('shop_from_id') border-red-500 @enderror">
                        <option value="">Sélectionner...</option>
                        @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ old('shop_from_id', $currentShopId) === $shop->id ? 'selected' : '' }}>
                            {{ $shop->nom }}
                        </option>
                        @endforeach
                    </select>
                    @error('shop_from_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Boutique destinataire <span class="text-red-500">*</span>
                    </label>
                    <select name="shop_to_id" x-model="shopToId" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('shop_to_id') border-red-500 @enderror">
                        <option value="">Sélectionner...</option>
                        @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ old('shop_to_id') === $shop->id ? 'selected' : '' }}>
                            {{ $shop->nom }}
                        </option>
                        @endforeach
                    </select>
                    @error('shop_to_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div x-show="shopFromId && shopToId && shopFromId === shopToId" class="text-red-600 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> La boutique source et la boutique destinataire doivent être différentes.
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
                <textarea name="notes" rows="2" placeholder="Motif du transfert..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 text-sm">{{ old('notes') }}</textarea>
            </div>

            {{-- Lignes articles --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Articles à transférer</h2>
                    <button type="button" @click="addLine()"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                        <i class="fas fa-plus"></i> Ajouter un article
                    </button>
                </div>

                <div x-show="!shopFromId" class="text-gray-400 text-sm italic py-4 text-center">
                    Sélectionnez d'abord la boutique source pour voir les articles disponibles.
                </div>

                <div x-show="shopFromId" class="space-y-3">
                    <template x-for="(line, idx) in lines" :key="idx">
                        <div class="grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded-lg">
                            <div class="col-span-7">
                                <label x-show="idx === 0" class="block text-xs text-gray-500 mb-1">Article (stock de la boutique source)</label>
                                <select :name="'lignes['+idx+'][stock_id]'" x-model="line.stock_id"
                                    @change="onStockChange($event, idx)" required
                                    class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="">Choisir un article...</option>
                                    @foreach($stocks as $shopId => $shopStocks)
                                    <optgroup label="{{ $shops->firstWhere('id', $shopId)?->nom ?? $shopId }}"
                                        x-show="shopFromId === '{{ $shopId }}'">
                                        @foreach($shopStocks as $stock)
                                        <option value="{{ $stock->id }}"
                                            data-shop="{{ $shopId }}"
                                            data-nom="{{ $stock->nom }}"
                                            data-max="{{ $stock->quantite }}">
                                            {{ $stock->nom }} — stock : {{ $stock->quantite }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-3">
                                <label x-show="idx === 0" class="block text-xs text-gray-500 mb-1">
                                    Quantité <span x-show="line.max" class="text-gray-400">(max <span x-text="line.max"></span>)</span>
                                </label>
                                <input type="number" :name="'lignes['+idx+'][quantite]'"
                                    x-model="line.quantite" min="1" :max="line.max || 9999" required
                                    class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                <p x-show="line.max && line.quantite > line.max"
                                    class="text-red-500 text-xs mt-0.5">Quantité supérieure au stock disponible.</p>
                            </div>
                            <div class="col-span-2 flex justify-end">
                                <button type="button" @click="removeLine(idx)" x-show="lines.length > 1"
                                    class="text-red-400 hover:text-red-600 p-2 rounded-lg hover:bg-red-50">
                                    <i class="fas fa-trash text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                @error('lignes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Résumé --}}
            <div x-show="lines.some(l => l.nom)" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-blue-800 mb-2">Récapitulatif du transfert</h3>
                <ul class="space-y-1">
                    <template x-for="line in lines.filter(l => l.nom)" :key="line.stock_id">
                        <li class="text-sm text-blue-700 flex items-center gap-2">
                            <i class="fas fa-box text-xs"></i>
                            <span x-text="line.nom"></span>
                            <span class="text-blue-500">×</span>
                            <span class="font-semibold" x-text="line.quantite"></span>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    :disabled="shopFromId && shopToId && shopFromId === shopToId"
                    class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-lg font-medium flex items-center gap-2">
                    <i class="fas fa-exchange-alt"></i> Créer le transfert
                </button>
                <a href="{{ route('transfers.index') }}" class="text-gray-600 hover:text-gray-800 px-6 py-2 rounded-lg border border-gray-300">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function transferForm() {
    return {
        shopFromId: '{{ old('shop_from_id', $currentShopId) }}',
        shopToId:   '{{ old('shop_to_id') }}',
        lines: [{ stock_id: '', nom: '', quantite: 1, max: null }],

        addLine() {
            this.lines.push({ stock_id: '', nom: '', quantite: 1, max: null });
        },
        removeLine(idx) {
            this.lines.splice(idx, 1);
        },
        onStockChange(e, idx) {
            const opt = e.target.selectedOptions[0];
            if (opt && opt.dataset.nom) {
                this.lines[idx].nom = opt.dataset.nom;
                this.lines[idx].max = parseInt(opt.dataset.max) || null;
                if (this.lines[idx].quantite > this.lines[idx].max) {
                    this.lines[idx].quantite = this.lines[idx].max;
                }
            } else {
                this.lines[idx].nom = '';
                this.lines[idx].max = null;
            }
        },
    }
}
</script>
@endsection
