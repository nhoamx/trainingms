<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\WorkCenterOrganizationReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterOrganizationReportServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_uses_paper_columns_when_raw_data_is_empty(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'evaluee_name' => 'Persona Presencial',
            'raw_data' => null,
            'referencia_iii_answers' => [
                'referencia_iii' => [
                    '1' => 'A',
                    '2' => 'B',
                ],
            ],
            'referencia_i_answers' => [
                '1' => 'SI',
                '2' => 'NO',
            ],
            'citsats_s1' => [
                '1' => true,
                '2' => false,
            ],
            'demographic_data' => [
                'edad' => ['decenas' => '3', 'unidades' => '9'],
                'sexo' => 'Masculino',
                'estado_civil' => 'Casado',
                'nivel_estudios' => 'Licenciatura - Terminada',
                'datos_laborales' => [
                    'experiencia' => [
                        'tiempo_puesto_actual' => 'Entre 1 a 4 años',
                        'tiempo_experiencia_laboral' => 'Entre 10 a 14 años',
                    ],
                    'tipo_puesto' => 'Supervisor',
                    'tipo_jornada' => 'Diurna',
                    'tipo_personal' => 'Confianza',
                    'rotacion_turnos' => 'No',
                    'ocupacion_puesto' => 'Jefatura',
                    'tipo_contratacion' => 'Indeterminado',
                    'departamento_seccion_area' => 'Operaciones',
                ],
            ],
        ]);

        $service = app(WorkCenterOrganizationReportService::class);
        $result = $service->getOrganizationWorkCenters($organization);

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['evaluations']);

        $evaluation = $result[0]['evaluations'][0];

        $this->assertSame('Persona Presencial', $evaluation['evaluee_name']);
        $this->assertSame('A', $evaluation['referencia_iii']['1']);
        $this->assertSame('SI', $evaluation['referencia_i']['1']);
        $this->assertTrue($evaluation['referencia_i_acontecimientos_traumaticos']['1']);
        $this->assertSame('39', $evaluation['referencia_v']['edad']);
        $this->assertSame('Masculino', $evaluation['referencia_v']['sexo']);
    }

    public function test_it_maps_paper_cio_shape_for_referencia_iii_and_referencia_v(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'personal_folio' => '00001',
            'evaluee_name' => 'Persona CIO',
            'raw_data' => [
                '1' => ['value' => 'A'],
                '2' => ['value' => 'D'],
                '64' => ['value' => 'B'],
                '65' => ['value' => 'SI'],
                'referencia_i' => [
                    '1' => [
                        'row' => 0,
                        'block' => 'answers_block_1',
                        'value' => 'NO',
                    ],
                    '2' => [
                        'row' => 1,
                        'block' => 'answers_block_1',
                        'value' => 'SI',
                    ],
                    'acontecimientos_traumaticos' => [
                        '1' => [
                            'row' => 0,
                            'block' => 'answers_block_ats',
                            'value' => 'NO',
                        ],
                    ],
                ],
            ],
            'referencia_iii_answers' => null,
            'demographic_data' => [
                'age' => ['tens' => 5, 'units' => 7, 'value' => 57],
                'gender' => ['value' => 'Masculino'],
                'marital_status' => ['value' => 'Soltero'],
                'education_level' => ['value' => 'Sin formacion'],
                'time_in_current_position' => ['value' => '25 anos o mas'],
                'work_experience' => ['value' => 'Entre 15 a 19 anos'],
                'position_type' => ['value' => 'Operativo'],
                'work_schedule' => ['value' => 'Fijo diurno'],
                'personnel_type' => ['value' => 'Confianza'],
                'shift_rotation' => ['value' => 'NO'],
                'contract_type' => ['value' => 'Por tiempo determinado (temporal)'],
            ],
        ]);

        $service = app(WorkCenterOrganizationReportService::class);
        $result = $service->getOrganizationWorkCenters($organization);

        $this->assertCount(1, $result);
        $this->assertCount(1, $result[0]['evaluations']);

        $evaluation = $result[0]['evaluations'][0];

        $this->assertSame('A', $evaluation['referencia_iii']['1']);
        $this->assertSame('D', $evaluation['referencia_iii']['2']);
        $this->assertSame('B', $evaluation['referencia_iii']['64']);
        $this->assertSame('NO', $evaluation['referencia_i']['1']);
        $this->assertSame('SI', $evaluation['referencia_i']['2']);
        $this->assertSame('NO', $evaluation['referencia_i_acontecimientos_traumaticos']['1']);

        $this->assertSame('57', $evaluation['referencia_v']['edad']);
        $this->assertSame('Masculino', $evaluation['referencia_v']['sexo']);
        $this->assertSame('Soltero', $evaluation['referencia_v']['estado_civil']);
        $this->assertSame('Sin formacion', $evaluation['referencia_v']['nivel_estudios']);
    }
}
