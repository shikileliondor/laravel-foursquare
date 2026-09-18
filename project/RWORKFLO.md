0. Mission

Construire un backend Laravel minimaliste, API only, pour l’application mobile Foursquare Côte d’Ivoire.

Le backend doit être :

simple ;

propre ;

sécurisé ;

facile à déployer sur cPanel ;

basé sur PostgreSQL ;

sans frontend Laravel ;

sans Blade ;

sans Inertia ;

sans Filament ;

sans microservices ;

sans architecture inutilement complexe.

Le but est de fournir uniquement des API REST JSON consommées plus tard par :

l’application Flutter ;

un éventuel panel React.

1. Fonctionnalités V1

Le backend doit gérer uniquement :

Authentification administrateur

Districts

Zones

Églises

Actualités

Événements

Bannières

Médias / uploads

Devices FCM

Notifications

Endpoint Home

Audit minimal

Ne pas développer dans cette version :

comptes membres ;

profils membres ;

paiements ;

dons ;

dîmes ;

inscriptions événements ;

QR Code ;

formations ;

quiz ;

progression ;

chat ;

messagerie ;

gestion financière.

2. Stack

Utiliser :

Laravel
PostgreSQL
Laravel Sanctum
API Resources
Form Requests
Policies / Middleware
Laravel Notifications si utile
Firebase Cloud Messaging plus tard
PHPUnit / Pest pour tests

Prendre la dernière version stable de Laravel compatible avec la version PHP du serveur.

3. Structure organisationnelle

La structure métier est strictement :

National
↓
District
↓
Zone
↓
Église locale

Règles :

une zone appartient à un district ;

une église appartient à une zone ;

une église ne stocke pas directement district_id ;

le district est obtenu via zone -> district ;

les structures sont désactivées plutôt que supprimées brutalement.

4. Tables principales

Créer les migrations suivantes :

users
districts
zones
churches
media
news
events
banners
devices
notifications
audit_logs
personal_access_tokens

personal_access_tokens est fourni par Sanctum.

5. Users / Admin

Utiliser users uniquement pour les administrateurs en V1.

Champs :

id UUID
name
email UNIQUE
password
role
status
last_login_at
timestamps

Rôles préparés :

SUPER_ADMIN
NATIONAL_ADMIN
DISTRICT_ADMIN
ZONE_ADMIN
CHURCH_ADMIN

En V1, seul SUPER_ADMIN est réellement utilisé.

Statuts :

ACTIVE
SUSPENDED

6. Districts

Champs :

id UUID
name
slug UNIQUE
code nullable
description nullable
status
timestamps

Statuts :

ACTIVE
INACTIVE

7. Zones

Champs :

id UUID
district_id FK
name
slug UNIQUE
code nullable
description nullable
status
timestamps

Relation :

District hasMany Zones
Zone belongsTo District

8. Churches

Champs :

id UUID
zone_id FK

name
slug UNIQUE
code nullable

pastor_name

address nullable
commune nullable
quartier nullable

secretariat_phone nullable
official_whatsapp nullable
official_email nullable

latitude nullable
longitude nullable

main_service_day nullable
main_service_time nullable

description nullable
image_media_id nullable

status
timestamps

Règles :

afficher le nom du pasteur ;

ne jamais publier automatiquement son numéro personnel ;

les numéros sont ceux du secrétariat / église.

9. News

Champs :

id UUID

title
slug UNIQUE
excerpt nullable
content
cover_media_id nullable

scope_type

district_id nullable
zone_id nullable
church_id nullable

priority
is_featured boolean

status
published_at nullable

created_by FK users

timestamps

scope_type :

NATIONAL
DISTRICT
ZONE
CHURCH

priority :

NORMAL
IMPORTANT
URGENT

status :

DRAFT
PUBLISHED
ARCHIVED

Règles :

NATIONAL -> aucun target ID
DISTRICT -> district_id uniquement
ZONE -> zone_id uniquement
CHURCH -> church_id uniquement

La validation doit être faite côté Laravel.

10. Events

Champs :

id UUID

title
slug UNIQUE
description
cover_media_id nullable

scope_type

district_id nullable
zone_id nullable
church_id nullable

organizer_name nullable

start_at
end_at

venue_name nullable
address nullable
commune nullable

latitude nullable
longitude nullable

contact_phone nullable
official_whatsapp nullable

priority
is_featured boolean

status

created_by FK users

timestamps

Statuts :

DRAFT
PUBLISHED
CANCELLED
ARCHIVED

Ne pas stocker :

UPCOMING
ONGOING
PAST

Les calculer dynamiquement depuis start_at / end_at.

Validation obligatoire :

end_at >= start_at

11. Banners

Champs :

id UUID
title
subtitle nullable
media_id
button_text nullable

link_type

news_id nullable
event_id nullable
church_id nullable
external_url nullable

display_order default 0
is_active default true

starts_at nullable
ends_at nullable

created_by FK users

timestamps

link_type :

NONE
NEWS
EVENT
CHURCH
EXTERNAL

12. Media

Table :

id UUID
original_name
stored_name
path
mime_type
file_size
width nullable
height nullable
uploaded_by nullable
timestamps

Pour la V1 :

stockage local Laravel ;

fichiers dans storage/app/public ;

php artisan storage:link ;

JPEG / PNG / WebP uniquement ;

max 5 Mo ;

noms générés par serveur ;

ne jamais utiliser directement le nom brut du client comme nom final.

Prévoir une abstraction simple pour migrer plus tard vers S3/Cloudinary si nécessaire.

13. Devices

Champs :

id UUID
fcm_token UNIQUE
platform
app_version nullable
notifications_enabled boolean default true
last_seen_at nullable
timestamps

Aucune identité membre en V1.

14. Notifications

Champs :

id UUID
title
body

type

news_id nullable
event_id nullable

audience

district_id nullable
zone_id nullable
church_id nullable

status

retry_count default 0
last_error nullable
sent_at nullable

created_by FK users

timestamps

Types :

GENERAL
NEWS
EVENT

Audience V1 utilisée :

ALL

Préparer aussi :

DISTRICT
ZONE
CHURCH

Statuts :

DRAFT
PENDING
PROCESSING
SENT
FAILED

15. Audit logs

Table minimale :

id UUID
user_id nullable
action
entity_type nullable
entity_id nullable
ip_address nullable
user_agent nullable
metadata jsonb nullable
created_at

Ne jamais logger :

password ;

tokens complets ;

secrets ;

clés Firebase ;

DB password.

16. API publique

Préfixe :

/api/v1

Routes :

GET /api/v1/home

GET /api/v1/news
GET /api/v1/news/{slug}

GET /api/v1/events
GET /api/v1/events/{slug}

GET /api/v1/districts
GET /api/v1/zones
GET /api/v1/zones?district_id={uuid}

GET /api/v1/churches
GET /api/v1/churches/{slug}

POST /api/v1/devices

17. API Admin

Préfixe :

/api/v1/admin

Routes CRUD protégées pour :

districts
zones
churches
news
events
banners
media
notifications

Exemples :

POST /api/v1/admin/news
PATCH /api/v1/admin/news/{id}
DELETE /api/v1/admin/news/{id}

18. Auth API

Utiliser Laravel Sanctum.

Routes :

POST /api/v1/auth/login
POST /api/v1/auth/logout
GET /api/v1/auth/me

Login :

{
"email": "...",
"password": "..."
}

Réponse :

{
"success": true,
"data": {
"user": {},
"token": "..."
}
}

Pour la V1, un token Sanctum simple suffit.

Ne pas mettre de logique d’auth membre.

19. Format standard des réponses

Succès simple :

{
"success": true,
"data": {}
}

Liste paginée :

{
"success": true,
"data": [],
"meta": {
"page": 1,
"per_page": 20,
"total": 100,
"last_page": 5
}
}

Erreur :

{
"success": false,
"error": {
"code": "NEWS_NOT_FOUND",
"message": "Actualité introuvable"
}
}

Créer un système simple pour uniformiser les erreurs JSON.

20. Validation

Utiliser des FormRequest.

Exemples :

StoreNewsRequest
UpdateNewsRequest

StoreEventRequest
UpdateEventRequest

StoreChurchRequest
UpdateChurchRequest

Ne jamais faire confiance au frontend.

Toutes les règles métier importantes doivent être revalidées côté Laravel.

21. Sécurité obligatoire

Implémenter :

validation stricte ;

Eloquent / Query Builder paramétré ;

aucune concaténation SQL dangereuse ;

rate limiting ;

auth Sanctum ;

Policies / middleware pour routes admin ;

CORS contrôlé ;

HTTPS en production ;

upload sécurisé ;

validation MIME ;

protection mass assignment via $fillable ou $guarded correctement définis ;

aucun secret dans Flutter / React ;

erreurs de production sans stack trace ;

APP_DEBUG=false en production ;

variables sensibles uniquement dans .env.

22. Protection XSS

Le backend ne doit pas accepter du HTML dangereux.

Pour news.content :

si texte simple : stocker texte ;

si HTML riche : nettoyer avec une whitelist stricte ;

interdire scripts, iframe arbitraires, javascript:, object, embed.

Ne pas faire confiance au rendu frontend.

23. Recherche et filtres

Churches :

GET /api/v1/churches?search=yopougon
GET /api/v1/churches?district_id=...
GET /api/v1/churches?zone_id=...

Recherche sur :

name
commune
quartier
zone
district

News :

GET /api/v1/news?search=convention
GET /api/v1/news?scope=NATIONAL

Events :

GET /api/v1/events?status=upcoming
GET /api/v1/events?search=jeunesse

24. Pagination

Par défaut :

20 éléments

Limiter le maximum à :

100

25. Endpoint Home

Créer un HomeController.

GET /api/v1/home

Réponse :

{
"success": true,
"data": {
"banners": [],
"featured_news": null,
"featured_event": null,
"latest_news": [],
"upcoming_events": []
}
}

Un seul appel pour l’écran d’accueil Flutter.

26. Architecture Laravel

Rester minimaliste.

Structure recommandée :

app/
├── Http/
│ ├── Controllers/
│ │ ├── Api/V1/
│ │ └── Api/V1/Admin/
│ ├── Requests/
│ └── Resources/
│
├── Models/
├── Policies/
├── Services/
└── Support/

Ne pas créer :

Domain layer complexe ;

Repository pattern partout ;

CQRS ;

Event sourcing ;

microservices.

Créer un Service uniquement lorsqu’une logique dépasse réellement un CRUD simple.

27. Models

Chaque model doit avoir :

casts ;

relations ;

fillable/guarded ;

scopes utiles ;

UUID si retenu ;

enums si utiles.

Exemple relations :

District hasMany Zone
Zone belongsTo District
Zone hasMany Church
Church belongsTo Zone

28. API Resources

Toujours utiliser Laravel API Resources pour les réponses métier publiques importantes.

Exemples :

NewsResource
EventResource
ChurchResource
DistrictResource
ZoneResource
BannerResource

Ne pas retourner directement les modèles Eloquent partout.

29. Routes

Utiliser :

routes/api.php

Versionner :

Route::prefix('v1')->group(...)

Séparer clairement :

public
auth
admin

30. Rate limiting

Prévoir des limites distinctes :

public API
auth
uploads
admin mutations

La route login doit être plus stricte.

31. Config production

.env production :

APP_ENV=production
APP_DEBUG=false

APP_URL=https://api.studiobeyam.net

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

LOG_CHANNEL=stack
LOG_LEVEL=warning

Ne jamais commiter .env.

Créer .env.example.

32. Déploiement cPanel

Le projet Laravel doit être déployé de manière standard :

/home/.../foursquare-api

Le document root du sous-domaine doit pointer vers :

/home/.../foursquare-api/public

Procédure :

composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan storage:link

Permissions :

storage/
bootstrap/cache/

doivent être inscriptibles par PHP.

33. PostgreSQL production

Créer :

base dédiée ;

utilisateur dédié ;

mot de passe fort.

Ne jamais utiliser un compte PostgreSQL superuser pour l’application.

34. Tests minimums

Créer des Feature Tests pour :

auth login
district list
zone filter
church search
news list
news detail
event list
event detail
admin protected routes
news create validation
event date validation
scope validation

35. Ordre d’implémentation

L’agent doit développer dans cet ordre :

1. Initialisation Laravel API
2. PostgreSQL config
3. Sanctum
4. User/Admin auth
5. District
6. Zone
7. Church
8. Media
9. News
10. Events
11. Banners
12. Home
13. Devices
14. Notifications
15. Audit
16. Tests
17. Production config
18. Deployment notes

Ne pas sauter directement aux notifications ou au frontend.

36. Commandes de départ

Créer le projet :

composer create-project laravel/laravel foursquare-api
cd foursquare-api

Installer Sanctum si nécessaire selon la version Laravel :

php artisan install:api

Configurer PostgreSQL dans .env.

Puis :

php artisan migrate
php artisan serve

37. Génération des composants

Exemples :

php artisan make:model District -m
php artisan make:model Zone -m
php artisan make:model Church -m
php artisan make:model Media -m
php artisan make:model News -m
php artisan make:model Event -m
php artisan make:model Banner -m
php artisan make:model Device -m
php artisan make:model Notification -m
php artisan make:model AuditLog -m

Controllers API :

php artisan make:controller Api/V1/NewsController --api
php artisan make:controller Api/V1/EventController --api
php artisan make:controller Api/V1/ChurchController --api

Admin :

php artisan make:controller Api/V1/Admin/NewsController --api
php artisan make:controller Api/V1/Admin/EventController --api

Requests :

php artisan make:request StoreNewsRequest
php artisan make:request UpdateNewsRequest
php artisan make:request StoreEventRequest
php artisan make:request UpdateEventRequest

Resources :

php artisan make:resource NewsResource
php artisan make:resource EventResource
php artisan make:resource ChurchResource

38. Definition of Done

Une fonctionnalité est considérée terminée si :

migration présente ;

model présent ;

relations présentes ;

validation FormRequest présente ;

controller API propre ;

API Resource présent si utile ;

auth/policy présente si nécessaire ;

tests passent ;

erreurs JSON propres ;

pagination présente pour les listes ;

aucune donnée sensible exposée ;

endpoint documenté dans le README ou OpenAPI si ajouté.

39. Règle absolue pour l’agent

Ne pas surarchitecturer.

Le projet doit rester :

Laravel API simple
Sanctum
Controllers
Form Requests
API Resources
Models
Policies
Services seulement si nécessaires

Le backend doit être minimal dans sa structure, mais strict dans ses validations et sa sécurité.

40. Objectif final V1

À la fin, ces endpoints doivent fonctionner correctement :

/api/v1/home
/api/v1/news
/api/v1/events
/api/v1/districts
/api/v1/zones
/api/v1/churches

/api/v1/auth/login
/api/v1/auth/logout
/api/v1/auth/me

/api/v1/admin/*

Et le backend doit être prêt à être consommé par Flutter sans contenir de logique frontend.
