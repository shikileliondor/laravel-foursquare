# Déploiement cPanel

Le workflow GitHub `.github/workflows/deploy.yml`, à la racine du dépôt, déploie automatiquement après la réussite des tests sur `main`. Un lancement manuel est aussi possible dans GitHub Actions.

Dans GitHub, créer l'environnement **production**, puis y définir :

| Type   | Nom              | Valeur                                                                                                     |
| ------ | ---------------- | ---------------------------------------------------------------------------------------------------------- |
| Secret | `FTP_PASSWORD`   | Mot de passe du compte FTP `c2860566c@winestock.studiobeyam.net`                                           |
| Secret | `PRODUCTION_ENV` | Contenu complet du `.env` de production, avec `APP_KEY`, URL, base PostgreSQL et autres secrets            |
| Secret | `FTP_SERVER_DIR` | Chemin FTP du dossier Laravel, par exemple `/foursquare-api` ; le chemin dépend de la racine du compte FTP |

Le serveur FTPS est `web42.lws-hosting.com`, port `21` : ce nom pointe vers le même serveur que `ftp.studiobeyam.net` et correspond au certificat TLS présenté. Le `.env` local est configuré pour le développement et ne doit pas servir en production. Préparer le secret `PRODUCTION_ENV` avec `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://api.studiobeyam.net` et les identifiants MySQL réels. Conserver la même `APP_KEY` entre deux déploiements.

Dans cPanel, faire pointer le document root du sous-domaine vers `foursquare-api/public`. Le serveur doit disposer de PHP 8.4.1 ou plus et des extensions requises par Composer. La configuration de production fournie utilise MySQL ; renseigner `DB_CONNECTION=mysql` et les identifiants réels dans `PRODUCTION_ENV`. `storage/` et `bootstrap/cache/` doivent être inscriptibles. Après le premier transfert, exécuter `php artisan migrate --force` et `php artisan storage:link` dans le terminal cPanel. Réexécuter les migrations après un déploiement qui en ajoute : FTP ne peut pas lancer de commandes PHP sur le serveur.

Le transfert n'efface pas les fichiers distants, afin de préserver les uploads et données persistantes.
