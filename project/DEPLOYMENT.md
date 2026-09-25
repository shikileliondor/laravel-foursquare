# Déploiement cPanel

Le workflow GitHub `.github/workflows/deploy.yml` construit Laravel après la réussite des tests sur `main`, puis envoie **un seul fichier** `foursquare-release.zip` par FTPS. L'extraction dans cPanel est manuelle. Un lancement manuel est aussi possible dans GitHub Actions.

Dans GitHub, créer l'environnement **production**, puis y définir :

| Type   | Nom            | Valeur                                                           |
| ------ | -------------- | ---------------------------------------------------------------- |
| Secret | `FTP_PASSWORD` | Mot de passe du compte FTP `c2860566c@winestock.studiobeyam.net` |

Le serveur FTPS est `web42.lws-hosting.com`, port `21`. Le ZIP est déposé à la racine accessible du compte FTP, car le chemin `FTP_SERVER_DIR` configuré dans GitHub n'existe pas depuis ce compte. Ce secret n'est plus utilisé.

Après le transfert, dans le gestionnaire de fichiers cPanel :

1. Repérer `foursquare-release.zip` à la racine du compte FTP. Le déplacer vers `public_html/project` si nécessaire, puis l'extraire **dans `project`** : `artisan`, `app/` et `public/` doivent être directement dans ce dossier.
2. Créer ou conserver `.env` dans ce dossier avec les valeurs MySQL de production. Le ZIP exclut volontairement `.env` et le secret GitHub `PRODUCTION_ENV` n'est pas inclus dans l'archive. Garder la même `APP_KEY` lors des mises à jour.
3. Faire pointer la racine web du sous-domaine uniquement vers le sous-dossier `public/`. Le serveur doit avoir PHP 8.4.1 ou plus ; `storage/` et `bootstrap/cache/` doivent être inscriptibles.
4. Dans le terminal cPanel, exécuter `php artisan migrate --force` et, la première fois, `php artisan storage:link`. Ne jamais lancer `migrate:fresh` en production.
5. Pour les notifications push, déposer le JSON Firebase Admin dans `storage/app/firebase/`, hors de `public/`, puis mettre `FCM_CREDENTIALS=storage/app/firebase` dans `.env`. `FCM_PROJECT_ID` peut rester vide si le JSON contient `project_id`. **Le ZIP de déploiement ne contient jamais ce fichier** — il est ignoré par git : le déposer une fois par FTP suffit, les mises à jour suivantes ne l'effacent pas.
6. Configurer une tâche cron cPanel toutes les minutes pour vider la file des notifications : `cd /home/<user>/public_html/project && php artisan queue:work --stop-when-empty --quiet`. Sans ce cron, « Envoyer » laisse la notification en `PENDING` indéfiniment : le panel semble fonctionner mais rien ne part.
7. Vérifier la chaîne d'envoi depuis le terminal cPanel, avant d'utiliser le panel : `php artisan fcm:test --token=<token-du-telephone>`. La commande n'écrit rien en base et son code de sortie est non nul si rien n'est livré. Un « FCM non configuré » renvoie à l'étape 5.
8. Supprimer le ZIP après extraction, particulièrement si le dossier FTP est sous `public_html`.

Le ZIP ne contient ni `node_modules`, ni les tests, ni `.env`. L'extraction n'efface pas les uploads ou données persistantes déjà présents sur le serveur.
