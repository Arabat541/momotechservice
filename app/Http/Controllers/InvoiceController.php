<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Repair;
use App\Services\CashSessionService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private CashSessionService $cashSessionService,
    ) {}

    public function index(Request $request)
    {
        $statut   = $request->query('statut');
        $query    = Invoice::with(['repair', 'client']);

        if ($statut) {
            $query->where('statut', $statut);
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20);
        return view('invoices.index', compact('invoices'));
    }

    public function creerDepuisReparation(Request $request, string $repairId)
    {
        $repair  = Repair::findOrFail($repairId);
        $shopId  = $request->attributes->get('shopId');
        $user    = $request->attributes->get('user');

        $validated = $request->validate([
            'acompte' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $session = $this->cashSessionService->sessionOuverte($shopId);
        if (!$session) {
            return back()->with('error', 'Aucune caisse ouverte. Veuillez ouvrir la caisse d\'abord.');
        }

        try {
            $invoice = $this->invoiceService->creerDepuisReparation(
                $repair,
                floatval($validated['acompte']),
                $session->id,
                $user->id
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Facture créée.');
    }

    public function show(string $id)
    {
        $invoice = Invoice::with(['repair', 'client', 'cashSession', 'createdBy'])->findOrFail($id);
        return view('invoices.show', compact('invoice'));
    }

    public function paiementFinal(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);
        $shopId  = $request->attributes->get('shopId');
        $user    = $request->attributes->get('user');

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
        ]);

        $session = $this->cashSessionService->sessionOuverte($shopId);
        if (!$session) {
            return back()->with('error', 'Aucune caisse ouverte.');
        }

        try {
            $invoice = $this->invoiceService->enregistrerPaiementFinal(
                $invoice,
                floatval($validated['montant']),
                $session->id
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Paiement enregistré.');
    }

    public function print(string $id)
    {
        $invoice = Invoice::with(['repair', 'client', 'createdBy', 'repair.shop'])->findOrFail($id);
        return view('invoices.print', compact('invoice'));
    }
}
