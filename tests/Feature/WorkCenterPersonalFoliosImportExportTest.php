<?php

namespace Tests\Feature;

use App\Exports\WorkCenterPersonalFoliosExport;
use App\Imports\WorkCenterPersonalFoliosImport;
use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class WorkCenterPersonalFoliosImportExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_only_matching_work_center_and_personal_folio(): void
    {
        $organization = Organization::factory()->create();
        $workCenterA = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $workCenterB = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $evaluationA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterA->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '01402',
            'evaluee_name' => 'Nombre A',
        ]);

        $evaluationB = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterB->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '01402',
            'evaluee_name' => 'Nombre B',
        ]);

        $import = new WorkCenterPersonalFoliosImport($organization->id);

        $import->collection(new Collection([
            new Collection([
                'id_centro_de_trabajo' => $workCenterA->id,
                'folio_personal' => '1402',
                'source' => 'paper',
                'nombre' => 'Nombre Actualizado',
            ]),
        ]));

        $evaluationA->refresh();
        $evaluationB->refresh();

        $this->assertSame('Nombre Actualizado', $evaluationA->evaluee_name);
        $this->assertSame('Nombre B', $evaluationB->evaluee_name);
        $this->assertSame(1, $import->getSummary()['updated']);
    }

    public function test_it_clears_name_when_excel_cell_is_empty(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '00042',
            'evaluee_name' => 'Nombre Previo',
        ]);

        $import = new WorkCenterPersonalFoliosImport($organization->id);

        $import->collection(new Collection([
            new Collection([
                'id_centro_de_trabajo' => $workCenter->id,
                'folio_personal' => '00042',
                'source' => 'paper',
                'nombre' => '',
            ]),
        ]));

        $evaluation->refresh();

        $this->assertSame('', $evaluation->evaluee_name);
        $this->assertSame(1, $import->getSummary()['updated']);
    }

    public function test_export_includes_expected_headers_and_center_columns(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Centro Norte',
        ]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '01001',
            'evaluee_name' => 'Persona Uno',
        ]);

        DemographicData::factory()->create([
            'paper_evaluation_id' => $evaluation->id,
            'position' => 'Analista',
            'department' => 'Recursos Humanos',
            'contract_type' => 'Tiempo indeterminado',
        ]);

        $export = new WorkCenterPersonalFoliosExport($organization);
        $rows = $export->collection();

        $this->assertSame([
            'Centro de trabajo',
            'ID Centro de trabajo',
            'Folio Personal',
            'Source',
            'Nombre',
            'Genero',
            'Edad',
            'Estado civil',
            'Nivel de estudios',
            'Puesto',
            'Departamento',
            'Tipo de puesto',
            'Tipo de contratacion',
            'Tipo de personal',
            'Jornada',
            'Rotacion de turnos',
            'Tiempo en puesto actual',
            'Experiencia laboral',
        ], $export->headings());

        $this->assertCount(1, $rows);

        $mapped = $export->map($rows->first());

        $this->assertSame('Centro Norte', $mapped[0]);
        $this->assertSame($workCenter->id, $mapped[1]);
        $this->assertSame('01001', $mapped[2]);
        $this->assertSame('paper', $mapped[3]);
        $this->assertSame('Persona Uno', $mapped[4]);
        $this->assertSame('Analista', $mapped[9]);
        $this->assertSame('Recursos Humanos', $mapped[10]);
        $this->assertSame('Tiempo indeterminado', $mapped[12]);
    }

    public function test_it_updates_only_matching_source_with_same_work_center_and_folio(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '02222',
            'evaluee_name' => 'Nombre Paper',
        ]);

        $onlineEvaluation = PaperEvaluation::factory()->online()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'online',
            'processing_status' => 'completed',
            'personal_folio' => '02222',
            'evaluee_name' => 'Nombre Online',
        ]);

        $import = new WorkCenterPersonalFoliosImport($organization->id);

        $import->collection(new Collection([
            new Collection([
                'id_centro_de_trabajo' => $workCenter->id,
                'folio_personal' => '02222',
                'source' => 'online',
                'nombre' => 'Online Actualizado',
            ]),
        ]));

        $paperEvaluation->refresh();
        $onlineEvaluation->refresh();

        $this->assertSame('Nombre Paper', $paperEvaluation->evaluee_name);
        $this->assertSame('Online Actualizado', $onlineEvaluation->evaluee_name);
        $this->assertSame(1, $import->getSummary()['updated']);
    }

    public function test_it_updates_demographic_fields_and_syncs_referencia_v_data(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'evaluation_type' => 'referencia_v',
            'personal_folio' => '00077',
            'demographic_data' => [
                'datos_laborales' => [
                    'ocupacion_puesto' => 'Operador',
                    'departamento_seccion_area' => 'Produccion',
                    'tipo_contratacion' => 'Por obra o proyecto',
                    'experiencia' => [
                        'tiempo_puesto_actual' => 'Menos de 6 meses',
                    ],
                ],
            ],
        ]);

        DemographicData::factory()->create([
            'paper_evaluation_id' => $evaluation->id,
            'position' => 'Operador',
            'department' => 'Produccion',
            'contract_type' => 'Por obra o proyecto',
            'time_in_current_position' => 'Menos de 6 meses',
        ]);

        $import = new WorkCenterPersonalFoliosImport($organization->id);

        $import->collection(new Collection([
            new Collection([
                'id_centro_de_trabajo' => $workCenter->id,
                'folio_personal' => '00077',
                'source' => 'paper',
                'puesto' => 'Supervisor de linea',
                'departamento' => 'Calidad',
                'tipo_de_contratacion' => 'Tiempo indeterminado',
                'tiempo_en_puesto_actual' => '',
            ]),
        ]));

        $evaluation->refresh();
        $evaluation->demographicData->refresh();

        $this->assertSame('Supervisor de linea', $evaluation->demographicData->position);
        $this->assertSame('Calidad', $evaluation->demographicData->department);
        $this->assertSame('Tiempo indeterminado', $evaluation->demographicData->contract_type);
        $this->assertNull($evaluation->demographicData->time_in_current_position);
        $this->assertSame('Supervisor de linea', $evaluation->demographic_data['datos_laborales']['ocupacion_puesto']);
        $this->assertSame('Calidad', $evaluation->demographic_data['datos_laborales']['departamento_seccion_area']);
        $this->assertSame('Tiempo indeterminado', $evaluation->demographic_data['datos_laborales']['tipo_contratacion']);
        $this->assertNull($evaluation->demographic_data['datos_laborales']['experiencia']['tiempo_puesto_actual']);
        $this->assertSame(1, $import->getSummary()['updated']);
    }
}
