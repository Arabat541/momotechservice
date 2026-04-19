@extends('layouts.dashboard')
@section('page-title', 'Catalogue pannes par modèle')

@section('content')
<div class="space-y-6" x-data="{ openModel: null }">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Catalogue pannes</h1>
            <p class="text-gray-500 text-sm mt-1">Pré-définissez les pannes courantes par modèle d'appareil</p>
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

    {{-- Ajouter un modèle --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Ajouter un modèle d'appareil</h2>
        <form method="POST" action="{{ route('panne-templates.store-model') }}"
              class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Marque <span class="text-red-500">*</span></label>
                <input type="text" name="marque" placeholder="Apple, Samsung…" required maxlength="100"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Modèle <span class="text-red-500">*</span></label>
                <input type="text" name="modele" placeholder="iPhone 14, Galaxy S23…" required maxlength="100"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type <span class="text-red-500">*</span></label>
                <select name="type" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="smartphone">Smartphone</option>
                    <option value="tablette">Tablette</option>
                    <option value="ordinateur">Ordinateur</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-plus"></i> Ajouter
            </button>
        </form>
    </div>

    {{-- Liste des modèles --}}
    @forelse($models as $model)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50"
             @click="openModel = openModel === {{ $model->id }} ? null : {{ $model->id }}">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-{{ $model->type === 'smartphone' ? 'mobile-alt' : ($model->type === 'tablette' ? 'tablet-alt' : ($model->type === 'ordinateur' ? 'laptop' : 'microchip')) }} text-blue-600 text-sm"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-900">{{ $model->marque }} {{ $model->modele }}</p>
                    <p class="text-xs text-gray-500 capitalize">{{ $model->type }} — {{ $model->panneTemplates->count() }} panne(s) prédéfinie(s)</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('panne-templates.destroy-model', $model->id) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Supprimer ce modèle et toutes ses pannes ?')"
                        class="text-red-400 hover:text-red-600 text-sm p-1" @click.stop>
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                <i class="fas fa-chevron-down text-gray-400 text-sm transition-transform"
                   :class="openModel === {{ $model->id }} ? 'rotate-180' : ''"></i>
            </div>
        </div>

        <div x-show="openModel === {{ $model->id }}" x-transition class="border-t border-gray-100">
            {{-- Pannes existantes --}}
            @if($model->panneTemplates->count() > 0)
            <div class="divide-y divide-gray-100">
                @foreach($model->panneTemplates as $template)
                <div class="flex items-center justify-between px-6 py-3">
                    <div>
                        <span class="text-sm text-gray-800">{{ $template->description }}</span>
                        @if($template->prix_estime > 0)
                        <span class="ml-3 text-xs text-gray-500">{{ number_format($template->prix_estime, 0, ',', ' ') }} cfa</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('panne-templates.destroy-template', $template->id) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Supprimer cette panne ?')"
                            class="text-red-400 hover:text-red-600 text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Ajouter une panne --}}
            <div class="px-6 py-4 bg-gray-50">
                <form method="POST" action="{{ route('panne-templates.store-template', $model->id) }}"
                      class="flex gap-3 items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                        <input type="text" name="description" placeholder="Ex: Remplacement écran…" required maxlength="255"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="w-36">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Prix estimé (cfa)</label>
                        <input type="number" name="prix_estime" min="0" placeholder="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit"
                        class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <i class="fas fa-plus mr-1"></i> Ajouter
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-12 text-center text-gray-400">
        <i class="fas fa-mobile-alt text-3xl mb-3 block"></i>
        Aucun modèle d'appareil. Ajoutez-en un ci-dessus.
    </div>
    @endforelse
</div>
@endsection
