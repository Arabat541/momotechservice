@extends('layouts.dashboard')
@section('page-title', 'Crédit revendeurs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Crédit revendeurs</h1>
        @if(session('user_role') === 'patron')
        <div class="flex items-center gap-2">
            <a href="{{ route('export.module', 'credits') }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-csv"></i> Exporter CSV
            </a>
            <a href="{{ route('export.pdf', 'credits') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Exporter PDF
            </a>
        </div>
        @endif
    </div>

    {{-- Filtre client --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-600 mb-1">Filtrer par client</label>
                <select name="client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les revendeurs</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ request('client_id') === $client->id ? 'selected' : '' }}>
                        {{ $client->nom }}{{ $client->nom_boutique ? ' — ' . $client->nom_boutique : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            @if(request('client_id'))
            <a href="{{ route('credit.index') }}" class="text-gray-500 hover:text-gray-700 px-4 py-2 rounded-lg text-sm border border-gray-300">
                <i class="fas fa-times mr-1"></i> Réinitialiser
            </a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Notes</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Montant</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Solde après</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transactions as $tx)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('clients.show', $tx->client_id) }}" class="text-blue-600 hover:underline text-sm font-medium">
                            {{ optional($tx->client)->nom }}
                        </a>
                        @if(optional($tx->client)->nom_boutique)
                        <div class="text-xs text-gray-400">{{ optional($tx->client)->nom_boutique }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $tx->type === 'dette' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                            {{ $tx->type === 'dette' ? 'Crédit accordé' : 'Remboursement' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $tx->notes ?? '—' }}</td>
                    <td class="px-6 py-4 text-right font-medium text-sm {{ $tx->type === 'dette' ? 'text-red-600' : 'text-green-600' }}">
                        {{ $tx->type === 'dette' ? '+' : '-' }}{{ number_format($tx->montant, 0, ',', ' ') }} F
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-700">{{ number_format($tx->solde_apres, 0, ',', ' ') }} F</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-coins text-3xl mb-3 block"></i>
                        Aucune transaction crédit.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($transactions, 'hasPages') && $transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $transactions->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
