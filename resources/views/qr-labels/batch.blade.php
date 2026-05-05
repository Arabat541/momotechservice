@extends('layouts.base')

@section('body')
<div class="bg-white min-h-screen p-6">
    {{-- Contrôles --}}
    <div class="no-print mb-6 flex gap-3 items-center">
        <button onclick="history.back()"
                style="background:#6b7280;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer;font-family:sans-serif;">
            ← Retour
        </button>
        <button onclick="window.print()"
                style="background:#2563eb;color:#fff;border:none;padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer;font-family:sans-serif;">
            Imprimer {{ count($items) }} étiquette(s)
        </button>
        <span style="font-size:13px;color:#6b7280;">{{ count($items) }} étiquette(s)</span>
    </div>

    {{-- Grille d'étiquettes --}}
    <div class="label-grid">
        @foreach($items as $index => $item)
        <div class="label-wrap">
            <div class="line shop-name">{{ $settings?->companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
            <div class="line repair-num">{{ $item['repair']->numeroReparation }}</div>
            <div class="line client-info">{{ $item['repair']->client_nom }} · {{ $item['repair']->client_telephone }}</div>
            <div class="line appareil">{{ $item['repair']->appareil_marque_modele }}</div>
            <div class="barcode-wrap">
                <svg id="barcode-{{ $index }}"></svg>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 7pt;
    background: #fff;
    color: #000;
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
.barcode-wrap { width: 55mm; text-align: center; }
.barcode-wrap svg { width: 55mm !important; height: 12mm !important; display: block; margin: 0 auto; }

.label-wrap {
    width: 59mm;
    height: 38mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    padding: 0.5mm 1mm;
    page-break-inside: avoid;
    break-inside: avoid;
}

@media screen {
    body { background: #f3f4f6; padding: 10mm; }
    .label-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 3mm;
    }
    .label-wrap {
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        box-shadow: 0 1px 4px rgba(0,0,0,.1);
    }
    .no-print { display: flex; }
}
@media print {
    @page { size: A4 portrait; margin: 6mm; }
    .no-print { display: none !important; }
    body { background: #fff; padding: 0; }
    .label-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 2mm;
    }
    .label-wrap {
        border: 0.5pt solid #999;
        border-radius: 0;
        box-shadow: none;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    @foreach($items as $index => $item)
    try {
        JsBarcode('#barcode-{{ $index }}', '{{ $item['repair']->numeroReparation }}', {
            format: 'CODE128',
            width: 1.6,
            height: 34,
            displayValue: false,
            margin: 0,
        });
    } catch (e) {}
    @endforeach
});
</script>
@endsection
