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
.badge-blue { background:#dbeafe; color:#1d4ed8; }
.badge-purple { background:#f3e8ff; color:#7e22ce; }
.alert-row td { background:#fef2f2; }
</style>
</head>
<body>

@php $reportTitle = 'LISTE DES CLIENTS'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>Téléphone</th>
            <th>Type</th>
            <th class="right">Solde crédit (F)</th>
            <th class="right">Limite crédit (F)</th>
            <th class="right">Nb réparations</th>
        </tr>
    </thead>
    <tbody>
        @forelse($clients as $c)
        @php $depasseLimite = $c->credit_limite && $c->solde_credit > $c->credit_limite; @endphp
        <tr class="{{ $depasseLimite ? 'alert-row' : '' }}">
            <td style="font-weight:700;">{{ $c->nom }}{{ $c->nom_boutique ? ' (' . $c->nom_boutique . ')' : '' }}</td>
            <td>{{ $c->telephone }}</td>
            <td>
                @if($c->type === 'revendeur')
                <span class="badge badge-purple">Revendeur</span>
                @else
                <span class="badge badge-blue">Particulier</span>
                @endif
            </td>
            <td class="right" style="{{ $c->solde_credit > 0 ? 'color:#dc2626; font-weight:700;' : 'color:#94a3b8;' }}">
                {{ $c->solde_credit > 0 ? number_format($c->solde_credit, 0, ',', ' ') : '—' }}
                @if($depasseLimite) ⚠ @endif
            </td>
            <td class="right">{{ $c->credit_limite ? number_format($c->credit_limite, 0, ',', ' ') : '—' }}</td>
            <td class="right">{{ $c->repairs_count ?? $c->repairs()->count() }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:12px;">Aucun client.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTAL : {{ $clients->count() }} client(s)</td>
            <td class="right">{{ number_format($clients->sum('solde_credit'), 0, ',', ' ') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
