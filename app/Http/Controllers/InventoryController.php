<?php

namespace App\Http\Controllers;

use App\Models\InventoryLine;
use App\Models\InventorySession;
use App\Models\Stock;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $service) {}

    public function index(Request $request)
    {
        $sessions = InventorySession::with('createdBy')->orderByDesc('created_at')->paginate(20);
        return view('inventory.index', compact('sessions'));
    }

    public function ouvrir(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $session = $this->service->ouvrir($shopId, $user->id, $validated['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.show', $session->id)
            ->with('success', 'Inventaire ouvert. Vous pouvez saisir les quantités comptées.');
    }

    public function show(string $id)
    {
        $session = InventorySession::with(['lines.stock', 'createdBy', 'closedBy'])->findOrFail($id);
        return view('inventory.show', compact('session'));
    }

    public function saisir(Request $request, string $sessionId, string $lineId)
    {
        $session = InventorySession::findOrFail($sessionId);

        if (!$session->isEnCours()) {
            return back()->with('error', 'Cet inventaire est terminé.');
        }

        $line = InventoryLine::where('id', $lineId)
            ->where('inventory_session_id', $sessionId)
            ->firstOrFail();

        $validated = $request->validate([
            'quantite_comptee' => ['required', 'integer', 'min:0', 'max:999999'],
            'notes'            => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->saisirLigne($line, $validated['quantite_comptee'], $validated['notes'] ?? null);

        if ($request->ajax()) {
            return response()->json(['ecart' => $line->fresh()->ecart]);
        }

        return back()->with('success', 'Quantité enregistrée.');
    }

    public function cloturer(Request $request, string $id)
    {
        $session = InventorySession::findOrFail($id);
        $user    = $request->attributes->get('user');

        $validated = $request->validate([
            'appliquer_ajustements' => ['nullable', 'boolean'],
        ]);

        try {
            $session = $this->service->cloturer(
                $session,
                $user->id,
                (bool) ($validated['appliquer_ajustements'] ?? true)
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory.show', $session->id)
            ->with('success', 'Inventaire clôturé. Les stocks ont été ajustés.');
    }

    public function rapport(string $id)
    {
        $session = InventorySession::with(['lines.stock', 'createdBy', 'closedBy'])->findOrFail($id);
        return view('inventory.rapport', compact('session'));
    }
}
