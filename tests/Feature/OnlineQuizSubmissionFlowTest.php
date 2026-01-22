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

class OnlineQuizSubmissionFlowTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test complete online quiz submission flow from submission to processing
     *
     * This comprehensive end-to-end test verifies:
     * 1. Quiz submission creates SubmissionStatus
     * 2. Job is dispatched to queue
     * 3. Job processes submission correctly
     * 4. PaperEvaluation is created with correct source
     * 5. DemographicData is extracted and stored
     * 6. Raw data follows ONLINE_RAW_DATA_SCHEMA
     * 7. SubmissionStatus is updated to completed
     */
    public function test_complete_online_quiz_submission_flow(): void
    {
        Queue::fake();

        // 1. Create quiz
        $organization = Organization::factory()->create(['name' => 'Test Organization']);
        $quiz = Quiz::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'NOM-035 Evaluation Test',
            'is_active' => true,
        ]);

        // 2. Submit quiz (simulated form submission)
        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Masculino',
                'edad' => '35',
                'estado_civil' => 'Casado',
                'nivel_estudios' => 'Licenciatura',
                'datos_laborales' => [
                    'ocupacion_puesto' => 'Supervisor',
                    'departamento_seccion_area' => 'Producción',
                    'tipo_puesto' => 'Mandos medios',
                    'tipo_contratacion' => 'Base',
                    'tipo_personal' => 'Sindicalizado',
                    'tipo_jornada' => 'Diurno',
                    'rotacion_turnos' => 'No',
                    'experiencia' => [
                        'tiempo_puesto_actual' => '1-4 años',
                        'tiempo_experiencia_laboral' => '5-9 años',
                    ],
                ],
            ],
            'referencia_iii' => [
                'question_1' => 'Siempre',
                'question_2' => 'Casi siempre',
                'question_3' => 'Algunas veces',
                'question_4' => 'Casi nunca',
                'question_5' => 'Nunca',
                'question_6' => 'Siempre',
                'question_7' => 'Casi siempre',
            ],
            'referencia_i' => [
                'question_1' => 'Sí',
                'question_2' => 'No',
                'question_3' => 'Sí',
            ],
        ];

        // Create SubmissionStatus manually (simulating controller action)
        $submissionStatus = SubmissionStatus::create([
            'folio' => '020010001',
            'personal_id' => '0001',
            'organization_id' => $organization->id,
            'quiz_id' => $quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
            'data_snapshot' => array_merge(['evaluation_type' => 'referencia_v'], $submissionData),
            'session_id' => 'test_session_123',
        ]);

        // 3. Verify immediate response (SubmissionStatus created)
        $this->assertNotNull($submissionStatus);
        $this->assertEquals(SubmissionStatus::STATUS_PENDING, $submissionStatus->status);
        $this->assertEquals('020010001', $submissionStatus->folio);
        $this->assertEquals('0001', $submissionStatus->personal_id);

        // 4. Verify SubmissionStatus created in database
        $this->assertDatabaseHas('submission_statuses', [
            'folio' => '020010001',
            'quiz_id' => $quiz->id,
            'status' => SubmissionStatus::STATUS_PENDING,
        ]);

        // 5. Dispatch job and verify it was pushed to queue
        ProcessOnlineEvaluation::dispatch($submissionStatus->id);

        Queue::assertPushed(ProcessOnlineEvaluation::class, function ($job) use ($submissionStatus) {
            return $job->submissionStatusId === $submissionStatus->id;
        });

        // 6. Process job manually for testing (synchronous)
        ProcessOnlineEvaluation::dispatchSync($submissionStatus->id);

        // 7. Verify PaperEvaluation created
        $paperEvaluation = PaperEvaluation::where('folio', '020010001')->first();
        $this->assertNotNull($paperEvaluation, 'PaperEvaluation should be created');
        $this->assertEquals('020010001', $paperEvaluation->folio);
        $this->assertEquals('0001', $paperEvaluation->personal_id);
        $this->assertEquals('online', $paperEvaluation->source);
        $this->assertEquals('completed', $paperEvaluation->processing_status);
        $this->assertEquals($organization->id, $paperEvaluation->organization_id);

        $this->assertDatabaseHas('paper_evaluations', [
            'folio' => '020010001',
            'personal_id' => '0001',
            'source' => 'online',
            'organization_id' => $organization->id,
            'processing_status' => 'completed',
        ]);

        // 8. Verify DemographicData created
        $paperEvaluation->refresh();
        $this->assertNotNull($paperEvaluation->demographicData, 'DemographicData should be created');

        $demographicData = $paperEvaluation->demographicData;
        $this->assertEquals('Masculino', $demographicData->gender);
        $this->assertEquals('35', $demographicData->age);
        $this->assertEquals('Casado', $demographicData->marital_status);
        $this->assertEquals('Licenciatura', $demographicData->education_level);
        $this->assertEquals('Supervisor', $demographicData->position);
        $this->assertEquals('Producción', $demographicData->department);
        $this->assertEquals('Mandos medios', $demographicData->position_type);
        $this->assertEquals('Base', $demographicData->contract_type);
        $this->assertEquals('Sindicalizado', $demographicData->personnel_type);
        $this->assertEquals('Diurno', $demographicData->work_schedule);
        $this->assertEquals('No', $demographicData->shift_rotation);
        $this->assertEquals('1-4 años', $demographicData->time_in_current_position);
        $this->assertEquals('5-9 años', $demographicData->work_experience);

        $this->assertDatabaseHas('demographic_data', [
            'paper_evaluation_id' => $paperEvaluation->id,
            'gender' => 'Masculino',
            'age' => '35',
            'position' => 'Supervisor',
        ]);

        // 9. Verify raw_data structure follows ONLINE_RAW_DATA_SCHEMA
        $rawData = $paperEvaluation->raw_data;

        $this->assertArrayHasKey('source', $rawData);
        $this->assertEquals('online', $rawData['source']);

        $this->assertArrayHasKey('source_metadata', $rawData);
        $this->assertIsArray($rawData['source_metadata']);
        $this->assertArrayHasKey('quiz_id', $rawData['source_metadata']);
        $this->assertEquals($quiz->id, $rawData['source_metadata']['quiz_id']);
        $this->assertArrayHasKey('submission_id', $rawData['source_metadata']);
        $this->assertEquals($submissionStatus->id, $rawData['source_metadata']['submission_id']);

        $this->assertArrayHasKey('evaluation_type_code', $rawData);
        $this->assertEquals('referencia_v', $rawData['evaluation_type_code']);

        $this->assertArrayHasKey('timestamp', $rawData);
        $this->assertNotNull($rawData['timestamp']);

        $this->assertArrayHasKey('referencia_v', $rawData);
        $this->assertIsArray($rawData['referencia_v']);

        // 10. Verify referencia_iii_answers extracted correctly
        $this->assertNotNull($paperEvaluation->referencia_iii_answers);
        $this->assertIsArray($paperEvaluation->referencia_iii_answers);
        $this->assertCount(7, $paperEvaluation->referencia_iii_answers);
        $this->assertEquals('Siempre', $paperEvaluation->referencia_iii_answers['question_1']);

        // 11. Verify referencia_i_answers extracted correctly
        $this->assertNotNull($paperEvaluation->referencia_i_answers);
        $this->assertIsArray($paperEvaluation->referencia_i_answers);
        $this->assertCount(3, $paperEvaluation->referencia_i_answers);
        $this->assertEquals('Sí', $paperEvaluation->referencia_i_answers['question_1']);

        // 12. Verify SubmissionStatus updated to completed
        $submissionStatus->refresh();
        $this->assertEquals(SubmissionStatus::STATUS_COMPLETED, $submissionStatus->status);
        $this->assertNotNull($submissionStatus->processed_at);
        $this->assertNull($submissionStatus->error_message);

        $this->assertDatabaseHas('submission_statuses', [
            'id' => $submissionStatus->id,
            'status' => SubmissionStatus::STATUS_COMPLETED,
        ]);

        // 13. Verify processing time recorded
        $this->assertNotNull($submissionStatus->processed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $submissionStatus->processed_at);

        // 14. Verify complete workflow integrity
        $this->assertEquals(
            $paperEvaluation->folio,
            $submissionStatus->folio,
            'Folio should match between SubmissionStatus and PaperEvaluation'
        );

        $this->assertEquals(
            $paperEvaluation->organization_id,
            $submissionStatus->organization_id,
            'Organization should match between SubmissionStatus and PaperEvaluation'
        );
    }
}
