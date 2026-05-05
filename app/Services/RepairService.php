<?php

namespace App\Services;

use App\Models\Repair;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RepairService
{
    public function __construct(private NotificationService $notificationService) {}


    // ─────────────────────────────────────────────────────────────
    //  RÉFÉRENTIEL DES STATUTS
    //  Clé    = valeur stockée en base de données
    //  color  = famille Tailwind CSS (sans intensité)
    //  icon   = classe Font Awesome (fas fa-...)
    //  label  = libellé court pour badges
    // ─────────────────────────────────────────────────────────────
    public const STATUTS = [
        'En attente'             => ['color' => 'slate',   'icon' => 'clock',        'label' => 'En attente'],
        'En attente de paiement' => ['color' => 'amber',   'icon' => 'money-bill',   'label' => 'Att. paiement'],
        'En cours'               => ['color' => 'blue',    'icon' => 'wrench',       'label' => 'En cours'],
        'En attente de pièces'   => ['color' => 'orange',  'icon' => 'box-open',     'label' => 'Att. pièces'],
        'Terminé'                => ['color' => 'teal',    'icon' => 'check',        'label' => 'Terminé'],
        'Prêt pour retrait'      => ['color' => 'green',   'icon' => 'check-circle', 'label' => 'Prêt retrait'],
        'Irréparable'            => ['color' => 'red',     'icon' => 'times-circle', 'label' => 'Irréparable'],
        'Livré'                  => ['color' => 'emerald', 'icon' => 'handshake',    'label' => 'Livré'],
        'Annulé'                 => ['color' => 'gray',    'icon' => 'ban',          'label' => 'Annulé'],
    ];

    // ─────────────────────────────────────────────────────────────
    //  TRANSITIONS AUTORISÉES
    //  Structure : [ statutActuel => [ role => [statutsSuivants] ] ]
    //  États terminaux (Livré, Annulé) : tableau vide (pas de
    //  transition) sauf pour le patron qui peut forcer un reset.
    // ─────────────────────────────────────────────────────────────
    public const TRANSITIONS = [
        'En attente' => [
            'caissiere' => ['En attente de paiement', 'En cours', 'Annulé'],
            'patron'    => ['En attente de paiement', 'En cours', 'Irréparable', 'Annulé'],
        ],
        'En attente de paiement' => [
            'caissiere' => ['En cours', 'Annulé'],
            'patron'    => ['En cours', 'Annulé'],
        ],
        'En cours' => [
            'caissiere' => ['En attente de pièces', 'Terminé', 'Prêt pour retrait', 'Irréparable', 'Annulé'],
            'patron'    => ['En attente de pièces', 'Terminé', 'Prêt pour retrait', 'Irréparable', 'Annulé'],
        ],
        'En attente de pièces' => [
            'caissiere' => ['En cours', 'Annulé'],
            'patron'    => ['En cours', 'Annulé'],
        ],
        'Terminé' => [
            'caissiere' => ['Prêt pour retrait', 'Livré', 'Annulé'],
            'patron'    => ['Prêt pour retrait', 'Livré', 'Annulé'],
        ],
        'Prêt pour retrait' => [
            'caissiere' => ['Livré', 'En cours'],
            'patron'    => ['Livré', 'En cours', 'Annulé'],
        ],
        'Irréparable' => [
            'caissiere' => ['Annulé'],
            'patron'    => ['Annulé', 'En cours'],
        ],
        'Livré'  => ['caissiere' => [], 'patron' => []],
        'Annulé' => ['caissiere' => [], 'patron' => []],
    ];

    /**
     * Retourne les statuts vers lesquels une réparation peut évoluer
     * depuis $currentStatut pour un rôle donné.
     */
    public function allowedTransitions(string $currentStatut, string $role): array
    {
        return self::TRANSITIONS[$currentStatut][$role] ?? array_keys(self::STATUTS);
    }

    /**
     * Retourne les classes Tailwind CSS pour le badge d'un statut.
     * Ex : 'bg-blue-100 text-blue-700 border-blue-200'
     */
    public function badgeClasses(string $statut): string
    {
        $map = [
            'En attente'             => 'bg-slate-100   text-slate-700   border-slate-200',
            'En attente de paiement' => 'bg-amber-100   text-amber-700   border-amber-200',
            'En cours'               => 'bg-blue-100    text-blue-700    border-blue-200',
            'En attente de pièces'   => 'bg-orange-100  text-orange-700  border-orange-200',
            'Terminé'                => 'bg-teal-100    text-teal-700    border-teal-200',
            'Prêt pour retrait'      => 'bg-green-100   text-green-700   border-green-200',
            'Irréparable'            => 'bg-red-100     text-red-700     border-red-200',
            'Livré'                  => 'bg-emerald-600 text-white       border-emerald-700',
            'Annulé'                 => 'bg-gray-100    text-gray-500    border-gray-200',
        ];

        return $map[$statut] ?? 'bg-gray-100 text-gray-500 border-gray-200';
    }

    /**
     * Retourne les champs de date à mettre à jour automatiquement lors
     * d'un changement de statut (ex : 'Terminé' → date_terminee = now()).
     */
    public function autoDateFields(string $newStatut): array
    {
        return match ($newStatut) {
            'Terminé'          => ['date_terminee'     => now()],
            'Prêt pour retrait'=> ['date_pret_retrait' => now()],
            'Irréparable'      => ['date_irreparable'  => now()],
            'Livré'            => ['date_retrait'      => now()],
            default            => [],
        };
    }

    public function buildPannes(array $descriptions, array $montants): array
    {
        $pannes = [];
        foreach ($descriptions as $i => $desc) {
            if ($desc) {
                $pannes[] = [
                    'description' => $desc,
                    'montant' => floatval($montants[$i] ?? 0),
                ];
            }
        }
        return $pannes;
    }

    public function buildPieces(array $pieceIds, array $pieceQtes, ?string $shopId): array
    {
        $pieces = [];
        foreach ($pieceIds as $i => $stockId) {
            if (!$stockId) continue;

            $qte = intval($pieceQtes[$i] ?? 1);
            $stock = Stock::withoutGlobalScopes()->where('id', $stockId)->where('shopId', $shopId)->first();

            if ($stock && $stock->quantite >= $qte) {
                $stock->decrement('quantite', $qte);
                $pieces[] = [
                    'stockId' => $stockId,
                    'nom' => $stock->nom,
                    'quantiteUtilisee' => $qte,
                    'prixVente' => $stock->prixVente,
                    'prixAchat' => $stock->prixAchat,
                ];
            }
        }
        return $pieces;
    }

    // Restore stock quantities for pieces already used in a repair (call before re-processing pieces on update).
    public function restorePiecesStock(array $oldPieces, ?string $shopId): void
    {
        foreach ($oldPieces as $piece) {
            $stockId = $piece['stockId'] ?? null;
            $qte     = intval($piece['quantiteUtilisee'] ?? 0);
            if (!$stockId || $qte <= 0) continue;

            Stock::withoutGlobalScopes()
                ->where('id', $stockId)
                ->where('shopId', $shopId)
                ->increment('quantite', $qte);
        }
    }

    public function computeTotals(array $pannes, array $pieces, float $montantPaye): array
    {
        $totalPannes = array_sum(array_column($pannes, 'montant'));
        $totalPieces = array_sum(array_map(
            fn($p) => ($p['prixVente'] ?? 0) * ($p['quantiteUtilisee'] ?? 0),
            $pieces
        ));
        $total = $totalPannes + $totalPieces;
        $reste = $total - $montantPaye;

        return [
            'total' => $total,
            'paye' => $montantPaye,
            'reste' => $reste,
            'etat_paiement' => $reste <= 0 ? 'Soldé' : 'Non soldé',
        ];
    }

    public function create(array $validated, ?string $shopId, string $userId): Repair
    {
        if (!$shopId) {
            throw new \RuntimeException('Aucune boutique sélectionnée.');
        }
        return DB::transaction(function () use ($validated, $shopId, $userId) {
            $pannes = $this->buildPannes(
                $validated['panne_description'] ?? [],
                $validated['panne_montant'] ?? []
            );
            $pieces = $this->buildPieces(
                $validated['piece_stock_id'] ?? [],
                $validated['piece_quantite'] ?? [],
                $shopId
            );
            $totals = $this->computeTotals($pannes, $pieces, floatval($validated['montant_paye'] ?? 0));

            return Repair::create([
                'shopId'                   => $shopId,
                'numeroReparation'         => $validated['numeroReparation'] ?? 'REP-' . strtoupper(Str::random(8)),
                'type_reparation'          => $validated['type_reparation'],
                'client_nom'               => $validated['client_nom'],
                'client_telephone'         => $validated['client_telephone'],
                'appareil_marque_modele'   => $validated['appareil_marque_modele'],
                'pannes_services'          => $pannes,
                'pieces_rechange_utilisees'=> $pieces,
                'total_reparation'         => $totals['total'],
                'montant_paye'             => $totals['paye'],
                'reste_a_payer'            => $totals['reste'],
                'statut_reparation'        => $validated['statut_reparation'] ?? 'En cours',
                'date_creation'            => now(),
                'date_mise_en_reparation'  => now(),
                'date_rendez_vous'         => $validated['date_rendez_vous'] ?? null,
                'etat_paiement'            => $totals['etat_paiement'],
                'mode_paiement'            => $validated['mode_paiement'] ?? null,
                'userId'                   => $userId,
            ]);
        });
    }

    public function applyPayment(Repair $repair, float $montantPaye): array
    {
        $reste = $repair->total_reparation - $montantPaye;
        return [
            'montant_paye'  => $montantPaye,
            'reste_a_payer' => $reste,
            'etat_paiement' => $reste <= 0 ? 'Soldé' : 'Non soldé',
        ];
    }

    public function onStatutChange(Repair $repair, string $nouveauStatut, NotificationService $notif): void
    {
        if ($nouveauStatut === 'Prêt pour retrait') {
            $notif->notifierReparationPrete($repair);
        }
    }
}
