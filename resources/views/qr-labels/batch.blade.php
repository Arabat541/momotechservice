@extends('layouts.base')

@section('body')
<div class="no-print">
    <button onclick="history.back()" class="btn-ctrl">&#8592; Retour</button>
    <button onclick="window.print()" class="btn-ctrl btn-blue">
        Imprimer {{ count($items) }} étiquette(s)
    </button>
    <span class="count-label">{{ count($items) }} étiquette(s)</span>
</div>

<div class="label-grid">
    @foreach($items as $item)
    <div class="label-wrap">
        <div class="line shop-name">{{ $settings?->companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
        <div class="line repair-num">{{ $item['repair']->numeroReparation }}</div>
        <div class="barcode-wrap">
            <svg id="barcode-{{ $item['repair']->id }}"></svg>
        </div>
        <div class="line client-info">
            {{ $item['repair']->client_nom }}&nbsp;|&nbsp;{{ $item['repair']->client_telephone }}
        </div>
        <div class="line appareil">{{ $item['repair']->appareil_marque_modele }}</div>
        <div class="line date-info">{{ \Carbon\Carbon::parse($item['repair']->date_creation)->format('d/m/Y') }}</div>
    </div>
    @endforeach
</div>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; background: #fff; color: #000; }

.label-wrap {
    width: 59mm;
    height: 38mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1mm;
    page-break-inside: avoid;
    break-inside: avoid;
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
    body { background: #f3f4f6; padding: 10mm; }
    .no-print { display: flex; gap: 8px; align-items: center; margin-bottom: 10mm; }
    .label-grid { display: flex; flex-wrap: wrap; gap: 3mm; }
    .label-wrap {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }
    .btn-ctrl {
        background: #6b7280;
        color: #fff;
        border: none;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        font-family: sans-serif;
    }
    .btn-blue { background: #2563eb; }
    .count-label { font-size: 13px; color: #6b7280; font-family: sans-serif; }
}
@media print {
    @page { size: A4 portrait; margin: 5mm; }
    .no-print { display: none !important; }
    body { background: #fff; padding: 0; }
    .label-grid {
        display: grid;
        grid-template-columns: repeat(3, 59mm);
        gap: 2mm;
    }
    .label-wrap { border: 0.5pt solid #000; border-radius: 0; box-shadow: none; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    @foreach($items as $item)
    try {
        JsBarcode('#barcode-{{ $item['repair']->id }}', '{{ $item['repair']->numeroReparation }}', {
            format: 'CODE128',
            width: 1.5,
            height: 40,
            displayValue: true,
            fontSize: 8,
            margin: 0,
        });
    } catch (e) {}
    @endforeach
    setTimeout(function () { window.print(); }, 300);
});
</script>
@endsection
