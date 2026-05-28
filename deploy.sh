#!/bin/bash
# Script de déploiement pour Hostinger
# Usage: cd ~/domains/momotechservice.com/app_laravel && bash deploy.sh

set -e

echo "=== Déploiement MOMO TECH SERVICE ==="

# 0. Mode maintenance (évite les erreurs visibles pendant la mise à jour)
echo ">> Maintenance ON..."
php artisan down --retry=60

# 1. Pull les derniers changements
echo ">> Git pull..."
git pull origin main

# 2. Installer les dépendances (sans dev)
echo ">> Composer install..."
php composer.phar install --no-dev --optimize-autoloader --no-interaction

# 3. Migrations (--force obligatoire en production)
echo ">> Migrations..."
php artisan migrate --force

# 4. Vider et reconstruire les caches
echo ">> Cache clear & rebuild..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Copier les assets publics vers public_html (si modifiés)
echo ">> Sync public assets..."
cp -f public/.htaccess ../public_html/.htaccess 2>/dev/null || true
# Ne pas écraser index.php de public_html (version Hostinger)

# 6. Sync images et autres assets
echo ">> Sync images..."
mkdir -p ../public_html/images
cp -rf public/images/* ../public_html/images/ 2>/dev/null || true

# 7. Remettre en ligne
echo ">> Maintenance OFF..."
php artisan up

echo "=== Déploiement terminé ! ==="
