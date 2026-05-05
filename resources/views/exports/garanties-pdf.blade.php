<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
table { width:100%; border-collapse:collapse; font-size:9px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.badge { display:inline-block; padding:2px 5px; border-radius:3px; font-size:7px; font-weight:700; }
.badge-green { background:#dcfce7; color:#15803d; }
.badge-orange { background:#ffedd5; color:#c2410c; }
.badge-gray { background:#f1f5f9; color:#475569; }
.badge-red { background:#fee2e2; color:#b91c1c; }
</style>
</head>
<body>

@php $reportTitle = 'GARANTIES PIÈCES DÉTACHÉES'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>Désignation</th>
            <th>Client</th>
            <th>Statut</th>
            <th>Date début</th>
            <th>Date expiration</th>
            <th>Durée (jours)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($warranties as $w)
        <tr>
            <td style="font-weight:700;">{{ $w->designation }}</td>
            <td>{{ optional($w->client)->nom ?? '—' }}</td>
            <td>
                @if($w->statut === 'active')
                <span class="badge badge-green">Active</span>
                @elseif($w->statut === 'utilisee')
                <span class="badge badge-orange">Utilisée</span>
                @elseif($w->statut === 'expiree')
                <span class="badge badge-red">Expirée</span>
                @else
                <span class="badge badge-gray">{{ $w->statut }}</span>
                @endif
            </td>
            <td>{{ $w->date_debut ? \Carbon\Carbon::parse($w->date_debut)->format('d/m/Y') : '—' }}</td>
            <td>{{ $w->date_expiration ? \Carbon\Carbon::parse($w->date_expiration)->format('d/m/Y') : '—' }}</td>
            <td>{{ $w->duree_jours }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:12px;">Aucune garantie.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">TOTAL : {{ $warranties->count() }} garantie(s)</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
