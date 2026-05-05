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
.badge-red { background:#fee2e2; color:#b91c1c; }
.badge-orange { background:#ffedd5; color:#c2410c; }
</style>
</head>
<body>

@php $reportTitle = 'APPAREILS NON RÉCUPÉRÉS'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>N° Réparation</th>
            <th>Client</th>
            <th>Appareil</th>
            <th>Terminée le</th>
            <th class="right">Délai dépassé (j)</th>
            <th>Date limite</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @forelse($repairs as $r)
        @php
            $dateRef   = $r->date_terminee ?? $r->date_creation;
            $jours     = $dateRef ? (int) now()->diffInDays($dateRef) : 0;
        @endphp
        <tr>
            <td style="font-weight:700;">{{ $r->numeroReparation }}</td>
            <td>{{ $r->client_nom }}</td>
            <td>{{ $r->appareil_marque_modele }}</td>
            <td>{{ $dateRef?->format('d/m/Y') ?? '—' }}</td>
            <td class="right" style="font-weight:700; color:{{ $jours > 60 ? '#dc2626' : '#ea580c' }};">{{ $jours }}</td>
            <td>{{ $r->date_limite_recuperation ? \Carbon\Carbon::parse($r->date_limite_recuperation)->format('d/m/Y') : '—' }}</td>
            <td>
                @if($r->mis_en_vente)
                <span class="badge badge-orange">Mis en vente</span>
                @else
                <span class="badge badge-red">Non récupéré</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:12px;">Aucun appareil abandonné.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7">TOTAL : {{ count($repairs) }} appareil(s) non récupéré(s)</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
