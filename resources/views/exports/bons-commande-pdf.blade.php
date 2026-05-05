<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
table { width:100%; border-collapse:collapse; font-size:9px; }
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
.badge-blue { background:#dbeafe; color:#1d4ed8; }
.badge-orange { background:#ffedd5; color:#c2410c; }
.badge-gray { background:#f1f5f9; color:#475569; }
.badge-red { background:#fee2e2; color:#b91c1c; }
</style>
</head>
<body>

@php $reportTitle = 'BONS DE COMMANDE FOURNISSEURS'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>N° Bon</th>
            <th>Fournisseur</th>
            <th>Date commande</th>
            <th>Livraison prévue</th>
            <th class="right">Total estimé (F)</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
        @php
            $total = $order->lines->sum(fn($l) => ($l->prix_unitaire_estime ?? 0) * $l->quantite_commandee);
        @endphp
        <tr>
            <td style="font-weight:700;">{{ $order->numero }}</td>
            <td>{{ optional($order->supplier)->nom ?? '—' }}</td>
            <td>{{ \Carbon\Carbon::parse($order->date_commande)->format('d/m/Y') }}</td>
            <td>{{ $order->date_livraison_prevue ? \Carbon\Carbon::parse($order->date_livraison_prevue)->format('d/m/Y') : '—' }}</td>
            <td class="right">{{ number_format($total, 0, ',', ' ') }}</td>
            <td>
                @if($order->statut === 'recu')
                <span class="badge badge-green">Reçu</span>
                @elseif($order->statut === 'partiellement_recu')
                <span class="badge badge-orange">Partiel</span>
                @elseif($order->statut === 'envoye')
                <span class="badge badge-blue">Envoyé</span>
                @elseif($order->statut === 'annule')
                <span class="badge badge-red">Annulé</span>
                @else
                <span class="badge badge-gray">Brouillon</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:12px;">Aucun bon de commande.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL : {{ $orders->count() }} bon(s) de commande</td>
            <td class="right">{{ number_format($orders->sum(fn($o) => $o->lines->sum(fn($l) => ($l->prix_unitaire_estime ?? 0) * $l->quantite_commandee)), 0, ',', ' ') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
