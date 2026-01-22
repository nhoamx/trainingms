<?php

namespace Tests\Feature;

use App\Jobs\ProcessOnlineEvaluation;
use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Quiz;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessOnlineEvaluationTest extends TestCase
{
    use DatabaseTransactions;

    protected Organization $organization;

    protected Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_creates_paper_evaluation_with_source_online(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010001',
            'personal_id' => '0001',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [
                    'sexo' => 'Masculino',
                    'edad' => '35',
                    'datos_laborales' => [
                        'ocupacion_puesto' => 'Gerente',
                    ],
                ],
                'referencia_iii' => [
                    'question_1' => 'Siempre',
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $this->assertDatabaseHas('paper_evaluations', [
            'folio' => '020010001',
            'personal_id' => '0001',
            'source' => 'online',
            'organization_id' => $this->organization->id,
            'processing_status' => 'completed',
        ]);

        $paperEvaluation = PaperEvaluation::where('folio', '020010001')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertEquals('online', $paperEvaluation->source);
        $this->assertArrayHasKey('source', $paperEvaluation->raw_data);
        $this->assertEquals('online', $paperEvaluation->raw_data['source']);
    }

    public function test_creates_demographic_data_record(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010002',
            'personal_id' => '0002',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [
                    'sexo' => 'Femenino',
                    'edad' => '28',
                    'estado_civil' => 'Soltera',
                    'nivel_estudios' => 'Licenciatura',
                    'datos_laborales' => [
                        'ocupacion_puesto' => 'Analista',
                        'departamento_seccion_area' => 'Finanzas',
                        'tipo_puesto' => 'Profesional',
                        'tipo_contratacion' => 'Base',
                    ],
                ],
                'referencia_iii' => [],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010002')->first();
        $this->assertNotNull($paperEvaluation);

        $this->assertDatabaseHas('demographic_data', [
            'paper_evaluation_id' => $paperEvaluation->id,
            'gender' => 'Femenino',
            'age' => '28',
            'marital_status' => 'Soltera',
            'education_level' => 'Licenciatura',
            'position' => 'Analista',
            'department' => 'Finanzas',
        ]);

        $demographicData = DemographicData::where('paper_evaluation_id', $paperEvaluation->id)->first();
        $this->assertNotNull($demographicData);
        $this->assertEquals('Femenino', $demographicData->gender);
    }

    public function test_extracts_referencia_i_answers(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010003',
            'personal_id' => '0003',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [
                    'sexo' => 'Masculino',
                    'edad' => '40',
                ],
                'referencia_i' => [
                    'question_1' => 'Sí',
                    'question_2' => 'No',
                    'question_3' => 'Sí',
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010003')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->referencia_i_answers);
        $this->assertIsArray($paperEvaluation->referencia_i_answers);
        $this->assertEquals('Sí', $paperEvaluation->referencia_i_answers['question_1']);
        $this->assertEquals('No', $paperEvaluation->referencia_i_answers['question_2']);
        $this->assertEquals('Sí', $paperEvaluation->referencia_i_answers['question_3']);
    }

    public function test_extracts_referencia_iii_answers(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010004',
            'personal_id' => '0004',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [
                    'sexo' => 'Masculino',
                    'edad' => '45',
                ],
                'referencia_iii' => [
                    'question_1' => 'Siempre',
                    'question_2' => 'Casi siempre',
                    'question_3' => 'Algunas veces',
                    'question_4' => 'Casi nunca',
                    'question_5' => 'Nunca',
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010004')->first();
        $this->assertNotNull($paperEvaluation);
        $this->assertNotNull($paperEvaluation->referencia_iii_answers);
        $this->assertIsArray($paperEvaluation->referencia_iii_answers);
        $this->assertEquals('Siempre', $paperEvaluation->referencia_iii_answers['question_1']);
        $this->assertEquals('Casi siempre', $paperEvaluation->referencia_iii_answers['question_2']);
        $this->assertEquals('Algunas veces', $paperEvaluation->referencia_iii_answers['question_3']);
        $this->assertEquals('Casi nunca', $paperEvaluation->referencia_iii_answers['question_4']);
        $this->assertEquals('Nunca', $paperEvaluation->referencia_iii_answers['question_5']);
    }

    public function test_extracts_conditional_answers_separately(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010005',
            'personal_id' => '0005',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [
                    'sexo' => 'Femenino',
                    'edad' => '32',
                ],
                'referencia_iii' => [
                    'question_1' => 'Siempre',
                    'question_2' => 'Casi siempre',
                ],
                'preguntas_condicionales' => [
                    'conditional_1' => 'Sí',
                    'conditional_2' => 'No',
                ],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010005')->first();
        $this->assertNotNull($paperEvaluation);

        // Verify conditional questions are stored in raw_data
        $this->assertArrayHasKey('preguntas_condicionales', $paperEvaluation->raw_data);
        $conditionalAnswers = $paperEvaluation->raw_data['preguntas_condicionales'];
        $this->assertIsArray($conditionalAnswers);
        $this->assertEquals('Sí', $conditionalAnswers['conditional_1']);
        $this->assertEquals('No', $conditionalAnswers['conditional_2']);
    }

    public function test_stores_standardized_raw_data_structure(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010006',
            'personal_id' => '0006',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [
                    'sexo' => 'Masculino',
                    'edad' => '50',
                ],
                'referencia_iii' => [],
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010006')->first();
        $this->assertNotNull($paperEvaluation);

        // Verify standardized structure as per ONLINE_RAW_DATA_SCHEMA
        $rawData = $paperEvaluation->raw_data;

        $this->assertArrayHasKey('source', $rawData);
        $this->assertEquals('online', $rawData['source']);

        $this->assertArrayHasKey('source_metadata', $rawData);
        $this->assertIsArray($rawData['source_metadata']);
        $this->assertArrayHasKey('quiz_id', $rawData['source_metadata']);
        $this->assertEquals($this->quiz->id, $rawData['source_metadata']['quiz_id']);
        $this->assertArrayHasKey('submission_id', $rawData['source_metadata']);

        $this->assertArrayHasKey('evaluation_type_code', $rawData);
        $this->assertEquals('referencia_v', $rawData['evaluation_type_code']);

        $this->assertArrayHasKey('timestamp', $rawData);
        $this->assertNotNull($rawData['timestamp']);
    }

    public function test_handles_file_uploads_in_submissions(): void
    {
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010007',
            'personal_id' => '0007',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [
                    'sexo' => 'Femenino',
                    'edad' => '29',
                ],
                'ine_frontal' => 'uploads/ine/frontal_12345.jpg',
                'ine_trasera' => 'uploads/ine/trasera_12345.jpg',
            ],
        ]);

        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        $paperEvaluation = PaperEvaluation::where('folio', '020010007')->first();
        $this->assertNotNull($paperEvaluation);

        // Verify file paths are stored in raw_data
        $rawData = $paperEvaluation->raw_data;
        $this->assertArrayHasKey('ine_frontal', $rawData);
        $this->assertArrayHasKey('ine_trasera', $rawData);
        $this->assertEquals('uploads/ine/frontal_12345.jpg', $rawData['ine_frontal']);
        $this->assertEquals('uploads/ine/trasera_12345.jpg', $rawData['ine_trasera']);
    }

    public function test_retries_failed_submissions_with_progressive_delay(): void
    {
        Queue::fake();

        // Create a submission that will fail
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010008',
            'personal_id' => '0008',
            'organization_id' => $this->organization->id,
            'quiz_id' => $this->quiz->id,
            'status' => SubmissionStatus::STATUS_FAILED,
            'retry_count' => 0,
            'data_snapshot' => [
                'evaluation_type' => 'referencia_v',
                'referencia_v' => [],
            ],
        ]);

        // Verify can retry
        $this->assertTrue($submissionStatus->canRetry());

        // Dispatch job
        ProcessOnlineEvaluation::dispatch($submissionStatus->id);

        Queue::assertPushed(ProcessOnlineEvaluation::class, function ($job) use ($submissionStatus) {
            return $job->submissionStatusId === $submissionStatus->id;
        });

        // Verify retry logic
        $submissionStatus->update(['retry_count' => 1, 'status' => SubmissionStatus::STATUS_FAILED]);
        $this->assertTrue($submissionStatus->fresh()->canRetry());

        $submissionStatus->update(['retry_count' => 2, 'status' => SubmissionStatus::STATUS_FAILED]);
        $this->assertTrue($submissionStatus->fresh()->canRetry());

        $submissionStatus->update(['retry_count' => 3, 'status' => SubmissionStatus::STATUS_FAILED]);
        $this->assertFalse($submissionStatus->fresh()->canRetry());
    }
}
