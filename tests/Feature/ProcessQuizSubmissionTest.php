<?php

namespace Tests\Feature;

use App\Jobs\ProcessQuizSubmission;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Quiz;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProcessQuizSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_hybrid_submission_syncs_normalized_answers_into_existing_paper_evaluation(): void
    {
        $organization = Organization::factory()->create();
        $quiz = Quiz::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'paper',
            'evaluation_type' => 'referencia_iii',
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $submissionStatus = SubmissionStatus::create([
            'folio' => $paperEvaluation->folio,
            'personal_id' => $paperEvaluation->personal_folio,
            'organization_id' => $organization->id,
            'quiz_id' => $quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => $paperEvaluation->evaluation_type,
                'paper_evaluation_id' => $paperEvaluation->id,
                'referencia_i' => [
                    '1' => true,
                    '2' => false,
                ],
                'referencia_iii' => [
                    '1' => 'B',
                    '2' => 'C',
                    'condition_customer_service' => true,
                    '65' => 'D',
                ],
                'is_hybrid' => true,
            ],
        ]);

        ProcessQuizSubmission::dispatchSync($submissionStatus->id, false);

        $this->assertDatabaseHas('evaluation_answers', [
            'paper_evaluation_id' => $paperEvaluation->id,
            'instrument' => 'referencia_i',
            'question_key' => '1',
            'answer_value' => 'true',
        ]);

        $this->assertDatabaseHas('evaluation_answers', [
            'paper_evaluation_id' => $paperEvaluation->id,
            'instrument' => 'referencia_iii',
            'question_key' => '1',
            'answer_value' => 'B',
        ]);

        $this->assertDatabaseHas('evaluation_answers', [
            'paper_evaluation_id' => $paperEvaluation->id,
            'instrument' => 'referencia_iii',
            'question_key' => 'condition_cs',
            'answer_value' => 'true',
        ]);
    }
}
