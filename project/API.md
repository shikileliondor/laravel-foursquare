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
`/home` renvoie en un appel `banners`, `featured_news`, `featured_news_item`, `featured_event`, `latest_news`, `upcoming_events`.
`featured_news` est une liste d'actualites a la une, triee de la plus recente a la plus ancienne, prevue pour alimenter une section defilante. `featured_news_item` reprend le premier element pour les anciens clients qui attendent une seule actualite.

La recherche églises porte sur le nom, la commune, le quartier, la zone et le district.

`POST /devices` :

```json
{
    "fcm_token": "...",
    "platform": "android|ios|web",
    "app_version": "1.0.0",
    "church_id": "uuid|null"
}
```

Le token n'est jamais renvoyé par l'API.

`church_id` est facultatif mais **conditionne le ciblage** : sans lui, l'appareil ne reçoit que
les notifications d'audience `ALL`. Le serveur en déduit seul `zone_id` et `district_id` ; ne
les envoyez pas. Renvoyer le même `fcm_token` met l'enregistrement à jour (y compris pour
détacher l'appareil en repassant `church_id` à `null`).

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

`POST /admin/notifications/{id}/send` déclenche l'envoi FCM : passe la notification en `PENDING`
et met le job en file. Renvoie `409 NOTIFICATION_NOT_SENDABLE` si elle est déjà `PENDING`,
`PROCESSING` ou `SENT`. Le `PATCH` reste un simple éditeur de champs : il n'envoie jamais rien.

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

Couvre les 9 ressources (actualités, événements, bannières, notifications, médias, districts,
zones, églises, appareils) avec liste paginée, recherche, création, édition et suppression.
Les champs conditionnels suivent les règles du serveur : les cibles de portée n'apparaissent
qu'avec le `scope_type` correspondant, idem pour `link_type` et `audience`. Les erreurs `422`
sont réaffichées champ par champ.

### Images

Tout champ image (`cover_media_id`, `media_id`, `image_media_id`) s'édite via une vignette et une
fenêtre de sélection : grille des médias paginée, import direct (JPG, PNG, WebP, 5 Mo max) et
bouton « Retirer ». La section **Médias** est une galerie avec glisser-déposer, copie d'URL et
suppression. Les listes affichent la vignette de chaque contenu.

Les ressources renvoient l'identifiant du média en plus de l'objet imbriqué (`cover_media_id` à
côté de `cover`), sans quoi le panel ne pourrait pas pré-remplir un champ image à l'édition.

### Textes

Les champs riches (`content` d'une actualité, `description` d'un événement) ont une barre d'outils
qui insère les balises autorisées et un aperçu. Le serveur applique de toute façon
`HtmlSanitizer` à l'enregistrement.

### Notifications

Liste avec statuts colorés, colonne `last_error` et actions directes : « Envoyer » appelle
`POST /admin/notifications/{id}/send`, « Remettre en brouillon » annule une mise en file.

### Champs conditionnels

Quand le panel masque une cible (changement de `link_type`, de `type` ou d'`audience`), il envoie
`null` pour ce champ afin de nettoyer une valeur devenue obsolète. Les règles correspondantes
acceptent donc `null` mais refusent toujours une cible qui contredit le type choisi.

Pour ajouter un champ ou une ressource, il suffit d'étendre l'objet `RESOURCES` en haut du script :
les tableaux et les formulaires en sont entièrement dérivés.

## Notifications push (FCM)

Ce que l'application mobile doit adapter est resume dans
[FRONTEND.md](FRONTEND.md).

Envoi via **FCM HTTP v1**, sans SDK : un JWT signé avec le compte de service est échangé contre
un jeton OAuth (mis en cache 50 min), puis chaque appareil reçoit son propre message — l'API v1
n'a plus d'envoi groupé. Les envois partent par lots parallèles de `FCM_CHUNK` appareils.

```env
FCM_CREDENTIALS=storage/app/firebase/service-account.json
FCM_PROJECT_ID=votre-projet
FCM_CHUNK=50
FCM_TIMEOUT=10
```

Le JSON vient de la console Firebase (Paramètres du projet → Comptes de service → Générer une
clé privée). **Le déposer hors de `public/`.** `FCM_PROJECT_ID` est déduit du JSON s'il est vide.

L'envoi est un job en file (`QUEUE_CONNECTION=database`), il faut donc un worker :

```bash
php artisan queue:work --stop-when-empty   # cron cPanel, toutes les minutes
```

Cycle d'un statut : `DRAFT` → `PENDING` (mise en file) → `PROCESSING` → `SENT` ou `FAILED`.
`FAILED` renseigne `last_error` et incrémente `retry_count` ; l'action « Envoyer » rejoue.

Un token refusé par FCM (`UNREGISTERED`, `INVALID_ARGUMENT`, `SENDER_ID_MISMATCH`) fait
supprimer l'appareil. Si tous les appareils visés sont dans ce cas, la notification finit
quand même en `SENT` : rien n'a échoué côté serveur, rejouer ne changerait rien.

Tant que `FCM_CREDENTIALS` n'est pas renseigné, l'envoi échoue proprement en `FAILED` avec
« FCM non configuré » dans `last_error` — aucune exception non gérée.

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
