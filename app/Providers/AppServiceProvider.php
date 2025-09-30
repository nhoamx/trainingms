<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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

        // Registrar el servicio para reportes de categoría
        $this->app->singleton(\App\Services\CategoryReportService::class);
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
