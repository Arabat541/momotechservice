<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\Invoice;
use App\Models\Repair;
use Illuminate\Http\Request;

class ApiClientController extends Controller
{
    /**
     * Return the authenticated client's repairs (paginated).
     * Auth: Sanctum token with 'revendeur' ability.
     */
    public function repairs(Request $request)
    {
        $client = $request->user()->client ?? null;

        if (!$client) {
            return response()->json(['error' => 'Client non trouvé.'], 404);
        }

        $repairs = Repair::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->orderBy('date_creation', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $repairs->map(fn ($r) => [
                'numero'    => $r->numeroReparation,
                'appareil'  => $r->appareil_marque_modele,
                'statut'    => $r->statut_reparation,
                'total'     => $r->total_reparation,
                'paye'      => $r->montant_paye,
                'reste'     => $r->reste_a_payer,
                'cree_le'   => $r->date_creation?->toDateString(),
            ]),
            'meta' => [
                'current_page' => $repairs->currentPage(),
                'last_page'    => $repairs->lastPage(),
                'total'        => $repairs->total(),
            ],
        ]);
    }

    public function invoices(Request $request)
    {
        $client = $request->user()->client ?? null;

        if (!$client) {
            return response()->json(['error' => 'Client non trouvé.'], 404);
        }

        $invoices = Invoice::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $invoices->map(fn ($i) => [
                'numero'    => $i->numero_facture,
                'montant'   => $i->montant_final,
                'paye'      => $i->montant_paye,
                'reste'     => $i->reste_a_payer,
                'statut'    => $i->statut,
                'cree_le'   => $i->created_at?->toDateString(),
            ]),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
                'total'        => $invoices->total(),
            ],
        ]);
    }

    public function credits(Request $request)
    {
        $client = $request->user()->client ?? null;

        if (!$client) {
            return response()->json(['error' => 'Client non trouvé.'], 404);
        }

        $transactions = CreditTransaction::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'solde_credit' => $client->solde_credit,
            'data' => $transactions->map(fn ($t) => [
                'type'    => $t->type,
                'montant' => $t->montant,
                'notes'   => $t->notes,
                'date'    => $t->created_at?->toDateString(),
            ]),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'total'        => $transactions->total(),
            ],
        ]);
    }
}
