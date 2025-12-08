<?php

namespace App\Observers;

use App\Models\EvaluationCustomField;
use App\Services\OrganizationReportCacheService;

/**
 * Observer for EvaluationCustomField model to handle cache invalidation.
 *
 * Clears organization report caches when custom fields change,
 * as this affects Likert report custom field filters.
 */
class EvaluationCustomFieldObserver
{
    public function __construct(
        protected OrganizationReportCacheService $cacheService
    ) {}

    /**
     * Handle the EvaluationCustomField "created" event.
     */
    public function created(EvaluationCustomField $evaluationCustomField): void
    {
        $this->invalidateCache($evaluationCustomField);
    }

    /**
     * Handle the EvaluationCustomField "updated" event.
     */
    public function updated(EvaluationCustomField $evaluationCustomField): void
    {
        $this->invalidateCache($evaluationCustomField);
    }

    /**
     * Handle the EvaluationCustomField "deleted" event.
     */
    public function deleted(EvaluationCustomField $evaluationCustomField): void
    {
        $this->invalidateCache($evaluationCustomField);
    }

    /**
     * Invalidate cache for the organization via the paper evaluation
     */
    private function invalidateCache(EvaluationCustomField $evaluationCustomField): void
    {
        $paperEvaluation = $evaluationCustomField->paperEvaluation;

        if ($paperEvaluation && $paperEvaluation->organization_id) {
            $this->cacheService->forgetOrganizationCaches($paperEvaluation->organization_id);
        }
    }
}
