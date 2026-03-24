<?php

namespace Tests\Feature;

use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BackfillPaperDemographicDataCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_dry_run_reports_candidates_without_writing(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        PaperEvaluation::factory()->referenciaV()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '00041',
            'demographic_data' => [
                'sexo' => 'masculino',
                'edad' => ['decenas' => 3, 'unidades' => 2],
            ],
        ]);

        $target = PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '00041',
            'demographic_data' => null,
        ]);

        $this->artisan('evaluations:backfill-paper-demographic-data', [
            '--work-center' => $workCenter->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 target records to inspect.')
            ->expectsOutput('DRY RUN - No changes were made.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('demographic_data', [
            'paper_evaluation_id' => $target->id,
        ]);
    }

    public function test_command_backfills_demographic_data_from_related_referencia_v(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $sourceRefV = PaperEvaluation::factory()->referenciaV()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '00042',
            'folio' => '03010900042',
            'demographic_data' => [
                'sexo' => 'femenino',
                'edad' => ['decenas' => 2, 'unidades' => 7],
                'estado_civil' => 'casado',
                'nivel_estudios' => 'licenciatura',
                'ocupacion_puesto' => 'Coordinador',
                'departamento_seccion_area' => 'Administracion',
                'tipo_puesto' => 'supervision',
                'tipo_contratacion' => 'tiempo_indeterminado',
                'tipo_personal' => 'confianza',
                'tipo_jornada' => 'diurno',
                'rotacion_turnos' => 'no',
                'tiempo_puesto_actual' => 'entre_1_y_4_anios',
                'tiempo_experiencia_laboral' => 'entre_5_y_10_anios',
            ],
        ]);

        $targetRefI = PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '00042',
            'folio' => '01010900042',
            'demographic_data' => null,
        ]);

        $this->assertDatabaseMissing('demographic_data', [
            'paper_evaluation_id' => $targetRefI->id,
        ]);

        $this->artisan('evaluations:backfill-paper-demographic-data', [
            '--work-center' => $workCenter->id,
            '--force' => true,
        ])
            ->expectsOutput('Found 1 target records to inspect.')
            ->expectsOutput('Backfilled 1 records into demographic_data.')
            ->assertSuccessful();

        $targetRefI->refresh();

        $this->assertEquals($sourceRefV->demographic_data, $targetRefI->demographic_data);
        $this->assertNotNull($targetRefI->demographicData);
        $this->assertInstanceOf(DemographicData::class, $targetRefI->demographicData);
        $this->assertEquals('Femenino', $targetRefI->demographicData->gender);
        $this->assertEquals('Administracion', $targetRefI->demographicData->department);
    }

    public function test_command_repairs_existing_empty_demographic_row_from_object_structured_ref_v_data(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        PaperEvaluation::factory()->referenciaV()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '00043',
            'folio' => '03010900043',
            'demographic_data' => [
                'age' => ['value' => 35],
                'gender' => ['value' => 'Masculino'],
                'marital_status' => ['value' => 'Casado'],
                'position_type' => ['value' => 'Supervisor'],
                'work_schedule' => ['value' => 'Fijo diurno'],
                'contract_type' => ['value' => 'Tiempo indeterminado'],
                'personnel_type' => ['value' => 'Confianza'],
                'shift_rotation' => ['value' => 'NO'],
                'education_level' => ['value' => 'Licenciatura'],
                'time_in_current_position' => ['value' => 'Entre 1 a 4 anos'],
                'work_experience' => ['value' => 'Entre 5 a 9 anos'],
            ],
        ]);

        $targetRefI = PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '00043',
            'folio' => '01010900043',
            'demographic_data' => null,
        ]);

        DemographicData::create([
            'paper_evaluation_id' => $targetRefI->id,
            'gender' => null,
            'age' => null,
            'marital_status' => null,
            'education_level' => null,
            'position' => null,
            'department' => null,
            'position_type' => null,
            'contract_type' => null,
            'personnel_type' => null,
            'work_schedule' => null,
            'shift_rotation' => null,
            'time_in_current_position' => null,
            'work_experience' => null,
            'extra_fields' => null,
        ]);

        $this->artisan('evaluations:backfill-paper-demographic-data', [
            '--work-center' => $workCenter->id,
            '--force' => true,
        ])
            ->expectsOutput('Found 1 target records to inspect.')
            ->expectsOutput('Backfilled 1 records into demographic_data.')
            ->assertSuccessful();

        $targetRefI->refresh();

        $this->assertNotNull($targetRefI->demographicData);
        $this->assertEquals('Masculino', $targetRefI->demographicData->gender);
        $this->assertEquals('Fijo diurno', $targetRefI->demographicData->work_schedule);
        $this->assertEquals('Supervisor', $targetRefI->demographicData->position);
    }
}
