# Application de livraison

Ce dépôt contient le socle de départ de l'application de livraison mobile.

Stack proposée :
- Backend : Laravel
- Base de données : MySQL
- Mobile : Flutter
- Authentification : Laravel Sanctum
- Notifications : Firebase Cloud Messaging

## Structure du projet
- app/ : modèles, contrôleurs, middleware
- config/ : configuration Laravel
- database/migrations/ : migrations SQL
- routes/ : endpoints API
- public/ : point d'entrée HTTP

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## API principale

- POST /api/auth/register
- POST /api/auth/login
- GET /api/auth/profile
- GET /api/produits
- POST /api/commandes
- GET /api/commandes
- PUT /api/livraisons/{id}/assign
- PUT /api/livraisons/{id}/statut
- POST /api/paiements

## Rôles
- admin
- gestionnaire
- client
- livreur

## Prochaine étape

1. installer les dépendances Laravel ;
2. créer le fichier .env ;
3. exécuter les migrations ;
4. commencer le développement mobile Flutter.
