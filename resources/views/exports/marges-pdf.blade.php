<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
table { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:16px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
thead th.right { text-align:right; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody td.right { text-align:right; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
tfoot td.right { text-align:right; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.kpi { display:table; width:100%; margin-bottom:16px; }
.kpi-cell { display:table-cell; width:25%; border:1px solid #e2e8f0; background:#f8fafc; padding:10px 12px; }
.kpi-label { font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px; }
.kpi-value { font-size:16px; font-weight:700; color:#1e293b; }
.kpi-green { color:#16a34a; }
.kpi-red { color:#dc2626; }
</style>
</head>
<body>

@php $reportTitle = 'RAPPORT DE MARGE'; @endphp
@include('reports._pdf_header')

{{-- KPIs --}}
<table width="100%" style="margin-bottom:18px; border-spacing:6px; border-collapse:separate;">
    <tr>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">CA total</div>
            <div style="font-size:16px; font-weight:700; color:#1e293b;">{{ number_format($totaux['ca'], 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Coût pièces</div>
            <div style="font-size:16px; font-weight:700; color:#dc2626;">{{ number_format($totaux['cout_pieces'], 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Marge brute</div>
            <div style="font-size:16px; font-weight:700; color:{{ $totaux['marge'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($totaux['marge'], 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Taux moyen</div>
            <div style="font-size:16px; font-weight:700; color:#2563eb;">{{ $totaux['taux_marge'] }}%</div>
        </td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>N° Réparation</th>
            <th>Client</th>
            <th>Appareil</th>
            <th class="right">CA (F)</th>
            <th class="right">Coût pièces (F)</th>
            <th class="right">Marge (F)</th>
            <th class="right">Taux %</th>
        </tr>
    </thead>
    <tbody>
        @forelse($lignes as $l)
        <tr>
            <td style="font-weight:700;">{{ $l['repair']->numeroReparation }}</td>
            <td>{{ $l['repair']->client_nom }}</td>
            <td>{{ $l['repair']->appareil_marque_modele }}</td>
            <td class="right">{{ number_format($l['ca'], 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($l['cout_pieces'], 0, ',', ' ') }}</td>
            <td class="right" style="font-weight:700; color:{{ $l['marge'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($l['marge'], 0, ',', ' ') }}</td>
            <td class="right">{{ $l['taux_marge'] }}%</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:12px;">Aucune réparation soldée sur cette période.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="font-weight:700;">TOTAL</td>
            <td class="right">{{ number_format($totaux['ca'], 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($totaux['cout_pieces'], 0, ',', ' ') }}</td>
            <td class="right" style="color:{{ $totaux['marge'] >= 0 ? '#16a34a' : '#dc2626' }};">{{ number_format($totaux['marge'], 0, ',', ' ') }}</td>
            <td class="right">{{ $totaux['taux_marge'] }}%</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
