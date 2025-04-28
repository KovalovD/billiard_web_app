<?php

namespace App\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Route;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Route::group(['prefix' => 'api', 'middleware' => 'api'], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        });
    }
}
