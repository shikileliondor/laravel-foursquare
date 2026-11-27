# Déployer sur cPanel

Production : API `https://api.studiobeyam.net/api/v1`, panel `https://api.studiobeyam.net/admin/`.

Après chaque push sur `main`, GitHub lance les tests, prépare `foursquare-release.zip` et l'envoie par FTPS avec le compte `ajoutapifoursquare@studiobeyam.net`. Ce compte s'ouvre directement sur **le dossier de l'API** (celui qui contient `artisan`, `app/` et `public/`) : le ZIP y arrive prêt à être extrait. L'extraction reste manuelle, rien ne change en ligne tant qu'elle n'est pas faite.

## Dans cPanel

1. Ouvrir **Gestionnaire de fichiers**, aller dans le dossier de l'API et repérer `foursquare-release.zip`.
2. **Sauvegarder la base** : phpMyAdmin → base de production → **Exporter** (format SQL), et garder le fichier. Certaines migrations sont irréversibles (ex. suppression des zones) : en cas de problème, on restaure cette sauvegarde.
3. Choisir **Extraire** sur le ZIP, dans ce même dossier, en écrasant les fichiers existants. `artisan`, `app/` et `public/` doivent rester directement dans le dossier, sans sous-dossier créé par l'extraction. Le ZIP ne contient ni `.env` ni les fichiers de `storage/` : ils sont conservés.
4. Dans le terminal cPanel, depuis le dossier de l'API :

    ```bash
    php artisan migrate --force
    php artisan optimize:clear
    php artisan optimize      # met en cache configuration, routes et vues : chaque requête démarre plus vite
    php artisan storage:link  # uniquement si le lien n'existe pas déjà
    ```

5. Supprimer `foursquare-release.zip`.

Configuration à faire une fois : la racine web du sous-domaine `api.studiobeyam.net` pointe sur le sous-dossier `public/` du dossier de l'API ; PHP **8.4.1 ou plus** ; `storage/` et `bootstrap/cache/` inscriptibles ; `.env` de production (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://api.studiobeyam.net`, base MySQL de l'hébergement, même `APP_KEY` à chaque mise à jour, `PUBLIC_CACHE_STORE=file` pour servir les lectures publiques sans MySQL). Après une modification du `.env`, relancer `php artisan optimize`.

Tâche cron cPanel obligatoire pour publier la notification du verset du jour à 07 h 00, heure d’Abidjan : `php artisan schedule:run` toutes les minutes. Ajouter aussi `php artisan queue:work --stop-when-empty --quiet` toutes les minutes uniquement si `FCM_QUEUE=true` (voir plus bas).

### Images servies sans PHP

Fait le 2026-11-27 sur `~/public_html/project` : `php artisan storage:link` (lien `public/storage` vers `storage/app/public`, conservé par les déploiements car le ZIP ne contient pas `public/storage`). Pour l'activer, ajouter `MEDIA_DIRECT_URLS=true` au `.env` puis lancer `php artisan optimize` : l'API renvoie alors `https://api.studiobeyam.net/storage/media/<uuid>.<ext>`, servi directement par Apache avec un cache d'un an (`public/.htaccess`). L'ancienne route `/api/v1/media/{id}/file` reste active pour les adresses déjà en cache dans les téléphones. Pour revenir en arrière : `MEDIA_DIRECT_URLS=false` puis `php artisan optimize`.

### Programmes de Niangon Révélation et de la Côtière (affiches)

Après le déploiement : `php artisan db:seed --class=ProgrammesSeeder --force`. Importe les 14 affiches de `public/assets/images` dans la médiathèque et crée 7 événements (Convention Nationale, Convention Panafricaine, Offrandes des orphelins des pasteurs, Morasha, Rentrée de la Côtière, Autel des Parfums de septembre et de novembre 2026) et 5 actualités de Niangon Révélation, puis complète les horaires de culte de l'église s'ils sont vides. Relançable sans doublon : une fiche déjà présente n'est enrichie que si elle n'a jamais été modifiée, une correction faite dans le panel n'est jamais écrasée. Années déduites (absentes des affiches) : Autel des Parfums du 6 septembre et Offrandes du 13 septembre, en 2026.

### Première mise en service de la V1 nationale

Après `migrate`, toujours depuis le dossier de l'API :

```bash
php artisan db:seed --force          # District de la Côtière, Niangon Révélation, catégories (aucune donnée de démo en production)
php artisan foursquare:create-admin  # premier super administrateur, mot de passe demandé
```

Vérifier aussi qu'aucun compte inconnu n'existe (l'ancienne inscription publique donnait tous les droits) :
`SELECT id, name, email, role, created_at FROM users;` dans phpMyAdmin, et supprimer ou suspendre ce qui n'est pas légitime.

Pour une mise à jour, refaire les étapes 1 à 5. Ne pas lancer `migrate:fresh` en production.

## Notifications push (première installation)

1. Déposer le JSON Firebase Admin dans `storage/app/firebase/` du dossier de l'API, hors de `public/`, puis mettre `FCM_CREDENTIALS=storage/app/firebase` dans `.env`. `FCM_PROJECT_ID` peut rester vide si le JSON contient `project_id`. Le ZIP ne contient jamais ce fichier, qui est ignoré par git : le déposer une fois par FTP suffit, les mises à jour ne l'effacent pas.
2. Lancer `php artisan optimize` pour prendre en compte le `.env`. Sans cron ni worker, « Envoyer » dans le panel envoie tout de suite : la notification passe directement à `SENT` (ou `FAILED`, avec la raison dans la colonne Erreur).

    Quand le parc d'appareils deviendra grand, mettre `FCM_QUEUE=true` dans `.env` pour que l'envoi ne bloque plus la page, et créer la tâche cron qui vide la file, toutes les minutes :

    ```bash
    cd <chemin du dossier de l'API> && php artisan queue:work --stop-when-empty --quiet
    ```

    Avec `FCM_QUEUE=true` et sans ce cron, « Envoyer » laisse la notification en `PENDING` indéfiniment.

3. Avant d'utiliser le panel, vérifier l'envoi depuis le terminal cPanel :

    ```bash
    php artisan fcm:test --token=<token-du-telephone>
    ```

    La commande n'écrit rien en base et se termine en erreur si rien n'est livré. « FCM non configuré » renvoie à l'étape 1.

Le seul secret GitHub nécessaire au transfert est `FTP_PASSWORD` (environnement `production`) : le mot de passe du compte FTP `ajoutapifoursquare@studiobeyam.net`. Le secret `FTP_SERVER_DIR` n'est plus utilisé. Le secret `PRODUCTION_ENV` n'est pas copié dans le ZIP : `.env` reste à gérer sur le serveur.
