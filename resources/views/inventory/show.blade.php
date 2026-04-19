@extends('layouts.dashboard')
@section('page-title', 'Inventaire en cours')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('inventory.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Inventaire du {{ \Carbon\Carbon::parse($session->created_at)->format('d/m/Y') }}</h1>
                <p class="text-gray-500 text-sm">Par {{ optional($session->createdBy)->prenom }} {{ optional($session->createdBy)->nom }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($session->statut === 'en_cours')
                <span class="flex items-center gap-2 text-orange-600 font-medium text-sm">
                    <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span> En cours
                </span>
            @else
                <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Terminé</span>
                <a href="{{ route('inventory.rapport', $session->id) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-print mr-1"></i> Rapport
                </a>
            @endif
        </div>
    </div>

    {{-- Progression --}}
    @php
        $total = $session->lines->count();
        $compt = $session->lines->whereNotNull('quantite_comptee')->count();
        $pct = $total > 0 ? round($compt / $total * 100) : 0;
    @endphp
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Progression : {{ $compt }} / {{ $total }} articles comptés</span>
            <span class="text-sm font-bold text-blue-600">{{ $pct }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    {{-- Lignes --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Articles à compter</h2>
            @if($session->statut === 'en_cours' && $compt === $total)
            <form method="POST" action="{{ route('inventory.cloturer', $session->id) }}">
                @csrf
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="ajuster_stocks" value="1" class="rounded">
                        Ajuster les stocks automatiquement
                    </label>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
                        onclick="return confirm('Clôturer cet inventaire ?')">
                        <i class="fas fa-lock mr-1"></i> Clôturer
                    </button>
                </div>
            </form>
            @endif
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Article</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Stock théorique</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Quantité comptée</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Écart</th>
                    @if($session->statut === 'en_cours')
                    <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($session->lines as $line)
                <tr class="hover:bg-gray-50" id="line-{{ $line->id }}">
                    <td class="px-6 py-3 text-sm font-medium text-gray-800">{{ optional($line->stock)->nom ?? '—' }}</td>
                    <td class="px-6 py-3 text-right text-sm">{{ $line->quantite_theorique }}</td>
                    <td class="px-6 py-3 text-right text-sm">
                        @if($line->quantite_comptee !== null)
                            <span class="font-semibold">{{ $line->quantite_comptee }}</span>
                        @else
                            <span class="text-gray-400 italic">Non compté</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right text-sm font-medium">
                        @if($line->quantite_comptee !== null)
                            @php $ecart = $line->quantite_comptee - $line->quantite_theorique; @endphp
                            <span class="{{ $ecart < 0 ? 'text-red-600' : ($ecart > 0 ? 'text-yellow-600' : 'text-green-600') }}">
                                {{ $ecart > 0 ? '+' : '' }}{{ $ecart }}
                            </span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    @if($session->statut === 'en_cours')
                    <td class="px-6 py-3">
                        <form method="POST" action="{{ route('inventory.saisir', [$session->id, $line->id]) }}" class="flex items-center gap-2 justify-end">
                            @csrf
                            <input type="number" name="quantite_comptee" min="0"
                                value="{{ $line->quantite_comptee ?? '' }}"
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm w-20 focus:ring-2 focus:ring-blue-500"
                                placeholder="Qté">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-medium">
                                OK
                            </button>
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
