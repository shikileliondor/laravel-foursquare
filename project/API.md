# Foursquare Côte d'Ivoire — API v1

Backend Laravel, JSON only. Consommé par l'application Flutter et, plus tard, un panel React.
Base : `https://api.studiobeyam.net/api/v1`

## Format des réponses

Succès :

```json
{ "success": true, "data": {} }
```

Liste paginée (20 par défaut, 100 max via `?per_page=`) :

```json
{
    "success": true,
    "data": [],
    "meta": { "page": 1, "per_page": 20, "total": 100, "last_page": 5 }
}
```

Erreur :

```json
{
    "success": false,
    "error": { "code": "NEWS_NOT_FOUND", "message": "Actualité introuvable" }
}
```

Les erreurs de validation renvoient `422` avec `error.code = VALIDATION_ERROR` et le détail par champ dans `error.fields`.

## Endpoints publics

| Méthode | Endpoint           | Filtres                                                                   |
| ------- | ------------------ | ------------------------------------------------------------------------- |
| GET     | `/home`            | —                                                                         |
| GET     | `/news`            | `search`, `scope`, `district_id`, `zone_id`, `church_id`, `per_page`      |
| GET     | `/news/{slug}`     | —                                                                         |
| GET     | `/events`          | `search`, `status` (`upcoming` / `ongoing` / `past`), `scope`, `per_page` |
| GET     | `/events/{slug}`   | —                                                                         |
| GET     | `/districts`       | `per_page`                                                                |
| GET     | `/zones`           | `district_id`, `per_page`                                                 |
| GET     | `/churches`        | `search`, `district_id`, `zone_id`, `commune`, `per_page`                 |
| GET     | `/churches/{slug}` | —                                                                         |
| POST    | `/devices`         | enregistrement FCM                                                        |

Seul le contenu publié est exposé : `status = PUBLISHED` pour news/events, `status = ACTIVE` pour la structure.
`/home` renvoie en un appel `banners`, `featured_news`, `featured_event`, `latest_news`, `upcoming_events`.

La recherche églises porte sur le nom, la commune, le quartier, la zone et le district.

`POST /devices` :

```json
{ "fcm_token": "...", "platform": "android|ios|web", "app_version": "1.0.0" }
```

Le token n'est jamais renvoyé par l'API.

## Authentification

Sanctum, tokens porteurs. `POST /auth/login` est limité à 5 tentatives/minute.

```
POST /auth/login    { "email": "...", "password": "..." }  →  { "user": {}, "token": "..." }
POST /auth/logout   (Bearer)
GET  /auth/me       (Bearer)
```

Envoyer ensuite `Authorization: Bearer <token>`.

## Endpoints admin

Préfixe `/admin`, protégés par `auth:sanctum` + rôle admin + compte `ACTIVE`.
CRUD complet (`GET`, `POST`, `GET /{id}`, `PATCH /{id}`, `DELETE /{id}`) pour :

`districts`, `zones`, `churches`, `news`, `events`, `banners`, `notifications`

`media` : `GET /admin/media`, `POST /admin/media` (champ `file`), `GET|DELETE /admin/media/{id}`.
`devices` : lecture, mise à jour et suppression uniquement.

Les identifiants admin sont des UUID (sauf `users`, resté en entier).

### Règles métier vérifiées côté serveur

- **Portée** : `NATIONAL` sans cible ; `DISTRICT`/`ZONE`/`CHURCH` exigent leur seul identifiant correspondant.
- **Événements** : `end_at >= start_at`. `UPCOMING`/`ONGOING`/`PAST` ne sont jamais stockés, ils sont calculés.
- **Bannières** : `link_type` détermine le champ de lien autorisé ; les autres sont interdits.
- **News** : le HTML est nettoyé par liste blanche (`p`, `br`, `strong`, `em`, `u`, `ul`, `ol`, `li`, `h2`-`h4`, `blockquote`, `a`). Scripts, iframes, `on*=` et `javascript:` sont supprimés.
- **Slugs** : générés depuis le titre/nom s'ils ne sont pas fournis, unicité garantie.
- **Uploads** : JPEG / PNG / WebP, 5 Mo max, nom de fichier généré par le serveur.

### Limites de débit

| Groupe          | Limite                        |
| --------------- | ----------------------------- |
| API publique    | 60 req/min par IP             |
| Login           | 5 req/min par IP et par email |
| Mutations admin | 120 req/min par admin         |
| Uploads         | 20 req/min par admin          |

## Panel d'administration

Disponible sur `/admin/` ([public/admin/index.html](public/admin/index.html)) : une page autonome,
sans build ni dépendance, qui consomme uniquement l'API publique documentée ci-dessus.

Connexion via `POST /auth/login`, token conservé dans le `localStorage`, envoyé en `Bearer` sur
chaque appel. Un `401` renvoie automatiquement à l'écran de connexion.

Couvre les 9 ressources (districts, zones, églises, actualités, événements, bannières,
notifications, médias, appareils) avec liste paginée, recherche, création, édition et suppression.
Les champs conditionnels suivent les règles du serveur : les cibles de portée n'apparaissent
qu'avec le `scope_type` correspondant, idem pour `link_type` et `audience`. Les erreurs `422`
sont réaffichées champ par champ.

Pour ajouter un champ ou une ressource, il suffit d'étendre l'objet `RESOURCES` en haut du script :
les tableaux et les formulaires en sont entièrement dérivés.

## Installation locale

```bash
composer install
php artisan migrate
php artisan db:seed --class=FoursquareSeeder
php artisan storage:link
php artisan serve
```

Le seeder crée un `SUPER_ADMIN` à partir de `ADMIN_EMAIL` / `ADMIN_PASSWORD` (`config/foursquare.php`)
plus un jeu de données de démonstration.

## Déploiement cPanel

Racine du sous-domaine → `/home/<user>/foursquare-api/public`.

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

`storage/` et `bootstrap/cache/` doivent être inscriptibles par PHP.

`.env` de production :

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.studiobeyam.net
DB_CONNECTION=pgsql
LOG_LEVEL=warning
```

Base PostgreSQL et utilisateur dédiés, jamais un superuser. `.env` n'est jamais commité.

## Stockage des médias

`config/media.php` centralise le disque (`MEDIA_DISK`, `public` par défaut) et le dossier.
Passer à S3 ou Cloudinary revient à changer le disque : `MediaService` est le seul point d'écriture.

## Tests

```bash
php artisan test
```

Couvre l'authentification, la structure (districts/zones/églises), les news, les événements,
les devices, la pagination, la validation des portées et des dates, l'accès admin et les erreurs JSON.
