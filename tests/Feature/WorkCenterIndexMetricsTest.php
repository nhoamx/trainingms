<?php

namespace Tests\Feature;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkCenterIndexMetricsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_includes_people_and_clinical_attention_metrics_per_work_center(): void
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($adminRole);

        $organization = Organization::factory()->create();

        $centerA = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => true,
            'name' => 'Centro A',
            'type' => WorkCenterType::Headquarters->value,
        ]);

        $centerB = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'is_primary' => false,
            'name' => 'Centro B',
            'type' => WorkCenterType::Branch->value,
        ]);

        // Center A: person 00001 requires clinical attention (Section II yes).
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $centerA->id,
            'evaluation_type_code' => '01',
            'evaluation_type' => 'referencia_i',
            'personal_folio' => '00001',
            'demographic_data' => [
                'sexo' => 'masculino',
            ],
            'referencia_i_answers' => [
                '1' => true,
                '2' => false,
                '3' => false,
                '4' => false,
                '5' => false,
                '6' => false,
                '7' => false,
                '8' => false,
                '9' => false,
                '10' => false,
                '11' => false,
                '12' => false,
                '13' => false,
                '14' => false,
            ],
            'processing_status' => 'completed',
        ]);

        // Same person with another instrument should not increment evaluated people twice.
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $centerA->id,
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'personal_folio' => '00001',
            'processing_status' => 'completed',
        ]);

        // Center A: second person, no clinical criteria.
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $centerA->id,
            'evaluation_type_code' => '01',
            'evaluation_type' => 'referencia_i',
            'personal_folio' => '00002',
            'demographic_data' => [
                'sexo' => 'femenino',
            ],
            'referencia_i_answers' => [
                '1' => false,
                '2' => false,
                '3' => false,
                '4' => false,
                '5' => false,
                '6' => false,
                '7' => false,
                '8' => false,
                '9' => false,
                '10' => false,
                '11' => false,
                '12' => false,
                '13' => false,
                '14' => false,
            ],
            'processing_status' => 'completed',
        ]);

        // Center B: person 00003 requires clinical attention (Section III >= 3 yes).
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $centerB->id,
            'evaluation_type_code' => '01',
            'evaluation_type' => 'referencia_i',
            'personal_folio' => '00003',
            'demographic_data' => [
                'sexo' => 'femenino',
            ],
            'referencia_i_answers' => [
                'pregunta_1' => false,
                'pregunta_2' => false,
                'pregunta_3' => true,
                'pregunta_4' => true,
                'pregunta_5' => true,
                'pregunta_6' => false,
                'pregunta_7' => false,
                'pregunta_8' => false,
                'pregunta_9' => false,
                'pregunta_10' => false,
                'pregunta_11' => false,
                'pregunta_12' => false,
                'pregunta_13' => false,
                'pregunta_14' => false,
            ],
            'processing_status' => 'completed',
        ]);

        // Center B: second evaluated person without Ref I (counts as evaluated only).
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $centerB->id,
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'personal_folio' => '00004',
            'demographic_data' => [
                'sexo' => 'masculino',
            ],
            'processing_status' => 'completed',
        ]);

        // Failed evaluations must not count.
        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $centerA->id,
            'evaluation_type_code' => '01',
            'evaluation_type' => 'referencia_i',
            'personal_folio' => '99999',
            'processing_status' => 'failed',
        ]);

        $response = $this->actingAs($user)->get(route('organizations.work-centers.index', $organization));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('WorkCenters/Index')
            ->has('workCenters', 2)
            ->where('workCenters.0.id', $centerA->id)
            ->where('workCenters.0.evaluated_people_count', 2)
            ->where('workCenters.0.men_count', 1)
            ->where('workCenters.0.women_count', 1)
            ->where('workCenters.0.requires_clinical_attention_count', 1)
            ->where('workCenters.1.id', $centerB->id)
            ->where('workCenters.1.evaluated_people_count', 2)
            ->where('workCenters.1.men_count', 1)
            ->where('workCenters.1.women_count', 1)
            ->where('workCenters.1.requires_clinical_attention_count', 1)
        );
    }
}
