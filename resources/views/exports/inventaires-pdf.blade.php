<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
h3 { font-size:11px; font-weight:700; color:#1e293b; margin:14px 0 5px; border-bottom:1px solid #e2e8f0; padding-bottom:3px; }
table { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:14px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
thead th.right { text-align:right; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody td.right { text-align:right; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
tfoot td.right { text-align:right; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.ecart-pos { color:#16a34a; font-weight:700; }
.ecart-neg { color:#dc2626; font-weight:700; }
</style>
</head>
<body>

@php $reportTitle = 'INVENTAIRES'; @endphp
@include('reports._pdf_header')

@forelse($sessions as $session)
<h3>Session {{ $session->created_at->format('d/m/Y') }} — {{ $session->statut === 'cloturee' ? 'Clôturée' : 'En cours' }}</h3>
<table>
    <thead>
        <tr>
            <th>Article</th>
            <th class="right">Qté théorique</th>
            <th class="right">Qté comptée</th>
            <th class="right">Écart</th>
            <th class="right">Valeur écart (F)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($session->lines as $line)
        @php $ecart = $line->quantite_comptee - $line->quantite_theorique; @endphp
        <tr>
            <td>{{ optional($line->stock)->nom ?? '—' }}</td>
            <td class="right">{{ $line->quantite_theorique }}</td>
            <td class="right">{{ $line->quantite_comptee }}</td>
            <td class="right {{ $ecart > 0 ? 'ecart-pos' : ($ecart < 0 ? 'ecart-neg' : '') }}">
                {{ $ecart > 0 ? '+' : '' }}{{ $ecart }}
            </td>
            <td class="right {{ $ecart < 0 ? 'ecart-neg' : '' }}">
                {{ number_format(abs($ecart) * (optional($line->stock)->prixVente ?? 0), 0, ',', ' ') }}
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">Sous-total session</td>
            <td class="right">{{ $session->lines->sum(fn($l) => $l->quantite_comptee - $l->quantite_theorique) > 0 ? '+' : '' }}{{ $session->lines->sum(fn($l) => $l->quantite_comptee - $l->quantite_theorique) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@empty
<p style="text-align:center; color:#94a3b8; padding:20px;">Aucun inventaire.</p>
@endforelse

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
