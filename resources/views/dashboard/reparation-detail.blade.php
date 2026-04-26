@extends('layouts.dashboard')

@section('page-title', 'Détail Réparation')

@section('content')
<div class="bg-white rounded-xl shadow-2xl p-6 space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-800">{{ $repair->numeroReparation }}</h1>
            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $repair->type_reparation === 'place' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                {{ $repair->type_reparation === 'place' ? 'Sur Place' : 'Sur RDV' }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array(session('user_role'), ['caissiere', 'patron']))
            <a href="{{ route('reparations.receipt', $repair->id) }}" target="_blank"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                <i class="fas fa-print mr-2"></i> Imprimer Reçu
            </a>
            @endif
            <a href="{{ route('reparations.liste') }}" class="inline-flex items-center px-4 py-2 border rounded-md hover:bg-gray-50 text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ── CAISSIÈRE / PATRON : formulaire administratif ── --}}
        @if(in_array(session('user_role'), ['caissiere', 'patron']))
        <form action="{{ route('reparations.update', $repair->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <h3 class="font-semibold text-gray-700 border-b pb-2">
                <i class="fas fa-pen text-gray-400 mr-2"></i>Informations client & paiement
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Client</label>
                    <input type="text" name="client_nom" value="{{ $repair->client_nom }}"
                           class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Téléphone</label>
                    <input type="text" name="client_telephone" value="{{ $repair->client_telephone }}"
                           class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Appareil</label>
                <input type="text" name="appareil_marque_modele" value="{{ $repair->appareil_marque_modele }}"
                       class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Statut</label>
                    <select name="statut_reparation" class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
                        @foreach(['En attente', 'En cours', 'Terminé', 'Récupéré', 'Annulé'] as $s)
                            <option value="{{ $s }}" {{ $repair->statut_reparation === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Montant payé</label>
                    <input type="number" name="montant_paye" step="any" value="{{ $repair->montant_paye }}"
                           class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border no-spinner">
                </div>
            </div>

            @if($repair->type_reparation === 'rdv')
            <div>
                <label class="text-sm font-medium text-gray-600">Date RDV</label>
                <input type="date" name="date_rendez_vous"
                       value="{{ $repair->date_rendez_vous ? $repair->date_rendez_vous->format('Y-m-d') : '' }}"
                       class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
            </div>
            @endif

            <div class="flex items-center gap-3 pt-3 border-t">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i> Mettre à jour
                </button>
            </div>
        </form>
        @endif

        {{-- ── RÉPARATEUR / PATRON : formulaire diagnostic technique ── --}}
        @if(in_array(session('user_role'), ['reparateur', 'patron']))
        <form action="{{ route('reparations.diagnostic', $repair->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <h3 class="font-semibold text-gray-700 border-b pb-2">
                <i class="fas fa-wrench text-gray-400 mr-2"></i>Diagnostic technique
            </h3>

            <div>
                <label class="text-sm font-medium text-gray-600">Statut technique</label>
                <select name="statut_reparation" class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border">
                    @foreach(['En diagnostic', 'En cours', 'Terminé', 'En attente de pièces'] as $s)
                        <option value="{{ $s }}" {{ $repair->statut_reparation === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-600">Notes réparateur</label>
                <textarea name="notes_technicien" rows="3" maxlength="1000"
                          class="w-full text-sm py-2 border-gray-300 rounded-md px-3 border resize-none"
                          placeholder="Observations techniques…">{{ $repair->notes_technicien }}</textarea>
            </div>

            {{-- Pannes / Services --}}
            <div x-data="{ pannes: {{ json_encode(is_array($repair->pannes_services) && count($repair->pannes_services) ? $repair->pannes_services : [['description'=>'','montant'=>0]]) }} }">
                <label class="text-sm font-medium text-gray-600 block mb-1">Pannes / Services</label>
                <template x-for="(panne, i) in pannes" :key="i">
                    <div class="flex gap-2 mb-2">
                        <input type="text" :name="'panne_description['+i+']'" x-model="panne.description"
                               placeholder="Description panne"
                               class="flex-1 text-sm border border-gray-300 rounded-md px-3 py-2">
                        <input type="number" :name="'panne_montant['+i+']'" x-model="panne.montant"
                               placeholder="Montant" min="0" step="any"
                               class="w-28 text-sm border border-gray-300 rounded-md px-3 py-2 no-spinner">
                        <button type="button" @click="pannes.splice(i,1)"
                                class="text-red-400 hover:text-red-600 text-lg leading-none">×</button>
                    </div>
                </template>
                <button type="button" @click="pannes.push({description:'',montant:0})"
                        class="text-xs text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus mr-1"></i>Ajouter une panne
                </button>
            </div>

            {{-- Pièces de rechange --}}
            <div>
                <label class="text-sm font-medium text-gray-600 block mb-1">Pièces de rechange</label>
                @foreach(range(0, 4) as $i)
                <div class="flex gap-2 mb-2">
                    <select name="piece_stock_id[{{ $i }}]" class="flex-1 text-sm border border-gray-300 rounded-md px-3 py-2">
                        <option value="">— Sélectionner une pièce —</option>
                        @foreach($stocks as $s)
                            <option value="{{ $s->id }}"
                                @if(isset($repair->pieces_rechange_utilisees[$i]) && $repair->pieces_rechange_utilisees[$i]['stockId'] === $s->id) selected @endif>
                                {{ $s->nom }} ({{ $s->quantite }} dispo)
                            </option>
                        @endforeach
                    </select>
                    <input type="number" name="piece_quantite[{{ $i }}]" min="1" placeholder="Qté"
                           value="{{ $repair->pieces_rechange_utilisees[$i]['quantiteUtilisee'] ?? '' }}"
                           class="w-20 text-sm border border-gray-300 rounded-md px-3 py-2 no-spinner">
                </div>
                @endforeach
            </div>

            <div class="pt-3 border-t">
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white text-sm rounded-md hover:bg-orange-700">
                    <i class="fas fa-wrench mr-1"></i> Enregistrer diagnostic
                </button>
            </div>
        </form>
        @endif

        {{-- ── Résumé (toujours visible) ── --}}
        <div class="bg-gray-50 rounded-lg p-5 space-y-4 {{ in_array(session('user_role'), ['caissiere', 'patron']) && in_array(session('user_role'), ['reparateur', 'patron']) ? '' : 'lg:col-start-2' }}">
            <h3 class="font-semibold text-gray-700 border-b pb-2">Résumé</h3>

            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-gray-500">Date création:</span>
                    <p class="font-medium">{{ $repair->date_creation ? $repair->date_creation->format('d/m/Y H:i') : 'N/A' }}</p>
                </div>
                <div>
                    <span class="text-gray-500">État paiement:</span>
                    <p class="font-medium {{ $repair->etat_paiement === 'Soldé' ? 'text-green-600' : 'text-red-600' }}">{{ $repair->etat_paiement }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Total:</span>
                    <p class="font-bold text-lg">{{ number_format($repair->total_reparation, 0, ',', ' ') }} cfa</p>
                </div>
                <div>
                    <span class="text-gray-500">Reste à payer:</span>
                    <p class="font-bold text-lg {{ $repair->reste_a_payer > 0 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($repair->reste_a_payer, 0, ',', ' ') }} cfa</p>
                </div>
                @if($repair->date_retrait)
                <div>
                    <span class="text-gray-500">Date retrait:</span>
                    <p class="font-medium text-green-600">{{ $repair->date_retrait->format('d/m/Y H:i') }}</p>
                </div>
                @endif
                @if($repair->date_rendez_vous)
                <div>
                    <span class="text-gray-500">Date RDV:</span>
                    <p class="font-medium">{{ $repair->date_rendez_vous->format('d/m/Y') }}</p>
                </div>
                @endif
                @if($repair->assignedTo)
                <div class="col-span-2">
                    <span class="text-gray-500">Réparateur assigné:</span>
                    <p class="font-medium">{{ $repair->assignedTo->prenom }} {{ $repair->assignedTo->nom }}</p>
                </div>
                @endif
            </div>

            {{-- Récupération (caissière uniquement) --}}
            @if(in_array(session('user_role'), ['caissiere', 'patron']))
            <div class="border-t pt-3">
                <form action="{{ route('reparations.update', $repair->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @if($repair->date_retrait)
                        <input type="hidden" name="unmark_retrieved" value="1">
                        <button type="submit" class="text-sm px-4 py-2 bg-green-100 text-green-700 border border-green-200 rounded-md hover:bg-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Récupéré — Annuler
                        </button>
                    @else
                        <input type="hidden" name="mark_retrieved" value="1">
                        <button type="submit" class="text-sm px-4 py-2 bg-orange-100 text-orange-700 border border-orange-200 rounded-md hover:bg-orange-200">
                            <i class="fas fa-hand-holding mr-1"></i> Marquer comme récupéré
                        </button>
                    @endif
                </form>
            </div>
            @endif

            {{-- Pannes --}}
            @if(is_array($repair->pannes_services) && count($repair->pannes_services) > 0)
            <div class="border-t pt-3">
                <h4 class="font-semibold text-gray-600 mb-2">Pannes / Services</h4>
                @foreach($repair->pannes_services as $panne)
                <div class="flex justify-between text-sm py-1">
                    <span>{{ $panne['description'] ?? '' }}</span>
                    <span class="font-medium">{{ number_format($panne['montant'] ?? 0, 0, ',', ' ') }} cfa</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Pièces --}}
            @if(is_array($repair->pieces_rechange_utilisees) && count($repair->pieces_rechange_utilisees) > 0)
            <div class="border-t pt-3">
                <h4 class="font-semibold text-gray-600 mb-2">Pièces de rechange</h4>
                @foreach($repair->pieces_rechange_utilisees as $piece)
                <div class="flex justify-between text-sm py-1">
                    <span>{{ $piece['nom'] ?? '' }} (x{{ $piece['quantiteUtilisee'] ?? 0 }})</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Notes réparateur --}}
            @if($repair->notes_technicien)
            <div class="border-t pt-3">
                <h4 class="font-semibold text-gray-600 mb-1">Notes réparateur</h4>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $repair->notes_technicien }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Photos réparation --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4" x-data="{ showForm: false }">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-700">
                <i class="fas fa-camera text-gray-400 mr-2"></i>
                Photos ({{ $repair->photos->count() }}/5)
            </h3>
            @if($repair->photos->count() < 5)
            <button type="button" @click="showForm = !showForm"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1">
                <i class="fas fa-plus"></i> Ajouter une photo
            </button>
            @endif
        </div>

        {{-- Upload form --}}
        <div x-show="showForm" x-transition class="bg-gray-50 rounded-lg p-4 space-y-3">
            <form method="POST" action="{{ route('repair-photos.store', $repair->id) }}" enctype="multipart/form-data"
                class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Photo <span class="text-red-500">*</span></label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                    <p class="text-xs text-gray-400 mt-0.5">JPG, PNG ou WebP — max 5 Mo</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Type <span class="text-red-500">*</span></label>
                    <select name="type" required
                        class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                        <option value="avant">Avant réparation</option>
                        <option value="apres">Après réparation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Légende</label>
                    <div class="flex gap-2">
                        <input type="text" name="legende" placeholder="Optionnel…" maxlength="150"
                            class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-upload"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Photos grid --}}
        @if($repair->photos->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            @foreach($repair->photos as $photo)
            <div class="relative group">
                <a href="{{ $photo->url() }}" target="_blank">
                    <img src="{{ $photo->url() }}"
                        alt="{{ $photo->legende ?? $photo->type }}"
                        class="w-full h-28 object-cover rounded-lg border border-gray-200 hover:opacity-90 transition-opacity">
                </a>
                <span class="absolute top-1.5 left-1.5 text-xs font-semibold px-1.5 py-0.5 rounded
                    {{ $photo->type === 'avant' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                    {{ $photo->type === 'avant' ? 'Avant' : 'Après' }}
                </span>
                <form method="POST" action="{{ route('repair-photos.destroy', $photo->id) }}"
                    class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Supprimer cette photo ?')"
                        class="bg-red-500 hover:bg-red-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
                @if($photo->legende)
                <p class="text-xs text-gray-500 mt-1 truncate">{{ $photo->legende }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400 italic">Aucune photo pour cette réparation.</p>
        @endif
    </div>
</div>
@endsection
