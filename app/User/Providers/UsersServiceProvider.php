<?php

namespace App\User\Providers;

use Illuminate\Support\ServiceProvider;
use Route;

class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Route::group(['prefix' => 'api', 'middleware' => 'api'], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        });
    }
}
