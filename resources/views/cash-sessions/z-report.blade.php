@extends('layouts.base')

@section('body')
<div class="min-h-screen bg-white p-8 max-w-2xl mx-auto">
    {{-- En-tête --}}
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">RAPPORT Z — CLÔTURE DE CAISSE</h1>
        <p class="text-gray-600 mt-1">{{ \Carbon\Carbon::parse($report['session']->date)->format('d/m/Y') }}</p>
    </div>

    {{-- Session info --}}
    <div class="border border-gray-300 rounded p-4 mb-6 text-sm">
        <div class="grid grid-cols-2 gap-2">
            <div><span class="text-gray-500">Caissier(e) :</span> <strong>{{ optional($report['session']->user)->prenom }} {{ optional($report['session']->user)->nom }}</strong></div>
            <div><span class="text-gray-500">Statut :</span> <strong>{{ $report['session']->statut === 'fermee' ? 'Clôturée' : 'Ouverte' }}</strong></div>
        </div>
    </div>

    {{-- Ventes comptant --}}
    <table class="w-full text-sm mb-6 border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="text-left px-4 py-2 border border-gray-300 font-semibold" colspan="2">VENTES COMPTANT</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300">Nombre de ventes</td>
                <td class="px-4 py-2 border border-gray-300 text-right font-medium">{{ $report['nb_ventes_comptant'] }}</td>
            </tr>
            <tr>
                <td class="px-4 py-2 border border-gray-300 font-semibold">Total ventes comptant</td>
                <td class="px-4 py-2 border border-gray-300 text-right font-bold">{{ number_format($report['total_ventes_comptant'], 0, ',', ' ') }} F</td>
            </tr>
        </tbody>
    </table>

    {{-- Ventes crédit --}}
    <table class="w-full text-sm mb-6 border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="text-left px-4 py-2 border border-gray-300 font-semibold" colspan="2">VENTES À CRÉDIT</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300">Nombre de ventes</td>
                <td class="px-4 py-2 border border-gray-300 text-right">{{ $report['nb_ventes_credit'] }}</td>
            </tr>
            <tr>
                <td class="px-4 py-2 border border-gray-300">Valeur totale accordée</td>
                <td class="px-4 py-2 border border-gray-300 text-right">{{ number_format($report['total_ventes_credit'], 0, ',', ' ') }} F</td>
            </tr>
        </tbody>
    </table>

    {{-- Acomptes réparations --}}
    <table class="w-full text-sm mb-6 border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="text-left px-4 py-2 border border-gray-300 font-semibold" colspan="2">ACOMPTES RÉPARATIONS</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300">Nombre d'acomptes</td>
                <td class="px-4 py-2 border border-gray-300 text-right">{{ $report['nb_acomptes'] }}</td>
            </tr>
            <tr>
                <td class="px-4 py-2 border border-gray-300 font-semibold">Total acomptes</td>
                <td class="px-4 py-2 border border-gray-300 text-right font-bold">{{ number_format($report['total_acomptes'], 0, ',', ' ') }} F</td>
            </tr>
        </tbody>
    </table>

    {{-- Factures soldées --}}
    <table class="w-full text-sm mb-6 border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="text-left px-4 py-2 border border-gray-300 font-semibold" colspan="2">FACTURES SOLDÉES</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300">Nombre de factures soldées</td>
                <td class="px-4 py-2 border border-gray-300 text-right">{{ $report['nb_factures_soldees'] }}</td>
            </tr>
            <tr>
                <td class="px-4 py-2 border border-gray-300 font-semibold">Total factures soldées</td>
                <td class="px-4 py-2 border border-gray-300 text-right font-bold">{{ number_format($report['total_factures_soldees'], 0, ',', ' ') }} F</td>
            </tr>
        </tbody>
    </table>

    {{-- Récapitulatif final --}}
    <table class="w-full text-sm mb-6 border-collapse">
        <thead>
            <tr class="bg-gray-900 text-white">
                <th class="text-left px-4 py-2 border border-gray-700 font-semibold" colspan="2">RÉCAPITULATIF</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300">Fonds d'ouverture</td>
                <td class="px-4 py-2 border border-gray-300 text-right">{{ number_format($report['montant_ouverture'], 0, ',', ' ') }} F</td>
            </tr>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300 font-semibold text-lg">Total encaissé</td>
                <td class="px-4 py-2 border border-gray-300 text-right font-bold text-lg text-blue-700">{{ number_format($report['total_encaisse'], 0, ',', ' ') }} F</td>
            </tr>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300">Montant attendu en caisse</td>
                <td class="px-4 py-2 border border-gray-300 text-right">{{ number_format($report['montant_fermeture_attendu'], 0, ',', ' ') }} F</td>
            </tr>
            <tr class="border-b border-gray-200">
                <td class="px-4 py-2 border border-gray-300">Montant réel compté</td>
                <td class="px-4 py-2 border border-gray-300 text-right">
                    {{ $report['montant_fermeture_reel'] !== null ? number_format($report['montant_fermeture_reel'], 0, ',', ' ') . ' F' : 'N/A' }}
                </td>
            </tr>
            <tr class="bg-gray-50">
                <td class="px-4 py-2 border border-gray-300 font-bold text-lg">ÉCART</td>
                <td class="px-4 py-2 border border-gray-300 text-right font-bold text-lg
                    {{ ($report['ecart'] ?? 0) < 0 ? 'text-red-600' : (($report['ecart'] ?? 0) > 0 ? 'text-yellow-600' : 'text-green-600') }}">
                    {{ $report['ecart'] !== null ? (($report['ecart'] > 0 ? '+' : '') . number_format($report['ecart'], 0, ',', ' ') . ' F') : 'N/A' }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Signature --}}
    <div class="grid grid-cols-2 gap-8 mt-10 text-sm text-gray-600">
        <div class="text-center">
            <div class="border-t border-gray-400 pt-2 mt-12">Signature caissier(e)</div>
        </div>
        <div class="text-center">
            <div class="border-t border-gray-400 pt-2 mt-12">Signature patron</div>
        </div>
    </div>

    <div class="text-center mt-8 text-xs text-gray-400 no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i> Imprimer
        </button>
    </div>
</div>
@endsection
