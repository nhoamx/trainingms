<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar los servicios para la gestión de puestos y departamentos
        $this->app->singleton(\App\Services\OccupationPositionService::class);
        $this->app->singleton(\App\Services\DepartmentAreaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
