@extends('layouts.dashboard')
@section('page-title', 'Compétences réparateurs')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Compétences réparateurs</h1>
        <p class="text-gray-500 text-sm mt-1">Gérez les spécialités de chaque réparateur pour optimiser le planning</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif

    @forelse($techniciens as $tech)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                {{ mb_strtoupper(mb_substr($tech->prenom, 0, 1)) }}{{ mb_strtoupper(mb_substr($tech->nom, 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900">{{ $tech->prenom }} {{ $tech->nom }}</p>
                <p class="text-xs text-gray-500">{{ $tech->email }}</p>
            </div>
            <span class="ml-auto text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                {{ $tech->skills->count() }} compétence(s)
            </span>
        </div>

        {{-- Compétences existantes --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($tech->skills as $skill)
            <div class="flex items-center gap-1.5 bg-gray-100 rounded-full px-3 py-1 text-sm">
                <span class="text-gray-700">
                    @if($skill->marque && $skill->type_appareil)
                        {{ $skill->marque }} — {{ $skill->type_appareil }}
                    @elseif($skill->marque)
                        {{ $skill->marque }}
                    @else
                        {{ $skill->type_appareil }}
                    @endif
                </span>
                <form method="POST" action="{{ route('skills.destroy', $skill->id) }}" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-gray-400 hover:text-red-500 ml-1 text-xs leading-none">
                        <i class="fas fa-times"></i>
                    </button>
                </form>
            </div>
            @endforeach
            @if($tech->skills->isEmpty())
            <span class="text-xs text-gray-400 italic">Aucune compétence définie</span>
            @endif
        </div>

        {{-- Formulaire ajout --}}
        <form method="POST" action="{{ route('skills.store') }}"
              class="flex flex-wrap gap-3 items-end pt-3 border-t border-gray-100">
            @csrf
            <input type="hidden" name="user_id" value="{{ $tech->id }}">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Marque</label>
                <input type="text" name="marque" placeholder="Apple, Samsung…" maxlength="100"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 w-40">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Type d'appareil</label>
                <select name="type_appareil"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous types</option>
                    <option value="smartphone">Smartphone</option>
                    <option value="tablette">Tablette</option>
                    <option value="ordinateur">Ordinateur</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            <button type="submit"
                class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-1.5 rounded-lg text-sm font-medium">
                <i class="fas fa-plus mr-1"></i> Ajouter
            </button>
        </form>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-12 text-center text-gray-400">
        <i class="fas fa-user-cog text-3xl mb-3 block"></i>
        Aucun réparateur dans cette boutique.
    </div>
    @endforelse
</div>
@endsection
