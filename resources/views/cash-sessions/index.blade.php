@extends('layouts.dashboard')
@section('page-title', 'Caisse')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Caisse</h1>
            <p class="text-gray-500 text-sm mt-1">Gestion des sessions de caisse</p>
        </div>
        @php $sessionOuverte = $sessions->firstWhere('statut', 'ouverte'); @endphp
        @if(!$sessionOuverte)
        @if(session('user_role') === 'caissiere')
        <form method="POST" action="{{ route('caisse.ouvrir') }}">
            @csrf
            <div class="flex items-center gap-2">
                <div>
                    <input type="number" name="montant_ouverture" placeholder="Fonds d'ouverture (F)" min="0" step="100" required
                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-48 focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                    <i class="fas fa-cash-register"></i> Ouvrir la caisse
                </button>
            </div>
        </form>
        @else
        <span class="text-sm text-orange-600 font-medium flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i> En attente d'ouverture par la caissière
        </span>
        @endif
        @else
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-2 text-green-600 font-medium text-sm">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Caisse ouverte
            </span>
            <a href="{{ route('caisse.show', $sessionOuverte->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-eye mr-1"></i> Voir la session
            </a>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date & Heure ouv.</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Fermeture</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Caissier(e)</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Statut</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Ouverture</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Fermeture réelle</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Écart</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sessions as $session)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-gray-800">
                        {{ \Carbon\Carbon::parse($session->date)->format('d/m/Y') }}
                        <span class="text-gray-400 text-xs ml-1">{{ ($session->opened_at ?? $session->created_at)?->format('H\hi') }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($session->statut === 'fermee')
                            <span class="text-gray-700">{{ $session->closed_at?->format('H\hi') ?? '—' }}</span>
                        @else
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">En cours</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ optional($session->user)->prenom }} {{ optional($session->user)->nom }}
                    </td>
                    <td class="px-6 py-4">
                        @if($session->statut === 'ouverte')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Ouverte</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Fermée</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right text-sm">{{ number_format($session->montant_ouverture, 0, ',', ' ') }} F</td>
                    <td class="px-6 py-4 text-right text-sm">
                        {{ $session->montant_fermeture_reel !== null ? number_format($session->montant_fermeture_reel, 0, ',', ' ') . ' F' : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm">
                        @if($session->ecart !== null)
                            <span class="{{ $session->ecart < 0 ? 'text-red-600' : ($session->ecart > 0 ? 'text-yellow-600' : 'text-green-600') }} font-medium">
                                {{ $session->ecart > 0 ? '+' : '' }}{{ number_format($session->ecart, 0, ',', ' ') }} F
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('caisse.show', $session->id) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="Détail"><i class="fas fa-eye"></i></a>
                            @if($session->statut === 'fermee')
                            <a href="{{ route('caisse.z-report', $session->id) }}" class="text-gray-500 hover:text-gray-700 text-sm" title="Rapport Z"><i class="fas fa-print"></i></a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-cash-register text-3xl mb-3 block"></i>
                        Aucune session de caisse.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
