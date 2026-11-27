<?php

return [
    // Nom affiché dans l'en-tête et les titres de pages.
    'name' => env('SITE_NAME', 'Foursquare Niangon Révélation'),

    // Église du site (slug ou identifiant). Vide (SITE_CHURCH=) : site
    // national, sans la page « Notre église ».
    'church' => env('SITE_CHURCH', 'eglise-foursquare-niangon-revelation'),

    // Lien de téléchargement : le bouton est masqué tant qu'il est vide.
    'play_store_url' => env('PLAY_STORE_URL'),
    'app_store_url' => env('APP_STORE_URL'),
];
