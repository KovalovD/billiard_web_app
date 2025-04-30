<?php

return [
    \App\Core\Providers\AppServiceProvider::class,
    \App\Auth\Providers\AuthServiceProvider::class,
    \App\Core\Providers\RouteServiceProvider::class, // Ensure the RouteServiceProvider is included
];
