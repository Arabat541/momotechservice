<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
h3 { font-size:11px; font-weight:700; color:#1e293b; margin:16px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
table { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:14px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
thead th.right { text-align:right; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody td.right { text-align:right; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
tfoot td.right { text-align:right; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.badge { display:inline-block; padding:2px 5px; border-radius:3px; font-size:7px; font-weight:700; }
.badge-orange { background:#ffedd5; color:#c2410c; }
.badge-green { background:#dcfce7; color:#15803d; }
</style>
</head>
<body>

@php $reportTitle = 'DASHBOARD REVENDEUR — ' . strtoupper($client->nom); @endphp
@include('reports._pdf_header')

{{-- KPIs --}}
<table width="100%" style="margin-bottom:18px; border-spacing:6px; border-collapse:separate;">
    <tr>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">CA total</div>
            <div style="font-size:16px; font-weight:700; color:#1e293b;">{{ number_format($totalAchats, 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Encaissé</div>
            <div style="font-size:16px; font-weight:700; color:#16a34a;">{{ number_format($totalComptant, 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Crédit accordé</div>
            <div style="font-size:16px; font-weight:700; color:#ea580c;">{{ number_format($totalCredit, 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; border-radius:4px; width:25%;">
            <div style="font-size:9px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Solde dû</div>
            <div style="font-size:16px; font-weight:700; color:{{ $client->solde_credit > 0 ? '#dc2626' : '#16a34a' }};">{{ number_format($client->solde_credit, 0, ',', ' ') }} F</div>
        </td>
    </tr>
</table>

{{-- Achats --}}
<h3>Achats sur la période ({{ $achats->count() }})</h3>
<table>
    <thead>
        <tr>
            <th>Article</th>
            <th class="right">Qté</th>
            <th>Mode paiement</th>
            <th class="right">Total (F)</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($achats as $a)
        <tr>
            <td style="font-weight:700;">{{ $a->nom }}</td>
            <td class="right">{{ $a->quantite }}</td>
            <td>
                @if($a->mode_paiement === 'credit')
                <span class="badge badge-orange">Crédit</span>
                @else
                <span class="badge badge-green">Comptant</span>
                @endif
            </td>
            <td class="right">{{ number_format($a->total, 0, ',', ' ') }}</td>
            <td>{{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:10px;">Aucun achat sur cette période.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTAL CA PÉRIODE</td>
            <td class="right">{{ number_format($achats->sum('total'), 0, ',', ' ') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
