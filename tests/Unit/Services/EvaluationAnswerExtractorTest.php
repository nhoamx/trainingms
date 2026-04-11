<?php

namespace Tests\Unit\Services;

use App\Enums\EvaluationInstrument;
use App\Models\PaperEvaluation;
use App\Services\EvaluationAnswerExtractor;
use Tests\TestCase;

/**
 * Unit tests for EvaluationAnswerExtractor.
 *
 * Uses PaperEvaluation::make() to build unsaved model instances — no DB hits.
 */
class EvaluationAnswerExtractorTest extends TestCase
{
    private EvaluationAnswerExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new EvaluationAnswerExtractor;
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * @return array{value: string, mapping_section: string, row: int, block: int, margin: float, ambiguous: bool, confidence: float, selected_column: null}
     */
    private function slot(string $value, string $mappingSection): array
    {
        return [
            'row' => 1,
            'block' => 1,
            'value' => $value,
            'margin' => 0.1,
            'ambiguous' => false,
            'confidence' => 0.99,
            'mapping_section' => $mappingSection,
            'selected_column' => null,
        ];
    }

    // ── fromPaper: gri_binary ───────────────────────────────────────────────

    public function test_gri_binary_slots_map_to_referencia_i_with_slot_as_key(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => [
                '1' => $this->slot('SI', 'gri_binary'),
                '7' => $this->slot('NO', 'gri_binary'),
                '14' => $this->slot('SI', 'gri_binary'),
            ],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertCount(3, $rows);
        $this->assertSame(EvaluationInstrument::ReferenciaI->value, $rows[0]['instrument']);
        $this->assertSame('1', $rows[0]['question_key']);
        $this->assertSame('SI', $rows[0]['answer_value']);
        $this->assertSame('7', $rows[1]['question_key']);
        $this->assertSame('NO', $rows[1]['answer_value']);
        $this->assertSame('14', $rows[2]['question_key']);
    }

    // ── fromPaper: ats key offset ───────────────────────────────────────────

    public function test_ats_slots_map_to_referencia_i_with_slot_minus_74_key(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => [
                '75' => $this->slot('SI', 'ats'),
                '78' => $this->slot('NO', 'ats'),
                '80' => $this->slot('SI', 'ats'),
            ],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertCount(3, $rows);

        $byKey = array_column($rows, null, 'question_key');

        $this->assertSame(EvaluationInstrument::ReferenciaI->value, $byKey['1']['instrument']); // 75-74
        $this->assertSame(EvaluationInstrument::ReferenciaI->value, $byKey['4']['instrument']); // 78-74
        $this->assertSame(EvaluationInstrument::ReferenciaI->value, $byKey['6']['instrument']); // 80-74
    }

    public function test_ats_slots_do_not_produce_referencia_iii_rows(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => ['75' => $this->slot('SI', 'ats')],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertNotContains(EvaluationInstrument::ReferenciaIII->value, array_column($rows, 'instrument'));
    }

    // ── fromPaper: general_1_64 ─────────────────────────────────────────────

    public function test_general_1_64_slots_map_to_referencia_iii_with_slot_as_key(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => [
                '1' => $this->slot('A', 'general_1_64'),
                '32' => $this->slot('C', 'general_1_64'),
                '64' => $this->slot('E', 'general_1_64'),
            ],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertCount(3, $rows);

        $byKey = array_column($rows, null, 'question_key');

        $this->assertSame(EvaluationInstrument::ReferenciaIII->value, $byKey['1']['instrument']);
        $this->assertSame('C', $byKey['32']['answer_value']);
        $this->assertSame('E', $byKey['64']['answer_value']);
    }

    // ── fromPaper: conditional fixed keys ──────────────────────────────────

    public function test_conditional_1_slot_uses_fixed_key_condition_cs(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => ['65' => $this->slot('SI', 'conditional_1')],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertCount(1, $rows);
        $this->assertSame('condition_cs', $rows[0]['question_key']);
        $this->assertSame(EvaluationInstrument::ReferenciaIII->value, $rows[0]['instrument']);
    }

    public function test_conditional_2_slot_uses_fixed_key_condition_mgmt(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => ['70' => $this->slot('NO', 'conditional_2')],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertCount(1, $rows);
        $this->assertSame('condition_mgmt', $rows[0]['question_key']);
    }

    public function test_conditional_1_followup_uses_slot_minus_1_key(): void
    {
        // slots 66–69 → keys 65–68
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => [
                '66' => $this->slot('B', 'conditional_1_followup'),
                '69' => $this->slot('E', 'conditional_1_followup'),
            ],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);
        $byKey = array_column($rows, null, 'question_key');

        $this->assertArrayHasKey('65', $byKey); // 66-1
        $this->assertArrayHasKey('68', $byKey); // 69-1
        $this->assertSame('B', $byKey['65']['answer_value']);
    }

    public function test_conditional_2_followup_uses_slot_minus_2_key(): void
    {
        // slots 71–74 → keys 69–72
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => [
                '71' => $this->slot('A', 'conditional_2_followup'),
                '74' => $this->slot('D', 'conditional_2_followup'),
            ],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);
        $byKey = array_column($rows, null, 'question_key');

        $this->assertArrayHasKey('69', $byKey); // 71-2
        $this->assertArrayHasKey('72', $byKey); // 74-2
        $this->assertSame('D', $byKey['72']['answer_value']);
    }

    // ── fromPaper: edge cases ───────────────────────────────────────────────

    public function test_unknown_mapping_section_is_skipped(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => [
                '1' => $this->slot('SI', 'unknown_section'),
                '2' => $this->slot('SI', 'gri_binary'),
            ],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertCount(1, $rows);
        $this->assertSame('2', $rows[0]['question_key']);
    }

    public function test_slot_missing_value_key_is_skipped(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => [
                '1' => ['mapping_section' => 'gri_binary'],  // no 'value' key
                '2' => $this->slot('NO', 'gri_binary'),
            ],
        ]);

        $rows = $this->extractor->fromPaper($evaluation);

        $this->assertCount(1, $rows);
    }

    public function test_null_raw_data_returns_empty_array(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'paper',
            'raw_data' => null,
        ]);

        $this->assertSame([], $this->extractor->fromPaper($evaluation));
    }

    // ── fromOnline: referencia_i ────────────────────────────────────────────

    public function test_online_referencia_i_boolean_true_stored_as_string_true(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'online',
            'referencia_i_answers' => ['1' => true, '5' => false],
            'raw_data' => ['source' => 'online'],
        ]);

        $rows = $this->extractor->fromOnline($evaluation);
        $byKey = array_column($rows, null, 'question_key');

        $this->assertSame('true', $byKey['1']['answer_value']);
        $this->assertSame('false', $byKey['5']['answer_value']);
    }

    public function test_online_reducido_all_no_falls_back_to_raw_data_ats(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'online',
            'referencia_i_answers' => null,
            'raw_data' => [
                'source' => 'online',
                'referencia_i' => [
                    'acontecimientos_traumaticos' => [
                        '1' => false,
                        '2' => false,
                        '3' => false,
                        '4' => false,
                        '5' => false,
                        '6' => false,
                    ],
                ],
            ],
        ]);

        $rows = $this->extractor->fromOnline($evaluation);
        $refI = array_filter($rows, fn ($r) => $r['instrument'] === EvaluationInstrument::ReferenciaI->value);

        $this->assertCount(6, $refI);
        $byKey = array_column(array_values($refI), null, 'question_key');
        $this->assertSame('false', $byKey['1']['answer_value']);
    }

    // ── fromOnline: referencia_iii core ─────────────────────────────────────

    public function test_online_referencia_iii_core_answers_use_column_keys(): void
    {
        $answers = [];
        for ($i = 1; $i <= 64; $i++) {
            $answers[(string) $i] = 'C';
        }

        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'online',
            'referencia_iii_answers' => $answers,
            'raw_data' => ['source' => 'online'],
        ]);

        $rows = $this->extractor->fromOnline($evaluation);
        $refIII = array_filter($rows, fn ($r) => $r['instrument'] === EvaluationInstrument::ReferenciaIII->value);

        $this->assertCount(64, $refIII);
    }

    // ── fromOnline: referencia_iii conditionals ──────────────────────────────

    public function test_online_condition_customer_service_maps_to_condition_cs(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'online',
            'referencia_iii_answers' => [],
            'raw_data' => [
                'source' => 'online',
                'referencia_iii' => ['condition_customer_service' => true],
            ],
        ]);

        $rows = $this->extractor->fromOnline($evaluation);
        $byKey = array_column($rows, null, 'question_key');

        $this->assertArrayHasKey('condition_cs', $byKey);
        $this->assertSame('true', $byKey['condition_cs']['answer_value']);
    }

    public function test_online_condition_management_maps_to_condition_mgmt(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'online',
            'referencia_iii_answers' => [],
            'raw_data' => [
                'source' => 'online',
                'referencia_iii' => ['condition_management' => false],
            ],
        ]);

        $rows = $this->extractor->fromOnline($evaluation);
        $byKey = array_column($rows, null, 'question_key');

        $this->assertArrayHasKey('condition_mgmt', $byKey);
        $this->assertSame('false', $byKey['condition_mgmt']['answer_value']);
    }

    public function test_online_conditional_question_keys_65_to_72_stored_directly(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'online',
            'referencia_iii_answers' => [],
            'raw_data' => [
                'source' => 'online',
                'referencia_iii' => [
                    '65' => 'B',
                    '72' => 'D',
                ],
            ],
        ]);

        $rows = $this->extractor->fromOnline($evaluation);
        $byKey = array_column($rows, null, 'question_key');

        $this->assertArrayHasKey('65', $byKey);
        $this->assertSame('B', $byKey['65']['answer_value']);
        $this->assertArrayHasKey('72', $byKey);
        $this->assertSame('D', $byKey['72']['answer_value']);
    }

    public function test_online_raw_data_keys_outside_65_72_range_are_ignored(): void
    {
        $evaluation = PaperEvaluation::make([
            'id' => 'test-uuid',
            'source' => 'online',
            'referencia_iii_answers' => [],
            'raw_data' => [
                'source' => 'online',
                'referencia_iii' => [
                    '64' => 'A',  // below range — ignored
                    '65' => 'B',  // in range — stored
                    '73' => 'C',  // above range — ignored
                ],
            ],
        ]);

        $rows = $this->extractor->fromOnline($evaluation);
        $keys = array_column($rows, 'question_key');

        $this->assertNotContains('64', $keys);
        $this->assertContains('65', $keys);
        $this->assertNotContains('73', $keys);
    }

    // ── resolveSlotKey ──────────────────────────────────────────────────────

    public function test_resolve_slot_key_slot_rule_returns_slot_as_string(): void
    {
        $this->assertSame('42', $this->extractor->resolveSlotKey(42, 'slot'));
    }

    public function test_resolve_slot_key_ats_subtracts_74(): void
    {
        $this->assertSame('1', $this->extractor->resolveSlotKey(75, 'ats'));
        $this->assertSame('6', $this->extractor->resolveSlotKey(80, 'ats'));
    }

    public function test_resolve_slot_key_cond_1_followup_subtracts_1(): void
    {
        $this->assertSame('65', $this->extractor->resolveSlotKey(66, 'cond_1_followup'));
        $this->assertSame('68', $this->extractor->resolveSlotKey(69, 'cond_1_followup'));
    }

    public function test_resolve_slot_key_cond_2_followup_subtracts_2(): void
    {
        $this->assertSame('69', $this->extractor->resolveSlotKey(71, 'cond_2_followup'));
        $this->assertSame('72', $this->extractor->resolveSlotKey(74, 'cond_2_followup'));
    }

    public function test_resolve_slot_key_unknown_rule_falls_back_to_slot(): void
    {
        $this->assertSame('10', $this->extractor->resolveSlotKey(10, 'unknown_rule'));
    }
}
