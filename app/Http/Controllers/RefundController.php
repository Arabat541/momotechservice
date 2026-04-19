<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function __construct(private CreditService $creditService) {}

    public function rembourser(Request $request, string $id)
    {
        $shopId  = $request->attributes->get('shopId');
        $user    = $request->attributes->get('user');
        $invoice = Invoice::where('id', $id)->where('shopId', $shopId)->firstOrFail();

        if ($invoice->statut === 'annulee') {
            return back()->with('error', 'Cette facture est déjà annulée.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($invoice, $user, $validated) {
            // Créer un avoir si un client est lié et que du montant a été payé
            if ($invoice->client && $invoice->montant_paye > 0) {
                $this->creditService->enregistrerAvoir(
                    $invoice->client,
                    $invoice->montant_paye,
                    $user->id,
                    $validated['notes'] ?? "Avoir suite annulation facture {$invoice->numero_facture}"
                );
            }

            $invoice->update(['statut' => 'annulee']);

            // Remettre la réparation liée en "En cours" si elle était soldée
            if ($invoice->repair && $invoice->repair->etat_paiement === 'Soldé') {
                $invoice->repair->update([
                    'etat_paiement' => 'Non soldé',
                    'reste_a_payer' => $invoice->montant_final,
                    'montant_paye'  => 0,
                ]);
            }
        });

        return back()->with('success', "Facture {$invoice->numero_facture} annulée. Avoir créé si applicable.");
    }
}
