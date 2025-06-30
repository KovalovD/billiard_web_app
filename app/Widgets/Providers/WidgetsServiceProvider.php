<?php

namespace App\Widgets\Providers;

use Illuminate\Support\ServiceProvider;
use Route;

class WidgetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Route::group(['prefix' => 'api', 'middleware' => 'api'], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        });
    }
}
