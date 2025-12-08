<?php

namespace App\Observers;

use App\Models\DemographicData;
use App\Services\OrganizationReportCacheService;

/**
 * Observer for DemographicData model to handle cache invalidation.
 *
 * Clears organization report caches when demographic data changes,
 * as this affects Likert report demographics and filters.
 */
class DemographicDataObserver
{
    public function __construct(
        protected OrganizationReportCacheService $cacheService
    ) {}

    /**
     * Handle the DemographicData "created" event.
     */
    public function created(DemographicData $demographicData): void
    {
        $this->invalidateCache($demographicData);
    }

    /**
     * Handle the DemographicData "updated" event.
     */
    public function updated(DemographicData $demographicData): void
    {
        $this->invalidateCache($demographicData);
    }

    /**
     * Handle the DemographicData "deleted" event.
     */
    public function deleted(DemographicData $demographicData): void
    {
        $this->invalidateCache($demographicData);
    }

    /**
     * Invalidate cache for the organization via the paper evaluation
     */
    private function invalidateCache(DemographicData $demographicData): void
    {
        // Load the paper evaluation to get the organization ID
        $paperEvaluation = $demographicData->paperEvaluation;

        if ($paperEvaluation && $paperEvaluation->organization_id) {
            $this->cacheService->forgetOrganizationCaches($paperEvaluation->organization_id);
        }
    }
}
