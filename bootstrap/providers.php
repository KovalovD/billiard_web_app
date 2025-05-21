<?php

use App\Admin\Providers\AdminServiceProvider;
use App\Auth\Providers\AuthServiceProvider;
use App\Core\Providers\AppServiceProvider;
use App\Core\Providers\RouteServiceProvider;
use App\Matches\Providers\MatchesServiceProvider;

return [
    MatchesServiceProvider::class,
    AppServiceProvider::class,
    AuthServiceProvider::class,
    RouteServiceProvider::class, // Ensure the RouteServiceProvider is included
    AdminServiceProvider::class,
];
