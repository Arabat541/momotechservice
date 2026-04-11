<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu {{ $repair->numeroReparation }}</title>
<style>
    @page {
        size: 80mm auto;
        margin: 0;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'Courier New', 'Lucida Console', monospace;
        font-size: 13px;
        line-height: 1.4;
        width: 72mm;
        max-width: 72mm;
        padding: 3mm 4mm;
        color: #000;
        background: #fff;
    }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .separator { border-top: 1px dashed #000; margin: 4px 0; }
    .separator-double { border-top: 2px solid #000; margin: 5px 0; }
    .row { display: flex; justify-content: space-between; }
    .header { margin-bottom: 6px; }
    .header h1 { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
    .header p { font-size: 11px; line-height: 1.3; }
    .section { margin: 4px 0; }
    .label { font-weight: bold; }
    .total-row { font-size: 16px; font-weight: bold; }
    .small { font-size: 11px; }
    .barcode-wrap { text-align: center; margin: 6px 0; }
    .barcode-wrap svg { width: 100%; max-width: 64mm; height: auto; }
    .footer { font-size: 11px; text-align: center; margin-top: 6px; }
    .cut-line { border-top: 1px dashed #000; margin-top: 8px; }

    @media screen {
        body {
            width: 420px;
            max-width: 420px;
            margin: 20px auto;
            padding: 15px;
            border: 1px solid #ccc;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
    }
</style>
</head>
<body>
@php
    $company = $settings?->companyInfo ?? [];
    $warranty = $settings?->warranty ?? [];
    $pannes = is_array($repair->pannes_services) ? $repair->pannes_services : [];
    $pieces = is_array($repair->pieces_rechange_utilisees) ? $repair->pieces_rechange_utilisees : [];
    $isRdv = $repair->type_reparation === 'rdv';
@endphp

{{-- Header --}}
<div class="header center">
    <img src="/images/logo-receipt.png" alt="MTS" style="width:50px;height:50px;margin:0 auto 4px;display:block;">
    <h1>{{ $company['nom'] ?? 'MOMO TECH SERVICE' }}</h1>
    @if(!empty($company['adresse']))<p>{{ $company['adresse'] }}</p>@endif
    @if(!empty($company['telephone']))<p>Tél: {{ $company['telephone'] }}</p>@endif
    @if(!empty($company['slogan']))<p>{{ $company['slogan'] }}</p>@endif
</div>

<div class="separator-double"></div>

{{-- Repair info --}}
<div class="section">
    <div class="row"><span class="label">N°:</span><span>{{ $repair->numeroReparation }}</span></div>
    <div class="row"><span class="label">Date:</span><span>{{ $repair->date_creation ? $repair->date_creation->format('d/m/Y') : 'N/A' }}</span></div>
    @if($isRdv && $repair->date_rendez_vous)
    <div class="row"><span class="label">RDV:</span><span>{{ $repair->date_rendez_vous->format('d/m/Y') }}</span></div>
    @endif
</div>

<div class="separator"></div>

{{-- Client --}}
<div class="section">
    <div><span class="label">Client:</span> {{ $repair->client_nom }}</div>
    <div><span class="label">Tél:</span> {{ $repair->client_telephone }}</div>
    <div><span class="label">Appareil:</span> {{ $repair->appareil_marque_modele }}</div>
</div>

<div class="separator"></div>

{{-- Pannes --}}
@if(count($pannes) > 0)
<div class="section">
    <div class="bold" style="text-decoration:underline;margin-bottom:1px">Pannes / Services:</div>
    @foreach($pannes as $panne)
        @if(!empty($panne['description']) && ($panne['montant'] ?? 0) > 0)
        <div class="row small">
            <span>- {{ $panne['description'] }}</span>
            <span>{{ number_format($panne['montant'], 0, ',', ' ') }} cfa</span>
        </div>
        @endif
    @endforeach
</div>
@endif

{{-- Pièces --}}
@if(count($pieces) > 0)
<div class="section">
    <div class="bold" style="text-decoration:underline;margin-bottom:1px">Pièces:</div>
    @foreach($pieces as $piece)
        @if(!empty($piece['nom']) && ($piece['quantiteUtilisee'] ?? 0) > 0)
        <div class="small">- {{ $piece['nom'] }} (x{{ $piece['quantiteUtilisee'] }})</div>
        @endif
    @endforeach
</div>
@endif

<div class="separator-double"></div>

{{-- Totals --}}
<div class="section">
    <div class="row total-row">
        <span>TOTAL:</span>
        <span>{{ number_format($repair->total_reparation, 0, ',', ' ') }} cfa</span>
    </div>
    <div class="row small">
        <span>Payé:</span>
        <span>{{ number_format($repair->montant_paye, 0, ',', ' ') }} cfa</span>
    </div>
    <div class="row small bold">
        <span>Reste à payer:</span>
        <span>{{ number_format($repair->reste_a_payer, 0, ',', ' ') }} cfa</span>
    </div>
</div>

<div class="separator"></div>

{{-- Barcode --}}
<div class="barcode-wrap">
    <svg id="barcode"></svg>
</div>

<div class="separator"></div>

{{-- Footer --}}
<div class="footer">
    <p class="bold">Merci pour votre confiance!</p>
    @if(!empty($warranty['conditions']))
    <p>{{ $warranty['conditions'] }} (Durée: {{ $warranty['duree'] ?? '7' }} jours)</p>
    @endif
    <p class="bold" style="margin-top:2px">Statut: {{ $repair->statut_reparation }} | Paiement: {{ $repair->etat_paiement }}</p>
</div>

<div class="cut-line"></div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        JsBarcode('#barcode', '{{ $repair->numeroReparation }}', {
            format: 'CODE128',
            width: 2,
            height: 50,
            displayValue: true,
            fontSize: 14,
            font: 'Courier New',
            margin: 5,
            textMargin: 2
        });
    } catch(e) {
        document.getElementById('barcode').outerHTML =
            '<span style="font-family:monospace;letter-spacing:2px">{{ $repair->numeroReparation }}</span>';
    }
    setTimeout(function() { window.print(); }, 400);
});
</script>
</body>
</html>
