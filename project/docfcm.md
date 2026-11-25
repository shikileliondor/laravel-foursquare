# Documentation Codex - Notifications push

Ce document explique simplement comment les notifications push ont ete integrees dans le backend Laravel OrientMoi.

## Objectif

Le backend doit pouvoir :

- enregistrer les tokens push envoyes par l'application mobile ou web ;
- associer un token a un utilisateur connecte ou a un invite ;
- envoyer une notification push a tous les appareils actifs ;
- desactiver automatiquement les tokens invalides retournes par Firebase.

## Package utilise

Les notifications push passent par Firebase Cloud Messaging avec le package Laravel :

```json
"kreait/laravel-firebase": "^7.2"
```

La configuration Firebase est dans :

```txt
config/firebase.php
```

La variable importante cote `.env` est :

```env
FIREBASE_CREDENTIALS=/chemin/vers/firebase-service-account.json
```

Le fichier JSON doit etre le compte de service Firebase Admin SDK du projet Firebase.

## Stockage des tokens

Une table dediee a ete ajoutee :

```txt
database/migrations/2026_06_19_232350_create_push_tokens_table.php
```

Elle cree la table `push_tokens` avec :

- `user_id` : utilisateur lie au token, nullable pour les invites ;
- `guest_key` : identifiant invite fourni par le front ;
- `token` : token FCM unique ;
- `platform` : `android`, `ios` ou `web` ;
- `is_active` : indique si le token peut encore recevoir des notifications ;
- `last_used_at` : date de derniere activite du token.

Le modele Eloquent est :

```txt
app/Models/PushToken.php
```

Il declare les champs remplissables, les casts Laravel, et la relation :

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

Le modele `User` expose aussi la relation inverse :

```php
public function pushTokens(): HasMany
{
    return $this->hasMany(PushToken::class);
}
```

## Enregistrement d'un token push

La route publique est declaree dans :

```txt
routes/api.php
```

Endpoint :

```http
POST /api/push-tokens
```

Payload attendu :

```json
{
    "token": "FCM_TOKEN_ICI",
    "platform": "android",
    "guest_key": "guest-device-id-optionnel"
}
```

Validation :

```txt
app/Http/Requests/StorePushTokenRequest.php
```

Regles principales :

- `token` obligatoire, string, max 500 ;
- `platform` optionnel, limite a `android`, `ios`, `web` ;
- `guest_key` optionnel, string, max 255.

Controleur :

```txt
app/Http/Controllers/Api/PushTokenController.php
```

Le controleur recupere les donnees validees, detecte l'utilisateur connecte si present avec `$request->user()?->id`, puis appelle le service :

```txt
app/Services/PushTokenService.php
```

Logique du service :

- si le token existe deja, il est mis a jour ;
- si l'utilisateur est connecte, le token est rattache a `user_id` et `guest_key` est remis a `null` ;
- si l'utilisateur est invite, le token garde ou recoit `guest_key` ;
- `is_active` repasse a `true` ;
- `last_used_at` est mis a jour.

Reponse API :

```json
{
    "message": "Jeton push enregistre.",
    "data": {
        "id": 1,
        "user_id": null,
        "guest_key": "guest-device-id-optionnel",
        "token": "FCM_TOKEN_ICI",
        "platform": "android",
        "is_active": true
    }
}
```

## Envoi des notifications

Le service central d'envoi est :

```txt
app/Services/FcmService.php
```

Il recoit l'instance Firebase `Messaging` via injection de dependance :

```php
public function __construct(private readonly Messaging $messaging) {}
```

### Envoyer a tous les tokens actifs

Methode :

```php
public function broadcast(string $title, string $body, array $data = []): array
```

Elle :

- recupere tous les tokens actifs dans `push_tokens` ;
- ignore les lignes sans token ;
- envoie les notifications par lots de 500 tokens ;
- retourne le nombre de succes et d'echecs.

Exemple :

```php
$result = $fcm->broadcast(
    'Nouvelle alerte',
    'Une nouvelle information est disponible.',
    ['link' => '/alerts/1']
);
```

### Envoyer a un utilisateur precis

Methode :

```php
public function sendToUser(int $userId, string $title, string $body, array $data = []): array
```

Elle recupere tous les tokens actifs de l'utilisateur puis utilise la meme logique d'envoi.

Exemple :

```php
$result = $fcm->sendToUser(
    $user->id,
    'Recommandation disponible',
    'Ton resultat d orientation est pret.',
    ['link' => '/recommendations']
);
```

## Nettoyage des tokens invalides

Apres chaque envoi, Firebase retourne un rapport.

Dans `FcmService`, les tokens invalides ou inconnus sont recuperes avec :

```php
$report->invalidTokens();
$report->unknownTokens();
```

Ces tokens sont ensuite desactives :

```php
PushToken::query()
    ->whereIn('token', $dead)
    ->update(['is_active' => false]);
```

On ne supprime donc pas les tokens directement. On les garde en base, mais `is_active = false` empeche les prochains envois.

## Routes admin

Les routes admin sont dans :

```txt
routes/web.php
```

Elles sont protegees par :

```php
['auth', AdminMiddleware::class]
```

Endpoints :

```http
POST /api/admin/notifications
GET /api/admin/notifications/stats
```

Controleur :

```txt
app/Http/Controllers/Api/Admin/AdminNotificationController.php
```

### Envoyer une notification globale

Payload :

```json
{
    "title": "Titre",
    "body": "Message de la notification",
    "link": "/alerts/1"
}
```

Le champ `link` est optionnel. S'il est fourni, il est ajoute dans les donnees FCM :

```php
$extra = $data['link'] ? ['link' => $data['link']] : [];
```

### Voir les statistiques

`GET /api/admin/notifications/stats` retourne :

- `total` : tokens actifs ;
- `linked` : tokens actifs lies a un utilisateur ;
- `guest` : tokens actifs invites.

## Flux complet

1. Le front obtient un token FCM depuis Firebase.
2. Le front appelle `POST /api/push-tokens`.
3. Laravel valide le payload avec `StorePushTokenRequest`.
4. `PushTokenService` cree ou met a jour la ligne `push_tokens`.
5. Un admin appelle `POST /api/admin/notifications`.
6. `AdminNotificationController` appelle `FcmService::broadcast()`.
7. `FcmService` envoie via Firebase par lots de 500.
8. Les tokens morts sont marques `is_active = false`.

## Fichiers principaux a connaitre

```txt
config/firebase.php
database/migrations/2026_06_19_232350_create_push_tokens_table.php
app/Models/PushToken.php
app/Models/User.php
app/Http/Requests/StorePushTokenRequest.php
app/Http/Controllers/Api/PushTokenController.php
app/Http/Controllers/Api/Admin/AdminNotificationController.php
app/Services/PushTokenService.php
app/Services/FcmService.php
routes/api.php
routes/web.php
```

## Points d'attention pour un agent Codex

- Ne pas envoyer directement depuis un controleur : passer par `FcmService`.
- Ne pas dupliquer la logique d'enregistrement : passer par `PushTokenService`.
- Garder `token` unique en base.
- Garder l'envoi par lots de 500 tokens, car FCM limite les envois multicast.
- Ne pas supprimer les tokens invalides sans raison : les desactiver avec `is_active = false`.
- Ne jamais mettre le JSON Firebase dans Git. Utiliser `FIREBASE_CREDENTIALS` dans `.env`.
- Si une notification doit cibler un utilisateur precis, utiliser `sendToUser()`.
- Si une notification doit cibler tout le monde, utiliser `broadcast()`.
