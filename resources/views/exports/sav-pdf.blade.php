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
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.badge { display:inline-block; padding:2px 5px; border-radius:3px; font-size:7px; font-weight:700; }
.badge-green { background:#dcfce7; color:#15803d; }
.badge-orange { background:#ffedd5; color:#c2410c; }
.badge-blue { background:#dbeafe; color:#1d4ed8; }
.badge-red { background:#fee2e2; color:#b91c1c; }
</style>
</head>
<body>

@php $reportTitle = 'DOSSIERS SAV'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>N° SAV</th>
            <th>Client</th>
            <th>Appareil</th>
            <th>Réparation liée</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Décision</th>
        </tr>
    </thead>
    <tbody>
        @forelse($savs as $sav)
        <tr>
            <td style="font-weight:700;">{{ $sav->numeroSAV }}</td>
            <td>{{ $sav->client_nom }}<br><span style="color:#94a3b8; font-size:8px;">{{ $sav->client_telephone }}</span></td>
            <td>{{ $sav->appareil_marque_modele }}</td>
            <td>{{ $sav->numeroReparationOrigine ?: '—' }}</td>
            <td>
                @if($sav->statut === 'Résolu')
                <span class="badge badge-green">Résolu</span>
                @elseif($sav->statut === 'En cours')
                <span class="badge badge-blue">En cours</span>
                @elseif($sav->statut === 'Refusé')
                <span class="badge badge-red">Refusé</span>
                @else
                <span class="badge badge-orange">En attente</span>
                @endif
            </td>
            <td>{{ $sav->date_creation ? \Carbon\Carbon::parse($sav->date_creation)->format('d/m/Y') : '—' }}</td>
            <td style="font-size:8px; color:#475569;">{{ $sav->decision ? \Illuminate\Support\Str::limit($sav->decision, 60) : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:12px;">Aucun dossier SAV.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7">TOTAL : {{ $savs->count() }} dossier(s) SAV</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
