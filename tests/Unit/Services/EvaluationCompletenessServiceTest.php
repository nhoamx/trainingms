<?php

namespace Tests\Unit\Services;

use App\Models\EvaluationAnswer;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\EvaluationCompletenessService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EvaluationCompletenessServiceTest extends TestCase
{
    use DatabaseTransactions;

    private EvaluationCompletenessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(EvaluationCompletenessService::class);
    }

    public function test_get_missing_answers_returns_missing_question_keys_for_referencia_i(): void
    {
        $evaluation = PaperEvaluation::factory()->create([
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        $this->createAnswers($evaluation, 'referencia_i', [
            '1' => 'SI',
            '2' => 'NO',
            '4' => 'SI',
        ]);

        $missing = $this->service->getMissingAnswers($evaluation->fresh());

        $this->assertContains('3', $missing);
        $this->assertContains('14', $missing);
        $this->assertNotContains('1', $missing);
        $this->assertCount(11, $missing);
    }

    public function test_get_missing_answers_for_referencia_iii_adds_conditional_questions_when_condition_is_true(): void
    {
        $evaluation = PaperEvaluation::factory()->create([
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        $generalAnswers = [];
        for ($i = 1; $i <= 64; $i++) {
            $generalAnswers[(string) $i] = 'A';
        }

        $this->createAnswers($evaluation, 'referencia_iii', $generalAnswers + [
            'condition_cs' => 'true',
            '65' => 'B',
        ]);

        $missing = $this->service->getMissingAnswers($evaluation->fresh());

        $this->assertContains('66', $missing);
        $this->assertContains('68', $missing);
        $this->assertNotContains('69', $missing);
        $this->assertCount(3, $missing);
    }

    public function test_get_missing_answers_for_referencia_iii_ignores_conditional_questions_when_condition_is_false(): void
    {
        $evaluation = PaperEvaluation::factory()->create([
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        $generalAnswers = [];
        for ($i = 1; $i <= 64; $i++) {
            $generalAnswers[(string) $i] = 'A';
        }

        $this->createAnswers($evaluation, 'referencia_iii', $generalAnswers + [
            'condition_cs' => 'false',
            'condition_mgmt' => 'NO',
        ]);

        $missing = $this->service->getMissingAnswers($evaluation->fresh());

        $this->assertSame([], $missing);
    }

    public function test_get_completeness_for_organization_returns_expected_stats(): void
    {
        $organization = Organization::factory()->create();

        $evaluationA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        $evaluationB = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        $this->createAnswers($evaluationA, 'referencia_i', [
            '1' => 'SI',
            '2' => 'NO',
            '3' => 'SI',
            '4' => 'NO',
        ]);

        for ($i = 1; $i <= 14; $i++) {
            $fullAnswers[(string) $i] = 'SI';
        }

        $this->createAnswers($evaluationB, 'referencia_i', $fullAnswers);

        $rows = $this->service->getCompletenessForOrganization($organization);
        $rowsById = $rows->keyBy('id');

        $this->assertCount(2, $rows);
        $this->assertSame(14, $rowsById[$evaluationA->id]['expected_questions']);
        $this->assertSame(4, $rowsById[$evaluationA->id]['answered_questions']);
        $this->assertSame(28.57, $rowsById[$evaluationA->id]['completeness_percentage']);
        $this->assertSame(14, $rowsById[$evaluationB->id]['answered_questions']);
        $this->assertSame(100.0, $rowsById[$evaluationB->id]['completeness_percentage']);
    }

    public function test_get_completeness_for_work_center_returns_aggregated_stats(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluationA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        $evaluationB = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        $this->createAnswers($evaluationA, 'referencia_i', [
            '1' => 'SI',
            '2' => 'NO',
        ]);

        $this->createAnswers($evaluationB, 'referencia_i', [
            '1' => 'SI',
            '2' => 'NO',
            '3' => 'SI',
            '4' => 'NO',
            '5' => 'SI',
            '6' => 'NO',
            '7' => 'SI',
        ]);

        $stats = $this->service->getCompletenessForWorkCenter($workCenter);

        $this->assertSame(2, $stats['evaluations']);
        $this->assertSame(28, $stats['expected_answers']);
        $this->assertSame(9, $stats['answered_answers']);
        $this->assertSame(19, $stats['missing_answers']);
        $this->assertSame(32.15, $stats['average_completeness_percentage']);
    }

    public function test_get_unanswered_by_instrument_returns_only_incomplete_rows_for_requested_instrument(): void
    {
        $organization = Organization::factory()->create();

        $incomplete = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        $complete = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        $otherInstrument = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
        ]);

        $this->createAnswers($incomplete, 'referencia_i', [
            '1' => 'SI',
        ]);

        $completeAnswers = [];
        for ($i = 1; $i <= 14; $i++) {
            $completeAnswers[(string) $i] = 'NO';
        }

        $this->createAnswers($complete, 'referencia_i', $completeAnswers);
        $this->createAnswers($otherInstrument, 'referencia_iii', ['1' => 'A']);

        $rows = $this->service->getUnansweredByInstrument($organization, 'referencia_i');

        $this->assertCount(1, $rows);
        $this->assertSame((string) $incomplete->id, $rows[0]['id']);
        $this->assertSame('referencia_i', $rows[0]['evaluation_type']);
    }

    /**
     * @param  array<string, string|null>  $answers
     */
    private function createAnswers(PaperEvaluation $evaluation, string $instrument, array $answers): void
    {
        foreach ($answers as $questionKey => $answerValue) {
            EvaluationAnswer::query()->create([
                'paper_evaluation_id' => $evaluation->id,
                'instrument' => $instrument,
                'question_key' => $questionKey,
                'answer_value' => $answerValue,
                'answer_meta' => null,
            ]);
        }
    }
}
