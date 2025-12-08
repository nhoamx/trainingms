<?php

namespace App\Observers;

use App\Models\PaperEvaluation;
use App\Services\OrganizationReportCacheService;

/**
 * Observer for PaperEvaluation model to handle cache invalidation.
 *
 * Clears organization report caches when evaluations are created, updated,
 * deleted, or restored to ensure reports always reflect current data.
 */
class PaperEvaluationObserver
{
    public function __construct(
        protected OrganizationReportCacheService $cacheService
    ) {}

    /**
     * Handle the PaperEvaluation "created" event.
     */
    public function created(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation);
    }

    /**
     * Handle the PaperEvaluation "updated" event.
     */
    public function updated(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation);
    }

    /**
     * Handle the PaperEvaluation "deleted" event.
     */
    public function deleted(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation);
    }

    /**
     * Handle the PaperEvaluation "restored" event.
     */
    public function restored(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation);
    }

    /**
     * Handle the PaperEvaluation "forceDeleted" event.
     */
    public function forceDeleted(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation);
    }

    /**
     * Invalidate cache for the organization
     */
    private function invalidateCache(PaperEvaluation $paperEvaluation): void
    {
        if ($paperEvaluation->organization_id) {
            $this->cacheService->forgetOrganizationCaches($paperEvaluation->organization_id);
        }
    }
}
