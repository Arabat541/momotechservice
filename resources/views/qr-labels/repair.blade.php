@extends('layouts.base')

@section('body')
<div class="no-print">
    <a href="{{ route('reparations.show', $repair->id) }}" class="btn-ctrl">&#8592; Retour</a>
    <button onclick="window.print()" class="btn-ctrl btn-blue">Imprimer l'étiquette</button>
</div>

<div class="label-wrap">
    <div class="line shop-name">{{ $settings?->companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
    <div class="line repair-num">{{ $repair->numeroReparation }}</div>
    <div class="barcode-wrap">
        <svg id="barcode"></svg>
    </div>
    <div class="line client-info">
        {{ $repair->client_nom }}&nbsp;|&nbsp;{{ $repair->client_telephone }}
    </div>
    <div class="line appareil">{{ $repair->appareil_marque_modele }}</div>
    <div class="line date-info">{{ \Carbon\Carbon::parse($repair->date_creation)->format('d/m/Y') }}</div>
</div>

<style>
@page { size: 59mm 38mm; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; background: #fff; color: #000; }

.label-wrap {
    width: 57mm;
    height: 36mm;
    margin: 1mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.line {
    width: 100%;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex-shrink: 0;
}
.shop-name  { font-size: 7pt; font-weight: bold; text-transform: uppercase; height: 4mm; line-height: 4mm; }
.repair-num { font-size: 8pt; font-weight: bold; height: 4mm; line-height: 4mm; }
.barcode-wrap {
    width: 55mm;
    height: 14mm;
    flex-shrink: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.barcode-wrap svg { width: 55mm !important; height: 14mm !important; display: block; }
.client-info { font-size: 6pt; height: 4mm; line-height: 4mm; }
.appareil    { font-size: 6pt; height: 4mm; line-height: 4mm; }
.date-info   { font-size: 6pt; height: 4mm; line-height: 4mm; }

@media screen {
    body {
        background: #e5e7eb;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 8mm;
        gap: 10px;
    }
    .no-print { display: flex; gap: 8px; }
    .label-wrap {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
    }
    .btn-ctrl {
        text-decoration: none;
        background: #6b7280;
        color: #fff;
        padding: 6px 14px;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        font-family: sans-serif;
    }
    .btn-blue { background: #2563eb; }
}
@media print {
    .no-print { display: none !important; }
    body { background: #fff; display: block; padding: 0; }
    .label-wrap { margin: 0; border: none; box-shadow: none; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        JsBarcode('#barcode', '{{ $repair->numeroReparation }}', {
            format: 'CODE128',
            width: 1.5,
            height: 40,
            displayValue: true,
            fontSize: 8,
            margin: 0,
        });
    } catch (e) {}
    setTimeout(function () { window.print(); }, 300);
});
</script>
@endsection
