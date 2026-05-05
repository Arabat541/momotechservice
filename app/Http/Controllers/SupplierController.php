<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $search    = $request->query('search');
        $query     = Supplier::withCount('reappros');

        if ($search) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where(function ($q) use ($escaped) {
                $q->where('nom', 'like', "%{$escaped}%")
                  ->orWhere('contact_nom', 'like', "%{$escaped}%")
                  ->orWhere('telephone', 'like', "%{$escaped}%");
            });
        }

        $suppliers = $query->orderBy('nom')->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $shopId = $request->attributes->get('shopId')
            ?? $request->input('shop_id');

        if (!$shopId) {
            return back()->with('error', 'Veuillez sélectionner une boutique.');
        }

        $validated = $request->validate([
            'nom'                    => ['required', 'string', 'max:150'],
            'contact_nom'            => ['nullable', 'string', 'max:150'],
            'telephone'              => ['nullable', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'email'                  => ['nullable', 'email', 'max:255'],
            'adresse'                => ['nullable', 'string', 'max:500'],
            'delai_livraison_jours'  => ['nullable', 'integer', 'min:0', 'max:365'],
            'conditions_paiement'    => ['nullable', 'string', 'max:500'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
        ]);

        $supplier = Supplier::create(array_merge($validated, ['shopId' => $shopId]));

        return redirect()->route('suppliers.show', $supplier->id)
            ->with('success', 'Fournisseur créé.');
    }

    public function show(string $id)
    {
        $supplier = Supplier::with(['reappros.stock', 'purchaseInvoices'])->findOrFail($id);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, string $id)
    {
        $supplier  = Supplier::findOrFail($id);
        $validated = $request->validate([
            'nom'                    => ['required', 'string', 'max:150'],
            'contact_nom'            => ['nullable', 'string', 'max:150'],
            'telephone'              => ['nullable', 'string', 'max:30', 'regex:/^[\d\+\-\s\(\)]{7,30}$/'],
            'email'                  => ['nullable', 'email', 'max:255'],
            'adresse'                => ['nullable', 'string', 'max:500'],
            'delai_livraison_jours'  => ['nullable', 'integer', 'min:0', 'max:365'],
            'conditions_paiement'    => ['nullable', 'string', 'max:500'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
            'actif'                  => ['nullable', 'boolean'],
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.show', $supplier->id)
            ->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->reappros()->exists()) {
            return back()->with('error', 'Impossible de supprimer : ce fournisseur a des réapprovisionnements liés.');
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Fournisseur supprimé.');
    }

    public function exportPdf(Request $request, string $id)
    {
        $supplier    = Supplier::with(['reappros.stock', 'purchaseInvoices'])->findOrFail($id);
        $shopId      = $request->attributes->get('shopId');
        $settings    = $shopId ? Settings::withoutGlobalScopes()->where('shopId', $shopId)->first() : null;
        $companyInfo = array_merge(['nom' => 'MOMO TECH SERVICE', 'adresse' => '', 'telephone' => ''], $settings?->companyInfo ?? []);
        $logoBase64  = null;
        foreach (['logo-receipt.png', 'logo-app.png'] as $file) {
            $path = public_path('images/' . $file);
            if (file_exists($path)) { $logoBase64 = base64_encode(file_get_contents($path)); break; }
        }

        return Pdf::loadView('exports.fournisseur-fiche-pdf', compact('supplier', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'portrait')
            ->download('fournisseur-' . \Illuminate\Support\Str::slug($supplier->nom) . '-' . now()->format('Y-m-d') . '.pdf');
    }
}
