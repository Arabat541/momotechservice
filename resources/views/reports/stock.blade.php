@extends('layouts.dashboard')
@section('page-title', 'Rapport Stock')

@section('content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rapport stock</h1>
            <p class="text-gray-500 text-sm mt-1">État et valorisation des stocks</p>
        </div>
        <a href="{{ route('reports.stock.pdf', request()->query()) }}" target="_blank"
           class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-file-pdf"></i> Exporter PDF
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Boutique</label>
                <select name="boutique_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Toutes les boutiques</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ $boutiqueId == $shop->id ? 'selected' : '' }}>{{ $shop->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Catégorie</label>
                <select name="categorie" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ $categorie === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter mr-1"></i>Filtrer
            </button>
        </form>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Références</p>
            <p class="text-2xl font-bold text-gray-900">{{ $nbTotal }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Valorisation</p>
            <p class="text-2xl font-bold text-blue-700">{{ number_format($valorisation, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Sous seuil</p>
            <p class="text-2xl font-bold {{ $sousSeuil->count() > 0 ? 'text-orange-500' : 'text-gray-400' }}">{{ $sousSeuil->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Épuisés</p>
            <p class="text-2xl font-bold {{ $epuises->count() > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $epuises->count() }}</p>
        </div>
    </div>

    {{-- Articles sous seuil --}}
    @if($sousSeuil->count() > 0)
    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
        <h3 class="font-semibold text-orange-800 mb-3"><i class="fas fa-exclamation-triangle mr-2"></i>Articles sous seuil d'alerte ({{ $sousSeuil->count() }})</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
            @foreach($sousSeuil as $s)
            <div class="bg-white rounded-lg px-3 py-2 border border-orange-100 text-sm">
                <p class="font-medium text-gray-800 truncate">{{ $s->nom }}</p>
                <p class="text-orange-600 text-xs">{{ $s->quantite }} / {{ $s->seuil_alerte }} min.</p>
                @if($s->shop) <p class="text-gray-400 text-xs">{{ $s->shop->nom }}</p> @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tableau complet --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Inventaire complet</h3>
            <span class="text-xs text-gray-400">{{ $stocks->count() }} article(s)</span>
        </div>
        @if($stocks->isEmpty())
            <p class="px-6 py-12 text-center text-gray-400"><i class="fas fa-box-open text-3xl mb-3 block"></i>Aucun article.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Article</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qté</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Seuil</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">P. Achat (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">P. Vente (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Valorisation (F)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">État</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($stocks as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $s->nom }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $s->categorie ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold {{ $s->quantite == 0 ? 'text-red-600' : ($s->isUnderThreshold() ? 'text-orange-500' : 'text-gray-900') }}">{{ $s->quantite }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-400">{{ $s->seuil_alerte ?: '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($s->prixAchat, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($s->prixVente, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-blue-700">{{ number_format($s->quantite * $s->prixAchat, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $s->shop?->nom ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($s->quantite == 0)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700">Épuisé</span>
                            @elseif($s->isUnderThreshold())
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-700">Faible</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">OK</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-sm font-bold text-gray-700">TOTAL VALORISATION</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-blue-700">{{ number_format($valorisation, 0, ',', ' ') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
