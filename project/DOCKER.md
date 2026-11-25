# Backend local avec Docker

Lance l'API, le panel, MariaDB, le worker des notifications et le planificateur. Prérequis : Docker Desktop démarré et un `.env` contenant `APP_KEY`. Les conteneurs ne reprennent que cette valeur : leur configuration est dans `docker/app/laravel.env`.

```powershell
docker compose up -d
```

Le premier lancement prend quelques minutes : construction de l'image, installation des dépendances, puis base remplie avec les données de lancement et de démonstration. Ensuite :

- API : http://127.0.0.1:8000/api/v1
- Panel : http://127.0.0.1:8000/admin/
- MariaDB : `127.0.0.1:3307`, base, utilisateur et mot de passe `foursquare` (local uniquement)

La base Docker est distincte de celle de XAMPP. Pour créer un compte du panel :

```powershell
docker compose exec app php artisan foursquare:create-admin admin@foursquare.ci --name="Admin local" --role=SUPER_ADMIN
```

Le code est monté en direct : une modification PHP s'applique sans reconstruire l'image. Le worker et le planificateur gardent l'ancien code en mémoire : après une modification qui les concerne, lancer `docker compose restart queue scheduler`. Les services redémarrent avec Docker Desktop tant qu'ils n'ont pas été arrêtés.

## Tester l'app sur un téléphone Android

**Par USB (le plus simple)** : activer le débogage USB, brancher le téléphone, puis :

```powershell
adb reverse tcp:8000 tcp:8000
```

Dans l'app, l'adresse de l'API reste `http://127.0.0.1:8000/api/v1`. Refaire la commande à chaque rebranchement.

**Par Wi-Fi** : PC et téléphone sur le même réseau. Dans l'app, utiliser `http://<IP du PC>:8000/api/v1`, avec l'IP de la carte Wi-Fi donnée par `ipconfig`. Le port 8000 est alors ouvert au réseau local : éviter les Wi-Fi publics.

Dans les deux cas, les URL des images suivent l'adresse utilisée par l'app : rien à changer côté API.

Si l'app refuse le HTTP (`Cleartext HTTP traffic ... not permitted`), ajouter `android:usesCleartextTraffic="true"` sur la balise `<application>` de `android/app/src/debug/AndroidManifest.xml`. Seules les versions de debug sont concernées.

**Notifications push** : déposer le JSON du compte de service Firebase (même projet Firebase que l'app) dans `storage/app/firebase/`, puis lancer `docker compose restart queue`. Pour vérifier l'envoi : `docker compose exec app php artisan fcm:test --token=<token-du-téléphone>`.

## Commandes utiles

| Besoin | Commande |
| --- | --- |
| État des services | `docker compose ps` |
| Journaux | `docker compose logs -f app queue`, et `storage/logs/laravel.log` pour Laravel |
| Artisan | `docker compose exec app php artisan <commande>` |
| Arrêter, relancer | `docker compose stop`, puis `docker compose up -d` |
| Repartir d'une base vide | `docker compose down -v`, puis `docker compose up -d` : efface la base Docker, les dépendances se réinstallent |
