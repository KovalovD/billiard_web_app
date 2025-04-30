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
    // Добавь /login, /logout если они используются для сессий
    'allowed_origins'          => [env('FRONTEND_URL', 'http://localhost:8001')], // Укажи свой фронтенд URL
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'], // Или укажи конкретные
    'allowed_methods'          => ['*'], // Или укажи конкретные
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true, // <--- ВАЖНО
];
