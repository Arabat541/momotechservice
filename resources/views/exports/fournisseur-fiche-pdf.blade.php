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
.badge { display:inline-block; padding:2px 5px; border-radius:3px; font-size:7px; font-weight:700; }
.badge-green { background:#dcfce7; color:#15803d; }
.badge-orange { background:#ffedd5; color:#c2410c; }
.badge-yellow { background:#fef9c3; color:#a16207; }
.info-block { background:#f8fafc; border:1px solid #e2e8f0; padding:10px 14px; margin-bottom:14px; }
.info-row { display:table; width:100%; margin-bottom:4px; }
.info-label { display:table-cell; width:140px; color:#64748b; font-size:9px; }
.info-val { display:table-cell; font-size:9px; font-weight:700; }
</style>
</head>
<body>

@php $reportTitle = 'FICHE FOURNISSEUR — ' . strtoupper($supplier->nom); @endphp
@include('reports._pdf_header')

{{-- Info fournisseur --}}
<div class="info-block">
    <div class="info-row"><span class="info-label">Nom</span><span class="info-val">{{ $supplier->nom }}</span></div>
    @if($supplier->contact_nom)<div class="info-row"><span class="info-label">Contact</span><span class="info-val">{{ $supplier->contact_nom }}</span></div>@endif
    @if($supplier->telephone)<div class="info-row"><span class="info-label">Téléphone</span><span class="info-val">{{ $supplier->telephone }}</span></div>@endif
    @if($supplier->email)<div class="info-row"><span class="info-label">Email</span><span class="info-val">{{ $supplier->email }}</span></div>@endif
    @if($supplier->adresse)<div class="info-row"><span class="info-label">Adresse</span><span class="info-val">{{ $supplier->adresse }}</span></div>@endif
</div>

{{-- Factures --}}
<h3>Factures ({{ $supplier->purchaseInvoices->count() }})</h3>
<table>
    <thead>
        <tr>
            <th>N° Facture</th>
            <th>Date</th>
            <th>Statut</th>
            <th class="right">Total TTC (F)</th>
            <th class="right">Reste (F)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($supplier->purchaseInvoices as $inv)
        <tr>
            <td style="font-weight:700;">{{ $inv->numero }}</td>
            <td>{{ $inv->date_facture ? \Carbon\Carbon::parse($inv->date_facture)->format('d/m/Y') : '—' }}</td>
            <td>
                @if($inv->statut === 'payee')
                <span class="badge badge-green">Payée</span>
                @elseif($inv->statut === 'partiellement_payee')
                <span class="badge badge-orange">Partielle</span>
                @else
                <span class="badge badge-yellow">En attente</span>
                @endif
            </td>
            <td class="right">{{ number_format($inv->montant_ttc, 0, ',', ' ') }}</td>
            <td class="right" style="color:{{ $inv->reste_a_payer > 0 ? '#dc2626' : '#94a3b8' }};">{{ number_format($inv->reste_a_payer, 0, ',', ' ') }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:10px;">Aucune facture.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">TOTAL FACTURES</td>
            <td class="right">{{ number_format($supplier->purchaseInvoices->sum('montant_ttc'), 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($supplier->purchaseInvoices->sum('reste_a_payer'), 0, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>

{{-- Appros --}}
<h3>Réapprovisionnements ({{ $supplier->reappros->count() }})</h3>
<table>
    <thead>
        <tr>
            <th>Article</th>
            <th class="right">Quantité</th>
            <th class="right">Prix unitaire (F)</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($supplier->reappros as $r)
        <tr>
            <td>{{ optional($r->stock)->nom ?? $r->nom_article ?? '—' }}</td>
            <td class="right">{{ $r->quantite }}</td>
            <td class="right">{{ $r->prix_unitaire ? number_format($r->prix_unitaire, 0, ',', ' ') : '—' }}</td>
            <td>{{ $r->created_at?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:10px;">Aucun appro.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7">TOTAL APPROS : {{ $supplier->reappros->sum('quantite') }} unité(s)</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
