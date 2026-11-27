<?php

return [
    // Swap to 's3' (or any Flysystem disk) without touching the code.
    'disk' => env('MEDIA_DISK', 'public'),

    'folder' => env('MEDIA_FOLDER', 'media'),

    // true une fois le lien public/storage créé (`php artisan storage:link`) :
    // les images sont alors servies par Apache, sans passer par PHP.
    'direct_urls' => (bool) env('MEDIA_DIRECT_URLS', false),

    'max_size_kb' => 5120,

    'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
];
