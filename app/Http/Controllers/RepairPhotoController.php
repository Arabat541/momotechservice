<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\RepairPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairPhotoController extends Controller
{
    private const DISK = 'local';
    private const DIR  = 'repairs';

    public function store(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        $request->validate([
            'photo'   => 'required|file|mimes:jpeg,jpg,png,webp|max:5120',
            'type'    => 'required|in:avant,apres',
            'legende' => 'nullable|string|max:150',
        ]);

        if ($repair->photos()->count() >= 5) {
            return back()->with('error', 'Maximum 5 photos par réparation.');
        }

        $file     = $request->file('photo');
        $filename = $repair->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        Storage::disk(self::DISK)->putFileAs(self::DIR, $file, $filename);

        RepairPhoto::create([
            'repair_id' => $repair->id,
            'chemin'    => $filename,
            'legende'   => $request->legende,
            'type'      => $request->type,
        ]);

        return back()->with('success', 'Photo ajoutée.');
    }

    public function serve(Request $request, int $photoId)
    {
        $shopId = $request->attributes->get('shopId');
        $photo  = RepairPhoto::findOrFail($photoId);

        Repair::where('id', $photo->repair_id)->where('shopId', $shopId)->firstOrFail();

        $path = self::DIR . '/' . $photo->chemin;

        if (!Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk(self::DISK)->path($path));
    }

    public function destroy(Request $request, int $photoId)
    {
        $shopId = $request->attributes->get('shopId');
        $photo  = RepairPhoto::findOrFail($photoId);

        Repair::where('id', $photo->repair_id)->where('shopId', $shopId)->firstOrFail();

        Storage::disk(self::DISK)->delete(self::DIR . '/' . $photo->chemin);

        $photo->delete();

        return back()->with('success', 'Photo supprimée.');
    }
}
