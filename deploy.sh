#!/bin/bash
# Script de déploiement pour Hostinger
# Usage: cd ~/domains/momotechservice.com/app_laravel && bash deploy.sh

set -e

echo "=== Déploiement MOMO TECH SERVICE ==="

# 1. Pull les derniers changements
echo ">> Git pull..."
git pull origin main

# 2. Installer les dépendances (sans dev)
echo ">> Composer install..."
php composer.phar install --no-dev --optimize-autoloader --no-interaction

# 3. Vider et reconstruire les caches
echo ">> Cache clear & rebuild..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Copier les assets publics vers public_html (si modifiés)
echo ">> Sync public assets..."
cp -f public/.htaccess ../public_html/.htaccess 2>/dev/null || true
# Ne pas écraser index.php de public_html (version Hostinger)

echo "=== Déploiement terminé ! ==="
