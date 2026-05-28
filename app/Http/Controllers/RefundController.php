<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\RepairPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{

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
            $invoice->update(['statut' => 'annulee']);

            if ($invoice->repair_id) {
                // Supprimer les RepairPayments : ils servent à l'imputation en caisse.
                // Les garder après annulation gonflerait artificiellement le montant attendu
                // en caisse et corromprait le prochain calcul de montant_paye sur la réparation.
                RepairPayment::where('repair_id', $invoice->repair_id)->delete();

                if ($invoice->repair) {
                    $invoice->repair->update([
                        'etat_paiement' => 'Non soldé',
                        'reste_a_payer' => $invoice->montant_final,
                        'montant_paye'  => 0,
                    ]);
                }
            }
        });

        return back()->with('success', "Facture {$invoice->numero_facture} annulée. Avoir créé si applicable.");
    }
}
