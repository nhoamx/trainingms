<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaperEvaluationWorkCenterScopeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_update_name_only_updates_records_in_same_work_center(): void
    {
        $organization = Organization::factory()->create(['folio_organization' => '12']);
        $workCenterA = WorkCenter::factory()->create(['organization_id' => $organization->id, 'code' => '01']);
        $workCenterB = WorkCenter::factory()->create(['organization_id' => $organization->id, 'code' => '02']);

        $evaluationInCenterA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterA->id,
            'organization_code' => '12',
            'work_center_code' => '01',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'personal_folio' => '00001',
            'folio' => '02120100001',
            'evaluee_name' => 'Nombre A',
        ]);

        $pairedEvaluationInCenterA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterA->id,
            'organization_code' => '12',
            'work_center_code' => '01',
            'evaluation_type_code' => '01',
            'evaluation_type' => 'referencia_i',
            'personal_folio' => '00001',
            'folio' => '01120100001',
            'evaluee_name' => 'Nombre A',
        ]);

        $evaluationInCenterB = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterB->id,
            'organization_code' => '12',
            'work_center_code' => '02',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'personal_folio' => '00001',
            'folio' => '02120200001',
            'evaluee_name' => 'Nombre B',
        ]);

        $evaluationInCenterA->updateName('Nombre Nuevo');

        $this->assertSame('Nombre Nuevo', $evaluationInCenterA->fresh()->evaluee_name);
        $this->assertSame('Nombre Nuevo', $pairedEvaluationInCenterA->fresh()->evaluee_name);
        $this->assertSame('Nombre B', $evaluationInCenterB->fresh()->evaluee_name);
    }

    public function test_update_personal_folio_is_scoped_by_work_center_and_preserves_folio_format(): void
    {
        $organization = Organization::factory()->create(['folio_organization' => '12']);
        $workCenterA = WorkCenter::factory()->create(['organization_id' => $organization->id, 'code' => '01']);
        $workCenterB = WorkCenter::factory()->create(['organization_id' => $organization->id, 'code' => '02']);

        $evaluationInCenterA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterA->id,
            'organization_code' => '12',
            'work_center_code' => '01',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'personal_folio' => '00001',
            'folio' => '02120100001',
        ]);

        $pairedEvaluationInCenterA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterA->id,
            'organization_code' => '12',
            'work_center_code' => '01',
            'evaluation_type_code' => '01',
            'evaluation_type' => 'referencia_i',
            'personal_folio' => '00001',
            'folio' => '01120100001',
        ]);

        $evaluationInCenterB = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenterB->id,
            'organization_code' => '12',
            'work_center_code' => '02',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'personal_folio' => '00001',
            'folio' => '02120200001',
        ]);

        $evaluationInCenterA->updatePersonalFolio('00077');

        $this->assertSame('02120100077', $evaluationInCenterA->fresh()->folio);
        $this->assertSame('01120100077', $pairedEvaluationInCenterA->fresh()->folio);
        $this->assertSame('00077', $evaluationInCenterA->fresh()->personal_folio);
        $this->assertSame('00077', $pairedEvaluationInCenterA->fresh()->personal_folio);

        $this->assertSame('02120200001', $evaluationInCenterB->fresh()->folio);
        $this->assertSame('00001', $evaluationInCenterB->fresh()->personal_folio);
    }
}
