<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Settings;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarginController extends Controller
{
    public function index(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $debut   = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin     = $request->input('fin', now()->toDateString());

        $repairs = Repair::when($shopId, fn($q) => $q->where('shopId', $shopId))
            ->where('etat_paiement', 'Soldé')
            ->whereBetween('date_creation', [$debut . ' 00:00:00', $fin . ' 23:59:59'])
            ->get();

        $lignes = $repairs->map(function ($repair) {
            $coutPieces = collect($repair->pieces_rechange_utilisees ?? [])->sum(function ($piece) {
                return ($piece['prixAchat'] ?? 0) * ($piece['quantiteUtilisee'] ?? 0);
            });

            $ca        = $repair->total_reparation ?? 0;
            $marge     = $ca - $coutPieces;
            $tauxMarge = $ca > 0 ? round(($marge / $ca) * 100, 1) : 0;

            return [
                'repair'       => $repair,
                'ca'           => $ca,
                'cout_pieces'  => $coutPieces,
                'marge'        => $marge,
                'taux_marge'   => $tauxMarge,
            ];
        })->sortByDesc('marge');

        $totaux = [
            'ca'          => $lignes->sum('ca'),
            'cout_pieces' => $lignes->sum('cout_pieces'),
            'marge'       => $lignes->sum('marge'),
            'taux_marge'  => $lignes->sum('ca') > 0
                ? round(($lignes->sum('marge') / $lignes->sum('ca')) * 100, 1)
                : 0,
        ];

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($lignes, $totaux, $debut, $fin);
        }

        if ($request->query('export') === 'pdf') {
            return $this->exportPdfView($lignes, $totaux, $debut, $fin, $shopId);
        }

        return view('margin.index', compact('lignes', 'totaux', 'debut', 'fin'));
    }

    private function exportPdfView($lignes, $totaux, string $debut, string $fin, ?string $shopId)
    {
        $companyInfo = $this->getCompanyInfo($shopId);
        $logoBase64  = $this->getLogoBase64();

        return Pdf::loadView('exports.marges-pdf', compact('lignes', 'totaux', 'debut', 'fin', 'companyInfo', 'logoBase64'))
            ->setPaper('a4', 'landscape')
            ->download('marges-' . now()->format('Y-m-d') . '.pdf');
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

    private function exportCsv($lignes, $totaux, string $debut, string $fin)
    {
        $headers = ['N° Réparation', 'Client', 'Appareil', 'CA (cfa)', 'Coût pièces (cfa)', 'Marge (cfa)', 'Taux marge (%)'];
        $csv     = implode(',', $headers) . "\n";

        foreach ($lignes as $l) {
            $csv .= implode(',', [
                '"' . $l['repair']->numeroReparation . '"',
                '"' . $l['repair']->client_nom . '"',
                '"' . $l['repair']->appareil_marque_modele . '"',
                $l['ca'],
                $l['cout_pieces'],
                $l['marge'],
                $l['taux_marge'],
            ]) . "\n";
        }

        $csv .= implode(',', ['"TOTAL"', '', '', $totaux['ca'], $totaux['cout_pieces'], $totaux['marge'], $totaux['taux_marge']]) . "\n";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"marge_{$debut}_{$fin}.csv\"",
        ]);
    }
}
