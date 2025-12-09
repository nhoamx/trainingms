<?php

namespace App\Providers;

use App\Models\DemographicData;
use App\Models\EvaluationComment;
use App\Models\EvaluationCustomField;
use App\Models\PaperEvaluation;
use App\Observers\DemographicDataObserver;
use App\Observers\EvaluationCommentObserver;
use App\Observers\EvaluationCustomFieldObserver;
use App\Observers\PaperEvaluationObserver;
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

        // Registrar el servicio de caché para reportes de organización
        $this->app->singleton(\App\Services\OrganizationReportCacheService::class);

        // Registrar el servicio para datos del dashboard de organización
        $this->app->singleton(\App\Services\OrganizationDataService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Register observers for cache invalidation
        PaperEvaluation::observe(PaperEvaluationObserver::class);
        DemographicData::observe(DemographicDataObserver::class);
        EvaluationCustomField::observe(EvaluationCustomFieldObserver::class);
        EvaluationComment::observe(EvaluationCommentObserver::class);
    }
}
