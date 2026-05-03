@extends('layouts.dashboard')
@section('page-title', 'Nouveau client')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('clients.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900">Nouveau client</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="{{ route('clients.store') }}" class="space-y-5">
            @csrf

            @if(!empty($shops))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Boutique <span class="text-red-500">*</span></label>
                <select name="shop_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Sélectionner une boutique...</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ old('shop_id') == $shop->id ? 'selected' : '' }}>{{ $shop->nom }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom complet <span class="text-red-500">*</span></label>
                    <input type="text" name="nom" value="{{ old('nom') }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nom') border-red-500 @enderror">
                    @error('nom')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone <span class="text-red-500">*</span></label>
                    <input type="text" name="telephone" value="{{ old('telephone') }}" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('telephone') border-red-500 @enderror">
                    @error('telephone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type de client <span class="text-red-500">*</span></label>
                    <select name="type" x-data x-model="type" x-init="type = '{{ old('type', 'particulier') }}'"
                        @change="type = $event.target.value"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="particulier" {{ old('type') === 'particulier' || !old('type') ? 'selected' : '' }}>Particulier</option>
                        <option value="revendeur" {{ old('type') === 'revendeur' ? 'selected' : '' }}>Revendeur</option>
                    </select>
                </div>
            </div>

            <div x-data="{ type: '{{ old('type', 'particulier') }}' }">
                <div x-show="type === 'revendeur'" class="grid grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la boutique</label>
                        <input type="text" name="nom_boutique" value="{{ old('nom_boutique') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Limite de crédit (F CFA)</label>
                        <input type="number" name="credit_limite" value="{{ old('credit_limite', 0) }}" min="0" step="1000"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                </button>
                <a href="{{ route('clients.index') }}" class="text-gray-600 hover:text-gray-800 px-6 py-2 rounded-lg border border-gray-300">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
