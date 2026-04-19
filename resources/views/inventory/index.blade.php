@extends('layouts.dashboard')
@section('page-title', 'Inventaires')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Inventaires</h1>
            <p class="text-gray-500 text-sm mt-1">Sessions de comptage physique du stock</p>
        </div>
        @php $enCours = $sessions->firstWhere('statut', 'en_cours'); @endphp
        <div class="flex items-center gap-2">
            @if(session('user_role') === 'patron')
            <a href="{{ route('export.module', 'inventaires') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-csv"></i> CSV
            </a>
            @endif
            @if(!$enCours)
            <form method="POST" action="{{ route('inventory.ouvrir') }}">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                    <i class="fas fa-clipboard-list"></i> Démarrer un inventaire
                </button>
            </form>
            @else
            <a href="{{ route('inventory.show', $enCours->id) }}" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-play-circle"></i> Continuer l'inventaire en cours
            </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Créé par</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Clôturé le</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sessions as $session)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-gray-800">
                        {{ \Carbon\Carbon::parse($session->created_at)->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ optional($session->createdBy)->prenom }} {{ optional($session->createdBy)->nom }}
                    </td>
                    <td class="px-6 py-4">
                        @if($session->statut === 'en_cours')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700">En cours</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Terminé</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $session->closed_at ? \Carbon\Carbon::parse($session->closed_at)->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('inventory.show', $session->id) }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-eye"></i></a>
                            @if($session->statut === 'termine')
                            <a href="{{ route('inventory.rapport', $session->id) }}" class="text-gray-500 hover:text-gray-700 text-sm" title="Rapport"><i class="fas fa-print"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-boxes text-3xl mb-3 block"></i>
                        Aucun inventaire réalisé.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
