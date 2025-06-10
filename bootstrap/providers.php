<?php

use App\Admin\Providers\AdminServiceProvider;
use App\Auth\Providers\AuthServiceProvider;
use App\Core\Providers\AppServiceProvider;
use App\Core\Providers\RouteServiceProvider;
use App\Leagues\Providers\LeaguesServiceProvider;
use App\Matches\Providers\MatchesServiceProvider;
use App\OfficialRatings\Providers\OfficialRatingsServiceProvider;
use App\Tournaments\Providers\TournamentsServiceProvider;

return [
    MatchesServiceProvider::class,
    AppServiceProvider::class,
    AuthServiceProvider::class,
    RouteServiceProvider::class,
    AdminServiceProvider::class,
    OfficialRatingsServiceProvider::class,
    TournamentsServiceProvider::class,
    LeaguesServiceProvider::class,
];
