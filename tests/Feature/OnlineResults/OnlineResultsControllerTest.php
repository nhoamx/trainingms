<?php

namespace Tests\Feature\OnlineResults;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OnlineResultsControllerTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    private User $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles using the seeder
        $this->seed(\Database\Seeders\RolesSeeder::class);

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'folio_organization' => '001',
        ]);
    }

    /** @test */
    public function it_displays_online_evaluations_list(): void
    {
        // Create some online evaluations
        PaperEvaluation::create([
            'folio' => '020010001',
            'evaluation_type_code' => '02',
            'organization_code' => '001',
            'personal_folio' => '0001',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'demographic_data' => [
                'sexo' => 'Masculino',
                'edad' => '25 - 29',
                'datos_laborales' => [
                    'ocupacion_puesto' => 'Desarrollador',
                ],
            ],
            'referencia_iii_answers' => ['q1' => 'answer1'],
            'raw_data' => [
                'quiz_id' => 1,
                'quiz_name' => 'Test Quiz',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organization.online-results', ['id' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('OnlineResults/List')
            ->has('organization')
            ->has('evaluations', 1)
            ->where('organization.id', $this->organization->id)
            ->where('organization.name', 'Test Organization')
        );
    }

    /** @test */
    public function it_displays_empty_state_when_no_online_evaluations(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('organization.online-results', ['id' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('OnlineResults/List')
            ->has('evaluations', 0)
        );
    }

    /** @test */
    public function it_displays_online_evaluation_detail(): void
    {
        $evaluation = PaperEvaluation::create([
            'folio' => '030010001',
            'evaluation_type_code' => '03',
            'organization_code' => '001',
            'personal_folio' => '0001',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_v',
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'demographic_data' => [
                'sexo' => 'Femenino',
                'edad' => '30 - 34',
                'estado_civil' => 'Casado',
                'datos_laborales' => [
                    'tipo_puesto' => 'Gerencial',
                    'experiencia' => [
                        'tiempo_puesto_actual' => '5 años',
                        'tiempo_experiencia_laboral' => '10 años',
                    ],
                ],
            ],
            'referencia_i_answers' => [
                'pregunta_1' => true,
                'pregunta_2' => false,
            ],
            'referencia_iii_conditional' => [
                'acontecimientos_traumaticos' => [
                    'accidente' => true,
                    'asalto' => false,
                ],
            ],
            'raw_data' => [
                'quiz_id' => 1,
                'quiz_name' => 'Test Quiz Completo',
                'custom_fields' => [
                    '1' => 'Custom Value 1',
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organization.online-results.show', [
                'organizationId' => $this->organization->id,
                'id' => $evaluation->id,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('OnlineResults/Detail')
            ->has('organization')
            ->has('evaluation')
            ->has('answers')
            ->where('evaluation.folio', '030010001')
            ->where('evaluation.has_referencia_i', true)
            ->where('evaluation.has_referencia_iii', true)
            ->where('evaluation.has_referencia_v', true)
        );
    }

    /** @test */
    public function it_handles_empty_referencia_i_answers_correctly(): void
    {
        $evaluation = PaperEvaluation::create([
            'folio' => '020010002',
            'evaluation_type_code' => '02',
            'organization_code' => '001',
            'personal_folio' => '0002',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '25 - 29'],
            'referencia_i_answers' => [], // Empty array
            'referencia_iii_conditional' => ['q1' => true],
            'raw_data' => ['quiz_id' => 1, 'quiz_name' => 'Test Quiz'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organization.online-results.show', [
                'organizationId' => $this->organization->id,
                'id' => $evaluation->id,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('OnlineResults/Detail')
            ->where('evaluation.has_referencia_i', false) // Should be false for empty array
        );
    }

    /** @test */
    public function it_only_shows_completed_online_evaluations(): void
    {
        // Create completed evaluation
        PaperEvaluation::create([
            'folio' => '020010001',
            'evaluation_type_code' => '02',
            'organization_code' => '001',
            'personal_folio' => '0001',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'demographic_data' => ['sexo' => 'Masculino'],
            'raw_data' => ['quiz_id' => 1, 'quiz_name' => 'Quiz 1'],
        ]);

        // Create pending evaluation (should not appear)
        PaperEvaluation::create([
            'folio' => '020010002',
            'evaluation_type_code' => '02',
            'organization_code' => '001',
            'personal_folio' => '0002',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Femenino'],
            'raw_data' => ['quiz_id' => 2, 'quiz_name' => 'Quiz 2'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organization.online-results', ['id' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('evaluations', 1) // Only completed evaluation
        );
    }

    /** @test */
    public function it_does_not_show_paper_evaluations_in_online_list(): void
    {
        // Create online evaluation
        PaperEvaluation::create([
            'folio' => '020010001',
            'evaluation_type_code' => '02',
            'organization_code' => '001',
            'personal_folio' => '0001',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'demographic_data' => ['sexo' => 'Masculino'],
            'raw_data' => ['quiz_id' => 1, 'quiz_name' => 'Online Quiz'],
        ]);

        // Create paper evaluation (should not appear)
        PaperEvaluation::create([
            'folio' => '020010002',
            'evaluation_type_code' => '02',
            'organization_code' => '001',
            'personal_folio' => '0002',
            'organization_id' => $this->organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'demographic_data' => ['sexo' => 'Femenino'],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organization.online-results', ['id' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('evaluations', 1) // Only online evaluation
        );
    }
}
