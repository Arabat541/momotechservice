# CRM - Gestion de Réparations et Stocks

Ce projet est une application complète de gestion de réparations (atelier, SAV) avec gestion des stocks, utilisateurs, tickets, et paramètres. Il comprend un backend Node.js/Express/MongoDB (TypeScript) et un frontend React (Vite, Tailwind CSS).

## Fonctionnalités principales
- Authentification JWT (employé, patron)
- Gestion des réparations sur place et sur rendez-vous
- Génération et impression de tickets/reçus
- Gestion des stocks de pièces détachées
- Gestion des utilisateurs et des rôles
- Paramétrage société et garantie
- Interface moderne (React, Tailwind)

## Structure du projet
```
CRM/
├── backend/         # API Node.js/Express/TypeScript
│   ├── src/         # Code source (contrôleurs, modèles, routes)
│   └── build/       # Code compilé
├── frontend/        # Application React (Vite, Tailwind)
│   ├── src/         # Pages, composants, hooks, lib
│   └── public/      # Assets statiques
└── README.md        # Ce fichier
```

## Prérequis
- Node.js >= 18
- MongoDB (local ou distant)

## Installation

### Backend
```bash
cd backend
npm install
npx tsc   # Compile TypeScript
node build/app.js  # Démarre le serveur (ou npx nodemon build/app.js)
```
Par défaut, l'API écoute sur `http://localhost:5000/api`.

### Frontend
```bash
cd frontend
npm install
npm run dev
```
L'application sera accessible sur `http://localhost:5173`.

## Configuration
- Les variables d'environnement sont à définir dans `.env` (backend et frontend).
- Exemple pour le backend :
  ```env
  MONGODB_URI=mongodb://localhost:27017/crm
  JWT_SECRET=un_secret
  ```
- Exemple pour le frontend :
  ```env
  VITE_API_URL=http://localhost:5000/api
  ```

## Scripts utiles
- `npm run dev` (frontend) : démarre le serveur de développement React
- `npx tsc` (backend) : compile le code TypeScript
- `node build/app.js` : lance l’API

## Développement
- Le code source du backend est dans `backend/src/` (TypeScript)
- Le code source du frontend est dans `frontend/src/` (React)
- Les tickets et reçus sont générés côté frontend et peuvent être imprimés

## Aide & Support
Pour toute question ou bug, ouvrez une issue ou contactez le développeur.

---
© 2025 - MOMO TECH
