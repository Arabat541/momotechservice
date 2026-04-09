# MOMO TECH SERVICE - Guide de Déploiement sur Hostinger

## Prérequis
- Hébergement Hostinger (Premium ou Business) avec PHP 8.2+ et MySQL
- Accès au gestionnaire de fichiers ou FTP
- Accès à phpMyAdmin

---

## Étape 1 : Préparer les fichiers

### Sur votre machine locale :
```bash
cd momotech-app
composer install --optimize-autoloader --no-dev
```

### Copier `.env.example` vers `.env` et modifier :
```bash
cp .env.example .env
php artisan key:generate
```

Puis éditez `.env` :
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://momotechservice.com

DB_HOST=localhost
DB_DATABASE=votre_base_de_donnees
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe

JWT_SECRET=Arabat54a
```

---

## Étape 2 : Créer la base de données

1. Connectez-vous à Hostinger → hPanel → **Bases de données MySQL**
2. Créez une nouvelle base de données et un utilisateur
3. Notez le nom de la base, l'utilisateur et le mot de passe
4. Mettez à jour `.env` avec ces informations

---

## Étape 3 : Uploader les fichiers

### Option A : Via le gestionnaire de fichiers Hostinger
1. Compressez le dossier `momotech-app` en `.zip`
2. Uploadez dans `public_html/`
3. Décompressez

### Option B : Via FTP (FileZilla)
1. Connectez-vous avec vos identifiants FTP Hostinger
2. Uploadez tout le contenu de `momotech-app/` dans `public_html/`

### Structure finale sur Hostinger :
```
public_html/
├── .env
├── .htaccess          ← redirige vers public/
├── artisan
├── composer.json
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── .htaccess      ← routing Laravel
│   └── index.php
├── resources/
├── routes/
├── storage/
└── vendor/
```

---

## Étape 4 : Configuration Hostinger

### Document Root (si possible)
Allez dans **hPanel → Sites Web → Paramètres** et changez le Document Root vers `public_html/public/`. Si ce n'est pas possible, le fichier `.htaccess` à la racine s'en chargera.

### Version PHP
Dans **hPanel → Avancé → Configuration PHP** :
- Sélectionnez PHP **8.2** ou supérieur
- Activez les extensions : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `json`, `bcmath`, `ctype`, `fileinfo`

---

## Étape 5 : Exécuter les migrations

### Via Terminal SSH (si disponible) :
```bash
cd public_html
php artisan migrate --force
php artisan db:seed --force
```

### Si pas de SSH, via navigateur :
Créez temporairement un fichier `public_html/public/setup.php` :

```php
<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('migrate', ['--force' => true]);
echo "Migrations OK<br>";
$kernel->call('db:seed', ['--force' => true]);
echo "Seed OK<br>";
echo "SUPPRIMEZ CE FICHIER MAINTENANT !";
```

**IMPORTANT : Supprimez `setup.php` immédiatement après utilisation !**

---

## Étape 6 : Permissions

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

Via le gestionnaire de fichiers Hostinger, faites un clic droit sur `storage/` et `bootstrap/cache/` → Permissions → 775.

---

## Étape 7 : Vérification

1. Visitez `https://momotechservice.com` → Page vitrine
2. Visitez `https://momotechservice.com/connexion` → Page de connexion
3. Connectez-vous avec `patron@momotech.com` / `password123`
4. **Changez immédiatement le mot de passe du patron !**

---

## Étape 8 : Configurer le domaine

1. Dans Hostinger → **Domaines** → pointez `momotechservice.com`
2. Activez le **SSL gratuit** (Let's Encrypt)
3. Forcez HTTPS dans `.env` :
   ```
   APP_URL=https://momotechservice.com
   ```

---

## Maintenance

### Vider le cache :
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Optimiser pour la production :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Résumé des identifiants par défaut

| Champ          | Valeur                  |
|----------------|-------------------------|
| Email patron   | patron@momotech.com     |
| Mot de passe   | password123             |
| URL admin      | /connexion              |
| URL vitrine    | /                       |
| Suivi réparation | /suivi                |

**Changez le mot de passe après la première connexion !**
