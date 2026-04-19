<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(private StockTransferService $service) {}

    public function index(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $query = StockTransfer::withoutGlobalScopes()
            ->with(['shopFrom', 'shopTo', 'createdBy'])
            ->orderByDesc('created_at');

        if ($user->role !== 'patron') {
            $query->where(function ($q) use ($shopId) {
                $q->where('shop_from_id', $shopId)
                  ->orWhere('shop_to_id', $shopId);
            });
        }

        $transfers = $query->paginate(20);

        return view('transfers.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        $currentShopId = $request->attributes->get('shopId');
        $shops  = Shop::withoutGlobalScopes()->get();
        $stocks = Stock::withoutGlobalScopes()
            ->where('quantite', '>', 0)
            ->orderBy('nom')
            ->get()
            ->groupBy('shopId');

        return view('transfers.create', compact('shops', 'stocks', 'currentShopId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shop_from_id'          => ['required', 'string', 'exists:shops,id'],
            'shop_to_id'            => ['required', 'string', 'exists:shops,id', 'different:shop_from_id'],
            'notes'                 => ['nullable', 'string', 'max:500'],
            'lignes'                => ['required', 'array', 'min:1'],
            'lignes.*.stock_id'     => ['required', 'string', 'exists:stocks,id'],
            'lignes.*.quantite'     => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->attributes->get('user');

        try {
            $transfer = $this->service->creer($validated, $user->id);
            return redirect()->route('transfers.show', $transfer->id)
                ->with('success', "Transfert {$transfer->numero} créé avec succès.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $transfer = StockTransfer::withoutGlobalScopes()
            ->with(['shopFrom', 'shopTo', 'createdBy', 'validatedBySender', 'validatedByReceiver', 'lines.stock'])
            ->findOrFail($id);

        if ($user->role !== 'patron' && !$transfer->impliqueBoutique($shopId)) {
            abort(403);
        }

        return view('transfers.show', compact('transfer', 'shopId', 'user'));
    }

    public function validerEnvoi(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $transfer = StockTransfer::withoutGlobalScopes()->with('lines.stock')->findOrFail($id);

        if ($user->role !== 'patron' && $transfer->shop_from_id !== $shopId) {
            abort(403, 'Seule la boutique expéditrice peut valider l\'envoi.');
        }

        try {
            $this->service->validerEnvoi($transfer, $user->id);
            return back()->with('success', 'Envoi validé. En attente de confirmation de la boutique destinataire.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function validerReception(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $transfer = StockTransfer::withoutGlobalScopes()->with('lines.stock')->findOrFail($id);

        if ($user->role !== 'patron' && $transfer->shop_to_id !== $shopId) {
            abort(403, 'Seule la boutique destinataire peut valider la réception.');
        }

        try {
            $this->service->validerReception($transfer, $user->id);
            return back()->with('success', 'Réception validée. Les articles sont maintenant disponibles dans la boutique.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function annuler(Request $request, string $id)
    {
        $transfer = StockTransfer::withoutGlobalScopes()->with('lines.stock')->findOrFail($id);

        try {
            $this->service->annuler($transfer);
            return back()->with('success', 'Transfert annulé.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
