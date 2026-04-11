<?php

namespace App\Services;

use App\Enums\EvaluationInstrument;
use App\Models\EvaluationAnswer;
use App\Models\PaperEvaluation;

class EvaluationAnswerSyncService
{
    public function __construct(
        protected EvaluationAnswerExtractor $extractor
    ) {}

    public function sync(PaperEvaluation $evaluation): int
    {
        $rows = match ($evaluation->source) {
            'paper' => $this->rowsFromPaper($evaluation),
            'online' => $this->extractor->fromOnline($evaluation),
            default => [],
        };

        return $this->upsertRows($rows);
    }

    public function syncOnlinePayload(PaperEvaluation $evaluation, array $answers): int
    {
        $rows = [];
        $now = now()->toDateTimeString();

        $referenciaIAnswers = $answers['referencia_i'] ?? null;

        if (is_array($referenciaIAnswers) && isset($referenciaIAnswers['acontecimientos_traumaticos']) && is_array($referenciaIAnswers['acontecimientos_traumaticos'])) {
            $referenciaIAnswers = $referenciaIAnswers['acontecimientos_traumaticos'];
        }

        if (is_array($referenciaIAnswers)) {
            foreach ($referenciaIAnswers as $questionKey => $value) {
                if (! is_scalar($value) && $value !== null) {
                    continue;
                }

                $rows[] = [
                    'paper_evaluation_id' => $evaluation->id,
                    'instrument' => EvaluationInstrument::ReferenciaI->value,
                    'question_key' => (string) $questionKey,
                    'answer_value' => $this->normalizeScalarValue($value),
                    'answer_meta' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $referenciaIIIAnswers = $answers['referencia_iii'] ?? null;

        if (is_array($referenciaIIIAnswers)) {
            foreach ($referenciaIIIAnswers as $questionKey => $value) {
                if ($questionKey === 'condition_customer_service') {
                    $rows[] = [
                        'paper_evaluation_id' => $evaluation->id,
                        'instrument' => EvaluationInstrument::ReferenciaIII->value,
                        'question_key' => 'condition_cs',
                        'answer_value' => $this->normalizeScalarValue($value),
                        'answer_meta' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    continue;
                }

                if ($questionKey === 'condition_management') {
                    $rows[] = [
                        'paper_evaluation_id' => $evaluation->id,
                        'instrument' => EvaluationInstrument::ReferenciaIII->value,
                        'question_key' => 'condition_mgmt',
                        'answer_value' => $this->normalizeScalarValue($value),
                        'answer_meta' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    continue;
                }

                if (! is_numeric($questionKey)) {
                    continue;
                }

                $questionNumber = (int) $questionKey;

                if ($questionNumber < 1 || $questionNumber > 72) {
                    continue;
                }

                if (! is_scalar($value) && $value !== null) {
                    continue;
                }

                $rows[] = [
                    'paper_evaluation_id' => $evaluation->id,
                    'instrument' => EvaluationInstrument::ReferenciaIII->value,
                    'question_key' => (string) $questionKey,
                    'answer_value' => $this->normalizeScalarValue($value),
                    'answer_meta' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $this->upsertRows($rows);
    }

    private function rowsFromPaper(PaperEvaluation $evaluation): array
    {
        $rows = $this->extractor->fromPaper($evaluation);

        if (! empty($rows)) {
            return $rows;
        }

        return $this->rowsFromLegacyPaperColumns($evaluation);
    }

    private function rowsFromLegacyPaperColumns(PaperEvaluation $evaluation): array
    {
        $rows = [];
        $now = now()->toDateTimeString();

        if (is_array($evaluation->referencia_i_answers)) {
            foreach ($evaluation->referencia_i_answers as $questionKey => $value) {
                if (! is_scalar($value) && $value !== null) {
                    continue;
                }

                $rows[] = [
                    'paper_evaluation_id' => $evaluation->id,
                    'instrument' => EvaluationInstrument::ReferenciaI->value,
                    'question_key' => (string) $questionKey,
                    'answer_value' => $this->normalizeScalarValue($value),
                    'answer_meta' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (is_array($evaluation->referencia_iii_answers)) {
            foreach ($evaluation->referencia_iii_answers as $questionKey => $value) {
                if (! is_scalar($value) && $value !== null) {
                    continue;
                }

                $rows[] = [
                    'paper_evaluation_id' => $evaluation->id,
                    'instrument' => EvaluationInstrument::ReferenciaIII->value,
                    'question_key' => (string) $questionKey,
                    'answer_value' => $this->normalizeScalarValue($value),
                    'answer_meta' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $rows;
    }

    private function upsertRows(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        EvaluationAnswer::upsert(
            $rows,
            uniqueBy: ['paper_evaluation_id', 'instrument', 'question_key'],
            update: ['answer_value', 'answer_meta', 'updated_at'],
        );

        return count($rows);
    }

    private function normalizeScalarValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        return (string) $value;
    }
}
