<?php

namespace App\Services;

use App\Enums\EvaluationInstrument;
use App\Models\PaperEvaluation;

/**
 * Extracts normalized answer rows from a PaperEvaluation for insertion into evaluation_answers.
 *
 * Paper evaluations: reads slots from raw_data[slot][value] routed by mapping_section.
 * Online evaluations: reads from normalized columns; conditionals from raw_data.referencia_iii.
 */
class EvaluationAnswerExtractor
{
    /**
     * Mapping from raw_data mapping_section values to extraction rules.
     *
     * @var array<string, array{instrument: EvaluationInstrument, key: string, fixed_key: string|null}>
     */
    private const PAPER_MAPPING_SECTIONS = [
        'gri_binary' => ['instrument' => EvaluationInstrument::ReferenciaI,   'key' => 'slot',            'fixed_key' => null],
        'ats' => ['instrument' => EvaluationInstrument::ReferenciaI,   'key' => 'ats',             'fixed_key' => null],
        'general_1_64' => ['instrument' => EvaluationInstrument::ReferenciaIII, 'key' => 'slot',            'fixed_key' => null],
        'conditional_1' => ['instrument' => EvaluationInstrument::ReferenciaIII, 'key' => 'slot',            'fixed_key' => 'condition_cs'],
        'conditional_1_followup' => ['instrument' => EvaluationInstrument::ReferenciaIII, 'key' => 'cond_1_followup', 'fixed_key' => null],
        'conditional_2' => ['instrument' => EvaluationInstrument::ReferenciaIII, 'key' => 'slot',            'fixed_key' => 'condition_mgmt'],
        'conditional_2_followup' => ['instrument' => EvaluationInstrument::ReferenciaIII, 'key' => 'cond_2_followup', 'fixed_key' => null],
    ];

    /**
     * Extract answer rows from a paper evaluation's raw_data slots.
     *
     * Each slot is an OCR object with at minimum: mapping_section, value.
     *
     * @return array<int, array{paper_evaluation_id: string, instrument: string, question_key: string, answer_value: string|null, answer_meta: null, created_at: string, updated_at: string}>
     */
    public function fromPaper(PaperEvaluation $evaluation): array
    {
        $rawData = $evaluation->raw_data;

        if (! is_array($rawData)) {
            return [];
        }

        $rows = [];
        $now = now()->toDateTimeString();

        foreach ($rawData as $slot => $data) {
            if (! is_array($data) || ! isset($data['mapping_section'], $data['value'])) {
                continue;
            }

            $mapping = self::PAPER_MAPPING_SECTIONS[$data['mapping_section']] ?? null;

            if ($mapping === null) {
                continue;
            }

            $questionKey = $mapping['fixed_key'] ?? $this->resolveSlotKey((int) $slot, $mapping['key']);
            $answerValue = $data['value'] !== null ? (string) $data['value'] : null;

            $rows[] = [
                'paper_evaluation_id' => $evaluation->id,
                'instrument' => $mapping['instrument']->value,
                'question_key' => $questionKey,
                'answer_value' => $answerValue,
                'answer_meta' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    /**
     * Extract answer rows from an online evaluation's columns.
     *
     * Referencia I: reads referencia_i_answers (boolean values).
     *   Falls back to raw_data.referencia_i.acontecimientos_traumaticos for the
     *   reducido case where the worker answered NO to all ATS screening questions
     *   (column is NULL but answers are present in raw_data).
     *
     * Referencia III core (1–64): reads referencia_iii_answers.
     *
     * Referencia III conditional (65–72 + condition flags): reads raw_data.referencia_iii
     *   because the normalized column only contains keys 1–64.
     *
     * @return array<int, array{paper_evaluation_id: string, instrument: string, question_key: string, answer_value: string|null, answer_meta: null, created_at: string, updated_at: string}>
     */
    public function fromOnline(PaperEvaluation $evaluation): array
    {
        $rows = [];
        $now = now()->toDateTimeString();

        // ── Referencia I ─────────────────────────────────────────────────────
        $refIAnswers = $evaluation->referencia_i_answers;

        if (empty($refIAnswers)) {
            $refIAnswers = $evaluation->raw_data['referencia_i']['acontecimientos_traumaticos'] ?? null;
        }

        if (is_array($refIAnswers)) {
            foreach ($refIAnswers as $key => $value) {
                $rows[] = [
                    'paper_evaluation_id' => $evaluation->id,
                    'instrument' => EvaluationInstrument::ReferenciaI->value,
                    'question_key' => (string) $key,
                    'answer_value' => $this->castBoolToString($value),
                    'answer_meta' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // ── Referencia III core (1–64) ────────────────────────────────────────
        if (is_array($evaluation->referencia_iii_answers)) {
            foreach ($evaluation->referencia_iii_answers as $key => $value) {
                $rows[] = [
                    'paper_evaluation_id' => $evaluation->id,
                    'instrument' => EvaluationInstrument::ReferenciaIII->value,
                    'question_key' => (string) $key,
                    'answer_value' => $value !== null ? (string) $value : null,
                    'answer_meta' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // ── Referencia III conditional (65–72 + condition flags) ──────────────
        $rawRefIII = $evaluation->raw_data['referencia_iii'] ?? null;

        if (is_array($rawRefIII)) {
            foreach ($rawRefIII as $key => $value) {
                if (is_numeric($key) && (int) $key >= 65 && (int) $key <= 72) {
                    $rows[] = [
                        'paper_evaluation_id' => $evaluation->id,
                        'instrument' => EvaluationInstrument::ReferenciaIII->value,
                        'question_key' => (string) $key,
                        'answer_value' => $value !== null ? (string) $value : null,
                        'answer_meta' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } elseif ($key === 'condition_customer_service') {
                    $rows[] = [
                        'paper_evaluation_id' => $evaluation->id,
                        'instrument' => EvaluationInstrument::ReferenciaIII->value,
                        'question_key' => 'condition_cs',
                        'answer_value' => $this->castBoolToString($value),
                        'answer_meta' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } elseif ($key === 'condition_management') {
                    $rows[] = [
                        'paper_evaluation_id' => $evaluation->id,
                        'instrument' => EvaluationInstrument::ReferenciaIII->value,
                        'question_key' => 'condition_mgmt',
                        'answer_value' => $this->castBoolToString($value),
                        'answer_meta' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Resolve a question_key from a raw slot number using the given rule.
     */
    public function resolveSlotKey(int $slot, string $rule): string
    {
        return match ($rule) {
            'slot' => (string) $slot,
            'ats' => (string) ($slot - 74),  // slot 75 → "1" … slot 80 → "6"
            'cond_1_followup' => (string) ($slot - 1),   // slot 66 → "65" … slot 69 → "68"
            'cond_2_followup' => (string) ($slot - 2),   // slot 71 → "69" … slot 74 → "72"
            default => (string) $slot,
        };
    }

    private function castBoolToString(mixed $value): ?string
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
