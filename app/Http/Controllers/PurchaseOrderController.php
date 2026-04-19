<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(private PurchaseOrderService $service) {}

    public function index(Request $request)
    {
        $statut  = $request->query('statut');
        $retard  = $request->boolean('retard');
        $query   = PurchaseOrder::with('supplier')->orderByDesc('date_commande');

        if ($statut) {
            $query->where('statut', $statut);
        }

        if ($retard) {
            $query->whereNotNull('date_livraison_prevue')
                  ->where('date_livraison_prevue', '<', now())
                  ->whereNotIn('statut', ['recu', 'annule']);
        }

        $orders    = $query->paginate(20);
        $enAlerte  = Stock::where('seuil_alerte', '>', 0)
            ->whereColumn('quantite', '<=', 'seuil_alerte')
            ->count();

        return view('purchase-orders.index', compact('orders', 'enAlerte'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::where('actif', true)->orderBy('nom')->get();
        $stocks    = Stock::where('seuil_alerte', '>', 0)
            ->orderByRaw('quantite - seuil_alerte ASC')
            ->get();

        return view('purchase-orders.create', compact('suppliers', 'stocks'));
    }

    public function store(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $validated = $request->validate([
            'supplier_id'                   => ['required', 'string', 'exists:suppliers,id'],
            'date_commande'                 => ['required', 'date'],
            'date_livraison_prevue'         => ['nullable', 'date', 'after_or_equal:date_commande'],
            'notes'                         => ['nullable', 'string', 'max:1000'],
            'lignes'                        => ['required', 'array', 'min:1'],
            'lignes.*.designation'          => ['required', 'string', 'max:255'],
            'lignes.*.quantite_commandee'   => ['required', 'integer', 'min:1'],
            'lignes.*.prix_unitaire_estime' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.stock_id'             => ['nullable', 'exists:stocks,id'],
        ]);

        try {
            $order = $this->service->creer($validated, $shopId, $user->id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('purchase-orders.show', $order->id)
            ->with('success', "Bon de commande {$order->numero} créé.");
    }

    public function show(string $id)
    {
        $order = PurchaseOrder::with(['supplier', 'lines.stock', 'createdBy'])->findOrFail($id);
        return view('purchase-orders.show', compact('order'));
    }

    public function envoyer(string $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        try {
            $this->service->envoyer($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bon de commande marqué comme envoyé.');
    }

    public function reception(Request $request, string $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'receptions'   => ['required', 'array'],
            'receptions.*' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $order = $this->service->recevoirPartiel($order, $validated['receptions']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $msg = $order->statut === 'recu'
            ? 'Commande entièrement reçue.'
            : 'Réception partielle enregistrée.';

        return back()->with('success', $msg);
    }

    public function annuler(string $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        try {
            $this->service->annuler($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bon de commande annulé.');
    }

    public function print(string $id)
    {
        $order = PurchaseOrder::with(['supplier', 'lines.stock', 'createdBy'])->findOrFail($id);
        return view('purchase-orders.print', compact('order'));
    }
}
