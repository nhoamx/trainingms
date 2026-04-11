<?php

namespace Tests\Feature;

use App\Jobs\BackfillEvaluationAnswers;
use App\Models\EvaluationAnswer;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\EvaluationAnswerExtractor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Feature tests for the BackfillEvaluationAnswers job.
 *
 * The extractor is mocked so no real extraction runs — the job's orchestration
 * logic (org scoping, chunk routing, upsert, error handling, logging) is what's under test.
 */
class BackfillEvaluationAnswersTest extends TestCase
{
    use DatabaseTransactions;

    private function scopedOrganization(): Organization
    {
        return Organization::factory()->create([
            'name' => BackfillEvaluationAnswers::SCOPED_ORGANIZATIONS[0],
        ]);
    }

    private function mockExtractor(array $paperRows = [], array $onlineRows = []): EvaluationAnswerExtractor
    {
        $mock = $this->createMock(EvaluationAnswerExtractor::class);

        $mock->method('fromPaper')->willReturn($paperRows);
        $mock->method('fromOnline')->willReturn($onlineRows);

        return $mock;
    }

    // ── Org scoping ─────────────────────────────────────────────────────────

    public function test_evaluations_outside_scoped_organizations_are_not_processed(): void
    {
        $outOfScope = Organization::factory()->create(['name' => 'UNRELATED S.A.']);

        PaperEvaluation::factory()->create([
            'organization_id' => $outOfScope->id,
            'source' => 'paper',
            'raw_data' => ['1' => ['value' => 'SI', 'mapping_section' => 'gri_binary']],
        ]);

        $extractor = $this->createMock(EvaluationAnswerExtractor::class);
        $extractor->expects($this->never())->method('fromPaper');
        $extractor->expects($this->never())->method('fromOnline');

        $this->app->instance(EvaluationAnswerExtractor::class, $extractor);

        (new BackfillEvaluationAnswers)->handle($extractor);
    }

    public function test_scoped_organization_evaluations_are_processed(): void
    {
        $org = $this->scopedOrganization();

        PaperEvaluation::factory()->create([
            'organization_id' => $org->id,
            'source' => 'paper',
            'raw_data' => ['1' => ['value' => 'SI', 'mapping_section' => 'gri_binary']],
        ]);

        $extractor = $this->createMock(EvaluationAnswerExtractor::class);
        $extractor->expects($this->once())->method('fromPaper')->willReturn([]);

        (new BackfillEvaluationAnswers)->handle($extractor);
    }

    // ── Source routing ──────────────────────────────────────────────────────

    public function test_paper_evaluation_routes_to_from_paper(): void
    {
        $org = $this->scopedOrganization();

        PaperEvaluation::factory()->create([
            'organization_id' => $org->id,
            'source' => 'paper',
            'raw_data' => ['1' => ['value' => 'SI', 'mapping_section' => 'gri_binary']],
        ]);

        $extractor = $this->createMock(EvaluationAnswerExtractor::class);
        $extractor->expects($this->once())->method('fromPaper')->willReturn([]);
        $extractor->expects($this->never())->method('fromOnline');

        (new BackfillEvaluationAnswers)->handle($extractor);
    }

    public function test_online_evaluation_routes_to_from_online(): void
    {
        $org = $this->scopedOrganization();

        PaperEvaluation::factory()->create([
            'organization_id' => $org->id,
            'source' => 'online',
            'referencia_i_answers' => ['1' => true],
            'raw_data' => ['source' => 'online'],
        ]);

        $extractor = $this->createMock(EvaluationAnswerExtractor::class);
        $extractor->expects($this->never())->method('fromPaper');
        $extractor->expects($this->once())->method('fromOnline')->willReturn([]);

        (new BackfillEvaluationAnswers)->handle($extractor);
    }

    // ── Upsert ──────────────────────────────────────────────────────────────

    public function test_rows_returned_by_extractor_are_upserted(): void
    {
        $org = $this->scopedOrganization();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $org->id,
            'source' => 'paper',
            'raw_data' => ['1' => ['value' => 'SI', 'mapping_section' => 'gri_binary']],
        ]);

        $fakeRows = [
            [
                'paper_evaluation_id' => $evaluation->id,
                'instrument' => 'referencia_i',
                'question_key' => '1',
                'answer_value' => 'SI',
                'answer_meta' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ];

        (new BackfillEvaluationAnswers)->handle($this->mockExtractor(paperRows: $fakeRows));

        $this->assertDatabaseHas('evaluation_answers', [
            'paper_evaluation_id' => $evaluation->id,
            'instrument' => 'referencia_i',
            'question_key' => '1',
            'answer_value' => 'SI',
        ]);
    }

    public function test_empty_rows_from_extractor_skips_upsert(): void
    {
        $org = $this->scopedOrganization();

        PaperEvaluation::factory()->create([
            'organization_id' => $org->id,
            'source' => 'paper',
            'raw_data' => ['1' => ['value' => 'SI', 'mapping_section' => 'gri_binary']],
        ]);

        (new BackfillEvaluationAnswers)->handle($this->mockExtractor());

        $this->assertDatabaseCount('evaluation_answers', 0);
    }

    // ── Error handling ──────────────────────────────────────────────────────

    public function test_extractor_exception_is_caught_and_logged_without_aborting(): void
    {
        $org = $this->scopedOrganization();

        // Two evaluations: the first throws, the second succeeds
        PaperEvaluation::factory()->create([
            'organization_id' => $org->id,
            'source' => 'paper',
            'raw_data' => ['1' => ['value' => 'SI', 'mapping_section' => 'gri_binary']],
        ]);

        $evaluation2 = PaperEvaluation::factory()->create([
            'organization_id' => $org->id,
            'source' => 'paper',
            'raw_data' => ['2' => ['value' => 'NO', 'mapping_section' => 'gri_binary']],
        ]);

        $fakeRows = [
            [
                'paper_evaluation_id' => $evaluation2->id,
                'instrument' => 'referencia_i',
                'question_key' => '2',
                'answer_value' => 'NO',
                'answer_meta' => null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ];

        $callCount = 0;
        $extractor = $this->createMock(EvaluationAnswerExtractor::class);
        $extractor->method('fromPaper')->willReturnCallback(function () use (&$callCount, $fakeRows) {
            $callCount++;
            if ($callCount === 1) {
                throw new \RuntimeException('OCR parse failed');
            }

            return $fakeRows;
        });

        Log::shouldReceive('error')->once();
        Log::shouldReceive('info')->once();

        (new BackfillEvaluationAnswers)->handle($extractor);

        // Second evaluation should still have been processed
        $this->assertDatabaseHas('evaluation_answers', [
            'paper_evaluation_id' => $evaluation2->id,
            'question_key' => '2',
        ]);
    }

    // ── No orgs found ───────────────────────────────────────────────────────

    public function test_logs_warning_when_no_scoped_organizations_exist(): void
    {
        // No organizations are created — scoped orgs not found
        Log::shouldReceive('warning')->once()->with('BackfillEvaluationAnswers: no scoped organizations found.');

        $extractor = $this->createMock(EvaluationAnswerExtractor::class);
        $extractor->expects($this->never())->method('fromPaper');

        (new BackfillEvaluationAnswers)->handle($extractor);
    }
}
