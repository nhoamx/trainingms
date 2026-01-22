<?php

namespace Tests\Feature;

use App\Jobs\ProcessOnlineEvaluation;
use App\Models\Organization;
use App\Models\Quiz;
use App\Models\SubmissionStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuizControllerTest extends TestCase
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
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function test_submit_creates_submission_status_record(): void
    {
        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Masculino',
                'edad' => '30',
                'estado_civil' => 'Soltero',
                'nivel_estudios' => 'Licenciatura',
                'datos_laborales' => [
                    'ocupacion_puesto' => 'Analista',
                    'departamento_seccion_area' => 'IT',
                    'tipo_puesto' => 'Profesional',
                    'tipo_contratacion' => 'Base',
                ],
            ],
            'referencia_iii' => [
                'question_1' => 'Siempre',
                'question_2' => 'Casi siempre',
            ],
            'referencia_i' => [
                'question_1' => 'Sí',
                'question_2' => 'No',
            ],
        ];

        $response = $this->post(route('quiz.submit', $this->quiz), $submissionData);

        $response->assertStatus(200);

        // Verify SubmissionStatus was created
        $this->assertDatabaseHas('submission_statuses', [
            'quiz_id' => $this->quiz->id,
            'organization_id' => $this->organization->id,
            'status' => SubmissionStatus::STATUS_PENDING,
        ]);

        $submissionStatus = SubmissionStatus::where('quiz_id', $this->quiz->id)->first();
        $this->assertNotNull($submissionStatus);
        $this->assertEquals(SubmissionStatus::STATUS_PENDING, $submissionStatus->status);
        $this->assertNotNull($submissionStatus->folio);
        $this->assertNotNull($submissionStatus->personal_id);
        $this->assertIsArray($submissionStatus->data_snapshot);
        $this->assertArrayHasKey('referencia_v', $submissionStatus->data_snapshot);
        $this->assertArrayHasKey('referencia_iii', $submissionStatus->data_snapshot);
    }

    public function test_submit_dispatches_process_online_evaluation_job(): void
    {
        Queue::fake();

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Femenino',
                'edad' => '28',
                'datos_laborales' => [
                    'ocupacion_puesto' => 'Coordinadora',
                ],
            ],
            'referencia_iii' => [
                'question_1' => 'Nunca',
            ],
        ];

        $response = $this->post(route('quiz.submit', $this->quiz), $submissionData);

        $response->assertStatus(200);

        // Verify job was dispatched
        Queue::assertPushed(ProcessOnlineEvaluation::class, function ($job) {
            return $job->submissionStatusId > 0;
        });

        // Verify the correct submission was queued
        $submissionStatus = SubmissionStatus::where('quiz_id', $this->quiz->id)->first();
        $this->assertNotNull($submissionStatus);

        Queue::assertPushed(ProcessOnlineEvaluation::class, function ($job) use ($submissionStatus) {
            return $job->submissionStatusId === $submissionStatus->id;
        });
    }

    public function test_submit_returns_completed_page_immediately(): void
    {
        Queue::fake();

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Masculino',
                'edad' => '42',
                'datos_laborales' => [
                    'ocupacion_puesto' => 'Gerente',
                ],
            ],
            'referencia_iii' => [
                'question_1' => 'Siempre',
            ],
        ];

        $response = $this->post(route('quiz.submit', $this->quiz), $submissionData);

        $response->assertStatus(200);

        // Verify Inertia response with completed page
        $response->assertInertia(fn ($page) => $page
            ->component('Quiz/Completed')
            ->has('quiz')
            ->has('folio')
            ->has('personalId')
            ->where('message', 'Gracias por completar la evaluación. Sus respuestas han sido enviadas exitosamente.')
        );

        // Verify quiz information is passed
        $response->assertInertia(fn ($page) => $page
            ->where('quiz.id', $this->quiz->id)
            ->where('quiz.name', $this->quiz->name)
            ->has('quiz.organization')
        );
    }

    public function test_submit_uploads_ine_files_before_job_dispatch(): void
    {
        Queue::fake();
        Storage::fake('public');

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Femenino',
                'edad' => '35',
                'datos_laborales' => [
                    'ocupacion_puesto' => 'Directora',
                ],
            ],
            'referencia_iii' => [
                'question_1' => 'Casi siempre',
            ],
            'ine_frente' => UploadedFile::fake()->image('ine_frente.jpg'),
            'ine_reverso' => UploadedFile::fake()->image('ine_reverso.jpg'),
        ];

        $response = $this->post(route('quiz.submit', $this->quiz), $submissionData);

        $response->assertStatus(200);

        // Verify files were uploaded before job dispatch
        $submissionStatus = SubmissionStatus::where('quiz_id', $this->quiz->id)->first();
        $this->assertNotNull($submissionStatus);

        // Check that file paths are in data_snapshot
        $this->assertArrayHasKey('referencia_v', $submissionStatus->data_snapshot);
        $this->assertArrayHasKey('ine_frente', $submissionStatus->data_snapshot['referencia_v']);
        $this->assertArrayHasKey('ine_reverso', $submissionStatus->data_snapshot['referencia_v']);

        // Verify files exist in storage
        $ineFrentePath = $submissionStatus->data_snapshot['referencia_v']['ine_frente'];
        $ineReversoPath = $submissionStatus->data_snapshot['referencia_v']['ine_reverso'];

        Storage::disk('public')->assertExists($ineFrentePath);
        Storage::disk('public')->assertExists($ineReversoPath);

        // Verify job was dispatched after file upload
        Queue::assertPushed(ProcessOnlineEvaluation::class);
    }

    public function test_submit_rejects_expired_quiz(): void
    {
        $expiredQuiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->subDays(1),
        ]);

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Masculino',
                'edad' => '40',
            ],
            'referencia_iii' => [],
        ];

        $response = $this->post(route('quiz.submit', $expiredQuiz), $submissionData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'El examen no está disponible o ha expirado');

        // Verify no SubmissionStatus was created
        $this->assertDatabaseMissing('submission_statuses', [
            'quiz_id' => $expiredQuiz->id,
        ]);
    }

    public function test_submit_rejects_inactive_quiz(): void
    {
        $inactiveQuiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Femenino',
                'edad' => '25',
            ],
            'referencia_iii' => [],
        ];

        $response = $this->post(route('quiz.submit', $inactiveQuiz), $submissionData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'El examen no está disponible o ha expirado');

        // Verify no SubmissionStatus was created
        $this->assertDatabaseMissing('submission_statuses', [
            'quiz_id' => $inactiveQuiz->id,
        ]);
    }

    public function test_submit_stores_custom_fields_in_snapshot(): void
    {
        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Masculino',
                'edad' => '33',
            ],
            'referencia_iii' => [],
            'custom_fields' => [
                'employee_number' => '12345',
                'department_code' => 'IT-001',
                'manager_name' => 'Juan Pérez',
            ],
        ];

        $response = $this->post(route('quiz.submit', $this->quiz), $submissionData);

        $response->assertStatus(200);

        $submissionStatus = SubmissionStatus::where('quiz_id', $this->quiz->id)->first();
        $this->assertNotNull($submissionStatus);
        $this->assertArrayHasKey('custom_fields', $submissionStatus->data_snapshot);
        $this->assertEquals('12345', $submissionStatus->data_snapshot['custom_fields']['employee_number']);
        $this->assertEquals('IT-001', $submissionStatus->data_snapshot['custom_fields']['department_code']);
        $this->assertEquals('Juan Pérez', $submissionStatus->data_snapshot['custom_fields']['manager_name']);
    }

    public function test_submit_handles_reduced_quiz_type(): void
    {
        $reducedQuiz = Quiz::factory()->reduced()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Femenino',
                'edad' => '27',
            ],
            'referencia_iii' => [
                'question_1' => 'Siempre',
            ],
        ];

        $response = $this->post(route('quiz.submit', $reducedQuiz), $submissionData);

        $response->assertStatus(200);

        $submissionStatus = SubmissionStatus::where('quiz_id', $reducedQuiz->id)->first();
        $this->assertNotNull($submissionStatus);
        $this->assertArrayHasKey('quiz_type', $submissionStatus->data_snapshot);
        $this->assertEquals('reducido', $submissionStatus->data_snapshot['quiz_type']);
    }

    public function test_submit_handles_cisneros_quiz_type(): void
    {
        $cisnerosQuiz = Quiz::factory()->cisneros()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Masculino',
                'edad' => '38',
            ],
            'escala_cisneros' => [
                'question_1' => 'Nunca',
                'question_2' => 'Pocas veces al año',
            ],
        ];

        $response = $this->post(route('quiz.submit', $cisnerosQuiz), $submissionData);

        $response->assertStatus(200);

        $submissionStatus = SubmissionStatus::where('quiz_id', $cisnerosQuiz->id)->first();
        $this->assertNotNull($submissionStatus);
        $this->assertArrayHasKey('quiz_type', $submissionStatus->data_snapshot);
        $this->assertEquals('cisneros', $submissionStatus->data_snapshot['quiz_type']);
        $this->assertArrayHasKey('escala_cisneros', $submissionStatus->data_snapshot);
    }
}
