<?php

namespace App\Admin\Providers;

use Illuminate\Support\ServiceProvider;
use Route;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Route::group(['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        });
    }
}
