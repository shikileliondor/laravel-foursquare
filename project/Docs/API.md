# Foursquare Côte d'Ivoire — API v1

Guide court à transmettre à l'IA frontend : [API_FRONTEND.md](API_FRONTEND.md).

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

| Méthode | Endpoint                  | Filtres                                                                   |
| ------- | ------------------------- | ------------------------------------------------------------------------- |
| GET     | `/home`                   | —                                                                         |
| GET     | `/news`                   | `search`, `scope`, `district_id`, `church_id`, `per_page`                 |
| GET     | `/news/{slug}`            | —                                                                         |
| GET     | `/events`                 | `search`, `status` (`upcoming` / `ongoing` / `past`), `scope`, `per_page` |
| GET     | `/events/{slug}`          | —                                                                         |
| GET     | `/districts`              | `per_page`                                                                |
| GET     | `/churches`               | `search`, `district_id`, `commune`, `per_page`                            |
| GET     | `/churches/{slug}`        | —                                                                         |
| GET     | `/notifications`          | `for_church_id`, `per_page` — historique des notifications envoyées       |
| POST    | `/events/{slug}/register` | inscription sans compte (voir plus bas)                                   |
| POST    | `/devices`                | enregistrement FCM                                                        |

**Église de référence** : `for_church_id` (accepté par `/home`, `/news`, `/events`, `/notifications`)
renvoie le national, le district de l'église et l'église elle-même. Sans église choisie, `/home`
ne montre que le national. Filtres de l'application : Toutes = `for_church_id`, National =
`scope=NATIONAL`, District = `scope=DISTRICT`, Mon église = `scope=CHURCH` (combinés à
`for_church_id`). `/news` et `/events` acceptent aussi `category_id`.

Chaque actualité et événement porte `source` (`{ "scope": "CHURCH", "label": "Église …" }`) pour
distinguer National / District / Église locale, et `category`. Les routes de détail acceptent le
slug **ou** l'identifiant (pratique pour les bannières et notifications). `/home` renvoie aussi
`church` (l'église de référence) et `launch_church` (église proposée au premier lancement, réglée
dans les paramètres du back-office).

**Inscriptions** : `POST /events/{slug}/register` avec `full_name`, `phone`, `email?`,
`church_id?` ou `church_name?`. Possible si l'événement est publié, non terminé et
`registration_enabled` (champ `registration_open` sur l'événement). Un numéro par événement
(`409 ALREADY_REGISTERED`), `409 REGISTRATION_CLOSED` sinon. Limité à 5 requêtes/minute et
30/heure par IP ; le champ `website` doit rester vide (piège à robots). Les participants ne sont
jamais exposés par l'API publique.

Seul le contenu publié est exposé : `status = PUBLISHED` pour news/events, `status = ACTIVE` pour la structure.
Une église n'apparaît que si elle **et** son district sont actifs. `/districts` renvoie `churches_count`,
le nombre d'églises actives du district.

Hiérarchie : Foursquare Côte d'Ivoire → districts → églises. Chaque église porte `district_id` et,
sur les routes églises, l'objet `district`. Le niveau Zone n'existe plus (route `/zones` et filtres
`zone_id` retirés).
`/home` renvoie en un appel `banners`, `featured_news`, `featured_news_item`, `featured_event`, `latest_news`, `upcoming_events`.
`featured_news` est une liste d'actualites a la une, triee de la plus recente a la plus ancienne, prevue pour alimenter une section defilante. `featured_news_item` reprend le premier element pour les anciens clients qui attendent une seule actualite.

La recherche églises porte sur le nom, la commune, le quartier et le district.

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
les notifications d'audience `ALL`. Le serveur en déduit seul `district_id` ; ne l'envoyez pas.
Renvoyer le même `fcm_token` met l'enregistrement à jour : sans `church_id` dans la requête,
l'appareil garde son église ; `church_id: null` le détache.

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

`districts`, `churches`, `news`, `events`, `news-categories`, `event-categories`, `banners`,
`notifications`, `users` (administrateurs)

Plus : `GET /admin/dashboard` (statistiques du périmètre), `GET /admin/registrations`
(`event_id`, `search`), `GET /admin/registrations/export` (CSV), `DELETE /admin/registrations/{id}`,
`GET|PUT /admin/settings`.

### Rôles et périmètres

Rôles fixes, vérifiés côté serveur par des Policies et des Gates (une interface masquée ne suffit pas) :

| Rôle             | Périmètre                                                                                        |
| ---------------- | ------------------------------------------------------------------------------------------------ |
| `SUPER_ADMIN`    | tout, y compris les paramètres et les autres super administrateurs                               |
| `NATIONAL_ADMIN` | tout le contenu, districts, églises, bannières, catégories, appareils, admins de district/église |
| `DISTRICT_ADMIN` | son district (`district_id`) : ses églises (sans les supprimer), contenus et notifications       |
| `CHURCH_ADMIN`   | son église (`church_id`) : sa fiche (sans changer district ni statut), contenus, inscriptions    |

Les listes admin sont filtrées au périmètre ; une action hors périmètre renvoie `403`. Personne
ne modifie son propre rôle ni ne supprime son compte ; suspendre un compte coupe ses sessions.
`GET /auth/me` renvoie `permissions`, la liste des modules du panel ouverts au compte.

Il n'y a pas d'inscription publique. Premier compte (et tout compte en ligne de commande) :
`php artisan foursquare:create-admin` (mot de passe demandé, 12 caractères, majuscules,
minuscules et chiffres). Ensuite, depuis le module Administrateurs du panel.

`media` : `GET /admin/media`, `POST /admin/media` (champ `file`), `GET|DELETE /admin/media/{id}`.
`devices` : lecture, mise à jour et suppression uniquement.

`POST /admin/notifications/{id}/send` déclenche l'envoi FCM : passe la notification en `PENDING`
et met le job en file. Renvoie `409 NOTIFICATION_NOT_SENDABLE` si elle est déjà `PENDING`,
`PROCESSING` ou `SENT`. Le `PATCH` reste un simple éditeur de champs : il n'envoie jamais rien.

Les identifiants admin sont des UUID (sauf `users`, resté en entier).

### Règles métier vérifiées côté serveur

- **Portée** : `NATIONAL` sans cible ; `DISTRICT`/`CHURCH` exigent leur seul identifiant correspondant.
- **Districts** : `DELETE` renvoie `409 DISTRICT_HAS_CHURCHES` tant que le district contient des églises.
  Le désactiver (`status = INACTIVE`) les retire de l'API publique sans rien supprimer.
- **Églises** : `district_id` obligatoire. Changer le district d'une église y rattache aussi ses appareils.
- **Notifications** : une audience `DISTRICT` ou `CHURCH` dont la cible n'existe plus n'atteint personne
  et passe en `FAILED` avec son motif.
- **Événements** : `end_at >= start_at`. `UPCOMING`/`ONGOING`/`PAST` ne sont jamais stockés, ils sont calculés.
- **Bannières** : `link_type` détermine le champ de lien autorisé ; les autres sont interdits.
- **News** : le HTML est nettoyé par liste blanche (`p`, `br`, `strong`, `em`, `u`, `ul`, `ol`, `li`, `h2`-`h4`, `blockquote`, `a`). Scripts, iframes, `on*=` et `javascript:` sont supprimés.
- **Slugs** : générés depuis le titre/nom s'ils ne sont pas fournis, unicité garantie.
- **Uploads** : JPEG / PNG / WebP, 5 Mo max, nom de fichier généré par le serveur.

### Limites de débit

| Groupe                      | Limite                                                 |
| --------------------------- | ------------------------------------------------------ |
| API publique                | 300 req/min par IP, hors réponses servies par le cache |
| Images (`/media/{id}/file`) | aucune limite                                          |
| Login                       | 5 req/min par IP et par email                          |
| Inscriptions aux événements | 5 req/min et 30 req/h par IP                           |
| Mutations admin             | 120 req/min par admin                                  |
| Uploads                     | 20 req/min par admin                                   |

Les lectures publiques (`GET`, sauf les recherches `search`) sont mises en cache 60 secondes, et le cache est vidé dès qu'un contenu change dans le panel. L'en-tête `X-Cache` vaut `HIT` ou `MISS`. Les images sont renvoyées avec `Cache-Control: immutable` : côté app, un cache disque (par exemple `cached_network_image` en Flutter) évite de les retélécharger.

## Panel d'administration

Disponible sur `/admin/` ([public/admin/index.html](public/admin/index.html)) : une page autonome,
sans build ni dépendance, qui consomme l'API v1 documentée ci-dessus.

Connexion via `POST /auth/login`, token conservé dans le `localStorage`, envoyé en `Bearer` sur
chaque appel. Un `401` renvoie automatiquement à l'écran de connexion.

Couvre les 8 ressources (actualités, événements, bannières, notifications, médias, districts,
églises, appareils) avec liste paginée, recherche, création, édition et suppression.
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

La colonne **Livraison** donne le bilan réel de l'envoi : `12 livrées`, plus le cas échéant
`2 tokens morts` et `1 échec`. Elle est indispensable parce qu'un `SENT` seul ne dit pas si un
téléphone a reçu quoi que ce soit — un envoi dont tous les tokens étaient périmés est un `SENT`
à zéro livraison.

### Champs conditionnels

Quand le panel masque une cible (changement de `link_type`, de `type` ou d'`audience`), il envoie
`null` pour ce champ afin de nettoyer une valeur devenue obsolète. Les règles correspondantes
acceptent donc `null` mais refusent toujours une cible qui contredit le type choisi.

Pour ajouter un champ ou une ressource, il suffit d'étendre l'objet `RESOURCES` en haut du script :
les tableaux et les formulaires en sont entièrement dérivés.

## Confidentialité

| Méthode | Route                           | Usage                                 |
| ------- | ------------------------------- | ------------------------------------- |
| `POST`  | `/privacy/consents`             | Enregistrer un consentement explicite |
| `POST`  | `/privacy/consents/withdrawals` | Enregistrer son retrait               |
| `POST`  | `/privacy/data-requests`        | Exercer un droit relatif aux données  |

Ces routes sont publiques, limitées en débit et protégées par un champ anti-robots `website`, qui
doit rester absent. Les événements de consentement sont immuables et idempotents grâce à
`action_id`. L'identifiant pseudonyme `subject_id` est généré et conservé par l'application ; il
n'est jamais renvoyé dans la réponse. Les finalités, exemples de corps JSON et règles d'intégration
sont détaillés dans [API_FRONTEND.md](API_FRONTEND.md).

Côté administration (admins nationaux uniquement, rubrique « Confidentialité » du panel) :

| Méthode | Route                          | Usage                                                  |
| ------- | ------------------------------ | ------------------------------------------------------ |
| `GET`   | `/admin/privacy-requests`      | Lister les demandes (`search`, `status`)               |
| `GET`   | `/admin/privacy-requests/{id}` | Détail d'une demande                                   |
| `PATCH` | `/admin/privacy-requests/{id}` | `status` et `resolution_note` ; horodate la clôture    |
| `GET`   | `/admin/privacy-consents`      | Journal des consentements, en lecture seule (`search`) |
| `GET`   | `/admin/privacy-consents/{id}` | Détail d'un événement de consentement                  |

Aucune suppression n'est exposée : les preuves de consentement et le suivi des demandes sont
conservés. Passer une demande à `COMPLETED` ou `REJECTED` enregistre `completed_at` et
l'administrateur qui l'a traitée ; chaque modification est tracée dans le journal d'audit.

## Notifications push (FCM)

Ce que l'application mobile doit adapter est resume dans
[FRONTEND.md](FRONTEND.md).

Envoi via **FCM HTTP v1**, sans SDK : un JWT signé avec le compte de service est échangé contre
un jeton OAuth (mis en cache 50 min), puis chaque appareil reçoit son propre message — l'API v1
n'a plus d'envoi groupé. Les envois partent par lots parallèles de `FCM_CHUNK` appareils.

```env
FCM_CREDENTIALS=storage/app/firebase
FCM_PROJECT_ID=votre-projet
FCM_CHUNK=50
FCM_TIMEOUT=10
```

Le JSON vient de la console Firebase (Paramètres du projet → Comptes de service → Générer une
clé privée). **Le déposer hors de `public/`.** `FCM_CREDENTIALS` peut pointer vers ce fichier JSON
ou vers son dossier, par exemple `storage/app/firebase`. `FCM_PROJECT_ID` est déduit du JSON s'il est vide.

L'envoi est un job en file (`QUEUE_CONNECTION=database`), il faut donc un worker :

```bash
php artisan queue:work --stop-when-empty   # cron cPanel, toutes les minutes
```

Cycle d'un statut : `DRAFT` → `PENDING` (mise en file) → `PROCESSING` → `SENT` ou `FAILED`.
`FAILED` renseigne `last_error` et incrémente `retry_count` ; l'action « Envoyer » rejoue.

Chaque envoi renseigne aussi son bilan, remis à zéro à chaque nouvelle tentative :

| Champ             | Sens                                                    |
| ----------------- | ------------------------------------------------------- |
| `delivered_count` | appareils que FCM a acceptés                            |
| `pruned_count`    | tokens périmés, donc appareils supprimés                |
| `failed_count`    | refus FCM qui ne justifient pas de supprimer l'appareil |

**`SENT` ne signifie pas « reçu ».** Il signifie que FCM a accepté l'envoi. Lire
`delivered_count` pour savoir si un téléphone a été joint.

Un token refusé par FCM (`UNREGISTERED`, `INVALID_ARGUMENT`, `SENDER_ID_MISMATCH`) fait
supprimer l'appareil. Si tous les appareils visés sont dans ce cas, la notification finit
quand même en `SENT` : rien n'a échoué côté serveur, rejouer ne changerait rien.

Tant que `FCM_CREDENTIALS` n'est pas renseigné, l'envoi échoue proprement en `FAILED` avec
« FCM non configuré » dans `last_error` — aucune exception non gérée.

### Vérifier la chaîne sans passer par le panel

```bash
php artisan fcm:test --token=<token-du-telephone>   # un seul appareil, même non enregistré
php artisan fcm:test                                # les appareils enregistrés, après confirmation
```

La commande court-circuite la base et la file pour ne répondre qu'à une question : « le téléphone
reçoit-il ? » Elle n'enregistre aucune notification dans l'historique et ne supprime aucun token,
contrairement à un envoi réel. Elle affiche le projet Firebase utilisé et, par token, `livrée`,
`token mort` ou `échec` avec le message de Google. Code de sortie non nul si rien n'est livré, ce
qui la rend utilisable en vérification de déploiement.

Le token du téléphone s'obtient dans la console de `flutter run` : l'application le journalise au
démarrage (en debug uniquement), avec l'URL d'API qu'elle utilise.

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

Suivre [DEPLOYMENT.md](DEPLOYMENT.md) pour le ZIP envoyé par FTPS, l'extraction manuelle dans `public_html/project`, la configuration `.env` MySQL et les migrations.

## Stockage des médias

`config/media.php` centralise le disque (`MEDIA_DISK`, `public` par défaut) et le dossier.
Passer à S3 ou Cloudinary revient à changer le disque : `MediaService` est le seul point d'écriture.

## Tests

```bash
php artisan test
```

Couvre l'authentification, la structure (districts/églises), les news, les événements,
les devices, la pagination, la validation des portées et des dates, l'accès admin et les erreurs JSON.
