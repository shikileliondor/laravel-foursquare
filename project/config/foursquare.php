<?php

return [
    // Credentials for the seeded SUPER_ADMIN account.
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@foursquare.ci'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],
];
