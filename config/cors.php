<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
     * Inclure toutes les routes web pour l'app mobile Capacitor.
     * Capacitor charge l'app via http://10.0.2.2 (émulateur) ou l'IP LAN.
     */
    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://10.0.2.2',           // Émulateur Android (alias localhost)
        'capacitor://localhost',      // Capacitor iOS
        'ionic://localhost',          // Ionic (compatibilité)
        'http://localhost:8100',      // Dev Ionic
        env('APP_URL', 'http://localhost'),
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/192\.168\.\d+\.\d+$/',  // IP LAN locale
        '/^http:\/\/10\.\d+\.\d+\.\d+$/',   // IP réseau privé
        '/^https?:\/\/.*\.resotravo\.com$/', // Production
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
