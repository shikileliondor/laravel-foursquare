# Déployer sur cPanel

Après chaque push sur `main`, GitHub lance les tests, prépare `foursquare-release.zip` et l'envoie à la racine du compte FTP. L'extraction sur le serveur se fait manuellement.

## Dans cPanel

1. Ouvrir **Gestionnaire de fichiers** et chercher `foursquare-release.zip` à la racine du compte FTP.
2. Déplacer le ZIP dans `public_html/project`, puis choisir **Extraire** dans ce dossier. Vérifier que `public_html/project/artisan`, `public_html/project/app` et `public_html/project/public` existent directement, sans dossier supplémentaire créé par l'extraction.
3. Créer ou conserver `public_html/project/.env` avec les paramètres de production. Le ZIP ne contient pas ce fichier. Garder la même `APP_KEY` lors des mises à jour.
4. Régler la racine web du sous-domaine sur `public_html/project/public`. Utiliser PHP **8.4.1 ou plus** et rendre `storage/` et `bootstrap/cache/` inscriptibles.
5. Dans le terminal cPanel, depuis `public_html/project`, lancer :

   ```bash
   php artisan migrate --force
   php artisan storage:link  # uniquement si le lien n'existe pas déjà
   ```

6. Supprimer `foursquare-release.zip` après l'extraction.

Pour une mise à jour, refaire les étapes 1, 2, 5 et 6, en conservant `.env` et les fichiers déjà présents dans `storage/`. Ne pas lancer `migrate:fresh` en production.

Le seul secret GitHub nécessaire au transfert est `FTP_PASSWORD`. Le secret `FTP_SERVER_DIR` n'est plus utilisé ; le ZIP arrive à la racine du compte FTP. Le secret `PRODUCTION_ENV` n'est pas copié dans le ZIP : `.env` reste à gérer sur le serveur.
