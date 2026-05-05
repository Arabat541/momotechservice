@extends('layouts.dashboard')
@section('page-title', 'Transferts intra-boutique')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Transferts intra-boutique</h1>
            <p class="text-gray-500 text-sm mt-1">Mouvements de stock entre boutiques</p>
        </div>
        @if(session('user_role') === 'patron')
        <div class="flex items-center gap-2">
            <a href="{{ route('export.module', 'transferts') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-csv"></i> CSV
            </a>
            <a href="{{ route('export.pdf', 'transferts') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="{{ route('transfers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-exchange-alt"></i> Nouveau transfert
            </a>
        </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">N°</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">De</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Vers</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Statut</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Créé par</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transfers as $transfer)
                @php
                    $badgeClass = match($transfer->statut) {
                        'en_attente_envoi'      => 'bg-yellow-100 text-yellow-700',
                        'en_attente_reception'  => 'bg-blue-100 text-blue-700',
                        'completee'             => 'bg-green-100 text-green-700',
                        'annulee'               => 'bg-red-100 text-red-600',
                        default                 => 'bg-gray-100 text-gray-600',
                    };
                    $badgeLabel = match($transfer->statut) {
                        'en_attente_envoi'      => 'En attente envoi',
                        'en_attente_reception'  => 'En attente réception',
                        'completee'             => 'Complété',
                        'annulee'               => 'Annulé',
                        default                 => $transfer->statut,
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('transfers.show', $transfer->id) }}" class="text-blue-600 hover:underline font-mono text-sm font-semibold">{{ $transfer->numero }}</a>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-medium text-gray-800">{{ optional($transfer->shopFrom)->nom }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-medium text-gray-800">{{ optional($transfer->shopTo)->nom }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ optional($transfer->createdBy)->prenom }} {{ optional($transfer->createdBy)->nom }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('transfers.show', $transfer->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-exchange-alt text-3xl mb-3 block"></i>
                        Aucun transfert enregistré.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($transfers->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $transfers->links() }}</div>
        @endif
    </div>
</div>
@endsection
