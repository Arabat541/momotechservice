<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Sale;
use App\Models\Invoice;
use App\Services\CashSessionService;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    public function __construct(private CashSessionService $cashSessionService) {}

    public function index(Request $request)
    {
        $sessions = CashSession::orderByDesc('date')->paginate(30);
        return view('cash-sessions.index', compact('sessions'));
    }

    public function ouvrir(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $validated = $request->validate([
            'montant_ouverture' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ]);

        try {
            $session = $this->cashSessionService->ouvrir(
                $shopId,
                $user->id,
                floatval($validated['montant_ouverture'])
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('caisse.show', $session->id)
            ->with('success', 'Caisse ouverte.');
    }

    public function show(Request $request, string $id)
    {
        $session = CashSession::withoutGlobalScopes()
            ->with(['sales.stock', 'invoices.repair', 'user'])
            ->findOrFail($id);

        // Vérifier l'accès : patron voit tout, caissière/technicien seulement sa boutique
        $user   = $request->attributes->get('user');
        $shopId = $request->attributes->get('shopId');
        if ($user->role !== 'patron' && $session->shopId !== $shopId) {
            abort(403, 'Accès refusé à cette session de caisse.');
        }

        return view('cash-sessions.show', compact('session'));
    }

    public function zReport(string $id)
    {
        $session = CashSession::withoutGlobalScopes()
            ->with(['sales.stock', 'invoices.repair.client', 'user'])
            ->findOrFail($id);

        $ventesComptant    = $session->sales->where('mode_paiement', 'comptant');
        $ventesCredit      = $session->sales->where('mode_paiement', 'credit');
        $acomptes          = $session->invoices->where('montant_paye', '>', 0);
        $soldees           = $session->invoices->where('statut', 'soldee');

        $report = [
            'session'                  => $session,
            'nb_ventes_comptant'       => $ventesComptant->count(),
            'total_ventes_comptant'    => $ventesComptant->sum('montant_paye'),
            'nb_ventes_credit'         => $ventesCredit->count(),
            'total_ventes_credit'      => $ventesCredit->sum('total'),
            'nb_acomptes'              => $acomptes->count(),
            'total_acomptes'           => $acomptes->sum('montant_paye'),
            'nb_factures_soldees'      => $soldees->count(),
            'total_factures_soldees'   => $soldees->sum('montant_paye'),
            'total_encaisse'           => $ventesComptant->sum('montant_paye') + $acomptes->sum('montant_paye'),
            'montant_ouverture'        => $session->montant_ouverture,
            'montant_fermeture_attendu'=> $session->montant_fermeture_attendu,
            'montant_fermeture_reel'   => $session->montant_fermeture_reel,
            'ecart'                    => $session->ecart,
        ];

        return view('cash-sessions.z-report', compact('report'));
    }

    public function fermer(Request $request, string $id)
    {
        $session = CashSession::findOrFail($id);

        $validated = $request->validate([
            'montant_fermeture_reel' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ]);

        try {
            $session = $this->cashSessionService->fermer($session, floatval($validated['montant_fermeture_reel']));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('caisse.show', $session->id)
            ->with('success', 'Caisse fermée. Écart : ' . $session->ecart);
    }
}
