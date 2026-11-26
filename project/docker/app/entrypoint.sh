#!/bin/sh
# Démarrage des conteneurs Laravel. Le conteneur `app` (Apache) prépare d'abord
# le projet ; `queue` et `scheduler` lancent directement leur commande.
set -eu

if [ "${1:-}" = "apache2-foreground" ]; then
    # Rapide quand vendor/ est à jour, complet au premier démarrage.
    composer install --no-interaction --prefer-dist --no-progress

    # Un cache créé sous Windows contiendrait des chemins C:\.
    php artisan config:clear
    php artisan route:clear

    # Base neuve : tables, données de lancement et données de démonstration.
    if php artisan migrate:status > /dev/null 2>&1; then
        php artisan migrate --force
    else
        php artisan migrate --force --seed
    fi

    # Les commandes ci-dessus tournent en root : Apache (www-data) doit
    # pouvoir réécrire le cache, les journaux et les vues compilées.
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

exec "$@"
