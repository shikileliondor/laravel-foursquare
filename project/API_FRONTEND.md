# API v1 — guide court pour l'IA frontend

Construis le front Foursquare Côte d'Ivoire avec cette API Laravel. Base de production : `https://api.studiobeyam.net/api/v1`. En local, remplacer seulement cette base par `http://127.0.0.1:8000/api/v1`. Envoyer `Accept: application/json`. Les routes publiques ci-dessous ne demandent pas de connexion.

## Lire les réponses

- Objet : `{ "success": true, "data": { ... } }` ; liste : `{ "success": true, "data": [ ... ], "meta": { "page": 1, "per_page": 20, "total": 42, "last_page": 3 } }`.
- Utiliser `data`, pas la réponse entière, pour alimenter les composants. Les listes acceptent `page` et `per_page` (20 par défaut, 100 maximum).
- Erreur : `{ "success": false, "error": { "code": "...", "message": "..." } }`. Une erreur `422` ajoute `error.fields` avec les messages de validation par champ.

```js
const base = 'https://api.studiobeyam.net/api/v1';
const response = await fetch(`${base}/home`, { headers: { Accept: 'application/json' } });
const json = await response.json();
if (!response.ok || !json.success) throw new Error(json.error?.message ?? 'Erreur API');
const home = json.data;
```

## Routes publiques

| Écran / besoin | Requête | Données principales |
| --- | --- | --- |
| Accueil | `GET /home` | `banners`, `featured_news`, `featured_event`, `latest_news`, `upcoming_events` dans `data` |
| Actualités | `GET /news` | Liste paginée ; filtres `search`, `scope`, `district_id`, `church_id` |
| Actualité | `GET /news/{slug}` | Détail, notamment `content` |
| Événements | `GET /events` | Liste paginée ; filtres `search`, `status=upcoming` (ou `ongoing`, `past`), `scope`, `district_id`, `church_id` |
| Événement | `GET /events/{slug}` | Détail de l'événement |
| Districts | `GET /districts` | Liste paginée ; chaque district a `id`, `name`, `churches_count` (églises actives) |
| Églises | `GET /churches` | Liste paginée ; filtres `search`, `district_id`, `commune` |
| Église | `GET /churches/{slug}` | Détail, dont `district`, `image`, coordonnées et contacts éventuels |

Pour les détails publics, utiliser le **slug** dans l'URL ; les identifiants des objets et des filtres sont des UUID. Les listes publiques ne montrent que les contenus publiés et les structures actives.

Hiérarchie : districts → églises. Le niveau Zone n'existe plus : `GET /zones`, les filtres `zone_id`, le champ `zone` des églises et `zones_count` des districts ont été retirés. Portées possibles : `NATIONAL`, `DISTRICT`, `CHURCH`.

## Champs utiles au front

- Actualité : `title`, `slug`, `excerpt`, `published_at`, `cover?.url`. `content` est présent sur la route de détail ; on peut aussi demander `?full=1` sur `/news`.
- Événement : `title`, `slug`, `description`, `start_at`, `end_at`, `time_status` (`UPCOMING`, `ONGOING`, `PAST`), `venue_name`, `commune`, `cover?.url`.
- Église : `name`, `slug`, `pastor_name`, `address`, `commune`, `quartier`, `district?.name`, `image?.url`, `latitude`, `longitude`, `secretariat_phone`, `official_whatsapp`.
- Bannière : `title`, `subtitle`, `media?.url`, `button_text`, `link_type`, puis `news_id`, `event_id`, `church_id` ou `external_url` selon le lien. Les liens internes donnent un **ID**, tandis que les routes de détail publiques demandent un **slug** : retrouver le slug dans des données déjà chargées ou prévoir une évolution de l'API.
- Une image peut être absente et `media.url` peut être relatif : le résoudre par rapport à l'origine de l'API. Les dates sont au format ISO 8601.

## Si le front comprend une administration

`POST /auth/login` avec `{ "email": "...", "password": "..." }` renvoie `data.user` et `data.token`. Envoyer ensuite `Authorization: Bearer <token>` à `GET /auth/me`, `POST /auth/logout` et aux routes `/admin/*`. Ne pas utiliser ces routes pour le site public.

Les ressources admin `districts`, `churches`, `news`, `events`, `banners` et `notifications` ont des routes de liste, création, détail, modification (`PATCH`) et suppression. `POST /admin/media` accepte un formulaire multipart avec le champ `file` (JPEG, PNG ou WebP, 5 Mo maximum). `POST /devices` est public et sert à enregistrer un token FCM avec `fcm_token` et `platform` (`android`, `ios` ou `web`).

Afficher les états de chargement, les listes vides et `error.message`. Gérer `401` comme une déconnexion admin, `404` comme un contenu introuvable et `422` avec `error.fields`. Les champs optionnels peuvent être `null` : ne pas inventer de contenu. Pour les autres détails, voir [API.md](API.md).
