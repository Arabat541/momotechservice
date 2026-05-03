<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:11px; color:#1e293b; padding:24px; }
    .kpi-grid { display:table; width:100%; margin-bottom:20px; }
    .kpi-cell { display:table-cell; width:25%; border:1px solid #e2e8f0; border-radius:6px; padding:10px 12px; background:#f8fafc; }
    .kpi-label { font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px; }
    .kpi-value { font-size:18px; font-weight:700; color:#1e293b; }
    .kpi-value.green { color:#16a34a; }
    .kpi-value.orange { color:#ea580c; }
    .kpi-value.blue { color:#2563eb; }
    h3 { font-size:12px; font-weight:700; color:#1e293b; margin:18px 0 8px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
    table { width:100%; border-collapse:collapse; font-size:10px; }
    thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:9px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
    thead th.right { text-align:right; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    tbody td.right { text-align:right; }
    tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
    tfoot td.right { text-align:right; }
    .badge { display:inline-block; padding:2px 6px; border-radius:9999px; font-size:8px; font-weight:700; }
    .badge-green { background:#dcfce7; color:#15803d; }
    .badge-purple { background:#f3e8ff; color:#7e22ce; }
    .two-col { display:table; width:100%; margin-bottom:20px; }
    .col-half { display:table-cell; width:50%; vertical-align:top; padding-right:10px; }
    .col-half:last-child { padding-right:0; padding-left:10px; }
</style>
</head>
<body>

@php $reportTitle = 'RAPPORT DES VENTES'; @endphp
@include('reports._pdf_header')

{{-- KPIs --}}
<table width="100%" style="margin-bottom:18px; border-spacing:6px; border-collapse:separate;">
    <tr>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">CA total</div>
            <div style="font-size:17px; font-weight:700; color:#1e293b;">{{ number_format($totalCA, 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Encaissé</div>
            <div style="font-size:17px; font-weight:700; color:#16a34a;">{{ number_format($totalEncaisse, 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">En attente crédit</div>
            <div style="font-size:17px; font-weight:700; color:{{ $totalCredit > 0 ? '#ea580c' : '#94a3b8' }};">{{ number_format($totalCredit, 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Nb ventes</div>
            <div style="font-size:17px; font-weight:700; color:#2563eb;">{{ $nbVentes }}</div>
        </td>
    </tr>
</table>

{{-- Top 10 --}}
<h3>Top 10 articles vendus</h3>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Article</th>
            <th class="right">Quantité</th>
            <th class="right">CA (F)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($top10 as $i => $art)
        <tr>
            <td style="color:#94a3b8;">{{ $i + 1 }}</td>
            <td>{{ $art['nom'] }}</td>
            <td class="right">{{ $art['quantite'] }}</td>
            <td class="right" style="font-weight:700;">{{ number_format($art['ca'], 0, ',', ' ') }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:12px;">Aucune vente.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Détail --}}
<h3>Détail des ventes</h3>
<table>
    <thead>
        <tr>
            <th>Article</th>
            <th>Client</th>
            <th>Mode</th>
            <th class="right">Qté</th>
            <th class="right">Total (F)</th>
            <th class="right">Encaissé (F)</th>
            <th class="right">Crédit (F)</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ventes as $v)
        <tr>
            <td>{{ $v->nom }}</td>
            <td>{{ $v->client ?: '—' }}</td>
            <td>{{ $v->mode_paiement === 'credit' ? 'Crédit' : 'Comptant' }}</td>
            <td class="right">{{ $v->quantite }}</td>
            <td class="right">{{ number_format($v->total, 0, ',', ' ') }}</td>
            <td class="right" style="color:#16a34a;">{{ number_format($v->montant_paye, 0, ',', ' ') }}</td>
            <td class="right" style="color:{{ $v->reste_credit > 0 ? '#ea580c' : '#94a3b8' }};">{{ $v->reste_credit > 0 ? number_format($v->reste_credit, 0, ',', ' ') : '—' }}</td>
            <td>{{ $v->date->format('d/m/Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:12px;">Aucune vente sur cette période.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL</td>
            <td class="right">{{ number_format($totalCA, 0, ',', ' ') }}</td>
            <td class="right" style="color:#16a34a;">{{ number_format($totalEncaisse, 0, ',', ' ') }}</td>
            <td class="right" style="color:#ea580c;">{{ number_format($totalCredit, 0, ',', ' ') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
