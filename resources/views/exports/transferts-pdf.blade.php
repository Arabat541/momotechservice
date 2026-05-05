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
.badge-gray { background:#f1f5f9; color:#475569; }
</style>
</head>
<body>

@php $reportTitle = 'TRANSFERTS INTRA-BOUTIQUE'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>N° Transfert</th>
            <th>De</th>
            <th>Vers</th>
            <th>Article</th>
            <th class="right">Quantité</th>
            <th>Statut</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $transfer)
            @foreach($transfer->lines as $line)
            <tr>
                <td style="font-weight:700;">{{ $transfer->numero }}</td>
                <td>{{ optional($transfer->shopFrom)->nom ?? '—' }}</td>
                <td>{{ optional($transfer->shopTo)->nom ?? '—' }}</td>
                <td>{{ optional($line->stock)->nom ?? '—' }}</td>
                <td class="right">{{ $line->quantite }}</td>
                <td>
                    @if($transfer->statut === 'recu')
                    <span class="badge badge-green">Reçu</span>
                    @elseif($transfer->statut === 'envoye')
                    <span class="badge badge-blue">Envoyé</span>
                    @else
                    <span class="badge badge-gray">{{ $transfer->statut }}</span>
                    @endif
                </td>
                <td>{{ $transfer->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        @empty
        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:12px;">Aucun transfert.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL : {{ $rows->count() }} transfert(s)</td>
            <td class="right">{{ $rows->sum(fn($t) => $t->lines->sum('quantite')) }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
