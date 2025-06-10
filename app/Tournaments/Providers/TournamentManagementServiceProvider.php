<?php

namespace App\Tournaments\Providers;

use App\Tournaments\Services\TournamentManagementService;
use App\Tournaments\Services\TournamentService;
use Illuminate\Support\ServiceProvider;
use Route;

class TournamentManagementServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register tournament management services
        $this->app->singleton(TournamentManagementService::class);

        // Extend existing tournament service if needed
        $this->app->extend(TournamentService::class, function ($service, $app) {
            return $service;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register routes
        $this->registerRoutes();

        // Register model observers if needed
        $this->registerObservers();

        // Register console commands if any
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    /**
     * Register tournament management routes
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix'     => 'api',
            'middleware' => 'api',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/management.php');
        });
    }

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
        // Register observers for automatic tournament progression
        // Example: TournamentMatch::observe(TournamentMatchObserver::class);
    }

    /**
     * Register console commands
     */
    protected function registerCommands(): void
    {
        // Register artisan commands for tournament management
        // Example: $this->commands([AdvanceTournamentCommand::class]);
    }
}
