<?php

use App\Auth\Providers\AuthServiceProvider;
use App\Core\Providers\AppServiceProvider;
use App\Core\Providers\RouteServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    RouteServiceProvider::class, // Ensure the RouteServiceProvider is included
];
