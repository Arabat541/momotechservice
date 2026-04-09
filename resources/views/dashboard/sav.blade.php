@extends('layouts.dashboard')

@section('page-title', 'Service Après-Vente (SAV)')

@section('content')
<div class="space-y-6" x-data="savPage()">
    <div class="flex justify-between items-center">
        <h3 class="text-xl font-semibold text-gray-800">Dossiers SAV</h3>
        <button @click="showCreate = true"
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white rounded-md text-sm font-semibold">
            <i class="fas fa-plus mr-2"></i> Nouveau dossier SAV
        </button>
    </div>

    {{-- SAV list --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        @if($savs->isEmpty())
            <p class="p-6 text-center text-gray-500">
                <i class="fas fa-shield-halved text-4xl text-gray-300 block mb-2"></i>
                Aucun dossier SAV pour le moment.
            </p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° SAV</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Appareil</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Réparation liée</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Garantie</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($savs as $sav)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-blue-600">{{ $sav->numeroSAV }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div>{{ $sav->client_nom }}</div>
                            <div class="text-xs text-gray-500">{{ $sav->client_telephone }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $sav->appareil_marque_modele }}</td>
                        <td class="px-4 py-3 text-sm">{{ $sav->numeroReparationOrigine ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($sav->sous_garantie)
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">Sous garantie</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-700">Hors garantie</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full
                                {{ $sav->statut === 'Résolu' ? 'bg-green-100 text-green-800' :
                                   ($sav->statut === 'Refusé' ? 'bg-red-100 text-red-800' :
                                   ($sav->statut === 'En cours' ? 'bg-yellow-100 text-yellow-800' :
                                   'bg-gray-100 text-gray-800')) }}">
                                {{ $sav->statut }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">{{ $sav->date_creation ? $sav->date_creation->format('d/m/Y') : 'N/A' }}</td>
                        <td class="px-4 py-3 text-center space-x-1">
                            <button @click="openEdit({{ $sav->toJson() }})"
                                    class="text-blue-600 hover:text-blue-800 p-1">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form action="{{ route('sav.destroy', $sav->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Supprimer ce dossier SAV ?')">
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
            {{ $savs->links() }}
        </div>
        @endif
    </div>

    {{-- Create Modal --}}
    <div x-show="showCreate" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showCreate = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Nouveau dossier SAV</h3>
                <button @click="showCreate = false" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <form action="{{ route('sav.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">N° Réparation d'origine (optionnel)</label>
                    <div class="relative">
                        <input type="text" name="numeroReparationOrigine" placeholder="REP-XXXXXXXX"
                               class="w-full border rounded-lg px-3 py-2" x-model="repairNumero" @input.debounce.500ms="lookupRepair()">
                        <span x-show="lookupLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <i class="fas fa-spinner fa-spin text-gray-400"></i>
                        </span>
                        <span x-show="lookupFound === true" class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500">
                            <i class="fas fa-check-circle"></i>
                        </span>
                        <span x-show="lookupFound === false && repairNumero.length > 3" class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500">
                            <i class="fas fa-times-circle"></i>
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-show="lookupFound !== true">Si renseigné, les infos client seront auto-remplies</p>
                    <p class="text-xs text-green-600 mt-1" x-show="lookupFound === true"><i class="fas fa-check mr-1"></i>Réparation trouvée — infos auto-remplies</p>
                    <p class="text-xs text-red-500 mt-1" x-show="lookupFound === false && repairNumero.length > 3"><i class="fas fa-exclamation-triangle mr-1"></i>Aucune réparation trouvée avec ce numéro</p>
                    <template x-if="lookupGarantie !== null">
                        <p class="text-xs mt-1" :class="lookupGarantie ? 'text-green-600' : 'text-orange-600'">
                            <i class="fas fa-shield-halved mr-1"></i>
                            <span x-text="lookupGarantie ? 'Sous garantie' + (lookupDateFin ? ' (jusqu\'au ' + lookupDateFin + ')' : '') : 'Hors garantie'"></span>
                        </p>
                    </template>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                        <input type="text" name="client_nom" x-model="createNom" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="client_telephone" x-model="createTel" required class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Appareil</label>
                    <input type="text" name="appareil_marque_modele" x-model="createAppareil" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description du problème</label>
                    <textarea name="description_probleme" required rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-2 rounded-lg font-semibold">
                    Créer le dossier SAV
                </button>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEdit" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showEdit = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Modifier dossier SAV</h3>
                <button @click="showEdit = false" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
            </div>
            <form :action="editAction" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select name="statut" x-model="editItem.statut" class="w-full border rounded-lg px-3 py-2">
                        <option value="En attente">En attente</option>
                        <option value="En cours">En cours</option>
                        <option value="Résolu">Résolu</option>
                        <option value="Refusé">Refusé</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Décision</label>
                    <input type="text" name="decision" x-model="editItem.decision" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description du problème</label>
                    <textarea name="description_probleme" x-model="editItem.description_probleme" rows="3" class="w-full border rounded-lg px-3 py-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" x-model="editItem.notes" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
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
function savPage() {
    return {
        showCreate: false,
        showEdit: false,
        editItem: { statut: '', decision: '', description_probleme: '', notes: '' },
        editAction: '',
        repairNumero: '',
        createNom: '',
        createTel: '',
        createAppareil: '',
        lookupLoading: false,
        lookupFound: null,
        lookupGarantie: null,
        lookupDateFin: null,
        openEdit(item) {
            this.editItem = {
                statut: item.statut || 'En attente',
                decision: item.decision || '',
                description_probleme: item.description_probleme || '',
                notes: item.notes || ''
            };
            this.editAction = '/dashboard/sav/' + item.id;
            this.showEdit = true;
        },
        async lookupRepair() {
            const numero = this.repairNumero.trim();
            if (numero.length < 4) {
                this.lookupFound = null;
                this.lookupGarantie = null;
                this.lookupDateFin = null;
                return;
            }
            this.lookupLoading = true;
            try {
                const resp = await fetch('/dashboard/sav/lookup-repair?numero=' + encodeURIComponent(numero));
                const data = await resp.json();
                this.lookupFound = data.found;
                if (data.found) {
                    this.createNom = data.client_nom || '';
                    this.createTel = data.client_telephone || '';
                    this.createAppareil = data.appareil_marque_modele || '';
                    this.lookupGarantie = data.sous_garantie;
                    this.lookupDateFin = data.date_fin_garantie;
                } else {
                    this.lookupGarantie = null;
                    this.lookupDateFin = null;
                }
            } catch(e) {
                this.lookupFound = null;
            }
            this.lookupLoading = false;
        }
    };
}
</script>
@endpush
@endsection
