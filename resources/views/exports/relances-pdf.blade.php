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
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.badge { display:inline-block; padding:2px 5px; border-radius:3px; font-size:7px; font-weight:700; }
.badge-orange { background:#ffedd5; color:#c2410c; }
.badge-red { background:#fee2e2; color:#b91c1c; }
</style>
</head>
<body>

@php $reportTitle = 'RELANCES CLIENTS'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>N° Réparation</th>
            <th>Client</th>
            <th>Téléphone</th>
            <th>Appareil</th>
            <th>Terminée le</th>
            <th class="right">Jours d'attente</th>
            <th class="right">Nb relances</th>
            <th>Dernière relance</th>
        </tr>
    </thead>
    <tbody>
        @forelse($repairs as $r)
        @php
            $jours = $r->date_terminee ? (int) now()->diffInDays($r->date_terminee) : 0;
        @endphp
        <tr>
            <td style="font-weight:700;">{{ $r->numeroReparation }}</td>
            <td>{{ $r->client_nom }}</td>
            <td>{{ $r->client_telephone }}</td>
            <td>{{ $r->appareil_marque_modele }}</td>
            <td>{{ $r->date_terminee?->format('d/m/Y') ?? '—' }}</td>
            <td class="right" style="font-weight:700; color:{{ $jours > 14 ? '#dc2626' : '#ea580c' }};">{{ $jours }}</td>
            <td class="right">
                @if($r->relance_count > 0)
                <span class="badge badge-orange">{{ $r->relance_count }}</span>
                @else
                <span style="color:#94a3b8;">0</span>
                @endif
            </td>
            <td>{{ $r->derniere_relance ? \Carbon\Carbon::parse($r->derniere_relance)->format('d/m/Y H:i') : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:12px;">Aucune relance en attente.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8">TOTAL : {{ $repairs->total() }} réparation(s) en attente de récupération</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
