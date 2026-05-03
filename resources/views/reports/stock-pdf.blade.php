<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size:10px; color:#1e293b; padding:24px; }
    h3 { font-size:11px; font-weight:700; color:#1e293b; margin:16px 0 6px; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
    table { width:100%; border-collapse:collapse; font-size:9.5px; }
    thead th { background:#f1f5f9; padding:5px 8px; text-align:left; font-size:8px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #cbd5e1; }
    thead th.right { text-align:right; }
    tbody tr:nth-child(even) { background:#f8fafc; }
    tbody td { padding:4px 8px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
    tbody td.right { text-align:right; }
    tfoot td { padding:6px 8px; font-weight:700; border-top:2px solid #cbd5e1; background:#f1f5f9; }
    tfoot td.right { text-align:right; }
    .alert-box { background:#fff7ed; border:1px solid #fed7aa; padding:8px 12px; border-radius:4px; margin-bottom:14px; }
    .alert-title { font-size:10px; font-weight:700; color:#c2410c; margin-bottom:6px; }
</style>
</head>
<body>

@php $reportTitle = 'RAPPORT STOCK'; $debut = null; $fin = null; @endphp
@include('reports._pdf_header')

{{-- KPIs --}}
<table width="100%" style="margin-bottom:16px; border-spacing:5px; border-collapse:separate;">
    <tr>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Références</div>
            <div style="font-size:16px; font-weight:700; color:#1e293b;">{{ $nbTotal }}</div>
        </td>
        <td style="background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Valorisation</div>
            <div style="font-size:16px; font-weight:700; color:#2563eb;">{{ number_format($valorisation, 0, ',', ' ') }} F</div>
        </td>
        <td style="background:#fff7ed; border:1px solid #fed7aa; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#c2410c; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Sous seuil</div>
            <div style="font-size:16px; font-weight:700; color:{{ $sousSeuil->count() > 0 ? '#ea580c' : '#94a3b8' }};">{{ $sousSeuil->count() }}</div>
        </td>
        <td style="background:#fff1f2; border:1px solid #fecdd3; padding:8px 12px; width:25%;">
            <div style="font-size:8px; color:#be123c; text-transform:uppercase; font-weight:700; margin-bottom:3px;">Épuisés</div>
            <div style="font-size:16px; font-weight:700; color:{{ $epuises->count() > 0 ? '#dc2626' : '#94a3b8' }};">{{ $epuises->count() }}</div>
        </td>
    </tr>
</table>

{{-- Articles sous seuil --}}
@if($sousSeuil->count() > 0)
<div class="alert-box">
    <div class="alert-title">⚠ Articles sous seuil d'alerte</div>
    <table>
        <thead><tr><th>Article</th><th class="right">Qté</th><th class="right">Seuil</th><th>Boutique</th></tr></thead>
        <tbody>
            @foreach($sousSeuil as $s)
            <tr>
                <td>{{ $s->nom }}</td>
                <td class="right" style="color:#ea580c; font-weight:700;">{{ $s->quantite }}</td>
                <td class="right" style="color:#64748b;">{{ $s->seuil_alerte }}</td>
                <td>{{ $s->shop?->nom ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Inventaire complet --}}
<h3>Inventaire complet</h3>
<table>
    <thead>
        <tr>
            <th>Article</th>
            <th>Catégorie</th>
            <th class="right">Qté</th>
            <th class="right">Seuil</th>
            <th class="right">P. Achat (F)</th>
            <th class="right">P. Vente (F)</th>
            <th class="right">Valorisation (F)</th>
            <th>Boutique</th>
            <th>État</th>
        </tr>
    </thead>
    <tbody>
        @forelse($stocks as $s)
        <tr>
            <td>{{ $s->nom }}</td>
            <td style="color:#64748b;">{{ $s->categorie ?: '—' }}</td>
            <td class="right" style="font-weight:700; color:{{ $s->quantite == 0 ? '#dc2626' : ($s->isUnderThreshold() ? '#ea580c' : '#1e293b') }};">{{ $s->quantite }}</td>
            <td class="right" style="color:#94a3b8;">{{ $s->seuil_alerte ?: '—' }}</td>
            <td class="right">{{ number_format($s->prixAchat, 0, ',', ' ') }}</td>
            <td class="right">{{ number_format($s->prixVente, 0, ',', ' ') }}</td>
            <td class="right" style="font-weight:700; color:#2563eb;">{{ number_format($s->quantite * $s->prixAchat, 0, ',', ' ') }}</td>
            <td>{{ $s->shop?->nom ?? '—' }}</td>
            <td style="color:{{ $s->quantite == 0 ? '#dc2626' : ($s->isUnderThreshold() ? '#ea580c' : '#16a34a') }}; font-weight:700;">
                {{ $s->quantite == 0 ? 'Épuisé' : ($s->isUnderThreshold() ? 'Faible' : 'OK') }}
            </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:12px;">Aucun article.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">TOTAL VALORISATION</td>
            <td class="right" style="color:#2563eb;">{{ number_format($valorisation, 0, ',', ' ') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

</body>
</html>
