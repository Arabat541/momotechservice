<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Repair;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q    = trim($request->query('q', ''));
        $role = $request->attributes->get('userRole', session('user_role', 'caissiere'));

        if (mb_strlen($q) < 2) {
            return response()->json(['results' => [], 'total' => 0]);
        }

        $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);

        $results = array_merge(
            $this->searchReparations($esc),
            $this->searchClients($esc),
            $this->searchStocks($esc),
            $role === 'patron' ? $this->searchFournisseurs($esc) : [],
            $this->searchFactures($esc)
        );

        return response()->json(['results' => $results, 'total' => count($results)]);
    }

    private function searchReparations(string $esc): array
    {
        return Repair::where(function ($q) use ($esc) {
            $q->where('numeroReparation',       'like', "%{$esc}%")
              ->orWhere('appareil_marque_modele','like', "%{$esc}%")
              ->orWhere('client_nom',            'like', "%{$esc}%")
              ->orWhere('client_telephone',      'like', "%{$esc}%");
        })
        ->orderByDesc('created_at')
        ->limit(5)
        ->get()
        ->map(fn($r) => [
            'type'     => 'reparation',
            'icon'     => 'wrench',
            'label'    => $r->numeroReparation,
            'sublabel' => $r->client_nom . ' — ' . $r->appareil_marque_modele,
            'url'      => route('reparations.show', $r->id),
        ])
        ->values()
        ->toArray();
    }

    private function searchClients(string $esc): array
    {
        return Client::where(function ($q) use ($esc) {
            $q->where('nom',       'like', "%{$esc}%")
              ->orWhere('telephone','like', "%{$esc}%");
        })
        ->orderBy('nom')
        ->limit(5)
        ->get()
        ->map(fn($c) => [
            'type'     => 'client',
            'icon'     => 'user',
            'label'    => $c->nom,
            'sublabel' => $c->telephone ?? ($c->nom_boutique ?? '—'),
            'url'      => route('clients.show', $c->id),
        ])
        ->values()
        ->toArray();
    }

    private function searchStocks(string $esc): array
    {
        return Stock::where('nom', 'like', "%{$esc}%")
        ->orderBy('nom')
        ->limit(5)
        ->get()
        ->map(fn($s) => [
            'type'     => 'stock',
            'icon'     => 'cube',
            'label'    => $s->nom,
            'sublabel' => $s->quantite . ' en stock' . ($s->categorie ? ' — ' . $s->categorie : ''),
            'url'      => route('stocks.index') . '?search=' . urlencode($s->nom),
        ])
        ->values()
        ->toArray();
    }

    private function searchFournisseurs(string $esc): array
    {
        return Supplier::where(function ($q) use ($esc) {
            $q->where('nom',        'like', "%{$esc}%")
              ->orWhere('contact_nom','like', "%{$esc}%");
        })
        ->orderBy('nom')
        ->limit(5)
        ->get()
        ->map(fn($s) => [
            'type'     => 'fournisseur',
            'icon'     => 'truck',
            'label'    => $s->nom,
            'sublabel' => $s->contact_nom ?? $s->telephone ?? '—',
            'url'      => route('suppliers.index') . '?search=' . urlencode($s->nom),
        ])
        ->values()
        ->toArray();
    }

    private function searchFactures(string $esc): array
    {
        return Invoice::with(['client', 'repair'])
        ->where(function ($q) use ($esc) {
            $q->where('numero_facture', 'like', "%{$esc}%")
              ->orWhereHas('client', fn($cq) => $cq->where('nom', 'like', "%{$esc}%"))
              ->orWhereHas('repair',  fn($rq) => $rq->where('client_nom', 'like', "%{$esc}%"));
        })
        ->orderByDesc('created_at')
        ->limit(5)
        ->get()
        ->map(fn($i) => [
            'type'     => 'facture',
            'icon'     => 'file-invoice',
            'label'    => $i->numero_facture,
            'sublabel' => $i->client?->nom ?? $i->repair?->client_nom ?? '—',
            'url'      => route('invoices.show', $i->id),
        ])
        ->values()
        ->toArray();
    }
}
