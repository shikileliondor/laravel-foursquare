<?php

return [
    // Compte créé par le seeder de développement uniquement. En production,
    // utiliser `php artisan foursquare:create-admin` : aucun mot de passe
    // par défaut n'existe.
    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
