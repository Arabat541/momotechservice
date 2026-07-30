#!/bin/bash
# Reconstruit public/vendor/tailwind/app.css après un changement de classes Tailwind.
# Usage : bash bin/build-css.sh
#
# À lancer après toute modification qui introduit de nouvelles classes Tailwind
# (vues .blade.php ou fichiers PHP contenant des classes littérales, ex: badges
# de statut dans app/Services/RepairService.php). Depuis que Tailwind n'est plus
# chargé en CDN (compilation live dans le navigateur), le CSS est figé au moment
# du build : une classe absente du build ne s'affichera pas.

set -e
cd "$(dirname "$0")/.."

TW_VERSION="v3.4.17"
TW_BIN="/tmp/tailwindcss-cli"

if [ ! -x "$TW_BIN" ]; then
    echo ">> Téléchargement du CLI Tailwind standalone ${TW_VERSION}..."
    curl -sL "https://github.com/tailwindlabs/tailwindcss/releases/download/${TW_VERSION}/tailwindcss-linux-x64" -o "$TW_BIN"
    chmod +x "$TW_BIN"
fi

echo ">> Build public/vendor/tailwind/app.css..."
"$TW_BIN" -c tailwind.config.js -i resources/css/app.css -o public/vendor/tailwind/app.css --minify

echo ">> Terminé. Pense à committer public/vendor/tailwind/app.css."
