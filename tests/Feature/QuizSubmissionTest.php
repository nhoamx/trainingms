<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuizSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    protected Organization $organization;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create organization for testing
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'folio_organization' => '001',
        ]);

        // Create user for authentication if needed
        $this->user = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_can_submit_full_quiz_evaluation(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'is_reduced' => false,
            'is_cisneros' => false,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Hombre',
                'edad' => '30-39',
                'estado_civil' => 'Casado(a)',
                'nivel_estudios' => 'Licenciatura',
                'datos_laborales' => [
                    'puesto' => 'Analista',
                    'antiguedad' => '5-10',
                    'tipo_contrato' => 'Indefinido',
                ],
            ],
            'referencia_iii' => [
                'q1' => 'A',
                'q2' => 'B',
                'acontecimientos_traumaticos' => [
                    'q1' => true,
                    'q2' => false,
                ],
            ],
            'referencia_i' => [
                'q1' => true,
                'q2' => false,
            ],
            'custom_fields' => [],
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Quiz/Completed')
            ->has('folio')
            ->has('personalId')
        );

        // Assert PaperEvaluation was created
        $this->assertDatabaseHas('paper_evaluations', [
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_v',
            'source' => 'online',
            'processing_status' => 'completed',
        ]);

        $evaluation = PaperEvaluation::where('organization_id', $this->organization->id)->first();
        $this->assertNotNull($evaluation);
        $this->assertEquals('03', substr($evaluation->folio, 0, 2)); // Referencia V code
        $this->assertEquals('001', substr($evaluation->folio, 2, 3)); // Organization code
        $this->assertNotNull($evaluation->demographic_data);
        $this->assertNotNull($evaluation->referencia_iii_answers);
        $this->assertNotNull($evaluation->referencia_i_answers);
        $this->assertEquals($quiz->id, $evaluation->raw_data['quiz_id']);
    }

    public function test_can_submit_reduced_quiz_evaluation(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'is_reduced' => true,
            'is_cisneros' => false,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Mujer',
                'edad' => '20-29',
            ],
            'referencia_iii' => [
                'acontecimientos_traumaticos' => [
                    'q1' => false,
                    'q2' => false,
                ],
            ],
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertSuccessful();

        // Assert PaperEvaluation was created with correct type
        $this->assertDatabaseHas('paper_evaluations', [
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'completed',
        ]);

        $evaluation = PaperEvaluation::where('organization_id', $this->organization->id)->first();
        $this->assertEquals('02', substr($evaluation->folio, 0, 2)); // Reduced code
    }

    public function test_can_submit_cisneros_quiz_evaluation(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'is_reduced' => false,
            'is_cisneros' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Hombre',
                'edad' => '40-49',
            ],
            'escala_cisneros' => [
                'q1' => 'Nunca',
                'q2' => 'Raramente',
                'q3' => 'Algunas veces',
            ],
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertSuccessful();

        // Assert PaperEvaluation was created with Cisneros type
        $this->assertDatabaseHas('paper_evaluations', [
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'cisneros',
            'source' => 'online',
            'processing_status' => 'completed',
        ]);

        $evaluation = PaperEvaluation::where('organization_id', $this->organization->id)->first();
        $this->assertEquals('04', substr($evaluation->folio, 0, 2)); // Cisneros code
        $this->assertNotNull($evaluation->cisneros_answers);
    }

    public function test_can_submit_quiz_with_file_uploads(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $ineFrente = UploadedFile::fake()->image('ine_frente.jpg');
        $ineReverso = UploadedFile::fake()->image('ine_reverso.jpg');

        $submissionData = [
            'referencia_v' => [
                'sexo' => 'Mujer',
                'edad' => '30-39',
            ],
            'referencia_iii' => [
                'q1' => 'A',
            ],
            'ine_frente' => $ineFrente,
            'ine_reverso' => $ineReverso,
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertSuccessful();

        $evaluation = PaperEvaluation::where('organization_id', $this->organization->id)->first();
        $this->assertNotNull($evaluation);
        $this->assertArrayHasKey('ine_frente', $evaluation->demographic_data);
        $this->assertArrayHasKey('ine_reverso', $evaluation->demographic_data);

        // Assert files were stored
        $this->assertTrue(Storage::disk('public')->exists($evaluation->demographic_data['ine_frente']));
        $this->assertTrue(Storage::disk('public')->exists($evaluation->demographic_data['ine_reverso']));
    }

    public function test_cannot_submit_to_inactive_quiz(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => false,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => ['sexo' => 'Hombre'],
            'referencia_iii' => ['q1' => 'A'],
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Assert no PaperEvaluation was created
        $this->assertDatabaseMissing('paper_evaluations', [
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_cannot_submit_to_expired_quiz(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->subDays(1),
        ]);

        $submissionData = [
            'referencia_v' => ['sexo' => 'Mujer'],
            'referencia_iii' => ['q1' => 'B'],
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Assert no PaperEvaluation was created
        $this->assertDatabaseMissing('paper_evaluations', [
            'organization_id' => $this->organization->id,
        ]);
    }

    public function test_folio_numbers_increment_correctly(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        // Create first evaluation
        $this->post(route('quiz.submit', $quiz->id), [
            'referencia_v' => ['sexo' => 'Hombre'],
            'referencia_iii' => ['q1' => 'A'],
        ]);

        // Create second evaluation
        $this->post(route('quiz.submit', $quiz->id), [
            'referencia_v' => ['sexo' => 'Mujer'],
            'referencia_iii' => ['q1' => 'B'],
        ]);

        $evaluations = PaperEvaluation::where('organization_id', $this->organization->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(2, $evaluations);
        $this->assertEquals('0001', $evaluations[0]->personal_folio);
        $this->assertEquals('0002', $evaluations[1]->personal_folio);
    }

    public function test_conditional_answers_are_stored_separately(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => ['sexo' => 'Hombre'],
            'referencia_iii' => [
                'q1' => 'A',
                'q2' => 'B',
                'conditional_sections' => [
                    'section1' => true,
                ],
                'conditional_customer_service' => [
                    'q1' => 'C',
                ],
            ],
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertSuccessful();

        $evaluation = PaperEvaluation::where('organization_id', $this->organization->id)->first();

        // Check regular answers don't contain conditional data
        $this->assertArrayNotHasKey('conditional_sections', $evaluation->referencia_iii_answers);
        $this->assertArrayNotHasKey('conditional_customer_service', $evaluation->referencia_iii_answers);

        // Check conditional answers are stored separately
        $this->assertNotNull($evaluation->referencia_iii_conditional);
        $this->assertArrayHasKey('conditional_sections', $evaluation->referencia_iii_conditional);
        $this->assertArrayHasKey('conditional_customer_service', $evaluation->referencia_iii_conditional);
    }

    public function test_custom_fields_are_stored_in_demographic_data(): void
    {
        $quiz = Quiz::factory()->create([
            'organization_id' => $this->organization->id,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        $submissionData = [
            'referencia_v' => ['sexo' => 'Mujer'],
            'referencia_iii' => ['q1' => 'A'],
            'custom_fields' => [
                'field1' => 'Custom Value 1',
                'field2' => 'Custom Value 2',
            ],
        ];

        $response = $this->post(route('quiz.submit', $quiz->id), $submissionData);

        $response->assertSuccessful();

        $evaluation = PaperEvaluation::where('organization_id', $this->organization->id)->first();
        $this->assertArrayHasKey('custom_fields', $evaluation->demographic_data);
        $this->assertEquals('Custom Value 1', $evaluation->demographic_data['custom_fields']['field1']);
    }
}
