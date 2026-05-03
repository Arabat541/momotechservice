<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
    h3 { font-size:11px; font-weight:700; color:#1e293b; margin:16px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
    table { width:100%; border-collapse:collapse; font-size:9.5px; }
    thead th { background:#f1f5f9; padding:5px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
    thead th.right { text-align:right; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    tbody td.right { text-align:right; }
    tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
    tfoot td.right { text-align:right; }
    .summary-row { display:table; width:100%; margin-bottom:14px; border-collapse:separate; }
    .sum-cell { display:table-cell; padding:10px 14px; border:1px solid #e2e8f0; background:#f8fafc; }
</style>
</head>
<body>

@php $reportTitle = 'RAPPORT FINANCIER'; @endphp
@include('reports._pdf_header')

{{-- Synthèse --}}
<h3>Synthèse financière</h3>
<table style="margin-bottom:16px;">
    <tbody>
        <tr style="background:#f0fdf4;">
            <td style="padding:7px 10px; font-weight:600;">Recettes réparations</td>
            <td style="padding:7px 10px; text-align:right; font-weight:700; color:#16a34a;">{{ number_format($recettesReparations, 0, ',', ' ') }} F</td>
        </tr>
        <tr style="background:#f0fdf4;">
            <td style="padding:7px 10px; font-weight:600;">Recettes ventes articles</td>
            <td style="padding:7px 10px; text-align:right; font-weight:700; color:#16a34a;">{{ number_format($recettesVentes, 0, ',', ' ') }} F</td>
        </tr>
        <tr style="background:#dcfce7; border-top:2px solid #86efac;">
            <td style="padding:8px 10px; font-weight:700; font-size:11px;">TOTAL RECETTES</td>
            <td style="padding:8px 10px; text-align:right; font-weight:700; font-size:11px; color:#15803d;">{{ number_format($totalRecettes, 0, ',', ' ') }} F</td>
        </tr>
        <tr><td colspan="2" style="padding:4px;"></td></tr>
        <tr style="background:#fff1f2;">
            <td style="padding:7px 10px; font-weight:600;">Dépenses fournisseurs (payé)</td>
            <td style="padding:7px 10px; text-align:right; font-weight:700; color:#dc2626;">{{ number_format($depensesTotal, 0, ',', ' ') }} F</td>
        </tr>
        <tr><td colspan="2" style="padding:4px;"></td></tr>
        <tr style="background:{{ $beneficeBrut >= 0 ? '#eff6ff' : '#fff1f2' }}; border-top:2px solid {{ $beneficeBrut >= 0 ? '#93c5fd' : '#fca5a5' }};">
            <td style="padding:10px; font-weight:700; font-size:13px;">BÉNÉFICE BRUT</td>
            <td style="padding:10px; text-align:right; font-weight:700; font-size:13px; color:{{ $beneficeBrut >= 0 ? '#1d4ed8' : '#dc2626' }};">
                {{ $beneficeBrut >= 0 ? '+' : '' }}{{ number_format($beneficeBrut, 0, ',', ' ') }} F
            </td>
        </tr>
    </tbody>
</table>

{{-- Charges dues --}}
@if($chargesDues->count() > 0)
<h3 style="color:#c2410c;">Charges dues — factures fournisseurs impayées</h3>
<table style="margin-bottom:16px;">
    <thead>
        <tr>
            <th>Fournisseur</th>
            <th>N° Facture</th>
            <th class="right">Total (F)</th>
            <th class="right">Payé (F)</th>
            <th class="right">Reste (F)</th>
            <th>Échéance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($chargesDues as $f)
        <tr>
            <td>{{ $f->supplier?->nom ?? '—' }}</td>
            <td style="font-family:monospace; font-size:9px;">{{ $f->numero }}</td>
            <td class="right">{{ number_format($f->montant_total, 0, ',', ' ') }}</td>
            <td class="right" style="color:#16a34a;">{{ number_format($f->montant_paye, 0, ',', ' ') }}</td>
            <td class="right" style="font-weight:700; color:#dc2626;">{{ number_format($f->reste_a_payer, 0, ',', ' ') }}</td>
            <td style="color:{{ $f->date_echeance && $f->date_echeance < now()->toDateString() ? '#dc2626' : '#64748b' }}; font-weight:{{ $f->date_echeance && $f->date_echeance < now()->toDateString() ? '700' : '400' }};">
                {{ $f->date_echeance ? \Carbon\Carbon::parse($f->date_echeance)->format('d/m/Y') : '—' }}
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL CHARGES DUES</td>
            <td class="right" style="color:#dc2626;">{{ number_format($chargesDues->sum('reste_a_payer'), 0, ',', ' ') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endif

{{-- Sessions caisse --}}
<h3>Sessions de caisse</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Boutique</th>
            <th>Statut</th>
            <th class="right">Ouverture (F)</th>
            <th class="right">Attendu (F)</th>
            <th class="right">Réel (F)</th>
            <th class="right">Écart (F)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sessions as $s)
        <tr>
            <td>{{ \Carbon\Carbon::parse($s->date)->format('d/m/Y') }}</td>
            <td>{{ $s->shop?->nom ?? '—' }}</td>
            <td>{{ $s->statut === 'ouverte' ? 'Ouverte' : 'Fermée' }}</td>
            <td class="right">{{ number_format($s->montant_ouverture, 0, ',', ' ') }}</td>
            <td class="right" style="color:#64748b;">{{ $s->montant_fermeture_attendu ? number_format($s->montant_fermeture_attendu, 0, ',', ' ') : '—' }}</td>
            <td class="right" style="font-weight:600;">{{ $s->montant_fermeture_reel ? number_format($s->montant_fermeture_reel, 0, ',', ' ') : '—' }}</td>
            <td class="right" style="color:{{ $s->ecart === null ? '#94a3b8' : ($s->ecart >= 0 ? '#16a34a' : '#dc2626') }}; font-weight:600;">
                {{ $s->ecart !== null ? (($s->ecart >= 0 ? '+' : '') . number_format($s->ecart, 0, ',', ' ')) : '—' }}
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:10px;">Aucune session.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTAL</td>
            <td class="right">{{ number_format($totalOuverture, 0, ',', ' ') }}</td>
            <td class="right"></td>
            <td class="right">{{ number_format($totalFermeture, 0, ',', ' ') }}</td>
            <td class="right" style="color:{{ $totalEcart >= 0 ? '#16a34a' : '#dc2626' }};">
                {{ ($totalEcart >= 0 ? '+' : '') . number_format($totalEcart, 0, ',', ' ') }}
            </td>
        </tr>
    </tfoot>
</table>

</body>
</html>
