<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout du workflow étendu à 9 statuts pour le module Réparations.
 *
 * Nouveaux statuts supportés (colonne VARCHAR existante, aucune rupture) :
 *   - En attente de paiement  (dépôt reçu, acompte non payé)
 *   - En attente de pièces    (diagnostic fait, pièces à commander)
 *   - Prêt pour retrait       (réparation terminée, client à prévenir)
 *   - Irréparable             (diagnostic négatif)
 *
 * Statuts conservés : En attente · En cours · Terminé · Livré · Annulé
 *
 * La migration ajoute deux colonnes de traçabilité pour les nouveaux états.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            // Date à laquelle la réparation est passée en "Prêt pour retrait"
            $table->timestamp('date_pret_retrait')->nullable()->after('date_terminee');
            // Date à laquelle la réparation a été déclarée "Irréparable"
            $table->timestamp('date_irreparable')->nullable()->after('date_pret_retrait');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(['date_pret_retrait', 'date_irreparable']);
        });
    }
};
