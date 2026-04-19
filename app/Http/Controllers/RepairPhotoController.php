<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\RepairPhoto;
use Illuminate\Http\Request;

class RepairPhotoController extends Controller
{
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
        $dest     = public_path('uploads/repairs');

        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $file->move($dest, $filename);

        RepairPhoto::create([
            'repair_id' => $repair->id,
            'chemin'    => $filename,
            'legende'   => $request->legende,
            'type'      => $request->type,
        ]);

        return back()->with('success', 'Photo ajoutée.');
    }

    public function destroy(Request $request, int $photoId)
    {
        $shopId = $request->attributes->get('shopId');
        $photo  = RepairPhoto::findOrFail($photoId);

        // Ensure the photo belongs to a repair in the current shop
        $repair = Repair::where('id', $photo->repair_id)->where('shopId', $shopId)->firstOrFail();

        $path = public_path('uploads/repairs/' . $photo->chemin);
        if (file_exists($path)) {
            unlink($path);
        }

        $photo->delete();

        return back()->with('success', 'Photo supprimée.');
    }
}
