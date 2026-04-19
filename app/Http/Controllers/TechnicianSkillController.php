<?php

namespace App\Http\Controllers;

use App\Models\TechnicianSkill;
use App\Models\User;
use Illuminate\Http\Request;

class TechnicianSkillController extends Controller
{
    public function index(Request $request)
    {
        $shopId      = $request->attributes->get('shopId');
        $techniciens = User::whereHas('shops', fn ($q) => $q->where('shops.id', $shopId))
            ->where('role', 'technicien')
            ->with('skills')
            ->get();

        return view('skills.index', compact('techniciens'));
    }

    public function store(Request $request)
    {
        $shopId = $request->attributes->get('shopId');

        $validated = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'marque'        => 'nullable|string|max:100',
            'type_appareil' => 'nullable|in:smartphone,tablette,ordinateur,autre',
        ]);

        // Ensure the user belongs to this shop
        User::whereHas('shops', fn ($q) => $q->where('shops.id', $shopId))
            ->where('id', $validated['user_id'])
            ->where('role', 'technicien')
            ->firstOrFail();

        TechnicianSkill::create($validated);

        return back()->with('success', 'Compétence ajoutée.');
    }

    public function destroy(Request $request, int $id)
    {
        $shopId = $request->attributes->get('shopId');
        $skill  = TechnicianSkill::findOrFail($id);

        // Ensure the skill's user belongs to this shop
        User::whereHas('shops', fn ($q) => $q->where('shops.id', $shopId))
            ->where('id', $skill->user_id)
            ->firstOrFail();

        $skill->delete();

        return back()->with('success', 'Compétence supprimée.');
    }
}
