<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
h3 { font-size:11px; font-weight:700; color:#1e293b; margin:16px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
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
.badge-orange { background:#ffedd5; color:#c2410c; }
.badge-red { background:#fee2e2; color:#b91c1c; }
</style>
</head>
<body>

@php $reportTitle = 'LISTE DES RÉPARATIONS'; @endphp
@include('reports._pdf_header')

<table>
    <thead>
        <tr>
            <th>N° Réparation</th>
            <th>Type</th>
            <th>Client</th>
            <th>Téléphone</th>
            <th>Appareil</th>
            <th class="right">Total (F)</th>
            <th class="right">Payé (F)</th>
            <th class="right">Reste (F)</th>
            <th>Statut</th>
            <th>Date création</th>
            <th>État paiement</th>
        </tr>
    </thead>
    <tbody>
        @forelse($repairs as $r)
        <tr>
            <td style="font-weight:700;">{{ $r->numeroReparation }}</td>
            <td>{{ ucfirst($r->type_reparation) }}</td>
            <td>{{ $r->client_nom }}</td>
            <td>{{ $r->client_telephone }}</td>
            <td>{{ $r->appareil_marque_modele }}</td>
            <td class="right">{{ number_format($r->total_reparation, 0, ',', ' ') }}</td>
            <td class="right" style="color:#16a34a;">{{ number_format($r->montant_paye, 0, ',', ' ') }}</td>
            <td class="right" style="color:{{ $r->reste_a_payer > 0 ? '#dc2626' : '#94a3b8' }};">{{ number_format($r->reste_a_payer, 0, ',', ' ') }}</td>
            <td>{{ $r->statut_reparation }}</td>
            <td>{{ $r->date_creation?->format('d/m/Y') }}</td>
            <td>
                @if($r->etat_paiement === 'Soldé')
                <span class="badge badge-green">Soldé</span>
                @elseif($r->etat_paiement === 'Partiel')
                <span class="badge badge-orange">Partiel</span>
                @else
                <span class="badge badge-red">{{ $r->etat_paiement }}</span>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center; color:#94a3b8; padding:12px;">Aucune réparation.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">TOTAL ({{ $repairs->count() }} réparation(s))</td>
            <td class="right">{{ number_format($repairs->sum('total_reparation'), 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($repairs->sum('montant_paye'), 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($repairs->sum('reste_a_payer'), 0, ',', ' ') }}</td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">Généré le {{ now()->format('d/m/Y à H:i') }} — {{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
</body>
</html>
