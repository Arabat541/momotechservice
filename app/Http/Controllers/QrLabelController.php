<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Settings;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrLabelController extends Controller
{
    public function repair(Request $request, string $id)
    {
        $shopId   = $request->attributes->get('shopId');
        $repair   = Repair::withoutGlobalScopes()
            ->where('id', $id)
            ->where('shopId', $shopId)
            ->firstOrFail();

        $settings = Settings::withoutGlobalScopes()->where('shopId', $shopId)->first();

        // URL de suivi public pour le client
        $trackUrl = route('track.search') . '?numero=' . urlencode($repair->numeroReparation);

        $qrSvg = QrCode::format('svg')
            ->size(150)
            ->errorCorrection('M')
            ->generate($trackUrl);

        return view('qr-labels.repair', compact('repair', 'settings', 'qrSvg', 'trackUrl'));
    }

    public function repairsBatch(Request $request)
    {
        $shopId    = $request->attributes->get('shopId');
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'max:50'],
            'ids.*' => ['required', 'string'],
        ]);

        $repairs = Repair::withoutGlobalScopes()
            ->whereIn('id', $validated['ids'])
            ->where('shopId', $shopId)
            ->get();

        $settings = Settings::withoutGlobalScopes()->where('shopId', $shopId)->first();

        $items = $repairs->map(function (Repair $repair) {
            $trackUrl = route('track.search') . '?numero=' . urlencode($repair->numeroReparation);
            return [
                'repair'  => $repair,
                'qrSvg'   => QrCode::format('svg')->size(120)->errorCorrection('M')->generate($trackUrl),
                'trackUrl'=> $trackUrl,
            ];
        });

        return view('qr-labels.batch', compact('items', 'settings'));
    }
}
