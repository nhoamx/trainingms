<?php

namespace App\Jobs;

use App\Models\EvaluationAnswer;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\EvaluationAnswerExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Backfills the evaluation_answers table from existing PaperEvaluation records.
 *
 * Scope: restricted to the three production organizations with verified raw_data formats.
 * Other organizations have different key formats (e.g. zero-padded, extra embedded sections)
 * and must be handled separately.
 *
 * Paper evaluations: all answers read from raw_data["slot"]["value"] using mapping_section.
 * Online evaluations: answers read from normalized columns; conditionals from raw_data.
 */
class BackfillEvaluationAnswers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    /** @var array<string> Organization names scoped for this backfill */
    public const SCOPED_ORGANIZATIONS = [
        'MAS BODEGA Y LOGISTICA S.A. DE C.V.',
        '7-SERVICIOS DE LOGISTICA S.A DE C.V.',
        'MAS BAKERIES S.A DE C.V.',
    ];

    public function __construct() {}

    /**
     * EvaluationAnswerExtractor is injected by the service container.
     */
    public function handle(EvaluationAnswerExtractor $extractor): void
    {
        $organizationIds = Organization::whereIn('name', self::SCOPED_ORGANIZATIONS)
            ->pluck('id');

        if ($organizationIds->isEmpty()) {
            Log::warning('BackfillEvaluationAnswers: no scoped organizations found.');

            return;
        }

        $total = 0;
        $errors = 0;

        PaperEvaluation::query()
            ->whereIn('organization_id', $organizationIds)
            ->whereNull('deleted_at')
            ->whereNotNull('raw_data')
            ->chunk(200, function ($evaluations) use ($extractor, &$total, &$errors) {
                foreach ($evaluations as $evaluation) {
                    try {
                        $rows = match ($evaluation->source) {
                            'paper' => $extractor->fromPaper($evaluation),
                            'online' => $extractor->fromOnline($evaluation),
                            default => [],
                        };

                        if (empty($rows)) {
                            continue;
                        }

                        EvaluationAnswer::upsert(
                            $rows,
                            uniqueBy: ['paper_evaluation_id', 'instrument', 'question_key'],
                            update: ['answer_value', 'answer_meta', 'updated_at'],
                        );

                        $total += count($rows);
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::error('BackfillEvaluationAnswers: failed for folio '.$evaluation->folio, [
                            'error' => $e->getMessage(),
                            'folio' => $evaluation->folio,
                            'source' => $evaluation->source,
                        ]);
                    }
                }
            });

        Log::info("BackfillEvaluationAnswers: completed. rows={$total}, errors={$errors}");
    }
}
