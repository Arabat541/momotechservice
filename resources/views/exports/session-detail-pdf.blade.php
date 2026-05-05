<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
h3 { font-size:11px; font-weight:700; color:#1e293b; margin:16px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
table { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:14px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
thead th.right { text-align:right; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody td.right { text-align:right; }
tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
tfoot td.right { text-align:right; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.kpi-row { margin-bottom:14px; display:table; width:100%; }
.kpi-cell { display:table-cell; border:1px solid #e2e8f0; background:#f8fafc; padding:8px 12px; width:25%; }
.kpi-label { font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:3px; }
.kpi-value { font-size:14px; font-weight:700; }
</style>
</head>
<body>

@php
$reportTitle = 'SESSION DE CAISSE — DÉTAIL';
$sessionDate = \Carbon\Carbon::parse($session->date)->format('d/m/Y');
@endphp
@include('reports._pdf_header')

{{-- Info session --}}
<table style="margin-bottom:14px;">
    <tbody>
        <tr>
            <td style="padding:4px 8px; color:#64748b; width:140px;">Date</td>
            <td style="padding:4px 8px; font-weight:700;">{{ $sessionDate }}</td>
            <td style="padding:4px 8px; color:#64748b; width:140px;">Caissier(e)</td>
            <td style="padding:4px 8px;">{{ optional($session->user)->prenom }} {{ optional($session->user)->nom }}</td>
        </tr>
        <tr>
            <td style="padding:4px 8px; color:#64748b;">Ouverture</td>
            <td style="padding:4px 8px;">{{ number_format($session->montant_ouverture, 0, ',', ' ') }} F</td>
            <td style="padding:4px 8px; color:#64748b;">Statut</td>
            <td style="padding:4px 8px;">{{ $session->statut === 'fermee' ? 'Clôturée' : 'Ouverte' }}</td>
        </tr>
    </tbody>
</table>

{{-- Table ventes --}}
<h3>Ventes articles ({{ $session->sales->count() }})</h3>
<table>
    <thead>
        <tr>
            <th>Article</th>
            <th>Client</th>
            <th>Mode</th>
            <th class="right">Total (F)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($session->sales as $sale)
        <tr>
            <td>{{ $sale->nom }}</td>
            <td>{{ $sale->client ?: '—' }}</td>
            <td>{{ $sale->mode_paiement === 'credit' ? 'Crédit' : 'Comptant' }}</td>
            <td class="right" style="font-weight:700;">{{ number_format($sale->total, 0, ',', ' ') }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:10px;">Aucune vente.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTAL VENTES</td>
            <td class="right">{{ number_format($session->sales->sum('total'), 0, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>

{{-- Table factures --}}
<h3>Factures réparations ({{ $session->invoices->count() }})</h3>
<table>
    <thead>
        <tr>
            <th>N° Facture</th>
            <th>Réparation</th>
            <th>Statut</th>
            <th class="right">Payé (F)</th>
            <th class="right">Reste (F)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($session->invoices as $inv)
        <tr>
            <td style="font-weight:700;">{{ $inv->numero_facture }}</td>
            <td>{{ optional($inv->repair)->numeroReparation ?? '—' }}</td>
            <td>{{ ucfirst(str_replace('_', ' ', $inv->statut)) }}</td>
            <td class="right" style="color:#16a34a;">{{ number_format($inv->montant_paye, 0, ',', ' ') }}</td>
            <td class="right" style="color:{{ $inv->reste_a_payer > 0 ? '#dc2626' : '#94a3b8' }};">{{ number_format($inv->reste_a_payer, 0, ',', ' ') }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:10px;">Aucune facture.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTAL FACTURES</td>
            <td class="right">{{ number_format($session->invoices->sum('montant_paye'), 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($session->invoices->sum('reste_a_payer'), 0, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
