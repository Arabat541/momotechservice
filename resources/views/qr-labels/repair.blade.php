@extends('layouts.base')

@section('body')
<div style="background:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:8mm;">
    {{-- Contrôles impression --}}
    <div class="no-print" style="position:fixed;top:10px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:100;">
        <a href="{{ route('reparations.show', $repair->id) }}"
           style="text-decoration:none;background:#6b7280;color:#fff;padding:6px 14px;border-radius:6px;font-size:13px;font-family:sans-serif;">
            ← Retour
        </a>
        <button onclick="window.print()"
                style="background:#2563eb;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer;font-family:sans-serif;">
            Imprimer l'étiquette
        </button>
    </div>

    {{-- Étiquette 59×38mm --}}
    <div class="label-wrap">
        <div class="line shop-name">{{ $settings?->companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
        <div class="line repair-num">{{ $repair->numeroReparation }}</div>
        <div class="line client-info">{{ $repair->client_nom }} · {{ $repair->client_telephone }}</div>
        <div class="line appareil">{{ $repair->appareil_marque_modele }}</div>
        <div class="barcode-wrap">
            <svg id="barcode-single"></svg>
        </div>
    </div>
</div>

<style>
@page {
    size: 59mm 38mm;
    margin: 1mm;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7pt;
    background: #fff;
    color: #000;
}
.label-wrap {
    width: 57mm;
    height: 36mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    padding: 0.5mm 0;
}
.line {
    width: 100%;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.shop-name  { font-size: 8pt; font-weight: bold; text-transform: uppercase; }
.repair-num { font-size: 9pt; font-weight: bold; letter-spacing: 0.5px; }
.client-info{ font-size: 7pt; }
.appareil   { font-size: 7pt; }
.barcode-wrap {
    width: 55mm;
    text-align: center;
}
.barcode-wrap svg {
    width: 55mm !important;
    height: 12mm !important;
    display: block;
    margin: 0 auto;
}
@media screen {
    body { background: #e5e7eb; }
    .label-wrap {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,.15);
        padding: 2mm 1mm;
        margin: 0 auto;
    }
    .no-print { display: flex !important; }
}
@media print {
    .no-print { display: none !important; }
    body { background: #fff; display: block; padding: 0; }
    .label-wrap { border: none; box-shadow: none; margin: 0; padding: 0.5mm 0; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        JsBarcode('#barcode-single', '{{ $repair->numeroReparation }}', {
            format: 'CODE128',
            width: 1.6,
            height: 34,
            displayValue: false,
            margin: 0,
        });
    } catch (e) {}
});
</script>
@endsection
