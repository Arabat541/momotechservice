<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\InventorySession;
use App\Models\PurchaseInvoice;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function export(Request $request, string $module): Response
    {
        $shopId = $request->attributes->get('shopId');

        return match ($module) {
            'factures-fournisseurs' => $this->exportPurchaseInvoices($shopId),
            'credits'               => $this->exportCredits($shopId),
            'inventaires'           => $this->exportInventory($shopId),
            'transferts'            => $this->exportTransfers($shopId),
            default                 => abort(404),
        };
    }

    private function exportPurchaseInvoices(string $shopId): Response
    {
        $rows = PurchaseInvoice::where('shopId', $shopId)->with('supplier', 'lines')->get();

        $csv = implode(',', ['N° Facture', 'Fournisseur', 'Date', 'Montant TTC', 'Montant Payé', 'Reste', 'Statut']) . "\n";

        foreach ($rows as $inv) {
            $csv .= implode(',', [
                '"' . $inv->numero . '"',
                '"' . optional($inv->supplier)->nom . '"',
                '"' . $inv->date_facture . '"',
                $inv->montant_ttc,
                $inv->montant_paye,
                $inv->reste_a_payer,
                '"' . $inv->statut . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="factures-fournisseurs.csv"',
        ]);
    }

    private function exportCredits(string $shopId): Response
    {
        $rows = CreditTransaction::where('shopId', $shopId)->with('client')->orderBy('created_at', 'desc')->get();

        $csv = implode(',', ['Date', 'Client', 'Type', 'Montant', 'Notes']) . "\n";

        foreach ($rows as $tx) {
            $csv .= implode(',', [
                '"' . $tx->created_at->format('d/m/Y H:i') . '"',
                '"' . optional($tx->client)->nom . '"',
                '"' . $tx->type . '"',
                $tx->montant,
                '"' . str_replace('"', '""', $tx->notes ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="credits.csv"',
        ]);
    }

    private function exportInventory(string $shopId): Response
    {
        $sessions = InventorySession::where('shopId', $shopId)->with('lines.stock')->orderBy('created_at', 'desc')->get();

        $csv = implode(',', ['Session', 'Date', 'Article', 'Qté théorique', 'Qté comptée', 'Écart']) . "\n";

        foreach ($sessions as $session) {
            foreach ($session->lines as $line) {
                $ecart = $line->quantite_comptee - $line->quantite_theorique;
                $csv .= implode(',', [
                    '"INV-' . $session->id . '"',
                    '"' . $session->created_at->format('d/m/Y') . '"',
                    '"' . optional($line->stock)->nom . '"',
                    $line->quantite_theorique,
                    $line->quantite_comptee,
                    $ecart,
                ]) . "\n";
            }
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventaires.csv"',
        ]);
    }

    private function exportTransfers(string $shopId): Response
    {
        $rows = StockTransfer::withoutGlobalScopes()
            ->where(fn ($q) => $q->where('shop_from_id', $shopId)->orWhere('shop_to_id', $shopId))
            ->with('shopFrom', 'shopTo', 'createdBy', 'lines.stock')
            ->orderBy('created_at', 'desc')
            ->get();

        $csv = implode(',', ['N° Transfert', 'De', 'Vers', 'Article', 'Quantité', 'Statut', 'Date']) . "\n";

        foreach ($rows as $transfer) {
            foreach ($transfer->lines as $line) {
                $csv .= implode(',', [
                    '"' . $transfer->numero . '"',
                    '"' . optional($transfer->shopFrom)->nom . '"',
                    '"' . optional($transfer->shopTo)->nom . '"',
                    '"' . optional($line->stock)->nom . '"',
                    $line->quantite,
                    '"' . $transfer->statut . '"',
                    '"' . $transfer->created_at->format('d/m/Y') . '"',
                ]) . "\n";
            }
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="transferts.csv"',
        ]);
    }
}
