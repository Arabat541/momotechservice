<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\RepairPayment;
use Illuminate\Support\Facades\DB;

class CashSessionService
{
    public function ouvrir(?string $shopId, string $userId, float $montantOuverture): CashSession
    {
        if (!$shopId) {
            throw new \RuntimeException('Aucune boutique sélectionnée.');
        }

        $today = now()->toDateString();

        $existante = CashSession::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->where('date', $today)
            ->first();

        if ($existante) {
            throw new \RuntimeException('Une caisse est déjà ouverte pour aujourd\'hui.');
        }

        return CashSession::create([
            'shopId'           => $shopId,
            'userId'           => $userId,
            'date'             => $today,
            'opened_at'        => now(),
            'montant_ouverture'=> $montantOuverture,
            'statut'           => 'ouverte',
        ]);
    }

    public function fermer(CashSession $session, float $montantReel): CashSession
    {
        if (!$session->isOuverte()) {
            throw new \RuntimeException('Cette caisse est déjà fermée.');
        }

        return DB::transaction(function () use ($session, $montantReel) {
            $attendu = $this->calculerMontantAttendu($session);
            $ecart   = $montantReel - $attendu;

            $session->update([
                'montant_fermeture_attendu' => $attendu,
                'montant_fermeture_reel'    => $montantReel,
                'ecart'                     => $ecart,
                'closed_at'                 => now(),
                'statut'                    => 'fermee',
            ]);

            return $session->fresh();
        });
    }

    public function sessionOuverte(?string $shopId): ?CashSession
    {
        if (!$shopId) {
            return null;
        }
        return CashSession::withoutGlobalScopes()
            ->where('shopId', $shopId)
            ->where('statut', 'ouverte')
            ->latest()
            ->first();
    }

    private function calculerMontantAttendu(CashSession $session): float
    {
        $totalVentes = $session->sales()
            ->where('mode_paiement', 'comptant')
            ->sum('montant_paye');

        // Paiements de réparations directement imputés à cette session via repair_payments.cash_session_id.
        // Couvre les paiements à la création, les acomptes et les soldes — quelle que soit la session
        // d'ouverture de la réparation.
        $totalRepairPayments = RepairPayment::where('cash_session_id', $session->id)->sum('montant');

        return $session->montant_ouverture + $totalVentes + $totalRepairPayments;
    }
}
