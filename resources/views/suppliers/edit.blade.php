@extends('layouts.dashboard')
@section('page-title', 'Modifier fournisseur')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('suppliers.show', $supplier->id) }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900">Modifier — {{ $supplier->nom }}</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('suppliers.update', $supplier->id) }}" class="space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du fournisseur <span class="text-red-500">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom', $supplier->nom) }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 @error('nom') border-red-500 @enderror">
                    @error('nom')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom du contact</label>
                    <input type="text" name="contact_nom" value="{{ old('contact_nom', $supplier->contact_nom) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                    <input type="text" name="telephone" value="{{ old('telephone', $supplier->telephone) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $supplier->email) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Délai livraison (jours)</label>
                    <input type="number" name="delai_livraison_jours" value="{{ old('delai_livraison_jours', $supplier->delai_livraison_jours) }}" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                    <input type="text" name="adresse" value="{{ old('adresse', $supplier->adresse) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conditions de paiement</label>
                    <input type="text" name="conditions_paiement" value="{{ old('conditions_paiement', $supplier->conditions_paiement) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="actif" value="0">
                        <input type="checkbox" name="actif" value="1" {{ old('actif', $supplier->actif) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 rounded border-gray-300">
                        <span class="text-sm font-medium text-gray-700">Fournisseur actif</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
                <a href="{{ route('suppliers.show', $supplier->id) }}" class="text-gray-600 hover:text-gray-800 px-6 py-2 rounded-lg border border-gray-300">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection
