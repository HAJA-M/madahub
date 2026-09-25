# MadaHub

Mini ERP pour PME malgaches — Laravel 12 + Livewire/Volt + Tailwind + MySQL.

## Modules (Phase 1 — MVP)

- **Auth** — connexion Breeze/Livewire
- **Dashboard** — chiffre d'affaires, bénéfice estimé, produits populaires, évolution mensuelle, alertes stock
- **Clients** — CRUD, recherche
- **Produits** — CRUD, prix d'achat/vente, seuil d'alerte
- **Ventes** — devis → commande → facture, avec conversion et validation (décrémente le stock)
- **Stock** — niveaux, alertes, historique des mouvements, ajustements manuels

## Développement local

Prérequis : PHP 8.2+, Composer, MySQL, Node 18+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# configurer DB_* dans .env, puis :
php artisan migrate --seed
composer run dev
```

`composer run dev` lance en parallèle le serveur PHP (port 8000), le worker de queue et Vite.

Compte de démo créé par le seeder : `admin@madahub.mg` / `password`.

## Déploiement (Railway)

Le dépôt contient un `Dockerfile` prêt pour la production (Apache + PHP-FPM, build des assets Vite en étape séparée, migrations automatiques au démarrage).

1. Sur [railway.app](https://railway.app), **New Project → Deploy from GitHub repo**, sélectionner ce dépôt (Railway détecte le `Dockerfile` automatiquement).
2. Ajouter un service **MySQL** dans le même projet (Railway génère les variables `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`).
3. Dans les variables du service web, définir :
   - `APP_NAME=MadaHub`
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_KEY` — générer en local avec `php artisan key:generate --show` et coller la valeur (`base64:...`)
   - `APP_URL` — l'URL fournie par Railway (ex. `https://madahub-production.up.railway.app`)
   - `DB_CONNECTION=mysql`
   - `DB_HOST=${{MySQL.MYSQLHOST}}`
   - `DB_PORT=${{MySQL.MYSQLPORT}}`
   - `DB_DATABASE=${{MySQL.MYSQLDATABASE}}`
   - `DB_USERNAME=${{MySQL.MYSQLUSER}}`
   - `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`
   - `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`
4. Déployer. L'`entrypoint.sh` du conteneur exécute automatiquement `php artisan migrate --force` et met en cache la config au démarrage.
5. (Optionnel) Pour peupler des données de démonstration en production, exécuter une fois depuis le terminal Railway : `php artisan db:seed`.

## Prochaines phases

Voir la feuille de route convenue : API REST + app mobile (Phase 2), notifications/PDF/paiements (Phase 3), fonctionnalités IA (Phase 4).
