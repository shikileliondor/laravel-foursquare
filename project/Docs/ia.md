# Passation IA — Foursquare Côte d'Ivoire (backend Laravel et panel)

Ce document est destiné à une IA (ou un développeur) qui reprend le projet. Il résume le contexte, les règles, l'architecture, ce qui a été fait, l'état exact du travail en cours et les pièges déjà rencontrés. Il ne contient aucun secret : les mots de passe et clés ne sont jamais écrits dans le dépôt.

## 1. Contexte

- Projet : application nationale « Foursquare Côte d'Ivoire » (V1). Plateforme d'information, de communication, d'événements et d'annuaire des églises. Pas de comptes pour les fidèles.
- Le cahier des charges complet est dans `Docs/prompt.md`. Le relire avant toute modification importante.
- L'utilisateur parle français, écrit des messages courts et veut de la rapidité, mais il faut lui expliquer clairement ce qui se passe et ne pas enchaîner des actions qu'il n'a pas demandées.

### Dépôts

- Backend + panel : `C:\xampp\htdocs\four\laravel-foursquare` (racine git), application Laravel dans `project/`. Remote : `https://github.com/shikileliondor/laravel-foursquare.git` (HTTPS via Git Credential Manager, les clés SSH n'ont pas accès). Branches : `main` (déployée) et `v1-backend-nationale` (travail V1, déjà fusionnée dans `main`).
- App mobile Flutter : `C:\Users\Lenovo\project\Frontfoursq-1\mon_app` (racine git `Frontfoursq-1`, remote `https://github.com/shikileliondor/Frontfoursq.git`, branche `main`).
- Ne jamais commiter `project.zip` (archive de 116 Mo à la racine du dépôt backend).

## 2. Règles à respecter

- Ne jamais mettre de mot de passe réel, de clé Firebase privée ou de secret de production dans git ni dans un fichier suivi.
- Ne jamais lancer de commande destructive sur la base de production (`migrate:fresh`, suppressions massives) ni supprimer des données sans sauvegarde et accord explicite.
- Ne pas pousser sur `main` sans demande explicite de l'utilisateur : un push sur `main` déclenche le déploiement (tests puis envoi du ZIP par FTP). L'outil de sécurité de Claude Code bloque d'ailleurs ces actions sans demande explicite, ainsi que la lecture du `.env` de production et l'envoi de fichiers sensibles en production par FTP.
- Ne pas inventer de districts, d'églises, de contacts ou de responsables pastoraux. Ne pas coder en dur Niangon Révélation dans l'architecture.
- Le niveau « zone » a été supprimé volontairement (hiérarchie : national → district → église).
- Ne pas proposer de fonctionnalités hors périmètre V1 (membres, finances, dîmes, messagerie, prières, streaming, zones...).

## 3. Architecture backend

- Laravel 13, PHP 8.4, Sanctum (jetons API), Fortify (starter kit web Inertia/React, inscription publique fermée), Pest, Pint, PHPStan (Larastan) niveau 7. Base : MariaDB/MySQL (production cPanel LWS), SQLite en mémoire pour les tests.
- Panel d'administration : page statique `public/admin/index.html` (servie sur `/admin/`), qui appelle l'API admin avec un jeton Bearer.
- Rôles (enum `App\Enums\AdminRole`) : `SUPER_ADMIN`, `NATIONAL_ADMIN`, `DISTRICT_ADMIN`, `CHURCH_ADMIN`. Les règles de périmètre sont centralisées dans `App\Support\Perimeter` et appliquées par les Policies (`app/Policies`) et les Gates `manage-national` et `manage-platform`.
- Portées des contenus : `NATIONAL`, `DISTRICT`, `CHURCH`. Audiences des notifications : `ALL`, `DISTRICT`, `CHURCH`.
- Contrôleurs admin : `app/Http/Controllers/Api/V1/Admin`, bâtis sur `CrudController` (validation, autorisation, hook `prepare()`, journal `AuditLog`).
- Personnalisation sans compte : l'app envoie `for_church_id` sur `/home`, `/news`, `/events`, `/notifications` pour recevoir national + district + église.
- Inscriptions aux événements sans compte (`POST /events/{slug}/register`), avec champ piège anti-robots `website`, limitation de débit et refus des doublons ; export CSV côté admin.
- Seeders : `LaunchSeeder` (district de la Côtière, Niangon Révélation, catégories : sûr en production) ; `DemoSeeder` (données de démonstration, uniquement en local et en test).
- Création des comptes admin : `php artisan foursquare:create-admin email --name=... --role=SUPER_ADMIN` (ou `--district=` / `--church=` avec un slug). La même commande réinitialise le mot de passe d'un compte existant.

Documentation à jour : `API.md` (complète), `API_FRONTEND.md` (guide court pour le front), `FRONTEND.md`, `DEPLOYMENT.md`, `DOCKER.md`.

## 4. Performance (pour 3000 utilisateurs sur un mutualisé)

- Cache court des lectures publiques : `App\Support\PublicCache` + middleware `App\Http\Middleware\CachePublicResponse`. Réponses GET publiques mises en cache 60 s (sauf `search` et erreurs), clé = hôte + chemin + paramètres triés. Le cache est vidé à chaque enregistrement ou suppression d'un contenu public grâce au trait `App\Models\Concerns\RefreshesPublicCache` (posé sur Banner, Church, District, Event, EventCategory, Media, News, NewsCategory, PushNotification, Setting). En-tête `X-Cache: HIT|MISS`.
- Le middleware de cache passe avant la limitation de débit (`prependToPriorityList` dans `bootstrap/app.php`) : une réponse en cache ne coûte aucune requête SQL.
- Limites : API publique 300 requêtes/min par IP (les réseaux mobiles partagent les IP), images `/media/{id}/file` sans limite, login 5/min, inscriptions 5/min et 30/h.
- Compression gzip du JSON et du panel dans `public/.htaccess`.
- En production : `PUBLIC_CACHE_STORE=file` dans `.env` et `php artisan optimize` après chaque déploiement (configuration et routes en cache : toute modification du `.env` exige un nouveau `php artisan optimize`).

## 5. Notifications push

- FCM HTTP v1 (`app/Services/FcmClient.php`, job `app/Jobs/SendPushNotification.php`). Le job gère lui-même ses erreurs et marque la notification `SENT` ou `FAILED` avec la raison.
- Envoi immédiat par défaut : le bouton « Envoyer » du panel exécute l'envoi pendant la requête (`dispatchSync`), sans worker ni cron. `FCM_QUEUE=true` dans `.env` remet l'envoi par file d'attente, qui exige alors la tâche cron `php artisan queue:work --stop-when-empty --quiet`.
- Projet Firebase utilisé : `foursquaremobileapi`. La clé de compte de service (fichier JSON) doit être déposée dans `storage/app/firebase/` (jamais dans `public/`) et le `.env` doit contenir `FCM_CREDENTIALS=storage/app/firebase`. En local, la clé existe dans `storage/app/private/` et `storage/app/firebase/` (dossiers ignorés par git).
- L'ancienne configuration de l'app pointait sur un autre projet (`foursquare-16e29`), ce qui donnait l'erreur `SenderId mismatch`. L'app Android a été basculée sur `foursquaremobileapi` (commit `842fcf3` du dépôt Flutter). iOS et web pointent encore sur `foursquare-16e29`.
- Test sans rien écrire en base : `php artisan fcm:test --token=<jeton>` (le jeton s'affiche dans les journaux de l'app en debug). En local sans base, ajouter `CACHE_STORE=array` devant la commande.

## 6. Déploiement

- GitHub Actions : workflow `tests` (`composer ci:check` : `vp check` qui vérifie aussi le formatage Markdown/YAML/HTML, `tsc`, Pint, tests), puis workflow `deploy` sur push vers `main` : construit `foursquare-release.zip` et l'envoie en FTPS sur `web42.lws-hosting.com` avec le compte `ajoutapifoursquare@studiobeyam.net`, dont la racine est le dossier de l'API. Seul secret : `FTP_PASSWORD` dans l'environnement GitHub `production` (un doublon existe aussi en secret de dépôt, inutile).
- L'extraction reste manuelle dans cPanel : sauvegarder la base, extraire le ZIP sur place, puis `php artisan migrate --force`, `php artisan optimize:clear`, `php artisan optimize`. Le ZIP ne contient ni `.env` ni les fichiers de `storage/`.
- URL de production : API `https://api.studiobeyam.net/api/v1`, panel `https://api.studiobeyam.net/admin/`.
- Avant de pousser : lancer `npm run check` (et `npm run check:fix` si besoin), sinon la CI échoue sur le formatage des fichiers `.md`, `.yaml` ou `.html`.

## 7. Environnement local

- Docker (`compose.yaml`, `docker/app/`, mode d'emploi dans `DOCKER.md`) : services `app` (Apache + PHP 8.4, port 8000), `db` (MariaDB 10.11, `127.0.0.1:3307`), `queue`, `scheduler`. Les conteneurs lisent `docker/app/laravel.env` (seul `APP_KEY` vient du `.env` de la machine), ce qui empêche les tests lancés dans le conteneur de toucher la base de développement.
- Premier lancement : `docker compose up -d` (migrations et données de démonstration automatiques). Créer ensuite un compte avec `docker compose exec app php artisan foursquare:create-admin ...`.
- Téléphone Android : par USB avec `adb reverse tcp:8000 tcp:8000` (URL `http://127.0.0.1:8000/api/v1`) ou en Wi-Fi avec l'IP du PC (`http://192.168.100.10:8000/api/v1` au moment de la rédaction). Le téléphone de test est un Samsung SM-A176B associé en débogage sans fil.
- App Flutter : l'URL de l'API se règle avec `--dart-define=API_BASE_URL=...` (`lib/core/network/api_config.dart`, défaut : production). Package Android : `net.studiobeyam.foursquareci`.
- Les tests et PHPStan se lancent sur la machine Windows : `php artisan test --compact`, `vendor/bin/pint`, `vendor/bin/phpstan analyse app --memory-limit=1G`.

## 8. Historique des travaux (commits principaux sur `main`)

- `394ec95` Suppression du niveau zone (migration irréversible, données converties, table gardée en `zones_archive`).
- `1ef6f93` Inscription publique fermée (elle donnait le rôle super admin à n'importe qui) et rôle explicite obligatoire.
- `da69f09` Rôles, périmètres, catégories, champs d'église, inscriptions aux événements, personnalisation `for_church_id`, historique des notifications, tableau de bord, paramètres.
- `9e7b4ca` Données de lancement partout, données de démonstration uniquement en local.
- `6a21248` Panel adapté aux rôles et documentation de l'API V1.
- `eb93c8c` Déploiement via le compte FTP dédié à l'API.
- `57113d1` Configuration Docker (commit fait par l'utilisateur).
- `4516096` Cache des lectures publiques, limites de débit adaptées, gzip, `optimize` au déploiement.
- `47bf472` Formatage exigé par la CI (`vp check`).
- `06b955c` Envoi immédiat des notifications depuis le panel, sans cron.

## 9. Travail en cours (non commité au moment de la rédaction)

Bug trouvé en production : créer une bannière avec « Ordre d'affichage » vide affiche « Une erreur est survenue ». Le panel envoie `display_order: null`, la validation l'accepte, mais la colonne n'accepte pas null (erreur MySQL 1048). Contournement immédiat : saisir 0 ou 1 dans ce champ.

Modifications locales en cours :

- `CrudController` : nouvelle propriété `$emptyDefaults` qui remplace un champ envoyé vide (null) par une valeur par défaut avant l'enregistrement. Utilisée par `BannerController` et `CategoryController` (`display_order` vide devient 0 ; la règle des catégories passe de `sometimes` à `nullable`).
- `PublicCache::bump()` et `CachePublicResponse` : toute erreur de cache est rapportée (`report()`) mais ne fait plus échouer l'enregistrement ni l'API publique.
- Docker : `entrypoint.sh` rend `storage` et `bootstrap/cache` à `www-data` après la préparation, et les services `queue` et `scheduler` tournent en `www-data` (sinon des dossiers de cache créés en root deviennent impossibles à écrire pour Apache).
- Nouveau test `tests/Feature/Api/V1/AdminEmptyFieldsTest.php` (3 tests, passent).

Reste à faire pour ce lot : lancer `npm run check`, Pint, PHPStan et la suite complète, reconstruire l'image Docker (`docker compose up -d --build`), commiter, puis pousser sur `main` uniquement si l'utilisateur le demande.

## 10. À faire ensuite

- Production : déposer la clé Firebase dans `storage/app/firebase/`, ajouter `FCM_CREDENTIALS` et `PUBLIC_CACHE_STORE=file` au `.env`, lancer `php artisan optimize`. La tâche cron `php artisan schedule:run` est facultative pour l'instant.
- Production : configurer obligatoirement le cron `php artisan schedule:run` toutes les minutes afin d’envoyer le verset du jour à 07 h 00, heure d’Abidjan.
- Production : supprimer les données de démonstration encore visibles (districts inventés comme « Abidjan 1 » ou « Bouaké », « Rattachements à confirmer » et ses 15 églises, actualités, événements et bannières de démonstration), après sauvegarde et avec l'accord de l'utilisateur.
- Production : vérifier la table `users` (l'ancienne inscription publique donnait tous les droits) et créer les comptes admin.
- Flutter : brancher `for_church_id`, mettre les images en cache disque (`cached_network_image`), basculer iOS et web sur le projet Firebase `foursquaremobileapi`.
- Changer le mot de passe FTP, partagé plusieurs fois dans la conversation, puis mettre à jour le secret GitHub `FTP_PASSWORD`.

## 11. Pièges déjà rencontrés

- Git Bash convertit les chemins commençant par `/` en chemins Windows : utiliser `MSYS_NO_PATHCONV=1` pour les commandes `docker compose exec`.
- PHPStan sur tout le projet signale 200 faux positifs sous Windows (`env()` dans `config/`) : analyser `app` uniquement.
- `vp check` vérifie aussi le formatage des fichiers Markdown, YAML et HTML : la CI échoue sinon.
- En production, la configuration est en cache : une modification du `.env` n'a aucun effet sans `php artisan optimize`.
- Dotenv est immuable : une variable d'environnement réelle passe avant le `.env`, et une clé présente deux fois dans `.env` garde sa première valeur.
- Le serveur FTP présente le certificat de `web42.lws-hosting.com` : utiliser cet hôte, pas `ftp.studiobeyam.net`.
- Ne jamais modifier des fichiers avec un script `php -r` contenant des expressions régulières échappées dans le shell : un tel script a déjà vidé dix modèles (restaurés depuis git). Écrire le script dans un fichier et vérifier `git diff --stat` après coup.
