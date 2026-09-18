<?php

return [
    // Swap to 's3' (or any Flysystem disk) without touching the code.
    'disk' => env('MEDIA_DISK', 'public'),

    'folder' => env('MEDIA_FOLDER', 'media'),

    'max_size_kb' => 5120,

    'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
];
