<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
    h3 { font-size:11px; font-weight:700; color:#1e293b; margin:14px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
    table { width:100%; border-collapse:collapse; font-size:9.5px; }
    thead th { background:#f1f5f9; padding:5px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
    thead th.right { text-align:right; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:5px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    tbody td.right { text-align:right; }
    tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
    tfoot td.right { text-align:right; }
    .badge { display:inline-block; padding:2px 6px; border-radius:4px; font-size:8px; font-weight:700; }
    .badge-red { background:#fee2e2; color:#dc2626; }
    .badge-green { background:#dcfce7; color:#16a34a; }
    .badge-blue { background:#dbeafe; color:#1d4ed8; }
    .info-box { border:1px solid #e2e8f0; background:#f8fafc; padding:10px 14px; margin-bottom:14px; }
    .info-row { display:table; width:100%; }
    .info-cell { display:table-cell; padding:3px 0; }
    .info-label { color:#64748b; font-size:9px; }
    .info-value { font-weight:600; font-size:10px; }
</style>
</head>
<body>

@php $reportTitle = 'RELEVÉ DE COMPTE'; @endphp
@include('reports._pdf_header')

{{-- Infos client --}}
<h3>Informations client</h3>
<div class="info-box" style="margin-bottom:14px;">
    <div class="info-row">
        <div class="info-cell" style="width:50%;">
            <div class="info-label">Nom</div>
            <div class="info-value">{{ $client->nom }}</div>
        </div>
        <div class="info-cell" style="width:25%;">
            <div class="info-label">Téléphone</div>
            <div class="info-value">{{ $client->telephone ?? '—' }}</div>
        </div>
        <div class="info-cell" style="width:25%;">
            <div class="info-label">Boutique</div>
            <div class="info-value">{{ $client->nom_boutique ?? '—' }}</div>
        </div>
    </div>
    <div class="info-row" style="margin-top:8px;">
        <div class="info-cell" style="width:33%;">
            <div class="info-label">Limite crédit</div>
            <div class="info-value">{{ number_format($client->credit_limite, 0, ',', ' ') }} F</div>
        </div>
        <div class="info-cell" style="width:33%;">
            <div class="info-label">Solde dû actuel</div>
            <div class="info-value" style="color:#dc2626;">{{ number_format($client->solde_credit, 0, ',', ' ') }} F</div>
        </div>
        <div class="info-cell" style="width:33%;">
            <div class="info-label">Disponible</div>
            <div class="info-value" style="color:#16a34a;">{{ number_format($client->creditDisponible(), 0, ',', ' ') }} F</div>
        </div>
    </div>
</div>

{{-- Transactions --}}
<h3>Transactions du {{ \Carbon\Carbon::parse($debut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }}</h3>

@if($transactions->isEmpty())
<p style="color:#94a3b8; padding:12px 0; text-align:center;">Aucune transaction sur cette période.</p>
@else
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Notes</th>
            <th class="right">Montant (F)</th>
            <th class="right">Solde après (F)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $tx)
        <tr>
            <td>{{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}</td>
            <td>
                @if($tx->type === 'dette')
                <span class="badge badge-red">Crédit accordé</span>
                @elseif($tx->type === 'remboursement')
                <span class="badge badge-green">Remboursement</span>
                @else
                <span class="badge badge-blue">Avoir</span>
                @endif
            </td>
            <td style="color:#64748b;">{{ $tx->notes ?? '—' }}</td>
            <td class="right" style="font-weight:600; color:{{ $tx->type === 'dette' ? '#dc2626' : '#16a34a' }};">
                {{ $tx->type === 'dette' ? '+' : '-' }}{{ number_format($tx->montant, 0, ',', ' ') }}
            </td>
            <td class="right" style="color:#1e293b;">{{ number_format($tx->solde_apres, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3">SOLDE FINAL AU {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }}</td>
            <td></td>
            <td class="right" style="color:{{ $client->solde_credit > 0 ? '#dc2626' : '#16a34a' }}; font-size:12px;">
                {{ number_format($client->solde_credit, 0, ',', ' ') }} F
            </td>
        </tr>
    </tfoot>
</table>
@endif

</body>
</html>
