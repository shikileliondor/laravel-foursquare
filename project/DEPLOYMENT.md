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

Deux tâches cron cPanel, toutes les minutes, depuis le dossier de l'API : `php artisan queue:work --stop-when-empty --quiet` (notifications, voir plus bas) et `php artisan schedule:run` (tâches planifiées, dont la libération des notifications bloquées).

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
2. Créer une tâche cron cPanel qui tourne toutes les minutes :

    ```bash
    cd <chemin du dossier de l'API> && php artisan queue:work --stop-when-empty --quiet
    ```

    Sans ce cron, « Envoyer » laisse la notification en `PENDING` indéfiniment : le panel semble fonctionner mais rien ne part.

3. Avant d'utiliser le panel, vérifier l'envoi depuis le terminal cPanel :

    ```bash
    php artisan fcm:test --token=<token-du-telephone>
    ```

    La commande n'écrit rien en base et se termine en erreur si rien n'est livré. « FCM non configuré » renvoie à l'étape 1.

Le seul secret GitHub nécessaire au transfert est `FTP_PASSWORD` (environnement `production`) : le mot de passe du compte FTP `ajoutapifoursquare@studiobeyam.net`. Le secret `FTP_SERVER_DIR` n'est plus utilisé. Le secret `PRODUCTION_ENV` n'est pas copié dans le ZIP : `.env` reste à gérer sur le serveur.
