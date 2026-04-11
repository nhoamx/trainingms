<?php

namespace App\Services;

use App\Enums\EvaluationInstrument;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Support\Collection;

class EvaluationCompletenessService
{
    /**
     * @return array<int, string>
     */
    public function getMissingAnswers(PaperEvaluation $evaluation): array
    {
        $instrument = $this->instrumentForEvaluation($evaluation);

        if (! $instrument) {
            return [];
        }

        $answersByKey = $this->answersByKey($evaluation, $instrument);
        $expectedKeys = $this->expectedQuestionKeys($instrument, $answersByKey);

        return $expectedKeys
            ->reject(fn (string $questionKey): bool => $answersByKey->get($questionKey) !== null)
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{id: string, folio: string, source: string, evaluation_type: string, work_center_id: ?string, expected_questions: int, answered_questions: int, missing_questions: array<int, string>, completeness_percentage: float}>
     */
    public function getCompletenessForOrganization(Organization $organization): Collection
    {
        return $this->buildCompletenessRows(
            PaperEvaluation::query()
                ->where('organization_id', $organization->id)
                ->where('processing_status', 'completed')
                ->whereIn('evaluation_type', $this->supportedEvaluationTypes())
        );
    }

    /**
     * @return array{work_center_id: string, evaluations: int, expected_answers: int, answered_answers: int, missing_answers: int, average_completeness_percentage: float}
     */
    public function getCompletenessForWorkCenter(WorkCenter $workCenter): array
    {
        $rows = $this->buildCompletenessRows(
            PaperEvaluation::query()
                ->where('work_center_id', $workCenter->id)
                ->where('processing_status', 'completed')
                ->whereIn('evaluation_type', $this->supportedEvaluationTypes())
        );

        $evaluations = $rows->count();
        $expectedAnswers = (int) $rows->sum('expected_questions');
        $answeredAnswers = (int) $rows->sum('answered_questions');
        $missingAnswers = (int) $rows->sum(fn (array $row): int => count($row['missing_questions']));
        $averageCompleteness = $evaluations > 0
            ? round((float) $rows->avg('completeness_percentage'), 2)
            : 0.0;

        return [
            'work_center_id' => (string) $workCenter->id,
            'evaluations' => $evaluations,
            'expected_answers' => $expectedAnswers,
            'answered_answers' => $answeredAnswers,
            'missing_answers' => $missingAnswers,
            'average_completeness_percentage' => $averageCompleteness,
        ];
    }

    /**
     * @return Collection<int, array{id: string, folio: string, source: string, evaluation_type: string, work_center_id: ?string, expected_questions: int, answered_questions: int, missing_questions: array<int, string>, completeness_percentage: float}>
     */
    public function getUnansweredByInstrument(Organization $organization, string $instrument): Collection
    {
        $enum = EvaluationInstrument::tryFrom($instrument);

        if (! $enum) {
            return collect();
        }

        return $this->getCompletenessForOrganization($organization)
            ->filter(function (array $row) use ($enum): bool {
                return $row['evaluation_type'] === $enum->value
                    && count($row['missing_questions']) > 0;
            })
            ->values();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<PaperEvaluation>  $query
     * @return Collection<int, array{id: string, folio: string, source: string, evaluation_type: string, work_center_id: ?string, expected_questions: int, answered_questions: int, missing_questions: array<int, string>, completeness_percentage: float}>
     */
    private function buildCompletenessRows($query): Collection
    {
        return $query
            ->with([
                'evaluationAnswers' => function ($answerQuery) {
                    $answerQuery->select(['paper_evaluation_id', 'instrument', 'question_key', 'answer_value']);
                },
            ])
            ->orderBy('folio')
            ->get()
            ->map(function (PaperEvaluation $evaluation): array {
                $missingQuestions = $this->getMissingAnswers($evaluation);
                $instrument = $this->instrumentForEvaluation($evaluation);
                $answersByKey = $instrument ? $this->answersByKey($evaluation, $instrument) : collect();
                $expectedQuestions = $instrument
                    ? $this->expectedQuestionKeys($instrument, $answersByKey)->count()
                    : 0;
                $answeredQuestions = $expectedQuestions - count($missingQuestions);
                $completeness = $expectedQuestions > 0
                    ? round(($answeredQuestions / $expectedQuestions) * 100, 2)
                    : 0.0;

                return [
                    'id' => (string) $evaluation->id,
                    'folio' => (string) $evaluation->folio,
                    'source' => (string) $evaluation->source,
                    'evaluation_type' => (string) $evaluation->evaluation_type,
                    'work_center_id' => $evaluation->work_center_id ? (string) $evaluation->work_center_id : null,
                    'expected_questions' => $expectedQuestions,
                    'answered_questions' => $answeredQuestions,
                    'missing_questions' => $missingQuestions,
                    'completeness_percentage' => $completeness,
                ];
            })
            ->values();
    }

    private function instrumentForEvaluation(PaperEvaluation $evaluation): ?EvaluationInstrument
    {
        return EvaluationInstrument::tryFrom((string) $evaluation->evaluation_type);
    }

    /**
     * @return Collection<string, string|null>
     */
    private function answersByKey(PaperEvaluation $evaluation, EvaluationInstrument $instrument): Collection
    {
        $answers = $evaluation->relationLoaded('evaluationAnswers')
            ? $evaluation->evaluationAnswers
            : $evaluation->evaluationAnswers()->get(['paper_evaluation_id', 'instrument', 'question_key', 'answer_value']);

        return $answers
            ->where('instrument', $instrument->value)
            ->pluck('answer_value', 'question_key');
    }

    /**
     * @param  Collection<string, string|null>  $answersByKey
     * @return Collection<int, string>
     */
    private function expectedQuestionKeys(EvaluationInstrument $instrument, Collection $answersByKey): Collection
    {
        return match ($instrument) {
            EvaluationInstrument::ReferenciaI => collect(range(1, 14))->map(fn (int $key): string => (string) $key),
            EvaluationInstrument::ReferenciaIII => $this->expectedReferenciaIIIQuestionKeys($answersByKey),
            EvaluationInstrument::Cisneros => collect(range(1, 44))->map(fn (int $key): string => (string) $key),
            EvaluationInstrument::Likert => collect(range(1, 23))->map(fn (int $key): string => (string) $key),
        };
    }

    /**
     * @param  Collection<string, string|null>  $answersByKey
     * @return Collection<int, string>
     */
    private function expectedReferenciaIIIQuestionKeys(Collection $answersByKey): Collection
    {
        $expected = collect(range(1, 64))->map(fn (int $key): string => (string) $key);

        if ($this->isTruthyAnswer($answersByKey->get('condition_cs'))) {
            $expected = $expected->merge(
                collect(range(65, 68))->map(fn (int $key): string => (string) $key)
            );
        }

        if ($this->isTruthyAnswer($answersByKey->get('condition_mgmt'))) {
            $expected = $expected->merge(
                collect(range(69, 72))->map(fn (int $key): string => (string) $key)
            );
        }

        return $expected->values();
    }

    private function isTruthyAnswer(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(strtoupper(trim($value)), ['SI', 'TRUE', '1', 'YES', 'Y'], true);
    }

    /**
     * @return array<int, string>
     */
    private function supportedEvaluationTypes(): array
    {
        return array_map(
            fn (EvaluationInstrument $instrument): string => $instrument->value,
            EvaluationInstrument::cases(),
        );
    }
}
