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

    'paths'                    => ['api/*', 'sanctum/csrf-cookie', '/login', '/logout'],
    'allowed_origins'          => [
        env('FRONTEND_URL', 'http://localhost:8001'),
        env('APP_URL', 'https://winnerbreak.com'),
        'https://winnerbreak.com',
        'https://*.winnerbreak.com',
    ],
    'allowed_origins_patterns' => [
        'https://*.railway.app',
        'https://*.winnerbreak.com',
    ],
    'allowed_headers'          => ['*'],
    'allowed_methods'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
