<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        $shopId      = $request->attributes->get('shopId');
        $semaine     = $request->query('semaine', Carbon::now()->startOfWeek()->toDateString());
        $techId      = $request->query('technicien');

        $debut = Carbon::parse($semaine)->startOfWeek();
        $fin   = $debut->copy()->endOfWeek();

        $techniciens = User::whereHas('shops', fn($q) => $q->where('shops.id', $shopId))
            ->whereIn('role', ['technicien', 'patron'])
            ->orderBy('nom')
            ->get();

        $query = Repair::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->whereNotIn('statut_reparation', ['Récupéré', 'Annulé'])
            ->with(['assignedTo', 'client']);

        if ($techId) {
            $query->where('assigned_to', $techId);
        }

        $reparations = $query->orderBy('date_creation')->get();

        // Grouper par technicien → par statut
        $planning = $techniciens->mapWithKeys(function (User $tech) use ($reparations) {
            return [
                $tech->id => [
                    'technicien'  => $tech,
                    'reparations' => $reparations->where('assigned_to', $tech->id)->values(),
                ],
            ];
        });

        // Non assignées
        $nonAssignees = $reparations->whereNull('assigned_to')->values();

        return view('planning.index', compact('planning', 'nonAssignees', 'techniciens', 'debut', 'fin', 'techId'));
    }

    public function assigner(Request $request, string $repairId)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::withoutGlobalScopes()
            ->where('id', $repairId)
            ->where('shopId', $shopId)
            ->firstOrFail();

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'string', 'exists:users,id'],
        ]);

        $repair->update(['assigned_to' => $validated['assigned_to'] ?? null]);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Réparation assignée.');
    }
}
