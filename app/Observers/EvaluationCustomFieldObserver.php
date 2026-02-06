<?php

namespace App\Observers;

use App\Models\EvaluationCustomField;
use App\Services\OrganizationReportCacheService;
use App\Support\BatchModeContext;
use Illuminate\Support\Facades\Log;

/**
 * Observer for EvaluationCustomField model to handle cache invalidation.
 *
 * Clears organization report caches when custom fields change,
 * as this affects Likert report custom field filters.
 *
 * Batch Mode Support:
 * During bulk imports, observers fire for every row. To prevent thousands
 * of warming jobs, we detect batch mode and skip warming. The import job
 * dispatches ONE warming job at the end.
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
        $this->invalidateCache($evaluationCustomField, 'created');
    }

    /**
     * Handle the EvaluationCustomField "updated" event.
     */
    public function updated(EvaluationCustomField $evaluationCustomField): void
    {
        $this->invalidateCache($evaluationCustomField, 'updated');
    }

    /**
     * Handle the EvaluationCustomField "deleted" event.
     */
    public function deleted(EvaluationCustomField $evaluationCustomField): void
    {
        $this->invalidateCache($evaluationCustomField, 'deleted');
    }

    /**
     * Invalidate cache for the organization via the paper evaluation
     *
     * Smart warming: Skips warming in batch mode to prevent job storms
     */
    private function invalidateCache(EvaluationCustomField $evaluationCustomField, string $event): void
    {
        $paperEvaluation = $evaluationCustomField->paperEvaluation;

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
                Log::debug("EvaluationCustomField {$event}: Skipped warming for org {$orgId} (batch mode)");
            }
        }
    }
}
