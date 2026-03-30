<?php

namespace Tests\Feature;

use App\Imports\EvaluationBulkUpdateImportV2;
use App\Models\DemographicData;
use App\Models\EvaluationComment;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Tests\TestCase;

class EvaluationBulkUpdateImportV2Test extends TestCase
{
    use DatabaseTransactions;

    public function test_it_matches_five_digit_folio_without_leading_zero_and_updates_fields(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $organization->id,
                'work_center_id' => $workCenter->id,
                'source' => 'paper',
                'processing_status' => 'completed',
                'evaluation_type' => 'likert',
                'personal_folio' => '01402',
                'evaluee_name' => 'Nombre anterior',
            ]);

        DemographicData::create([
            'paper_evaluation_id' => $evaluation->id,
            'contract_type' => 'Indirecto',
        ]);

        $import = new EvaluationBulkUpdateImportV2(
            $organization->id,
            'paper',
            null,
            $workCenter->id,
            'likert',
        );

        $rows = new Collection([
            new Collection([
                'folio' => '1402',
                'nombre_del_evaluado' => 'Nombre actualizado',
                'contratacion' => 'Directo',
            ]),
        ]);

        $import->collection($rows);

        $evaluation->refresh();
        $evaluation->demographicData->refresh();

        $this->assertSame('Nombre actualizado', $evaluation->evaluee_name);
        $this->assertSame('Directo', $evaluation->demographicData->contract_type);
        $this->assertSame(1, $import->getUpdatedCount());
        $this->assertSame(0, $import->getSkippedCount());
        $this->assertSame([], $import->getErrors());
    }

    public function test_it_does_not_match_when_scope_filters_do_not_match(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $workCenterA = WorkCenter::factory()->create([
            'organization_id' => $organizationA->id,
        ]);

        PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $organizationB->id,
                'work_center_id' => $workCenterA->id,
                'source' => 'paper',
                'processing_status' => 'completed',
                'evaluation_type' => 'likert',
                'personal_folio' => '00060',
            ]);

        $import = new EvaluationBulkUpdateImportV2(
            $organizationA->id,
            'paper',
            null,
            $workCenterA->id,
            'likert',
        );

        $rows = new Collection([
            new Collection([
                'folio' => '60',
                'nombre_del_evaluado' => 'No debe actualizar',
            ]),
        ]);

        $import->collection($rows);

        $this->assertSame(0, $import->getUpdatedCount());
        $this->assertSame(1, $import->getSkippedCount());
        $this->assertCount(1, $import->getErrors());
    }

    public function test_it_creates_and_updates_evaluation_comments_from_compact_column(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $organization->id,
                'work_center_id' => $workCenter->id,
                'source' => 'paper',
                'processing_status' => 'completed',
                'evaluation_type' => 'likert',
                'personal_folio' => '01402',
            ]);

        EvaluationComment::create([
            'paper_evaluation_id' => $evaluation->id,
            'factor' => 'Liderazgo',
            'comment' => 'Comentario anterior',
        ]);

        $import = new EvaluationBulkUpdateImportV2(
            $organization->id,
            'paper',
            null,
            $workCenter->id,
            'likert',
        );

        $rows = new Collection([
            new Collection([
                'folio' => '1402',
                'comentarios_adicionales' => "Comentario actualizado\nRiesgo alto",
                'factor_de_comentarios' => "Liderazgo\nCarga de trabajo",
            ]),
        ]);

        $import->collection($rows);

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluation->id,
            'factor' => 'Liderazgo',
            'comment' => 'Comentario actualizado',
        ]);

        $this->assertDatabaseHas('evaluation_comments', [
            'paper_evaluation_id' => $evaluation->id,
            'factor' => 'Carga de trabajo',
            'comment' => 'Riesgo alto',
        ]);
    }
}
