<?php

namespace App\Observers;

use App\Models\EvaluationComment;
use App\Services\OrganizationReportCacheService;

/**
 * Observer for EvaluationComment model to handle cache invalidation.
 *
 * Clears organization report caches when comments change,
 * as comments are displayed in the Likert report.
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
        $this->invalidateCache($evaluationComment);
    }

    /**
     * Handle the EvaluationComment "updated" event.
     */
    public function updated(EvaluationComment $evaluationComment): void
    {
        $this->invalidateCache($evaluationComment);
    }

    /**
     * Handle the EvaluationComment "deleted" event.
     */
    public function deleted(EvaluationComment $evaluationComment): void
    {
        $this->invalidateCache($evaluationComment);
    }

    /**
     * Invalidate cache for the organization via the paper evaluation
     */
    private function invalidateCache(EvaluationComment $evaluationComment): void
    {
        $paperEvaluation = $evaluationComment->paperEvaluation;

        if ($paperEvaluation && $paperEvaluation->organization_id) {
            $this->cacheService->forgetOrganizationCaches($paperEvaluation->organization_id);
        }
    }
}
