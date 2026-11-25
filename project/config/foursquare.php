<?php

return [
    // Compte créé par le seeder de développement uniquement. En production,
    // utiliser `php artisan foursquare:create-admin` : aucun mot de passe
    // par défaut n'existe.
    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Cache court de l'API publique (App\Support\PublicCache), invalidé à
    // chaque modification de contenu. Vide : magasin de cache par défaut. En
    // production, `file` répond sans ouvrir de connexion MySQL.
    'public_cache' => [
        'store' => env('PUBLIC_CACHE_STORE'),
        'ttl' => (int) env('PUBLIC_CACHE_TTL', 60),
    ],
];
