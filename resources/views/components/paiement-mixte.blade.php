@props([
    'namePrefix'         => 'lignes',
    'total'              => 0,
    'showChequeVirement' => false,
])

<div x-data="paiementMixte({{ (float) $total }}, '{{ $namePrefix }}')">
    <template x-for="(ligne, idx) in lignes" :key="idx">
        <div class="flex gap-2 items-center mb-1">
            <select :name="prefix + '[' + idx + '][moyen]'" x-model="ligne.moyen"
                    class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">— Moyen —</option>
                <option value="especes">Espèces</option>
                <option value="orange_money">Orange Money</option>
                <option value="moov_money">Moov Money</option>
                <option value="wave">Wave</option>
                <option value="mtn_money">MTN Money</option>
                @if($showChequeVirement)
                <option value="cheque">Chèque</option>
                <option value="virement">Virement</option>
                @endif
            </select>
            <input type="number" :name="prefix + '[' + idx + '][montant]'"
                   x-model.number="ligne.montant" step="any" min="0.01" placeholder="Montant"
                   @input="calcTotal()"
                   class="w-28 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 no-spinner">
            <button type="button" @click="removeLigne(idx)" x-show="lignes.length > 1"
                    class="text-red-400 hover:text-red-600 text-sm flex-shrink-0 px-1">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </template>

    <button type="button" @click="addLigne()"
            class="text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1 mt-1">
        <i class="fas fa-plus-circle"></i> Ajouter un moyen
    </button>

    <div class="mt-2 text-xs text-gray-500 flex gap-4">
        <span>Saisi : <strong class="text-gray-800" x-text="fmt(totalSaisi) + ' F'"></strong></span>
        <template x-if="refTotal > 0">
            <span :class="reste < 0 ? 'text-red-600' : (reste === 0 ? 'text-green-600' : 'text-purple-600')">
                Reste : <strong x-text="fmt(reste) + ' F'"></strong>
            </span>
        </template>
    </div>
</div>

@once
@push('scripts')
<script>
function paiementMixte(refTotal, prefix) {
    return {
        refTotal:   refTotal,
        prefix:     prefix,
        lignes:     [{ moyen: '', montant: '' }],
        totalSaisi: 0,
        reste:      refTotal,

        addLigne() {
            this.lignes.push({ moyen: '', montant: '' });
        },
        removeLigne(idx) {
            if (this.lignes.length > 1) {
                this.lignes.splice(idx, 1);
                this.calcTotal();
            }
        },
        calcTotal() {
            this.totalSaisi = this.lignes.reduce((s, l) => s + (parseFloat(l.montant) || 0), 0);
            this.reste      = this.refTotal - this.totalSaisi;
        },
        estValide() {
            return this.lignes.some(l => l.moyen !== '' && parseFloat(l.montant) > 0);
        },
        fmt(n) {
            return Math.round(n || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
    };
}
</script>
@endpush
@endonce
