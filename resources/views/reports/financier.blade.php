@extends('layouts.dashboard')
@section('page-title', 'Rapport Financier')

@section('content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rapport financier</h1>
            <p class="text-gray-500 text-sm mt-1">Recettes, dépenses et sessions de caisse</p>
        </div>
        <a href="{{ route('reports.financier.pdf', request()->query()) }}" target="_blank"
           class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            <i class="fas fa-file-pdf"></i> Exporter PDF
        </a>
    </div>

    {{-- Filtres --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Du</label>
                <input type="date" name="debut" value="{{ $debut }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Au</label>
                <input type="date" name="fin" value="{{ $fin }}"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Boutique</label>
                <select name="boutique_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Toutes les boutiques</option>
                    @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ $boutiqueId == $shop->id ? 'selected' : '' }}>{{ $shop->nom }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                <i class="fas fa-filter mr-1"></i>Filtrer
            </button>
        </form>
    </div>

    {{-- KPIs recettes / dépenses --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Recettes réparations</p>
            <p class="text-2xl font-bold text-green-700">{{ number_format($recettesReparations, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Recettes ventes</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($recettesVentes, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 border-l-4 border-l-green-500">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total recettes</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalRecettes, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Dépenses fournisseurs</p>
            <p class="text-2xl font-bold text-red-600">{{ number_format($depensesTotal, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 col-span-1 sm:col-span-2 lg:col-span-2 border-l-4 {{ $beneficeBrut >= 0 ? 'border-l-blue-500' : 'border-l-red-500' }}">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Bénéfice brut</p>
            <p class="text-3xl font-bold {{ $beneficeBrut >= 0 ? 'text-blue-700' : 'text-red-600' }}">
                {{ $beneficeBrut >= 0 ? '+' : '' }}{{ number_format($beneficeBrut, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span>
            </p>
        </div>
    </div>

    {{-- Charges dues --}}
    @if($chargesDues->count() > 0)
    <div class="bg-red-50 border border-red-200 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-red-200">
            <h3 class="font-semibold text-red-800"><i class="fas fa-exclamation-circle mr-2"></i>Charges dues — factures fournisseurs impayées ({{ $chargesDues->count() }})</h3>
        </div>
        <table class="w-full">
            <thead class="bg-red-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-red-700 uppercase">Fournisseur</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-red-700 uppercase">N° Facture</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-red-700 uppercase">Total (F)</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-red-700 uppercase">Payé (F)</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-red-700 uppercase">Reste (F)</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-red-700 uppercase">Échéance</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-red-100">
                @foreach($chargesDues as $f)
                <tr class="hover:bg-red-50 bg-white">
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $f->supplier?->nom ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm font-mono text-gray-600">{{ $f->numero }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ number_format($f->montant_total, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm text-right text-green-700">{{ number_format($f->montant_paye, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-red-700">{{ number_format($f->reste_a_payer, 0, ',', ' ') }}</td>
                    <td class="px-4 py-3 text-xs {{ $f->date_echeance && $f->date_echeance < now()->toDateString() ? 'text-red-700 font-bold' : 'text-gray-500' }}">
                        {{ $f->date_echeance ? \Carbon\Carbon::parse($f->date_echeance)->format('d/m/Y') : '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-red-100 border-t-2 border-red-200">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-red-800">TOTAL CHARGES DUES</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-red-800">{{ number_format($chargesDues->sum('reste_a_payer'), 0, ',', ' ') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Sessions caisse --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Sessions de caisse</h3>
            <span class="text-xs text-gray-400">{{ $sessions->count() }} session(s)</span>
        </div>
        @if($sessions->isEmpty())
            <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucune session de caisse sur cette période.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ouverture (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Attendu (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Réel (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Écart (F)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sessions as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-700">{{ \Carbon\Carbon::parse($s->date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $s->shop?->nom ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($s->statut === 'ouverte')
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Ouverte</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Fermée</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($s->montant_ouverture, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $s->montant_fermeture_attendu ? number_format($s->montant_fermeture_attendu, 0, ',', ' ') : '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-800">{{ $s->montant_fermeture_reel ? number_format($s->montant_fermeture_reel, 0, ',', ' ') : '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium {{ $s->ecart === null ? 'text-gray-300' : ($s->ecart >= 0 ? 'text-green-600' : 'text-red-600') }}">
                            {{ $s->ecart !== null ? ($s->ecart >= 0 ? '+' : '') . number_format($s->ecart, 0, ',', ' ') : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="3" class="px-4 py-3 text-sm font-bold text-gray-700">TOTAL</td>
                        <td class="px-4 py-3 text-sm text-right font-bold">{{ number_format($totalOuverture, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-sm text-right font-bold">{{ number_format($totalFermeture, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold {{ $totalEcart >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $totalEcart >= 0 ? '+' : '' }}{{ number_format($totalEcart, 0, ',', ' ') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
