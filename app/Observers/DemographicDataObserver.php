<?php

namespace App\Observers;

use App\Models\DemographicData;
use App\Services\OrganizationReportCacheService;
use App\Support\BatchModeContext;
use Illuminate\Support\Facades\Log;

/**
 * Observer for DemographicData model to handle cache invalidation.
 *
 * Clears organization report caches when demographic data changes,
 * as this affects Likert report demographics and filters.
 *
 * Batch Mode Support:
 * During bulk imports (5000+ rows), observers fire for every row.
 * To prevent 5000 warming jobs, we detect batch mode and skip warming.
 * The import job dispatches ONE warming job at the end.
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
        $this->invalidateCache($demographicData, 'created');
    }

    /**
     * Handle the DemographicData "updated" event.
     */
    public function updated(DemographicData $demographicData): void
    {
        $this->invalidateCache($demographicData, 'updated');
    }

    /**
     * Handle the DemographicData "deleted" event.
     */
    public function deleted(DemographicData $demographicData): void
    {
        $this->invalidateCache($demographicData, 'deleted');
    }

    /**
     * Invalidate cache for the organization via the paper evaluation
     *
     * Smart warming: Skips warming in batch mode to prevent job storms
     */
    private function invalidateCache(DemographicData $demographicData, string $event): void
    {
        // Load the paper evaluation to get the organization ID
        $paperEvaluation = $demographicData->paperEvaluation;

        if ($paperEvaluation && $paperEvaluation->organization_id) {
            $orgId = $paperEvaluation->organization_id;
            $isBatchMode = BatchModeContext::isEnabledForOrganization($orgId);

            // Always invalidate cache immediately (data must be fresh)
            // But skip warming if in batch mode (import job will warm at end)
            $this->cacheService->forgetOrganizationCaches(
                $orgId,
                warmCache: ! $isBatchMode
            );

            if ($isBatchMode) {
                Log::debug("DemographicData {$event}: Skipped warming for org {$orgId} (batch mode)");
            }
        }
    }
}
