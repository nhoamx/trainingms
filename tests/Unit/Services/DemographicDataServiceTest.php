<?php

namespace Tests\Unit\Services;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\DemographicDataService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DemographicDataServiceTest extends TestCase
{
    use DatabaseTransactions;

    private DemographicDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DemographicDataService::class);
    }

    public function test_creates_demographic_data_from_paper_evaluation(): void
    {
        $organization = Organization::factory()->create();

        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'online',
        ]);

        $rawDemographicData = [
            'sexo' => 'Masculino',
            'edad' => '35',
            'estado_civil' => 'Casado',
            'nivel_estudios' => 'Licenciatura',
            'datos_laborales' => [
                'ocupacion_puesto' => 'Gerente',
                'departamento_seccion_area' => 'Recursos Humanos',
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
        ];

        $demographicData = $this->service->createFromPaperEvaluation(
            $paperEvaluation,
            $rawDemographicData
        );

        $this->assertNotNull($demographicData);
        $this->assertEquals($paperEvaluation->id, $demographicData->paper_evaluation_id);
        $this->assertEquals('Masculino', $demographicData->gender);
        $this->assertEquals('35', $demographicData->age);
        $this->assertEquals('Casado', $demographicData->marital_status);
        $this->assertEquals('Licenciatura', $demographicData->education_level);
        $this->assertEquals('Gerente', $demographicData->position);
        $this->assertEquals('Recursos Humanos', $demographicData->department);

        $this->assertDatabaseHas('demographic_data', [
            'paper_evaluation_id' => $paperEvaluation->id,
            'gender' => 'Masculino',
            'age' => '35',
        ]);
    }

    public function test_updates_existing_demographic_data(): void
    {
        $organization = Organization::factory()->create();

        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'online',
        ]);

        $initialData = [
            'sexo' => 'Femenino',
            'edad' => '25',
            'datos_laborales' => [
                'ocupacion_puesto' => 'Analista',
            ],
        ];

        // Create initial demographic data
        $demographicData = $this->service->createFromPaperEvaluation(
            $paperEvaluation,
            $initialData
        );

        $this->assertEquals('Femenino', $demographicData->gender);
        $this->assertEquals('25', $demographicData->age);

        // Update with new data using updateOrCreate
        $updatedData = [
            'sexo' => 'Femenino',
            'edad' => '30',
            'datos_laborales' => [
                'ocupacion_puesto' => 'Gerente',
            ],
        ];

        $updatedDemographic = $this->service->updateOrCreate(
            $paperEvaluation,
            $updatedData
        );

        $this->assertEquals('30', $updatedDemographic->age);
        $this->assertEquals('Gerente', $updatedDemographic->position);
    }

    public function test_deletes_old_demographic_before_creating_new(): void
    {
        $organization = Organization::factory()->create();

        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'online',
        ]);

        // Create first demographic data
        $firstData = [
            'sexo' => 'Masculino',
            'edad' => '40',
            'datos_laborales' => [
                'ocupacion_puesto' => 'Supervisor',
            ],
        ];

        $firstDemographic = $this->service->createFromPaperEvaluation(
            $paperEvaluation,
            $firstData
        );

        $firstId = $firstDemographic->id;

        // Create second demographic data (should delete first)
        $secondData = [
            'sexo' => 'Masculino',
            'edad' => '41',
            'datos_laborales' => [
                'ocupacion_puesto' => 'Gerente',
            ],
        ];

        $secondDemographic = $this->service->createFromPaperEvaluation(
            $paperEvaluation,
            $secondData
        );

        // Verify different records
        $this->assertNotEquals($firstId, $secondDemographic->id);

        // Verify new record exists
        $this->assertDatabaseHas('demographic_data', [
            'id' => $secondDemographic->id,
            'age' => '41',
            'position' => 'Gerente',
        ]);

        // Verify only one NON-DELETED record exists for this paper evaluation
        $this->assertEquals(1, $paperEvaluation->fresh()->demographicData()->count());
    }

    public function test_stores_custom_fields_in_extra_fields(): void
    {
        $organization = Organization::factory()->create();

        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type_code' => '05',
            'source' => 'online',
        ]);

        // Likert data with questions in extra_fields
        $likertData = [
            'questions' => ['q1' => 4, 'q2' => 3, 'q3' => 5],
            'genero' => 'femenino',
            'puestos' => 'Coordinador',
            'areas' => 'Ventas',
        ];

        $demographicData = $this->service->createFromPaperEvaluation(
            $paperEvaluation,
            $likertData
        );

        $this->assertNotNull($demographicData->extra_fields);
        $this->assertIsArray($demographicData->extra_fields);
        $this->assertArrayHasKey('questions', $demographicData->extra_fields);
        $this->assertEquals(['q1' => 4, 'q2' => 3, 'q3' => 5], $demographicData->extra_fields['questions']);
    }

    public function test_handles_empty_demographic_data(): void
    {
        $organization = Organization::factory()->create();

        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'online',
        ]);

        $emptyData = [];

        $demographicData = $this->service->createFromPaperEvaluation(
            $paperEvaluation,
            $emptyData
        );

        $this->assertNotNull($demographicData);
        $this->assertEquals($paperEvaluation->id, $demographicData->paper_evaluation_id);
        $this->assertNull($demographicData->gender);
        $this->assertNull($demographicData->age);
        $this->assertNull($demographicData->marital_status);
        $this->assertNull($demographicData->education_level);
        $this->assertNull($demographicData->position);
        $this->assertNull($demographicData->department);
    }
}
