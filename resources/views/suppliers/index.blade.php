@extends('layouts.dashboard')
@section('page-title', 'Fournisseurs')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fournisseurs</h1>
            <p class="text-gray-500 text-sm mt-1">{{ $suppliers->total() }} fournisseur(s)</p>
        </div>
        <a href="{{ route('suppliers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
            <i class="fas fa-plus"></i> Nouveau fournisseur
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Nom</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Contact</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Téléphone</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Délai livraison</th>
                    <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Conditions</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Réappros</th>
                    <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($suppliers as $supplier)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-medium text-gray-900">
                        {{ $supplier->nom }}
                        @if(!$supplier->actif)
                        <span class="ml-2 px-1.5 py-0.5 text-xs rounded bg-gray-100 text-gray-500">Inactif</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $supplier->contact_nom ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $supplier->telephone ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ $supplier->delai_livraison_jours ? $supplier->delai_livraison_jours . ' j' : '—' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $supplier->conditions_paiement ?? '—' }}</td>
                    <td class="px-6 py-4 text-right text-sm text-gray-700">{{ $supplier->reappros_count ?? 0 }}</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('suppliers.show', $supplier->id) }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('suppliers.edit', $supplier->id) }}" class="text-gray-500 hover:text-gray-700 text-sm"><i class="fas fa-edit"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-truck text-3xl mb-3 block"></i>
                        Aucun fournisseur enregistré.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($suppliers->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $suppliers->links() }}</div>
        @endif
    </div>
</div>
@endsection
