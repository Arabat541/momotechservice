@extends('layouts.dashboard')
@section('page-title', 'Rapport Réparations')

@section('content')
<div class="space-y-6">

    {{-- En-tête --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rapport des réparations</h1>
            <p class="text-gray-500 text-sm mt-1">Activité réparation par période</p>
        </div>
        <a href="{{ route('reports.reparations.pdf', request()->query()) }}" target="_blank"
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
                <label class="block text-xs font-medium text-gray-600 mb-1">Statut</label>
                <select name="statut" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $s)
                        <option value="{{ $s }}" {{ $statut === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
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

    {{-- KPIs --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ $total }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Taux de clôture</p>
            <p class="text-2xl font-bold {{ $tauxCloture >= 70 ? 'text-green-600' : ($tauxCloture >= 40 ? 'text-orange-500' : 'text-red-500') }}">
                {{ $tauxCloture }}%
            </p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Délai moyen</p>
            <p class="text-2xl font-bold text-blue-600">{{ $delaiMoyen }}<span class="text-sm font-normal text-gray-500 ml-1">j</span></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">CA facturé</p>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCA, 0, ',', ' ') }}<span class="text-sm font-normal text-gray-500 ml-1">F</span></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Répartition statuts --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Répartition par statut</h3>
            </div>
            @if($repartition->isEmpty())
                <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucune réparation.</p>
            @else
            <div class="p-4 space-y-2">
                @php $colors = ['En attente'=>'slate','En attente de paiement'=>'amber','En cours'=>'blue','En attente de pièces'=>'orange','Terminé'=>'teal','Prêt pour retrait'=>'green','Irréparable'=>'red','Livré'=>'emerald','Annulé'=>'gray']; @endphp
                @foreach($repartition as $s => $cnt)
                @php $color = $colors[$s] ?? 'gray'; $pct = $total > 0 ? round($cnt / $total * 100) : 0; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700">{{ $s }}</span>
                        <span class="font-semibold text-gray-900">{{ $cnt }} <span class="text-gray-400 font-normal">({{ $pct }}%)</span></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-{{ $color }}-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Top 10 pannes --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Top 10 pannes</h3>
            </div>
            @if(empty($topPannes))
                <p class="px-6 py-8 text-center text-gray-400 text-sm">Aucune panne répertoriée.</p>
            @else
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Panne</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Occurrences</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($topPannes as $panne => $cnt)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-400">{{ $loop->iteration }}</td>
                        <td class="px-4 py-2 text-sm text-gray-800">{{ $panne }}</td>
                        <td class="px-4 py-2 text-sm text-right font-semibold text-blue-700">{{ $cnt }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- Tableau détaillé --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Détail des réparations</h3>
            <span class="text-xs text-gray-400">{{ $repairs->count() }} réparation(s)</span>
        </div>
        @if($repairs->isEmpty())
            <p class="px-6 py-12 text-center text-gray-400"><i class="fas fa-tools text-3xl mb-3 block"></i>Aucune réparation sur cette période.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">N°</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Appareil</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Payé (F)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Reste (F)</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Boutique</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($repairs as $r)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-xs font-mono text-blue-700">{{ $r->numeroReparation }}</td>
                        <td class="px-4 py-3 text-sm text-gray-800">{{ $r->client_nom }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $r->appareil_marque_modele }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full border {{ app(\App\Services\RepairService::class)->badgeClasses($r->statut_reparation) }}">
                                {{ $r->statut_reparation }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($r->total_reparation, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-green-700">{{ number_format($r->montant_paye, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right {{ $r->reste_a_payer > 0 ? 'text-red-600 font-medium' : 'text-gray-300' }}">
                            {{ $r->reste_a_payer > 0 ? number_format($r->reste_a_payer, 0, ',', ' ') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $r->shop?->nom ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $r->date_creation?->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-700">TOTAL</td>
                        <td class="px-4 py-3 text-sm text-right font-bold">{{ number_format($totalCA, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-green-700">{{ number_format($totalPaye, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-red-600">{{ number_format($totalRestant, 0, ',', ' ') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
