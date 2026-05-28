#!/bin/bash
# Script de déploiement pour Hostinger
# Usage: cd ~/domains/momotechservice.com/app_laravel && bash deploy.sh

set -e

echo "=== Déploiement MOMO TECH SERVICE ==="

# ── Phase 1 : pull (bash lit l'ANCIEN script) ──────────────────────────────
if [ "$1" != "--post-pull" ]; then

    # Mode maintenance AVANT le pull
    echo ">> Maintenance ON..."
    php artisan down --retry=60

    # Pull les derniers changements
    echo ">> Git pull..."
    git pull origin main

    # Relancer le script fraîchement mis à jour pour la phase post-pull.
    # 'exec' remplace le processus bash actuel → le NOUVEAU deploy.sh est lu.
    echo ">> Relance avec le script mis à jour..."
    exec bash "$0" --post-pull

fi

# ── Phase 2 : post-pull (bash lit le NOUVEAU script) ──────────────────────

# 1. Installer les dépendances (sans dev)
echo ">> Composer install..."
php composer.phar install --no-dev --optimize-autoloader --no-interaction

# 2. Migrations
echo ">> Migrations..."
php artisan migrate --force

# 3. Vider et reconstruire les caches
echo ">> Cache clear & rebuild..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Copier les assets publics vers public_html
echo ">> Sync public assets..."
cp -f public/.htaccess ../public_html/.htaccess 2>/dev/null || true

# 5. Sync images
echo ">> Sync images..."
mkdir -p ../public_html/images
cp -rf public/images/* ../public_html/images/ 2>/dev/null || true

# 6. Remettre en ligne
echo ">> Maintenance OFF..."
php artisan up

echo "=== Déploiement terminé ! ==="
