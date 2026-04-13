<?php

namespace Tests\Feature;

use App\Exports\WorkCenterPersonalFoliosExport;
use App\Imports\WorkCenterPersonalFoliosImport;
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

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'personal_folio' => '01001',
            'evaluee_name' => 'Persona Uno',
        ]);

        $export = new WorkCenterPersonalFoliosExport($organization);
        $rows = $export->collection();

        $this->assertSame([
            'Centro de trabajo',
            'ID Centro de trabajo',
            'Folio Personal',
            'Source',
            'Nombre',
        ], $export->headings());

        $this->assertCount(1, $rows);

        $mapped = $export->map($rows->first());

        $this->assertSame('Centro Norte', $mapped[0]);
        $this->assertSame($workCenter->id, $mapped[1]);
        $this->assertSame('01001', $mapped[2]);
        $this->assertSame('paper', $mapped[3]);
        $this->assertSame('Persona Uno', $mapped[4]);
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
}
