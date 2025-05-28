<?php

namespace App\Core\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        JsonResource::withoutWrapping();
        Model::shouldBeStrict(!$this->app->isProduction());

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Handle proxy headers for Railway/Heroku-like platforms
        if (request()->server('HTTP_X_FORWARDED_PROTO') === 'https' ||
            request()->server('HTTP_X_FORWARDED_SSL') === 'on' ||
            (request()->hasHeader('X-Forwarded-Proto') && request()->header('X-Forwarded-Proto') === 'https')) {
            URL::forceScheme('https');
        }
    }
}
