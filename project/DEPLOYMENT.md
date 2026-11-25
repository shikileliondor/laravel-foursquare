# Déployer sur cPanel

Après chaque push sur `main`, GitHub lance les tests, prépare `foursquare-release.zip` et l'envoie à la racine du compte FTP. L'extraction sur le serveur se fait manuellement.

## Dans cPanel

1. Ouvrir **Gestionnaire de fichiers** et chercher `foursquare-release.zip` à la racine du compte FTP.
2. Déplacer le ZIP dans `public_html/project`, puis choisir **Extraire** dans ce dossier. Vérifier que `public_html/project/artisan`, `public_html/project/app` et `public_html/project/public` existent directement, sans dossier supplémentaire créé par l'extraction.
3. Créer ou conserver `public_html/project/.env` avec les paramètres de production. Le ZIP ne contient pas ce fichier. Garder la même `APP_KEY` lors des mises à jour.
4. Régler la racine web du sous-domaine sur `public_html/project/public`. Utiliser PHP **8.4.1 ou plus** et rendre `storage/` et `bootstrap/cache/` inscriptibles.
5. **Sauvegarder la base** avant toute migration : phpMyAdmin → base de production → **Exporter** (format SQL), et garder le fichier. Certaines migrations sont irréversibles (ex. suppression des zones) : en cas de problème, on restaure cette sauvegarde. Ensuite, dans le terminal cPanel, depuis `public_html/project`, lancer :

   ```bash
   php artisan migrate --force
   php artisan storage:link  # uniquement si le lien n'existe pas déjà
   ```

6. Supprimer `foursquare-release.zip` après l'extraction.

### Première mise en service de la V1 nationale

Après `migrate`, toujours depuis `public_html/project` :

```bash
php artisan db:seed --force          # District de la Côtière, Niangon Révélation, catégories (aucune donnée de démo en production)
php artisan foursquare:create-admin  # premier super administrateur, mot de passe demandé
```

Vérifier aussi qu'aucun compte inconnu n'existe (l'ancienne inscription publique donnait tous les droits) :
`SELECT id, name, email, role, created_at FROM users;` dans phpMyAdmin, et supprimer ou suspendre ce qui n'est pas légitime.

Pour une mise à jour, refaire les étapes 1, 2, 5 et 6, en conservant `.env` et les fichiers déjà présents dans `storage/`. Ne pas lancer `migrate:fresh` en production.

## Notifications push (première installation)

1. Déposer le JSON Firebase Admin dans `public_html/project/storage/app/firebase/`, hors de `public/`, puis mettre `FCM_CREDENTIALS=storage/app/firebase` dans `.env`. `FCM_PROJECT_ID` peut rester vide si le JSON contient `project_id`. Le ZIP ne contient jamais ce fichier, qui est ignoré par git : le déposer une fois par FTP suffit, les mises à jour ne l'effacent pas.
2. Créer une tâche cron cPanel qui tourne toutes les minutes :

   ```bash
   cd /home/<user>/public_html/project && php artisan queue:work --stop-when-empty --quiet
   ```

   Sans ce cron, « Envoyer » laisse la notification en `PENDING` indéfiniment : le panel semble fonctionner mais rien ne part.
3. Avant d'utiliser le panel, vérifier l'envoi depuis le terminal cPanel :

   ```bash
   php artisan fcm:test --token=<token-du-telephone>
   ```

   La commande n'écrit rien en base et se termine en erreur si rien n'est livré. « FCM non configuré » renvoie à l'étape 1.

Le seul secret GitHub nécessaire au transfert est `FTP_PASSWORD`. Le secret `FTP_SERVER_DIR` n'est plus utilisé ; le ZIP arrive à la racine du compte FTP. Le secret `PRODUCTION_ENV` n'est pas copié dans le ZIP : `.env` reste à gérer sur le serveur.
