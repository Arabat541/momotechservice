@extends('layouts.dashboard')

@section('page-title', "Vente d'Articles")

@section('content')
<div class="space-y-6 p-4">
    <div class="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Vente de Pièce détachée</h2>
    </div>

    {{-- Sale form --}}
    <form action="{{ route('article.vendre') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-white p-4 rounded-lg shadow border">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Article en stock</label>
            <select name="article_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Choisir un article...</option>
                @foreach($stocks as $stock)
                    <option value="{{ $stock->id }}">{{ $stock->nom }} (Stock: {{ $stock->quantite }}, PV: {{ number_format($stock->prixVente, 0, ',', ' ') }} cfa)</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Quantité</label>
            <input type="number" name="quantite" min="1" value="1" required class="w-full border rounded-lg px-3 py-2 text-sm no-spinner">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom du client</label>
            <input type="text" name="client" required placeholder="Nom du client" class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700 text-white py-2 rounded-lg font-semibold text-sm">
                <i class="fas fa-shopping-cart mr-1"></i> Vendre
            </button>
        </div>
    </form>

    {{-- Sales history + replacement parts --}}
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Articles sortis du stock</h3>
        </div>
        @if($ventes->isEmpty() && empty($sortiesRechange))
            <p class="p-6 text-center text-gray-500">Aucune sortie de stock enregistrée.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Article</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantité</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type de sortie</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($ventes as $vente)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $vente->nom }}</td>
                        <td class="px-4 py-3 text-sm">{{ $vente->quantite }}</td>
                        <td class="px-4 py-3 text-sm">{{ $vente->client }}</td>
                        <td class="px-4 py-3 text-sm">Vendu</td>
                        <td class="px-4 py-3 text-sm">{{ $vente->date->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-sm">
                            <form action="{{ route('article.annuler', $vente->id) }}" method="POST"
                                  onsubmit="return confirm('Annuler cette vente et restaurer le stock ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    <i class="fas fa-undo mr-1"></i> Annuler
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @foreach($sortiesRechange as $sortie)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $sortie['nom'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $sortie['quantite'] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $sortie['client'] }}</td>
                        <td class="px-4 py-3 text-sm">Pièce de réchange</td>
                        <td class="px-4 py-3 text-sm">{{ \Carbon\Carbon::parse($sortie['date'])->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-400">—</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
