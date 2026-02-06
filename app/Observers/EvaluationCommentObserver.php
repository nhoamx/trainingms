<?php

namespace App\Observers;

use App\Models\EvaluationComment;
use App\Services\OrganizationReportCacheService;
use App\Support\BatchModeContext;
use Illuminate\Support\Facades\Log;

/**
 * Observer for EvaluationComment model to handle cache invalidation.
 *
 * Clears organization report caches when comments change,
 * as comments are displayed in the Likert report.
 *
 * Batch Mode Support:
 * During bulk imports, observers fire for every row. To prevent thousands
 * of warming jobs, we detect batch mode and skip warming. The import job
 * dispatches ONE warming job at the end.
 */
class EvaluationCommentObserver
{
    public function __construct(
        protected OrganizationReportCacheService $cacheService
    ) {}

    /**
     * Handle the EvaluationComment "created" event.
     */
    public function created(EvaluationComment $evaluationComment): void
    {
        $this->invalidateCache($evaluationComment, 'created');
    }

    /**
     * Handle the EvaluationComment "updated" event.
     */
    public function updated(EvaluationComment $evaluationComment): void
    {
        $this->invalidateCache($evaluationComment, 'updated');
    }

    /**
     * Handle the EvaluationComment "deleted" event.
     */
    public function deleted(EvaluationComment $evaluationComment): void
    {
        $this->invalidateCache($evaluationComment, 'deleted');
    }

    /**
     * Invalidate cache for the organization via the paper evaluation
     *
     * Smart warming: Skips warming in batch mode to prevent job storms
     */
    private function invalidateCache(EvaluationComment $evaluationComment, string $event): void
    {
        $paperEvaluation = $evaluationComment->paperEvaluation;

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
                Log::debug("EvaluationComment {$event}: Skipped warming for org {$orgId} (batch mode)");
            }
        }
    }
}
