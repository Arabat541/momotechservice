@extends('layouts.dashboard')

@section('page-title', 'Paramètres')

@section('content')
<div class="space-y-8 pb-12" x-data="parametresPage()">
    <h3 class="text-2xl font-bold text-gradient">Paramètres de l'application</h3>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Company info --}}
        <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
            <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
                <i class="fas fa-file-lines mr-2 text-blue-600"></i>
                Informations de l'entreprise
            </h4>
            <form action="{{ route('parametres.update') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nom de l'entreprise</label>
                    <input type="text" name="nomEntreprise" value="{{ $settings->companyInfo['nom'] ?? '' }}"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Slogan</label>
                    <input type="text" name="slogan" value="{{ $settings->companyInfo['slogan'] ?? '' }}"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Adresse</label>
                    <input type="text" name="adresse" value="{{ $settings->companyInfo['adresse'] ?? '' }}"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Téléphone</label>
                    <input type="text" name="telephone" value="{{ $settings->companyInfo['telephone'] ?? '' }}"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Hidden warranty fields to keep values --}}
                <input type="hidden" name="duree_garantie" value="{{ $settings->warranty['duree'] ?? '7' }}">
                <input type="hidden" name="message_garantie" value="{{ $settings->warranty['conditions'] ?? '' }}">

                <button type="submit" class="mt-4 w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-2 rounded-lg font-semibold">
                    <i class="fas fa-save mr-2"></i> Enregistrer Infos Entreprise
                </button>
            </form>
        </div>

        {{-- Warranty --}}
        <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
            <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
                <i class="fas fa-shield-halved mr-2 text-green-600"></i>
                Paramètres de garantie
            </h4>
            <form action="{{ route('parametres.update') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Durée de garantie (jours)</label>
                    <input type="number" name="duree_garantie" value="{{ $settings->warranty['duree'] ?? '7' }}"
                           class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-green-500 no-spinner">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Message de garantie</label>
                    <textarea name="message_garantie" rows="3"
                              class="w-full px-4 py-2 border-slate-300 rounded-lg border focus:ring-2 focus:ring-green-500">{{ $settings->warranty['conditions'] ?? '' }}</textarea>
                </div>

                {{-- Hidden company fields to keep values --}}
                <input type="hidden" name="nomEntreprise" value="{{ $settings->companyInfo['nom'] ?? '' }}">
                <input type="hidden" name="slogan" value="{{ $settings->companyInfo['slogan'] ?? '' }}">
                <input type="hidden" name="adresse" value="{{ $settings->companyInfo['adresse'] ?? '' }}">
                <input type="hidden" name="telephone" value="{{ $settings->companyInfo['telephone'] ?? '' }}">

                <button type="submit" class="mt-4 w-full bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white py-2 rounded-lg font-semibold">
                    <i class="fas fa-save mr-2"></i> Enregistrer Paramètres Garantie
                </button>
            </form>
        </div>
    </div>

    {{-- User management --}}
    <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
        <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <i class="fas fa-user mr-2 text-indigo-600"></i>
            Gestion des utilisateurs
        </h4>

        <div class="flex flex-wrap gap-2 mb-4">
            <a href="{{ route('users.export.csv') }}" class="inline-flex items-center px-3 py-1.5 text-sm border rounded-md hover:bg-gray-50">
                <i class="fas fa-file-csv mr-1"></i> Exporter CSV
            </a>
        </div>

        <h5 class="text-md font-medium text-slate-600 mb-3">Liste des utilisateurs</h5>
        @if($users->isEmpty())
            <p class="text-center text-slate-500 p-4">Aucun utilisateur trouvé.</p>
        @else
        <div class="space-y-3">
            @foreach($users as $u)
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center">
                    <i class="fas {{ $u->role === 'patron' ? 'fa-shield-halved text-amber-500' : 'fa-user text-blue-500' }} text-xl mr-3"></i>
                    <div>
                        <p class="font-medium text-slate-800">{{ $u->nom }} {{ $u->prenom }}</p>
                        <p class="text-xs text-slate-500">{{ $u->email }}</p>
                        <span class="text-xs text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full capitalize">{{ $u->role }}</span>
                    </div>
                </div>
                @if($user->id !== $u->id && $u->role !== 'patron')
                <form action="{{ route('users.destroy', $u->id) }}" method="POST"
                      onsubmit="return confirm('Supprimer le compte de {{ addslashes($u->email) }} ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:bg-red-100 hover:text-red-600 px-3 py-1.5 rounded text-sm">
                        <i class="fas fa-trash mr-1"></i> Supprimer
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- Create employee form --}}
        <div class="mt-6 pt-6 border-t border-slate-200">
            <h5 class="text-md font-medium text-slate-600 mb-3 flex items-center">
                <i class="fas fa-user-plus mr-2 text-green-600"></i>
                Créer un compte employé
            </h5>
            <form action="{{ route('users.register') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Nom</label>
                    <input type="text" name="nom" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Prénoms</label>
                    <input type="text" name="prenom" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Email</label>
                    <input type="email" name="email" required placeholder="email@example.com" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Mot de passe</label>
                    <input type="password" name="password" required minlength="8" placeholder="Min. 8 caractères" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white py-2 rounded-lg font-semibold">
                        <i class="fas fa-user-plus mr-2"></i> Créer l'employé
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Shop management --}}
    <div class="bg-white rounded-xl shadow-xl p-6 border border-slate-200">
        <h4 class="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <i class="fas fa-store mr-2 text-purple-600"></i>
            Gestion des boutiques
        </h4>

        @if($shops->isEmpty())
            <p class="text-center text-slate-500 p-4">Aucune boutique configurée.</p>
        @else
        <div class="space-y-4">
            @foreach($shops as $shop)
            @php $isCurrent = session('current_shop_id') === $shop->id; @endphp
            <div class="p-4 rounded-lg border {{ $isCurrent ? 'border-purple-300 bg-purple-50' : 'border-slate-200 bg-slate-50' }} shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-store text-purple-500"></i>
                        <span class="font-semibold text-slate-800">{{ $shop->nom }}</span>
                        @if($isCurrent)
                            <span class="text-xs bg-purple-200 text-purple-700 px-2 py-0.5 rounded-full">Active</span>
                        @endif
                    </div>
                    <form action="{{ route('shops.destroy', $shop->id) }}" method="POST"
                          onsubmit="return confirm('Supprimer la boutique {{ addslashes($shop->nom) }} et toutes ses données ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:bg-red-100 hover:text-red-600 px-3 py-1.5 rounded text-sm">
                            <i class="fas fa-trash mr-1"></i> Supprimer
                        </button>
                    </form>
                </div>
                {{-- Edit shop form --}}
                <div x-data="{ editing: false, nom: '{{ addslashes($shop->nom) }}', adresse: '{{ addslashes($shop->adresse) }}', telephone: '{{ addslashes($shop->telephone) }}' }">
                    <div class="flex items-center gap-2 mb-3">
                        <button @click="editing = !editing" class="text-xs text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-2 py-1 rounded">
                            <i class="fas fa-edit mr-1"></i> <span x-text="editing ? 'Annuler' : 'Modifier'"></span>
                        </button>
                    </div>
                    <form x-show="editing" x-cloak action="{{ route('shops.update', $shop->id) }}" method="POST" class="mb-3 space-y-2 bg-white p-3 rounded border border-blue-200">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs text-slate-500">Nom</label>
                                <input type="text" name="nom" x-model="nom" class="w-full text-sm border rounded px-2 py-1" required>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500">Adresse</label>
                                <input type="text" name="adresse" x-model="adresse" class="w-full text-sm border rounded px-2 py-1">
                            </div>
                            <div>
                                <label class="text-xs text-slate-500">Téléphone</label>
                                <input type="text" name="telephone" x-model="telephone" class="w-full text-sm border rounded px-2 py-1">
                            </div>
                        </div>
                        <button type="submit" class="text-xs bg-green-500 text-white px-3 py-1.5 rounded hover:bg-green-600">
                            <i class="fas fa-save mr-1"></i> Enregistrer
                        </button>
                    </form>
                    <div x-show="!editing">
                        <div class="text-sm text-slate-600 mb-3">
                            <p>Adresse: {{ $shop->adresse ?: '-' }}</p>
                            <p>Téléphone: {{ $shop->telephone ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Users in shop --}}
                <div class="border-t pt-2">
                    <p class="text-xs font-medium text-slate-500 mb-1">Utilisateurs assignés:</p>
                    @foreach($shop->users as $shopUser)
                    <div class="flex items-center justify-between py-1">
                        <span class="text-sm">{{ $shopUser->nom }} {{ $shopUser->prenom }} ({{ $shopUser->email }})</span>
                        @if($shopUser->role !== 'patron')
                        <form action="{{ route('shops.removeUser', $shop->id) }}" method="POST"
                              onsubmit="return confirm('Retirer {{ addslashes($shopUser->email) }} de cette boutique ?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="user_id" value="{{ $shopUser->id }}">
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                    @endforeach

                    {{-- Add user to shop --}}
                    <form action="{{ route('shops.addUser', $shop->id) }}" method="POST" class="flex items-center gap-2 mt-2">
                        @csrf
                        <select name="user_id" class="flex-1 text-xs border rounded px-2 py-1">
                            <option value="">Ajouter un utilisateur...</option>
                            @foreach($users->where('role', '!=', 'patron') as $availableUser)
                                @if(!$shop->users->contains('id', $availableUser->id))
                                <option value="{{ $availableUser->id }}">{{ $availableUser->nom }} {{ $availableUser->prenom }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="submit" class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                            <i class="fas fa-user-plus"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function parametresPage() {
    return {};
}
</script>
@endpush
@endsection
