<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Settings;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AbandonController extends Controller
{
    public function index(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $delai   = (int) ($request->query('delai', 30));

        $repairs = Repair::query()
            ->where('statut_reparation', 'Terminé')
            ->whereNull('date_retrait')
            ->whereRaw('COALESCE(date_terminee, date_creation) <= ?', [now()->subDays($delai)])
            ->orderBy('date_terminee')
            ->paginate(20);

        return view('abandons.index', compact('repairs', 'delai'));
    }

    public function exportPdf(Request $request)
    {
        $shopId = $request->attributes->get('shopId');
        $delai  = (int) ($request->query('delai', 30));

        $repairs = Repair::when($shopId, fn($q) => $q->where('shopId', $shopId))
            ->where('statut_reparation', 'Terminé')
            ->whereNull('date_retrait')
            ->whereRaw('COALESCE(date_terminee, date_creation) <= ?', [now()->subDays($delai)])
            ->orderBy('date_terminee')
            ->get();

        $companyInfo = $this->getCompanyInfo($shopId);
        $logoBase64  = $this->getLogoBase64();

        return Pdf::loadView('exports.abandons-pdf', compact('repairs', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'landscape')
            ->download('abandons-' . now()->format('Y-m-d') . '.pdf');
    }

    private function getCompanyInfo(?string $shopId): array
    {
        $settings = $shopId
            ? Settings::withoutGlobalScopes()->where('shopId', $shopId)->first()
            : Settings::withoutGlobalScopes()->first();
        $default = ['nom' => 'MOMO TECH SERVICE', 'adresse' => '', 'telephone' => ''];
        return array_merge($default, $settings?->companyInfo ?? []);
    }

    private function getLogoBase64(): ?string
    {
        foreach (['logo-receipt.png', 'logo-app.png'] as $file) {
            $path = public_path('images/' . $file);
            if (file_exists($path)) {
                return base64_encode(file_get_contents($path));
            }
        }
        return null;
    }

    public function mettreEnVente(Request $request, string $id)
    {
        $shopId  = $request->attributes->get('shopId');
        $repair  = Repair::findOrFail($id);
        $user    = $request->attributes->get('user');

        if ($repair->date_retrait || $repair->statut_reparation !== 'Terminé') {
            return back()->with('error', 'Cette réparation n\'est pas éligible à la mise en vente.');
        }

        $validated = $request->validate([
            'nom_article'  => 'required|string|max:200',
            'prix_vente'   => 'required|numeric|min:0|max:99999999',
            'categorie'    => 'required|in:telephone,accessoire,piece_detachee,autre',
        ]);

        // Créer un article en stock dans la boutique de la réparation
        Stock::create([
            'id'         => Str::random(25),
            'shopId'     => $repair->shopId,
            'nom'        => $validated['nom_article'],
            'categorie'  => $validated['categorie'],
            'quantite'   => 1,
            'prixAchat'  => $repair->total_reparation ?? 0,
            'prixVente'  => $validated['prix_vente'],
        ]);

        $repair->update(['mis_en_vente' => true]);

        return back()->with('success', "Appareil mis en vente dans le stock.");
    }

    public function setDateLimite(Request $request, string $id)
    {
        $shopId = $request->attributes->get('shopId');
        $repair = Repair::findOrFail($id);

        $validated = $request->validate([
            'date_limite_recuperation' => 'required|date|after:today',
        ]);

        $repair->update($validated);

        return back()->with('success', 'Date limite fixée.');
    }
}
