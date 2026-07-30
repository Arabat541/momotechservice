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

# 2. Sauvegarde DB avant migration
echo ">> Sauvegarde base de données..."
mkdir -p ~/backups
DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | tail -1)
DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | tail -1)
DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null | tail -1)
if [ -n "$DB_NAME" ]; then
    BACKUP_FILE=~/backups/db-$(date +%F-%H%M%S).sql.gz
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_FILE"
    # Ne garder que les 14 dernières sauvegardes
    ls -t ~/backups/db-*.sql.gz 2>/dev/null | tail -n +15 | xargs -r rm --
    echo "   Sauvegarde OK : $BACKUP_FILE"
else
    echo "   ATTENTION : impossible de lire la config DB, sauvegarde ignorée."
fi

# 3. Migrations
echo ">> Migrations..."
php artisan migrate --force

# 4. Vider et reconstruire les caches
echo ">> Cache clear & rebuild..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Copier les assets publics vers public_html
echo ">> Sync public assets..."
cp -f public/.htaccess ../public_html/.htaccess 2>/dev/null || true

# 6. Sync images
echo ">> Sync images..."
mkdir -p ../public_html/images
cp -rf public/images/* ../public_html/images/ 2>/dev/null || true

# 7. Sync assets front auto-hébergés (Tailwind, FontAwesome, Alpine, JsBarcode, QRCode.js)
echo ">> Sync vendor assets..."
mkdir -p ../public_html/vendor
cp -rf public/vendor/* ../public_html/vendor/ 2>/dev/null || true

# 8. Remettre en ligne
echo ">> Maintenance OFF..."
php artisan up

echo "=== Déploiement terminé ! ==="
