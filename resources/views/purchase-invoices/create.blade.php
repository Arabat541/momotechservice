@extends('layouts.dashboard')
@section('page-title', 'Nouvelle facture fournisseur')

@section('content')
<div class="max-w-3xl mx-auto space-y-6" x-data="purchaseInvoiceForm()">
    <div class="flex items-center gap-3">
        <a href="{{ route('purchase-invoices.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900">Nouvelle facture fournisseur</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('purchase-invoices.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur <span class="text-red-500">*</span></label>
                    <select name="supplier_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Sélectionner...</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ (old('supplier_id', $supplierId) == $s->id) ? 'selected' : '' }}>{{ $s->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">N° de facture fournisseur</label>
                    <input type="text" name="numero_externe" value="{{ old('numero_externe') }}" placeholder="Référence interne du fournisseur"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de la facture <span class="text-red-500">*</span></label>
                    <input type="date" name="date_facture" value="{{ old('date_facture', date('Y-m-d')) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'échéance</label>
                    <input type="date" name="date_echeance" value="{{ old('date_echeance') }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Lignes --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Lignes de la facture</h2>
                    <button type="button" @click="addLine()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Ajouter une ligne
                    </button>
                </div>
                <div class="space-y-3">
                    <template x-for="(line, idx) in lines" :key="idx">
                        <div class="grid grid-cols-12 gap-2 items-end">
                            <div class="col-span-5">
                                <label x-show="idx === 0" class="block text-xs text-gray-500 mb-1">Article</label>
                                <select :name="'lines['+idx+'][stock_id]'" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">Article libre</option>
                                    @foreach($stocks as $stock)
                                    <option value="{{ $stock->id }}">{{ $stock->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-3">
                                <label x-show="idx === 0" class="block text-xs text-gray-500 mb-1">Désignation</label>
                                <input type="text" :name="'lines['+idx+'][designation]'" x-model="line.designation" required
                                    class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Description">
                            </div>
                            <div class="col-span-1">
                                <label x-show="idx === 0" class="block text-xs text-gray-500 mb-1">Qté</label>
                                <input type="number" :name="'lines['+idx+'][quantite]'" x-model="line.quantite" min="1" required
                                    @input="calcTotal()" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="col-span-2">
                                <label x-show="idx === 0" class="block text-xs text-gray-500 mb-1">Prix unit. (F)</label>
                                <input type="number" :name="'lines['+idx+'][prix_unitaire]'" x-model="line.prix" min="0" required
                                    @input="calcTotal()" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button type="button" @click="removeLine(idx)" x-show="lines.length > 1"
                                    class="text-red-400 hover:text-red-600 p-2"><i class="fas fa-trash text-sm"></i></button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Total --}}
            <div class="flex justify-end pt-2">
                <div class="bg-gray-50 rounded-lg px-6 py-3 text-right">
                    <div class="text-xs text-gray-500 mb-1">Total facture</div>
                    <div class="text-xl font-bold text-gray-900" x-text="formatAmount(total) + ' F CFA'"></div>
                    <input type="hidden" name="montant_total" :value="total">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
                <a href="{{ route('purchase-invoices.index') }}" class="text-gray-600 hover:text-gray-800 px-6 py-2 rounded-lg border border-gray-300">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
function purchaseInvoiceForm() {
    return {
        lines: [{ designation: '', quantite: 1, prix: 0 }],
        total: 0,
        addLine() {
            this.lines.push({ designation: '', quantite: 1, prix: 0 });
        },
        removeLine(idx) {
            this.lines.splice(idx, 1);
            this.calcTotal();
        },
        calcTotal() {
            this.total = this.lines.reduce((sum, l) => sum + (Number(l.quantite) * Number(l.prix)), 0);
        },
        formatAmount(n) {
            return new Intl.NumberFormat('fr-FR').format(n);
        }
    }
}
</script>
@endsection
