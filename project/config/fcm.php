<?php

return [
    // Chemin absolu (ou relatif à base_path) du JSON de compte de service Firebase.
    // Console Firebase > Paramètres > Comptes de service > Générer une clé privée.
    'credentials' => env('FCM_CREDENTIALS'),

    // Identifiant du projet Firebase. Déduit du JSON s'il est absent.
    'project_id' => env('FCM_PROJECT_ID'),

    // Appareils envoyés en parallèle par lot. FCM HTTP v1 n'a pas de multicast.
    'chunk' => (int) env('FCM_CHUNK', 50),

    'timeout' => (int) env('FCM_TIMEOUT', 10),

    'endpoints' => [
        'token' => 'https://oauth2.googleapis.com/token',
        'send' => 'https://fcm.googleapis.com/v1/projects/:project/messages:send',
    ],

    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
];
