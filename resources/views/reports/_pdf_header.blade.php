{{-- Partial : en-tête commun des PDF --}}
<table width="100%" style="margin-bottom:20px; border-bottom:2px solid #2563eb; padding-bottom:12px;">
    <tr>
        <td width="60">
            @if(!empty($logoBase64))
            <img src="data:image/png;base64,{{ $logoBase64 }}" width="52" height="52" style="border-radius:6px;">
            @endif
        </td>
        <td style="padding-left:12px;">
            <div style="font-size:16px; font-weight:700; color:#1e293b;">{{ $companyInfo['nom'] ?? 'MOMO TECH SERVICE' }}</div>
            @if(!empty($companyInfo['adresse']))
            <div style="font-size:10px; color:#64748b;">{{ $companyInfo['adresse'] }}</div>
            @endif
            @if(!empty($companyInfo['telephone']))
            <div style="font-size:10px; color:#64748b;">Tél : {{ $companyInfo['telephone'] }}</div>
            @endif
        </td>
        <td style="text-align:right; vertical-align:top;">
            <div style="font-size:14px; font-weight:700; color:#2563eb;">{{ $reportTitle ?? '' }}</div>
            <div style="font-size:10px; color:#64748b; margin-top:4px;">
                @if(!empty($debut) && !empty($fin))
                Période : {{ \Carbon\Carbon::parse($debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }}
                @endif
            </div>
            <div style="font-size:9px; color:#94a3b8; margin-top:2px;">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
        </td>
    </tr>
</table>
