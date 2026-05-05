<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
table { width:100%; border-collapse:collapse; font-size:9px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
thead th.right { text-align:right; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody td.right { text-align:right; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
tfoot td.right { text-align:right; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.badge { display:inline-block; padding:2px 5px; border-radius:3px; font-size:7px; font-weight:700; }
.badge-green { background:#dcfce7; color:#15803d; }
.badge-orange { background:#ffedd5; color:#c2410c; }
</style>
</head>
<body>

@php $reportTitle = 'VENTES D\'ARTICLES'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Article</th>
            <th>Client</th>
            <th class="right">Qté</th>
            <th class="right">Prix unit. (F)</th>
            <th class="right">Total (F)</th>
            <th>Mode paiement</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ventes as $v)
        <tr>
            <td>{{ \Carbon\Carbon::parse($v->date)->format('d/m/Y') }}</td>
            <td style="font-weight:700;">{{ $v->nom }}</td>
            <td>{{ $v->client ?: '—' }}</td>
            <td class="right">{{ $v->quantite }}</td>
            <td class="right">{{ number_format($v->prixVente, 0, ',', ' ') }}</td>
            <td class="right" style="font-weight:700;">{{ number_format($v->total, 0, ',', ' ') }}</td>
            <td>{{ $v->mode_paiement === 'credit' ? 'Crédit' : 'Comptant' }}</td>
            <td>
                @if($v->statut === 'soldee')
                <span class="badge badge-green">Soldée</span>
                @else
                <span class="badge badge-orange">Crédit</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:12px;">Aucune vente.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">TOTAL CA ({{ $ventes->count() }} vente(s))</td>
            <td class="right" style="color:#16a34a;">{{ number_format($ventes->sum('total'), 0, ',', ' ') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
