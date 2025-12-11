<?php

namespace Tests\Feature;

use App\Models\DemographicData;
use App\Models\EvaluationComment;
use App\Models\EvaluationCustomField;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ClimaExportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $regularUser;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->regularUser->assignRole('organization');
    }

    public function test_get_clima_export_options_route_exists(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('organization.clima.export-options', $this->organization->id));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'evaluationTypes',
                'demographics',
                'customFields',
                'factors',
            ]);
    }

    public function test_get_clima_export_options_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->getJson(route('organization.clima.export-options', $this->organization->id));

        $response->assertStatus(403);
    }

    public function test_get_clima_export_options_returns_demographics_from_likert_evaluations(): void
    {
        // Create Likert evaluation with demographic data
        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        // Create demographic data
        DemographicData::create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => 'Masculino',
            'work_schedule' => 'Matutino',
            'contract_type' => 'Permanente',
            'position' => 'Operador',
            'department' => 'Producción',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('organization.clima.export-options', $this->organization->id));

        $response->assertStatus(200)
            ->assertJsonFragment(['evaluationTypes' => ['likert']]);
    }

    public function test_export_multi_sheet_route_exists(): void
    {
        // Create Likert evaluation
        PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [
                    [
                        'id' => 1,
                        'filters' => [],
                        'description' => 'Todos',
                    ],
                ],
                'factors' => ['Entorno Laboral Seguro', 'Seguridad Laboral'],
            ]);

        $response->assertStatus(200);
    }

    public function test_export_multi_sheet_requires_admin_role(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [
                    [
                        'id' => 1,
                        'filters' => [],
                        'description' => 'Todos',
                    ],
                ],
                'factors' => [],
            ]);

        $response->assertStatus(403);
    }

    public function test_export_multi_sheet_requires_at_least_one_combination(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [],
                'factors' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Debe agregar al menos una combinación']);
    }

    public function test_export_multi_sheet_limits_to_four_combinations(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [
                    ['id' => 1, 'filters' => [], 'description' => '1'],
                    ['id' => 2, 'filters' => [], 'description' => '2'],
                    ['id' => 3, 'filters' => [], 'description' => '3'],
                    ['id' => 4, 'filters' => [], 'description' => '4'],
                    ['id' => 5, 'filters' => [], 'description' => '5'],
                ],
                'factors' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Máximo 4 combinaciones permitidas']);
    }

    public function test_export_multi_sheet_returns_excel_file(): void
    {
        // Create Likert evaluation
        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        // Create demographic data
        DemographicData::create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => 'Masculino',
            'work_schedule' => 'Matutino',
            'contract_type' => 'Permanente',
            'position' => 'Operador',
            'department' => 'Producción',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [
                    [
                        'id' => 1,
                        'filters' => [],
                        'description' => 'Todos',
                    ],
                ],
                'factors' => ['Entorno Laboral Seguro'],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
    }

    public function test_export_multi_sheet_filters_by_demographic(): void
    {
        // Create Likert evaluations with different genders
        $maleEval = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
                'evaluee_name' => 'Juan Test',
            ]);

        DemographicData::create([
            'paper_evaluation_id' => $maleEval->id,
            'gender' => 'Masculino',
            'work_schedule' => 'Matutino',
        ]);

        $femaleEval = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
                'evaluee_name' => 'Maria Test',
            ]);

        DemographicData::create([
            'paper_evaluation_id' => $femaleEval->id,
            'gender' => 'Femenino',
            'work_schedule' => 'Vespertino',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [
                    [
                        'id' => 1,
                        'filters' => [
                            ['type' => 'demographic', 'key' => 'genero', 'value' => 'Masculino'],
                        ],
                        'description' => 'Masculino',
                    ],
                ],
                'factors' => ['Entorno Laboral Seguro'],
            ]);

        $response->assertStatus(200);
    }

    public function test_export_multi_sheet_includes_custom_fields(): void
    {
        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        EvaluationCustomField::create([
            'paper_evaluation_id' => $evaluation->id,
            'field_key' => 'linea_produccion',
            'key_label' => 'Línea de Producción',
            'value' => 'Línea A',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('organization.clima.export-options', $this->organization->id));

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertNotEmpty($data['customFields']);
    }

    public function test_export_multi_sheet_includes_comments(): void
    {
        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        EvaluationComment::create([
            'paper_evaluation_id' => $evaluation->id,
            'factor' => 'Entorno Laboral Seguro',
            'comment' => 'Comentario de prueba',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [
                    [
                        'id' => 1,
                        'filters' => [],
                        'description' => 'Todos',
                    ],
                ],
                'factors' => ['Entorno Laboral Seguro'],
            ]);

        $response->assertStatus(200);
    }

    public function test_export_returns_empty_sheet_when_no_matches(): void
    {
        // Create Likert evaluation with specific demographic
        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        DemographicData::create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => 'Masculino',
        ]);

        // Request export for "Femenino" which doesn't exist
        $response = $this->actingAs($this->admin)
            ->postJson(route('organization.clima.export-multi', $this->organization->id), [
                'combinations' => [
                    [
                        'id' => 1,
                        'filters' => [
                            ['type' => 'demographic', 'key' => 'genero', 'value' => 'Femenino'],
                        ],
                        'description' => 'Femenino',
                    ],
                ],
                'factors' => ['Entorno Laboral Seguro'],
            ]);

        // Should still return 200 with empty sheet (headers only)
        $response->assertStatus(200);
    }
}
