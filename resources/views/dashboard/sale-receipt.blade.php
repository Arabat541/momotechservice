<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu Vente</title>
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
    .qr-wrap { text-align: center; margin: 6px 0; }
    .qr-wrap svg { width: 100px; height: 100px; }
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
    $modePaiementLabels = [
        'comptant'     => 'Comptant',
        'credit'       => 'À crédit',
    ];
    $moyenPaiementLabels = [
        'especes'      => 'Espèces',
        'orange_money' => 'Orange Money',
        'wave'         => 'Wave',
        'mtn_money'    => 'MTN Money',
    ];
    $dateVente = $vente->date ? \Carbon\Carbon::parse($vente->date) : now();
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

{{-- Vente info --}}
<div class="section">
    <div class="row"><span class="label">Vente N°:</span><span>{{ $vente->numeroVente ?? substr($vente->id, 0, 12) }}</span></div>
    <div class="row"><span class="label">Date:</span><span>{{ $dateVente->format('d/m/Y H:i') }}</span></div>
    @if($vente->client)
    <div class="row"><span class="label">Client:</span><span>{{ $vente->client }}</span></div>
    @endif
</div>

<div class="separator"></div>

{{-- Article --}}
<div class="section">
    <div class="bold" style="text-decoration:underline;margin-bottom:2px">Article:</div>
    <div class="row">
        <span>{{ $vente->nom }}</span>
        <span>x{{ $vente->quantite }}</span>
    </div>
    <div class="row small">
        <span>Prix unitaire</span>
        <span>{{ number_format($vente->prixVente, 0, ',', ' ') }} cfa</span>
    </div>
</div>

<div class="separator-double"></div>

{{-- Totaux --}}
<div class="section">
    @if(($vente->remise ?? 0) > 0)
    <div class="row small">
        <span>Sous-total:</span>
        <span>{{ number_format($vente->prixVente * $vente->quantite, 0, ',', ' ') }} cfa</span>
    </div>
    <div class="row small">
        <span>Remise:</span>
        <span>-{{ number_format($vente->remise, 0, ',', ' ') }} cfa</span>
    </div>
    @endif
    <div class="row total-row">
        <span>TOTAL:</span>
        <span>{{ number_format($vente->total, 0, ',', ' ') }} cfa</span>
    </div>
    <div class="row small">
        <span>Payé:</span>
        <span>{{ number_format($vente->montant_paye, 0, ',', ' ') }} cfa</span>
    </div>
    @if($vente->reste_credit > 0)
    <div class="row small bold">
        <span>Reste à crédit:</span>
        <span>{{ number_format($vente->reste_credit, 0, ',', ' ') }} cfa</span>
    </div>
    @endif
    <div class="row small" style="margin-top:2px">
        <span>Mode:</span>
        <span>{{ $modePaiementLabels[$vente->mode_paiement] ?? $vente->mode_paiement }}</span>
    </div>
    @if($vente->moyen_paiement)
    <div class="row small">
        <span>Via:</span>
        <span>{{ $moyenPaiementLabels[$vente->moyen_paiement] ?? $vente->moyen_paiement }}</span>
    </div>
    @endif
</div>

<div class="separator"></div>

{{-- QR code --}}
<div class="qr-wrap">
    {!! $qrCode !!}
    <p class="small" style="margin-top:2px">Réf: {{ $vente->numeroVente ?? substr($vente->id, 0, 12) }}</p>
</div>

<div class="separator"></div>

{{-- Footer --}}
<div class="footer">
    <p class="bold">Merci pour votre confiance!</p>
    <p>{{ $company['nom'] ?? 'MOMO TECH SERVICE' }}</p>
    @if(!empty($company['telephone']))<p>{{ $company['telephone'] }}</p>@endif
</div>

<div class="cut-line"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() { window.print(); }, 400);
});
</script>
</body>
</html>
