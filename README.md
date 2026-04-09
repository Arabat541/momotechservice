# MOMO TECH SERVICE - CRM

Application Laravel de gestion de réparations, SAV et stocks pour ateliers de réparation.

## Fonctionnalités
- Authentification JWT (employé, patron)
- Tableau de bord multi-boutiques avec KPIs
- Gestion des réparations (sur place et RDV)
- Génération et impression de reçus avec code-barres
- Gestion des stocks avec réapprovisionnement CMP
- SAV avec auto-remplissage depuis les réparations
- Gestion des utilisateurs et rôles
- Paramétrage société et garantie

## Prérequis
- PHP >= 8.1
- Composer
- MySQL

## Installation
```bash
composer install
cp .env.example .env
# Configurer la base de données dans .env
php artisan migrate
php artisan serve
```

## Configuration
Variables d'environnement dans `.env` :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=momotech
DB_USERNAME=root
DB_PASSWORD=
JWT_SECRET=un_secret
```

---
© 2025 - MOMO TECH
