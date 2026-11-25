# Migration API — notifications push

_API v1 · 19 septembre 2026 · pour l'équipe application_

Les notifications push sont désormais envoyées par le serveur via **FCM HTTP v1**. Deux choses
changent pour l'application : la façon dont un appareil s'enregistre, et ce qu'il reçoit. Le reste
n'est qu'ajout de champs.

**Aucune rupture** : aucun champ existant n'a changé de nom ni de type. Mais une action est
requise, sans quoi le ciblage des notifications ne fonctionnera pas.

---

## En une minute

- **Envoyez `church_id` à l'enregistrement.** Sans lui, l'appareil ne reçoit que les notifications
  adressées à tout le monde — celles de district, de zone ou d'église ne lui parviendront jamais.
- **Ré-enregistrez l'appareil à chaque rafraîchissement du token.** Le serveur supprime désormais
  tout appareil dont FCM refuse le token.
- **Lisez `data.type` pour router le tap.** Le message transporte l'identifiant de l'actualité ou
  de l'événement concerné.

---

## 1. Enregistrement de l'appareil

```
POST /api/v1/devices
```

Endpoint public, non authentifié, limité à 60 requêtes/minute par IP. Renvoyer le même
`fcm_token` met l'enregistrement à jour.

```json
{
    "fcm_token": "dK3f...",
    "platform": "android",
    "app_version": "1.0.0",
    "church_id": "01a0b904-d822-73dd-9aa6-4ff1ed2c260f",
    "notifications_enabled": true
}
```

| Champ                   | Requis | Détail                                        |
| ----------------------- | ------ | --------------------------------------------- |
| `fcm_token`             | oui    | 255 caractères max, sert de clé d'unicité     |
| `platform`              | oui    | `android`, `ios` ou `web`                     |
| `app_version`           | non    | 30 caractères max                             |
| `church_id`             | non    | UUID d'une église existante — voir ci-dessous |
| `notifications_enabled` | non    | `true` par défaut                             |

### ⚠️ Sans `church_id`, le ciblage ne marche pas

C'est le seul rattachement que l'application transmet. Le serveur en déduit seul la zone et le
district — **ne les envoyez pas**, ils seront ignorés.

Dès que l'utilisateur choisit ou change son église, renvoyez `POST /devices` avec le même token.
Pour détacher un appareil, repassez `church_id` à `null`.

| Audience de la notification | Atteint l'appareil si…                       |
| --------------------------- | -------------------------------------------- |
| `ALL`                       | toujours — aucun rattachement nécessaire     |
| `DISTRICT`                  | l'église déclarée appartient à ce district   |
| `ZONE`                      | l'église déclarée appartient à cette zone    |
| `CHURCH`                    | l'église déclarée est exactement celle visée |

Dans tous les cas, un appareil dont `notifications_enabled` vaut `false` est ignoré.

### Réponse

`201` à la création, `200` à la mise à jour. Le `fcm_token` n'est jamais renvoyé.

```json
{
    "success": true,
    "data": {
        "id": "01a0b913-4ee4-72c2-9b8d-c15863df7f59",
        "platform": "android",
        "app_version": "1.0.0",
        "notifications_enabled": true,
        "district_id": "01a0b904-d7f6-73a1-bff6-740a3ee42e42",
        "zone_id": "01a0b904-d818-7127-8588-60837428a0dd",
        "church_id": "01a0b904-d822-73dd-9aa6-4ff1ed2c260f",
        "last_seen_at": "2026-09-19T09:50:45+00:00"
    }
}
```

`district_id` et `zone_id` sont remplis par le serveur à partir de `church_id`.

> **Corrigé** — à la création, la réponse renvoyait `notifications_enabled: null` alors que la
> valeur stockée était `true`. Si votre code traitait ce `null` comme « désactivé », il verra
> maintenant `true`.

---

## 2. Le message reçu sur le téléphone

Envoyé via FCM HTTP v1, un message par appareil. Priorité haute sur Android, son par défaut sur
les deux plateformes.

```json
{
    "notification": {
        "title": "Culte de rentrée",
        "body": "Rendez-vous dimanche à 9h."
    },
    "data": {
        "notification_id": "01a0b914-0a4f-714e-9b06-518122c10493",
        "type": "NEWS",
        "news_id": "01a0b904-d887-7124-9464-aed00de33cf8"
    },
    "android": { "priority": "high", "notification": { "sound": "default" } },
    "apns": { "payload": { "aps": { "sound": "default" } } }
}
```

| Clé de `data`     | Présence          | Usage                                                     |
| ----------------- | ----------------- | --------------------------------------------------------- |
| `notification_id` | toujours          | déduplication, accusé de lecture local                    |
| `type`            | toujours          | `GENERAL`, `NEWS` ou `EVENT` — décide de l'écran à ouvrir |
| `news_id`         | si `type = NEWS`  | ouvrir l'actualité correspondante                         |
| `event_id`        | si `type = EVENT` | ouvrir l'événement correspondant                          |

### Les clés vides sont absentes, pas nulles

Une notification `GENERAL` n'a ni `news_id` ni `event_id` dans `data`. **Testez la présence de la
clé, pas sa valeur.** Comme l'impose FCM, toutes les valeurs de `data` sont des chaînes.

### ⚠️ Un token refusé fait supprimer l'appareil

Quand FCM répond `UNREGISTERED`, `INVALID_ARGUMENT` ou `SENDER_ID_MISMATCH`, le serveur efface
l'enregistrement pour ne pas traîner de tokens morts.

Branchez `onTokenRefresh` sur un nouvel appel à `POST /devices`, sinon un appareil disparaît
définitivement des envois après une réinstallation ou une purge de token.

---

## 3. Champs ajoutés aux ressources

Tous additifs. Les identifiants de média étaient jusqu'ici absents : seul l'objet imbriqué était
exposé, ce qui empêchait de pré-remplir un champ image.

| Ressource            | Champs ajoutés                                  | Pourquoi                                             |
| -------------------- | ----------------------------------------------- | ---------------------------------------------------- |
| Actualité, Événement | `cover_media_id`                                | identifiant de l'image, à côté de l'objet `cover`    |
| Église               | `image_media_id`                                | idem, à côté de `image`                              |
| Bannière             | `media_id`, `is_active`, `starts_at`, `ends_at` | la fenêtre de visibilité n'était pas exposée du tout |
| Appareil             | `district_id`, `zone_id`, `church_id`           | rattachement, déduit par le serveur                  |
| District, Zone       | `zones_count`, `churches_count`                 | déclarés mais jamais renseignés jusqu'ici            |
| Notification         | `last_error`                                    | motif d'un envoi en échec                            |

---

## 4. Côté administration

Sans effet sur l'application mobile. À lire uniquement si vous consommez les endpoints `/admin`.

```
POST /api/v1/admin/notifications/{id}/send
```

Déclenche l'envoi : passe la notification en `PENDING` et met le job en file. Renvoie
`409 NOTIFICATION_NOT_SENDABLE` si elle est déjà `PENDING`, `PROCESSING` ou `SENT`.

Le `PATCH` reste un simple éditeur de champs : il n'envoie plus rien par effet de bord.

Cycle : `DRAFT` → `PENDING` → `PROCESSING` → `SENT` ou `FAILED`. En cas d'échec, `last_error`
porte le motif et `retry_count` s'incrémente.

### Un `null` explicite sur un champ énuméré est refusé

`status`, `priority`, `scope_type`, `link_type`, `audience` : ces colonnes ont une valeur par
défaut en base et n'acceptent pas `null`. Envoyer `null` renvoyait auparavant une erreur `500` ;
c'est maintenant un `422` propre. **Omettez la clé** pour laisser la base décider.

---

## 5. Checklist d'intégration

1. **Ajouter `church_id` à l'appel d'enregistrement** — et le renvoyer chaque fois que
   l'utilisateur change d'église.
2. **Brancher `onTokenRefresh` sur `POST /devices`** — sinon l'appareil sort définitivement des
   envois après une purge de token.
3. **Router le tap sur `data.type`** — vérifier la présence de `news_id` / `event_id`, pas leur
   valeur.
4. **Dédupliquer sur `notification_id`** — un message peut arriver deux fois si un envoi est
   rejoué après échec.
5. **Relire les réponses contenant `notifications_enabled`** — le champ renvoie désormais sa vraie
   valeur au lieu de `null`.

---

Détail complet des endpoints et de la configuration FCM : [API.md](API.md).
L'envoi devient opérationnel dès que `FCM_CREDENTIALS` est renseigné côté serveur.
