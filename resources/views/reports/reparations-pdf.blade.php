<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:20px; }
    h3 { font-size:11px; font-weight:700; color:#1e293b; margin:16px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
    table { width:100%; border-collapse:collapse; font-size:9.5px; }
    thead th { background:#f1f5f9; padding:5px 7px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
    thead th.right { text-align:right; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 7px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    tbody td.right { text-align:right; }
    tfoot td { padding:5px 7px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
    tfoot td.right { text-align:right; }
</style>
</head>
<body>

@php $reportTitle = 'RAPPORT DES RÉPARATIONS'; @endphp
@include('reports._pdf_header')

{{-- KPIs --}}
<table width="100%" style="margin-bottom:16px; border-spacing:5px; border-collapse:separate;">
    <tr>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Total</div>
            <div style="font-size:16px; font-weight:700; color:#1e293b;">{{ $total }}</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Taux clôture</div>
            <div style="font-size:16px; font-weight:700; color:{{ $tauxCloture >= 70 ? '#16a34a' : ($tauxCloture >= 40 ? '#ea580c' : '#dc2626') }};">{{ $tauxCloture }}%</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Délai moyen</div>
            <div style="font-size:16px; font-weight:700; color:#2563eb;">{{ $delaiMoyen }} j</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:3px;">CA facturé</div>
            <div style="font-size:16px; font-weight:700; color:#1e293b;">{{ number_format($totalCA, 0, ',', ' ') }} F</div>
        </td>
    </tr>
</table>

{{-- Répartition + Top pannes --}}
<table width="100%" style="margin-bottom:16px; border-collapse:separate; border-spacing:8px 0;">
    <tr valign="top">
        <td width="48%">
            <h3>Répartition par statut</h3>
            <table>
                <thead><tr><th>Statut</th><th class="right">Nb</th><th class="right">%</th></tr></thead>
                <tbody>
                    @foreach($repartition as $s => $cnt)
                    <tr>
                        <td>{{ $s }}</td>
                        <td class="right" style="font-weight:700;">{{ $cnt }}</td>
                        <td class="right" style="color:#64748b;">{{ $total > 0 ? round($cnt / $total * 100) : 0 }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
        <td width="4%"></td>
        <td width="48%">
            <h3>Top 10 pannes</h3>
            <table>
                <thead><tr><th>#</th><th>Panne</th><th class="right">Nb</th></tr></thead>
                <tbody>
                    @php $i = 1; @endphp
                    @foreach($topPannes as $panne => $cnt)
                    <tr>
                        <td style="color:#94a3b8;">{{ $i++ }}</td>
                        <td>{{ $panne }}</td>
                        <td class="right" style="font-weight:700; color:#2563eb;">{{ $cnt }}</td>
                    </tr>
                    @endforeach
                    @if(empty($topPannes))
                    <tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:8px;">—</td></tr>
                    @endif
                </tbody>
            </table>
        </td>
    </tr>
</table>

{{-- Tableau détaillé --}}
<h3>Détail des réparations</h3>
<table>
    <thead>
        <tr>
            <th>N°</th>
            <th>Client</th>
            <th>Appareil</th>
            <th>Statut</th>
            <th class="right">Total (F)</th>
            <th class="right">Payé (F)</th>
            <th class="right">Reste (F)</th>
            <th>Boutique</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($repairs as $r)
        <tr>
            <td style="font-family:monospace; color:#2563eb; font-size:8.5px;">{{ $r->numeroReparation }}</td>
            <td>{{ $r->client_nom }}</td>
            <td>{{ $r->appareil_marque_modele }}</td>
            <td>{{ $r->statut_reparation }}</td>
            <td class="right">{{ number_format($r->total_reparation, 0, ',', ' ') }}</td>
            <td class="right" style="color:#16a34a;">{{ number_format($r->montant_paye, 0, ',', ' ') }}</td>
            <td class="right" style="color:{{ $r->reste_a_payer > 0 ? '#dc2626' : '#94a3b8' }}; font-weight:{{ $r->reste_a_payer > 0 ? '700' : '400' }};">
                {{ $r->reste_a_payer > 0 ? number_format($r->reste_a_payer, 0, ',', ' ') : '—' }}
            </td>
            <td>{{ $r->shop?->nom ?? '—' }}</td>
            <td>{{ $r->date_creation?->format('d/m/Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:12px;">Aucune réparation.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL</td>
            <td class="right">{{ number_format($totalCA, 0, ',', ' ') }}</td>
            <td class="right" style="color:#16a34a;">{{ number_format($totalPaye, 0, ',', ' ') }}</td>
            <td class="right" style="color:#dc2626;">{{ number_format($totalRestant, 0, ',', ' ') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
