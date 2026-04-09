@extends('layouts.dashboard')

@section('page-title', 'Gestion des Stocks')

@section('content')
<div class="space-y-6" x-data="stocksPage()">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <h3 class="text-xl font-semibold text-gray-800">Gestion des stocks</h3>
        <div class="flex items-center gap-3">
            <form action="{{ route('stocks.index') }}" method="GET" class="flex items-center">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un article..."
                           class="pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-56">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                </div>
                @if(request('search'))
                <a href="{{ route('stocks.index') }}" class="ml-2 text-gray-500 hover:text-red-500 text-sm" title="Effacer">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </form>
            <button @click="showAdd = true"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white rounded-md text-sm font-semibold">
                <i class="fas fa-plus mr-2"></i> Ajouter un article
            </button>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100">Articles en stock</p>
                    <p class="text-3xl font-bold">{{ $stocks->count() }}</p>
                </div>
                <i class="fas fa-boxes-stacked text-4xl text-blue-200"></i>
            </div>
        </div>
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100">Valeur totale</p>
                    <p class="text-3xl font-bold">{{ number_format($stocks->sum(fn($s) => $s->quantite * $s->prixVente), 0, ',', ' ') }} cfa</p>
                </div>
                <i class="fas fa-credit-card text-4xl text-green-200"></i>
            </div>
        </div>
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100">Stock faible</p>
                    <p class="text-3xl font-bold">{{ $stocks->where('quantite', '<', 10)->count() }}</p>
                </div>
                <i class="fas fa-triangle-exclamation text-4xl text-orange-200"></i>
            </div>
        </div>
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100">Bénéfice net total attendu</p>
                    <p class="text-3xl font-bold">{{ number_format($stocks->sum(fn($s) => ($s->prixVente - $s->prixAchat) * $s->quantite), 0, ',', ' ') }} cfa</p>
                </div>
                <i class="fas fa-chart-line text-4xl text-purple-200"></i>
            </div>
        </div>
    </div>

    {{-- Inventory table --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-semibold text-gray-800">Inventaire</h4>
        </div>
        @if($stocks->isEmpty())
            <p class="p-6 text-center text-gray-500">Aucun article en stock. Cliquez sur "Ajouter un article" pour commencer.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Article</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix d'achat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix de vente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valeur stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bénéfice net attendu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($stocks as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->nom }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $item->quantite < 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                {{ $item->quantite }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($item->prixAchat, 0, ',', ' ') }} cfa</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($item->prixVente, 0, ',', ' ') }} cfa</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format($item->quantite * $item->prixVente, 0, ',', ' ') }} cfa</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ number_format(($item->prixVente - $item->prixAchat) * $item->quantite, 0, ',', ' ') }} cfa</td>
                        <td class="px-6 py-4 text-sm space-x-2">
                            <button @click="openReappro({{ $item->toJson() }})"
                                    class="text-green-600 hover:text-green-800 p-1" title="Réapprovisionner">
                                <i class="fas fa-truck-loading"></i>
                            </button>
                            <button @click="openHistorique('{{ $item->id }}', '{{ addslashes($item->nom) }}')"
                                    class="text-purple-600 hover:text-purple-800 p-1" title="Historique réappro">
                                <i class="fas fa-history"></i>
                            </button>
                            <button @click="openEdit({{ $item->toJson() }})"
                                    class="text-blue-600 hover:text-blue-800 p-1">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form action="{{ route('stocks.destroy', $item->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Supprimer cet article ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 p-1">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $stocks->links() }}
        </div>
        @endif
    </div>

    {{-- Add Modal --}}
    <div x-show="showAdd" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showAdd = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Ajouter un nouvel article</h3>
                <button @click="showAdd = false" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <form action="{{ route('stocks.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'article</label>
                    <input type="text" name="nom" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantité</label>
                    <input type="number" name="quantite" required min="0" class="w-full border rounded-lg px-3 py-2 no-spinner">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat (cfa)</label>
                    <input type="number" name="prixAchat" required step="any" class="w-full border rounded-lg px-3 py-2 no-spinner"
                           oninput="this.form.querySelector('[name=beneficeNetAttendu]').value = (parseFloat(this.form.querySelector('[name=prixVente]').value || 0) - parseFloat(this.value || 0))">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix de vente (cfa)</label>
                    <input type="number" name="prixVente" required step="any" class="w-full border rounded-lg px-3 py-2 no-spinner"
                           oninput="this.form.querySelector('[name=beneficeNetAttendu]').value = (parseFloat(this.value || 0) - parseFloat(this.form.querySelector('[name=prixAchat]').value || 0))">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bénéfice net attendu</label>
                    <input type="number" name="beneficeNetAttendu" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-2 rounded-lg font-semibold hover:from-green-700 hover:to-blue-700">
                    Ajouter
                </button>
            </form>
        </div>
    </div>

    {{-- Reappro Modal --}}
    <div x-show="showReappro" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showReappro = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-truck-loading mr-2 text-green-600"></i>Réapprovisionner</h3>
                <button @click="showReappro = false" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <form :action="reapproAction" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm">
                    <p class="font-semibold text-blue-800" x-text="reapproItem.nom"></p>
                    <p class="text-blue-600">Stock actuel : <span x-text="reapproItem.quantite"></span> | PA actuel : <span x-text="formatCFA(reapproItem.prixAchat)"></span> cfa</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantité reçue</label>
                    <input type="number" name="quantite" x-model.number="reapproQte" required min="1" class="w-full border rounded-lg px-3 py-2 no-spinner" @input="calcCMP()">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat unitaire (cfa)</label>
                    <input type="number" name="prixAchatUnitaire" x-model.number="reapproPrix" required step="any" min="0" class="w-full border rounded-lg px-3 py-2 no-spinner" @input="calcCMP()">
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm" x-show="reapproQte > 0 && reapproPrix >= 0">
                    <p class="font-semibold text-green-800">Calcul CMP :</p>
                    <p class="text-green-700">(<span x-text="reapproItem.quantite"></span> × <span x-text="formatCFA(reapproItem.prixAchat)"></span> + <span x-text="reapproQte"></span> × <span x-text="formatCFA(reapproPrix)"></span>) / <span x-text="reapproItem.quantite + reapproQte"></span></p>
                    <p class="font-bold text-green-900 mt-1">Nouveau PA (CMP) : <span x-text="formatCFA(nouveauCMP)"></span> cfa</p>
                    <p class="text-green-700">Nouveau stock : <span x-text="reapproItem.quantite + reapproQte"></span></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fournisseur (optionnel)</label>
                    <input type="text" name="fournisseur" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note (optionnel)</label>
                    <textarea name="note" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-2 rounded-lg font-semibold hover:from-green-700 hover:to-green-800">
                    <i class="fas fa-check mr-2"></i>Valider le réapprovisionnement
                </button>
            </form>
        </div>
    </div>

    {{-- Historique Réappro Modal --}}
    <div x-show="showHistorique" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showHistorique = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold"><i class="fas fa-history mr-2 text-purple-600"></i>Historique réappro — <span x-text="historiqueNom"></span></h3>
                <button @click="showHistorique = false" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <div class="p-4 overflow-y-auto flex-1">
                <template x-if="historiqueData.length === 0">
                    <p class="text-center text-gray-500 py-8">Aucun réapprovisionnement enregistré pour cet article.</p>
                </template>
                <template x-if="historiqueData.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Qté</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">PA unitaire</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Ancien PA</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Nouveau PA (CMP)</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Fournisseur</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="h in historiqueData" :key="h.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2" x-text="formatDate(h.date)"></td>
                                        <td class="px-3 py-2"><span class="text-green-600 font-semibold" x-text="'+' + h.quantite"></span></td>
                                        <td class="px-3 py-2" x-text="formatCFA(h.prixAchatUnitaire) + ' cfa'"></td>
                                        <td class="px-3 py-2" x-text="formatCFA(h.ancienPrixAchat) + ' cfa'"></td>
                                        <td class="px-3 py-2 font-semibold text-blue-700" x-text="formatCFA(h.nouveauPrixAchat) + ' cfa'"></td>
                                        <td class="px-3 py-2 text-gray-500" x-text="h.fournisseur || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEdit" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showEdit = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Modifier l'article</h3>
                <button @click="showEdit = false" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <form :action="editAction" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'article</label>
                    <input type="text" name="nom" x-model="editItem.nom" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantité</label>
                    <input type="number" name="quantite" x-model="editItem.quantite" required min="0" class="w-full border rounded-lg px-3 py-2 no-spinner">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix d'achat (cfa)</label>
                    <input type="number" name="prixAchat" x-model="editItem.prixAchat" required step="any" class="w-full border rounded-lg px-3 py-2 no-spinner"
                           @input="editItem.beneficeNetAttendu = editItem.prixVente - editItem.prixAchat">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prix de vente (cfa)</label>
                    <input type="number" name="prixVente" x-model="editItem.prixVente" required step="any" class="w-full border rounded-lg px-3 py-2 no-spinner"
                           @input="editItem.beneficeNetAttendu = editItem.prixVente - editItem.prixAchat">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bénéfice net attendu</label>
                    <input type="number" name="beneficeNetAttendu" x-model="editItem.beneficeNetAttendu" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700">
                    Mettre à jour
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function stocksPage() {
    return {
        showAdd: false,
        showEdit: false,
        showReappro: false,
        showHistorique: false,
        editItem: { nom: '', quantite: 0, prixAchat: 0, prixVente: 0, beneficeNetAttendu: 0 },
        editAction: '',
        reapproItem: { id: '', nom: '', quantite: 0, prixAchat: 0 },
        reapproAction: '',
        reapproQte: 0,
        reapproPrix: 0,
        nouveauCMP: 0,
        historiqueNom: '',
        historiqueData: [],
        openEdit(item) {
            this.editItem = { ...item, beneficeNetAttendu: item.prixVente - item.prixAchat };
            this.editAction = '/dashboard/stocks/' + item.id;
            this.showEdit = true;
        },
        openReappro(item) {
            this.reapproItem = { id: item.id, nom: item.nom, quantite: item.quantite, prixAchat: item.prixAchat };
            this.reapproAction = '/dashboard/stocks/' + item.id + '/reappro';
            this.reapproQte = 0;
            this.reapproPrix = 0;
            this.nouveauCMP = 0;
            this.showReappro = true;
        },
        calcCMP() {
            const ancQte = this.reapproItem.quantite;
            const ancPA = this.reapproItem.prixAchat;
            const newQte = this.reapproQte || 0;
            const newPA = this.reapproPrix || 0;
            const totalQte = ancQte + newQte;
            this.nouveauCMP = totalQte > 0 ? Math.round((ancQte * ancPA + newQte * newPA) / totalQte) : 0;
        },
        async openHistorique(stockId, nom) {
            this.historiqueNom = nom;
            this.historiqueData = [];
            this.showHistorique = true;
            try {
                const resp = await fetch('/dashboard/stocks/' + stockId + '/historique-reappro');
                const data = await resp.json();
                this.historiqueData = data.historique;
            } catch(e) {
                this.historiqueData = [];
            }
        },
        formatCFA(n) {
            return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
        formatDate(d) {
            if (!d) return '---';
            const dt = new Date(d);
            return dt.toLocaleDateString('fr-FR') + ' ' + dt.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
        }
    };
}
</script>
@endpush
@endsection
