<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
h3 { font-size:11px; font-weight:700; color:#1e293b; margin:14px 0 5px; border-bottom:1px solid #e2e8f0; padding-bottom:3px; }
table { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:16px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
thead th.right { text-align:right; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody td.right { text-align:right; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
tfoot td.right { text-align:right; }
.subtotal td { background:#e0f2fe; font-weight:700; font-size:9px; color:#0369a1; }
.subtotal td.right { text-align:right; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.badge { display:inline-block; padding:2px 5px; border-radius:3px; font-size:7px; font-weight:700; }
.badge-red { background:#fee2e2; color:#b91c1c; }
.badge-green { background:#dcfce7; color:#15803d; }
</style>
</head>
<body>

@php $reportTitle = 'HISTORIQUE CRÉDIT REVENDEURS'; @endphp
@include('reports._pdf_header')

@php
$grouped = $transactions->groupBy(fn($tx) => optional($tx->client)->nom ?? 'Inconnu');
$totalGeneral = $transactions->sum(fn($tx) => $tx->type === 'dette' ? $tx->montant : -$tx->montant);
@endphp

@forelse($grouped as $clientNom => $txs)
<h3>{{ $clientNom }}</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th class="right">Montant (F)</th>
            <th>Notes</th>
            <th class="right">Solde après (F)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($txs as $tx)
        <tr>
            <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
            <td>
                @if($tx->type === 'dette')
                <span class="badge badge-red">Dette</span>
                @else
                <span class="badge badge-green">{{ ucfirst($tx->type) }}</span>
                @endif
            </td>
            <td class="right" style="color:{{ $tx->type === 'dette' ? '#dc2626' : '#16a34a' }}; font-weight:700;">
                {{ $tx->type === 'dette' ? '+' : '-' }}{{ number_format($tx->montant, 0, ',', ' ') }}
            </td>
            <td>{{ $tx->notes ?: '—' }}</td>
            <td class="right">{{ number_format($tx->solde_apres ?? 0, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot class="subtotal">
        @php
        $sousTotal = $txs->sum(fn($tx) => $tx->type === 'dette' ? $tx->montant : -$tx->montant);
        @endphp
        <tr class="subtotal">
            <td colspan="2">Solde net {{ $clientNom }}</td>
            <td class="right" style="color:{{ $sousTotal > 0 ? '#dc2626' : '#16a34a' }};">{{ number_format(abs($sousTotal), 0, ',', ' ') }} F ({{ $sousTotal > 0 ? 'dû' : 'excédent' }})</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
@empty
<p style="text-align:center; color:#94a3b8; padding:20px;">Aucune transaction crédit.</p>
@endforelse

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
