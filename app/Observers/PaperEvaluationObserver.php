<?php

namespace App\Observers;

use App\Models\PaperEvaluation;
use App\Services\OrganizationReportCacheService;
use App\Support\BatchModeContext;
use Illuminate\Support\Facades\Log;

/**
 * Observer for PaperEvaluation model to handle cache invalidation.
 *
 * Clears organization report caches when evaluations are created, updated,
 * deleted, or restored to ensure reports always reflect current data.
 *
 * Batch Mode Support:
 * During bulk imports, observers fire for every row. To prevent thousands
 * of warming jobs, we detect batch mode and skip warming. The import job
 * dispatches ONE warming job at the end.
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
        $this->invalidateCache($paperEvaluation, 'created');
    }

    /**
     * Handle the PaperEvaluation "updated" event.
     */
    public function updated(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation, 'updated');
    }

    /**
     * Handle the PaperEvaluation "deleted" event.
     */
    public function deleted(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation, 'deleted');
    }

    /**
     * Handle the PaperEvaluation "restored" event.
     */
    public function restored(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation, 'restored');
    }

    /**
     * Handle the PaperEvaluation "forceDeleted" event.
     */
    public function forceDeleted(PaperEvaluation $paperEvaluation): void
    {
        $this->invalidateCache($paperEvaluation, 'forceDeleted');
    }

    /**
     * Invalidate cache for the organization
     *
     * Smart warming: Skips warming in batch mode to prevent job storms
     */
    private function invalidateCache(PaperEvaluation $paperEvaluation, string $event): void
    {
        if ($paperEvaluation->organization_id) {
            $orgId = $paperEvaluation->organization_id;
            $isBatchMode = BatchModeContext::isEnabledForOrganization($orgId);

            // Always invalidate cache immediately (data must be fresh)
            // But skip warming if in batch mode (import job will warm at end)
            $this->cacheService->forgetOrganizationCaches(
                $orgId,
                warmCache: ! $isBatchMode
            );

            if ($isBatchMode) {
                Log::debug("PaperEvaluation {$event}: Skipped warming for org {$orgId} (batch mode)");
            }
        }
    }
}
