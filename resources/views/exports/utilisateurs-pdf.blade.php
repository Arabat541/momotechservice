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
.badge-blue { background:#dbeafe; color:#1d4ed8; }
.badge-gray { background:#f1f5f9; color:#475569; }
</style>
</head>
<body>

@php $reportTitle = 'LISTE DES UTILISATEURS'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Boutiques assignées</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $u)
        <tr>
            <td style="font-weight:700;">{{ $u->nom }}</td>
            <td>{{ $u->prenom }}</td>
            <td>{{ $u->email }}</td>
            <td>
                @if($u->role === 'patron')
                <span class="badge badge-blue">Patron</span>
                @else
                <span class="badge badge-gray">Caissier(e)</span>
                @endif
            </td>
            <td>{{ $u->shops->pluck('nom')->implode(', ') ?: '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:12px;">Aucun utilisateur.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">TOTAL : {{ $users->count() }} utilisateur(s)</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
