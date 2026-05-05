<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Settings;
use App\Models\Warranty;
use App\Services\WarrantyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class WarrantyController extends Controller
{
    public function __construct(private WarrantyService $service) {}

    public function index(Request $request)
    {
        $statut  = $request->query('statut');
        $search  = $request->query('search');

        $query = Warranty::with(['client', 'sale'])->orderByDesc('date_expiration');

        if ($statut) {
            $query->where('statut', $statut);
        }

        if ($search) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('designation', 'like', "%{$escaped}%");
            });
        }

        $warranties = $query->paginate(20);

        return view('warranties.index', compact('warranties'));
    }

    public function store(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $user   = $request->attributes->get('user');

        $validated = $request->validate([
            'sale_id'    => ['required', 'string', 'exists:Sale,id'],
            'duree_jours'=> ['required', 'integer', 'min:1', 'max:3650'],
            'conditions' => ['nullable', 'string', 'max:500'],
        ]);

        $sale = Sale::withoutGlobalScopes()
            ->where('id', $validated['sale_id'])
            ->where('shopId', $shopId)
            ->with('stock')
            ->firstOrFail();

        try {
            $warranty = $this->service->creer($sale, $validated['duree_jours'], $user->id, $validated['conditions'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Garantie de {$warranty->duree_jours} jours créée (expire le {$warranty->date_expiration->format('d/m/Y')}).");
    }

    public function utiliser(Request $request, string $id)
    {
        $warranty  = Warranty::findOrFail($id);
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->service->utiliser($warranty, $validated['notes']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Garantie marquée comme utilisée.');
    }

    public function show(string $id)
    {
        $warranty = Warranty::with(['client', 'sale.stock', 'createdBy'])->findOrFail($id);
        return view('warranties.show', compact('warranty'));
    }

    public function print(string $id)
    {
        $warranty = Warranty::with(['client', 'sale.stock'])->findOrFail($id);
        return view('warranties.print', compact('warranty'));
    }

    public function exportPdf(Request $request)
    {
        $shopId    = $request->attributes->get('shopId');
        $statut    = $request->query('statut');
        $warranties = Warranty::with(['client', 'sale'])
            ->when($shopId, fn($q) => $q->where('shopId', $shopId))
            ->when($statut, fn($q) => $q->where('statut', $statut))
            ->orderByDesc('date_expiration')
            ->get();

        $settings    = $shopId ? Settings::withoutGlobalScopes()->where('shopId', $shopId)->first() : null;
        $companyInfo = array_merge(['nom' => 'MOMO TECH SERVICE', 'adresse' => '', 'telephone' => ''], $settings?->companyInfo ?? []);
        $logoBase64  = null;
        foreach (['logo-receipt.png', 'logo-app.png'] as $file) {
            $path = public_path('images/' . $file);
            if (file_exists($path)) { $logoBase64 = base64_encode(file_get_contents($path)); break; }
        }

        return Pdf::loadView('exports.garanties-pdf', compact('warranties', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'portrait')
            ->download('garanties-' . now()->format('Y-m-d') . '.pdf');
    }
}
