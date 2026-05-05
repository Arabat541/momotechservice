<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
h3 { font-size:11px; font-weight:700; color:#1e293b; margin:16px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
table { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:12px; }
thead th { background:#f1f5f9; padding:6px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
thead th.right { text-align:right; }
tbody td { padding:6px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
tbody td.right { text-align:right; }
.grand-total td { background:#1e40af; color:#fff; font-weight:700; font-size:11px; padding:8px; }
.grand-total td.right { text-align:right; }
.ecart-ok td { background:#dcfce7; color:#15803d; }
.ecart-err td { background:#fee2e2; color:#b91c1c; }
.footer { margin-top:20px; border-top:1px solid #e2e8f0; padding-top:8px; font-size:8px; color:#9ca3af; text-align:center; }
.recap-row td { padding:5px 8px; border-bottom:1px solid #f1f5f9; }
.recap-row td.right { text-align:right; font-weight:700; }
</style>
</head>
<body>

@php
$reportTitle = 'RAPPORT Z — CLÔTURE DE CAISSE';
$session = $report['session'];
@endphp
@include('reports._pdf_header')

{{-- Infos session --}}
@php
    $pdfOpened = $session->opened_at ?? $session->created_at;
    $pdfClosed = $session->closed_at;
    $pdfDuree  = ($pdfOpened && $pdfClosed) ? $pdfOpened->diff($pdfClosed) : null;
@endphp
<table>
    <thead><tr><th colspan="2">Informations session</th></tr></thead>
    <tbody>
        <tr class="recap-row"><td>Date</td><td class="right">{{ \Carbon\Carbon::parse($session->date)->format('d/m/Y') }}</td></tr>
        @if($pdfOpened)
        <tr class="recap-row"><td>Heure ouverture</td><td class="right">{{ $pdfOpened->format('H\hi') }}</td></tr>
        @endif
        @if($pdfClosed)
        <tr class="recap-row"><td>Heure fermeture</td><td class="right">{{ $pdfClosed->format('H\hi') }}@if($pdfDuree) — {{ $pdfDuree->h }}h{{ str_pad($pdfDuree->i, 2, '0', STR_PAD_LEFT) }} de service@endif</td></tr>
        @endif
        <tr class="recap-row"><td>Caissier(e)</td><td class="right">{{ optional($session->user)->prenom }} {{ optional($session->user)->nom }}</td></tr>
        <tr class="recap-row"><td>Statut</td><td class="right">{{ $session->statut === 'fermee' ? 'Clôturée' : 'Ouverte' }}</td></tr>
    </tbody>
</table>

{{-- Ventes comptant --}}
<h3>Ventes comptant</h3>
<table>
    <thead><tr><th>Libellé</th><th class="right">Valeur</th></tr></thead>
    <tbody>
        <tr class="recap-row"><td>Nombre de ventes</td><td class="right">{{ $report['nb_ventes_comptant'] }}</td></tr>
        <tr class="recap-row"><td>Total encaissé</td><td class="right" style="font-weight:700; color:#16a34a;">{{ number_format($report['total_ventes_comptant'], 0, ',', ' ') }} F</td></tr>
    </tbody>
</table>

{{-- Ventes crédit --}}
<h3>Ventes à crédit</h3>
<table>
    <thead><tr><th>Libellé</th><th class="right">Valeur</th></tr></thead>
    <tbody>
        <tr class="recap-row"><td>Nombre de ventes</td><td class="right">{{ $report['nb_ventes_credit'] }}</td></tr>
        <tr class="recap-row"><td>Total crédit accordé</td><td class="right" style="color:#ea580c;">{{ number_format($report['total_ventes_credit'], 0, ',', ' ') }} F</td></tr>
    </tbody>
</table>

{{-- Acomptes réparations --}}
<h3>Acomptes réparations</h3>
<table>
    <thead><tr><th>Libellé</th><th class="right">Valeur</th></tr></thead>
    <tbody>
        <tr class="recap-row"><td>Nombre d'acomptes</td><td class="right">{{ $report['nb_acomptes'] }}</td></tr>
        <tr class="recap-row"><td>Total acomptes</td><td class="right" style="font-weight:700;">{{ number_format($report['total_acomptes'], 0, ',', ' ') }} F</td></tr>
    </tbody>
</table>

{{-- Factures soldées --}}
<h3>Factures soldées</h3>
<table>
    <thead><tr><th>Libellé</th><th class="right">Valeur</th></tr></thead>
    <tbody>
        <tr class="recap-row"><td>Nombre de factures</td><td class="right">{{ $report['nb_factures_soldees'] }}</td></tr>
        <tr class="recap-row"><td>Total encaissé</td><td class="right" style="font-weight:700; color:#16a34a;">{{ number_format($report['total_factures_soldees'], 0, ',', ' ') }} F</td></tr>
    </tbody>
</table>

{{-- Récapitulatif caisse --}}
<h3>Récapitulatif caisse</h3>
<table>
    <thead><tr><th>Libellé</th><th class="right">Montant (F)</th></tr></thead>
    <tbody>
        <tr class="recap-row"><td>Fonds à l'ouverture</td><td class="right">{{ number_format($report['montant_ouverture'], 0, ',', ' ') }}</td></tr>
        <tr class="recap-row"><td>Total encaissé (ventes comptant + acomptes)</td><td class="right" style="color:#16a34a; font-weight:700;">{{ number_format($report['total_encaisse'], 0, ',', ' ') }}</td></tr>
        <tr class="recap-row"><td>Attendu en caisse</td><td class="right" style="font-weight:700;">{{ number_format($report['montant_fermeture_attendu'] ?? 0, 0, ',', ' ') }}</td></tr>
        <tr class="recap-row"><td>Réel compté</td><td class="right" style="font-weight:700;">{{ number_format($report['montant_fermeture_reel'] ?? 0, 0, ',', ' ') }}</td></tr>
    </tbody>
</table>

{{-- Écart --}}
@php $ecart = $report['ecart'] ?? 0; @endphp
<table>
    <tbody>
        <tr class="{{ $ecart == 0 ? 'ecart-ok' : 'ecart-err' }}">
            <td style="padding:8px; font-weight:700;">Écart de caisse</td>
            <td style="padding:8px; text-align:right; font-weight:700; font-size:14px;">
                {{ $ecart >= 0 ? '+' : '' }}{{ number_format($ecart, 0, ',', ' ') }} F
                {{ $ecart == 0 ? '✓ Équilibré' : ($ecart > 0 ? '(excédent)' : '(manquant)') }}
            </td>
        </tr>
    </tbody>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
