# API v1 — guide court pour l'IA frontend

Construis le front Foursquare Côte d'Ivoire avec cette API Laravel. Base de production : `https://api.studiobeyam.net/api/v1`. En local ([DOCKER.md](DOCKER.md)), remplacer seulement cette base par `http://127.0.0.1:8000/api/v1`. Sur un téléphone Android branché en USB, lancer d'abord `adb reverse tcp:8000 tcp:8000` ; en Wi-Fi, utiliser `http://<IP du PC>:8000/api/v1`. Envoyer `Accept: application/json`. Les routes publiques ci-dessous ne demandent pas de connexion.

## Lire les réponses

- Objet : `{ "success": true, "data": { ... } }` ; liste : `{ "success": true, "data": [ ... ], "meta": { "page": 1, "per_page": 20, "total": 42, "last_page": 3 } }`.
- Utiliser `data`, pas la réponse entière, pour alimenter les composants. Les listes acceptent `page` et `per_page` (20 par défaut, 100 maximum).
- Erreur : `{ "success": false, "error": { "code": "...", "message": "..." } }`. Une erreur `422` ajoute `error.fields` avec les messages de validation par champ.

```js
const base = 'https://api.studiobeyam.net/api/v1';
const response = await fetch(`${base}/home`, {
    headers: { Accept: 'application/json' },
});
const json = await response.json();
if (!response.ok || !json.success)
    throw new Error(json.error?.message ?? 'Erreur API');
const home = json.data;
```

## Routes publiques

| Écran / besoin | Requête                 | Données principales                                                                                             |
| -------------- | ----------------------- | --------------------------------------------------------------------------------------------------------------- |
| Accueil        | `GET /home`             | `daily_verse`, `social_links`, `donation_url`, `banners`, `featured_news`, `featured_event`, `latest_news`, `upcoming_events`   |
| Versets        | `GET /verses`           | Historique paginé des versets publiés, du plus récent au plus ancien                                            |
| Verset du jour | `GET /verses/today`     | Verset national du jour ou `data: null`                                                                         |
| Méditations    | `GET /meditations`      | Historique paginé (extraits), du plus récent au plus ancien                                                     |
| Méditation     | `GET /meditations/{id}` | Texte complet (`content` HTML) ; `GET /meditations/today` pour celle du jour ou `data: null`                    |
| Découvrir      | `GET /discover`         | Rubriques institutionnelles publiées, dans leur ordre d’affichage                                               |
| Rubrique       | `GET /discover/{key}`   | Texte, images et responsables publiés pour `national-office`                                                    |
| Actualités     | `GET /news`             | Liste paginée ; filtres `search`, `scope`, `district_id`, `church_id`                                           |
| Actualité      | `GET /news/{slug}`      | Détail, notamment `content`                                                                                     |
| Événements     | `GET /events`           | Liste paginée ; filtres `search`, `status=upcoming` (ou `ongoing`, `past`), `scope`, `district_id`, `church_id` |
| Événement      | `GET /events/{slug}`    | Détail de l'événement                                                                                           |
| Districts      | `GET /districts`        | Liste paginée ; chaque district a `id`, `name`, `churches_count` (églises actives)                              |
| Églises        | `GET /churches`         | Liste paginée ; filtres `search`, `district_id`, `commune`                                                      |
| Église         | `GET /churches/{slug}`  | Détail, dont `district`, `image`, coordonnées et contacts éventuels                                             |

Pour les détails publics, utiliser le **slug** dans l'URL ; les identifiants des objets et des filtres sont des UUID. Les listes publiques ne montrent que les contenus publiés et les structures actives.

**Église de référence (sans compte)** : stocker l'`id` de l'église choisie et l'envoyer en `for_church_id` sur `/home`, `/news`, `/events` et `/notifications` : on reçoit le national, le district et l'église. Sans église : `/home` ne montre que le national. Au premier lancement, proposer `data.launch_church` de `/home`. Quand l'église change, renvoyer aussi `POST /devices` avec le nouveau `church_id` (notifications).

Filtres Actus/Événements : Toutes = `for_church_id`, National = `scope=NATIONAL`, District = `scope=DISTRICT`, Mon église = `scope=CHURCH`. Chaque élément porte `source.label` (« Foursquare Côte d'Ivoire », nom du district ou de l'église) et `category?.name`.

Historique des notifications : `GET /notifications?for_church_id=…` ; ouvrir `news_slug` ou `event_slug`. Les détails acceptent aussi l'identifiant (`/news/{id}`).

Inscription à un événement si `registration_open` est vrai : `POST /events/{slug}/register` avec `full_name`, `phone`, `email?`, `church_id?`. Réponses : `201`, `409 ALREADY_REGISTERED`, `409 REGISTRATION_CLOSED`, `422` (champs invalides).

Hiérarchie : districts → églises. Le niveau Zone n'existe plus : `GET /zones`, les filtres `zone_id`, le champ `zone` des églises et `zones_count` des districts ont été retirés. Portées possibles : `NATIONAL`, `DISTRICT`, `CHURCH`.

## Champs utiles au front

- Actualité : `title`, `slug`, `excerpt`, `published_at`, `cover?.url`. `content` est présent sur la route de détail ; on peut aussi demander `?full=1` sur `/news`.
- Événement : `title`, `slug`, `description`, `start_at`, `end_at`, `time_status` (`UPCOMING`, `ONGOING`, `PAST`), `venue_name`, `commune`, `cover?.url`.
- Église : `name`, `slug`, `pastor_name`, `address`, `commune`, `quartier`, `district?.name`, `image?.url`, `latitude`, `longitude`, `secretariat_phone`, `official_whatsapp`.
- Bannière : `title`, `subtitle`, `media?.url`, `button_text`, `link_type`, puis `news_id`, `event_id`, `church_id` ou `external_url` selon le lien. Les liens internes donnent un **ID**, tandis que les routes de détail publiques demandent un **slug** : retrouver le slug dans des données déjà chargées ou prévoir une évolution de l'API.
- Une image peut être absente et `media.url` peut être relatif : le résoudre par rapport à l'origine de l'API. Les dates sont au format ISO 8601.

## Si le front comprend une administration

`POST /auth/login` avec `{ "email": "...", "password": "..." }` renvoie `data.user` et `data.token`. Envoyer ensuite `Authorization: Bearer <token>` à `GET /auth/me`, `POST /auth/logout` et aux routes `/admin/*`. Ne pas utiliser ces routes pour le site public.

Les ressources admin `districts`, `churches`, `news`, `events`, `banners`, `notifications`, `verses` et `national-leaders` ont des routes de liste, création, détail, modification (`PATCH`) et suppression. `discover-sections` expose uniquement liste, détail et modification, car ses six rubriques sont fixes. `POST /admin/media` accepte un formulaire multipart avec le champ `file` (JPEG, PNG ou WebP, 5 Mo maximum). `POST /devices` est public et sert à enregistrer un token FCM avec `fcm_token` et `platform` (`android`, `ios` ou `web`).

Le champ `daily_verse` de `/home` est national et ne dépend jamais de `for_church_id`. Une notification de type `DAILY_VERSE` contient `daily_verse_id`, son titre est `Verset du jour · {reference}` et son corps le texte du verset ; ouvrir cette notification mène à l’historique des versets. Une notification `DAILY_MEDITATION` contient `daily_meditation_id` : l’ouvrir mène au détail `GET /meditations/{id}`. `/home` contient aussi `daily_meditation` (sans `content`).

Afficher les états de chargement, les listes vides et `error.message`. Gérer `401` comme une déconnexion admin, `404` comme un contenu introuvable et `422` avec `error.fields`. Les champs optionnels peuvent être `null` : ne pas inventer de contenu. Pour les autres détails, voir [API.md](API.md).

## Confidentialité et consentements

L’application génère une fois un UUID aléatoire `subject_id`, le conserve dans son stockage sécurisé et l’utilise uniquement pour les événements de consentement. Chaque action utilise aussi un nouvel UUID `action_id` afin qu’une nouvelle tentative réseau ne crée pas de doublon.

Accorder un consentement :

```http
POST /privacy/consents
```

```json
{
    "action_id": "UUID unique de cette action",
    "subject_id": "UUID stable de cette installation",
    "purpose": "PUSH_NOTIFICATIONS",
    "policy_version": "2026-11-26",
    "platform": "android",
    "church_id": null,
    "event_id": null
}
```

Retirer un consentement avec un **nouvel** `action_id` :

```http
POST /privacy/consents/withdrawals
```

Les finalités acceptées sont `CHURCH_PERSONALIZATION`, `PUSH_NOTIFICATIONS` et `EVENT_CHURCH_ASSOCIATION`. Les plateformes sont `android`, `ios` et `web`. `church_id` et `event_id` sont facultatifs et servent à conserver le contexte exact du consentement. Un même `action_id` rejoué renvoie le premier enregistrement sans créer de doublon.

Demande d’accès, rectification, suppression, opposition ou portabilité :

```http
POST /privacy/data-requests
```

```json
{
    "type": "DELETION",
    "full_name": "Jean Kouassi",
    "phone": "+2250708091011",
    "email": null,
    "event_id": null,
    "details": "Inscription concernée et informations utiles.",
    "privacy_notice_version": "2026-11-26"
}
```

`phone` ou `email` est obligatoire. La réponse `201` contient une référence `DCP-...` à afficher et à faire conserver à l’utilisateur. L’API ne permet volontairement pas de relire publiquement la demande ni les données personnelles associées.
