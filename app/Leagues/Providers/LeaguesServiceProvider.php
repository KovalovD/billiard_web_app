<?php

namespace App\Leagues\Providers;

use Illuminate\Support\ServiceProvider;
use Route;

class LeaguesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Route::group(['prefix' => 'api', 'middleware' => 'api'], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        });
    }
}
