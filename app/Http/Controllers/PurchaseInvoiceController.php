<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\Reapprovisionnement;
use App\Models\Stock;
use App\Models\Supplier;
use App\Services\PurchaseInvoiceService;
use Illuminate\Http\Request;

class PurchaseInvoiceController extends Controller
{
    public function __construct(private PurchaseInvoiceService $service) {}

    public function index(Request $request)
    {
        $statut    = $request->query('statut');
        $retard    = $request->boolean('retard');
        $query     = PurchaseInvoice::with('supplier')->orderByDesc('date_facture');

        if ($statut) {
            $query->where('statut', $statut);
        }

        if ($retard) {
            $query->whereNotNull('date_echeance')
                  ->where('date_echeance', '<', now())
                  ->whereIn('statut', ['en_attente', 'partiellement_payee']);
        }

        $invoices       = $query->paginate(20);
        $totalDu        = PurchaseInvoice::whereIn('statut', ['en_attente', 'partiellement_payee'])->sum('reste_a_payer');
        $totalEnRetard  = PurchaseInvoice::whereNotNull('date_echeance')
            ->where('date_echeance', '<', now())
            ->whereIn('statut', ['en_attente', 'partiellement_payee'])
            ->sum('reste_a_payer');

        return view('purchase-invoices.index', compact('invoices', 'totalDu', 'totalEnRetard'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::where('actif', true)->orderBy('nom')->get();
        $stocks    = Stock::orderBy('nom')->get();

        // Réappros non encore liés à une facture fournisseur
        $reappros  = Reapprovisionnement::with('stock')
            ->whereNull('purchase_invoice_id')
            ->orderByDesc('date')
            ->get();

        $supplierId = $request->query('supplier_id');

        return view('purchase-invoices.create', compact('suppliers', 'stocks', 'reappros', 'supplierId'));
    }

    public function store(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $validated = $request->validate([
            'supplier_id'          => ['required', 'string', 'exists:suppliers,id'],
            'date_facture'         => ['required', 'date'],
            'date_echeance'        => ['nullable', 'date', 'after_or_equal:date_facture'],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'lignes'               => ['required', 'array', 'min:1'],
            'lignes.*.designation' => ['required', 'string', 'max:255'],
            'lignes.*.quantite'    => ['required', 'integer', 'min:1'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
            'lignes.*.stock_id'    => ['nullable', 'exists:stocks,id'],
            'lignes.*.reappro_id'  => ['nullable', 'string'],
        ]);

        try {
            $invoice = $this->service->creer($validated, $shopId, $user->id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchase-invoices.show', $invoice->id)
            ->with('success', "Facture fournisseur {$invoice->numero} créée.");
    }

    public function show(string $id)
    {
        $invoice = PurchaseInvoice::with(['supplier', 'lines.stock', 'reappros.stock', 'createdBy'])
            ->findOrFail($id);

        return view('purchase-invoices.show', compact('invoice'));
    }

    public function paiement(Request $request, string $id)
    {
        $invoice   = PurchaseInvoice::findOrFail($id);
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:0.01', 'max:9999999'],
        ]);

        try {
            $this->service->enregistrerPaiement($invoice, floatval($validated['montant']));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Paiement enregistré.');
    }

    public function print(string $id)
    {
        $invoice = PurchaseInvoice::with(['supplier', 'lines.stock', 'createdBy'])->findOrFail($id);
        return view('purchase-invoices.print', compact('invoice'));
    }
}
