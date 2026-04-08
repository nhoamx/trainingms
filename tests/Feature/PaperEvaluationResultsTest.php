<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaperEvaluationResultsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $orgUser;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization
        $this->organization = Organization::factory()->create();

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create organization user
        $this->orgUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->orgUser->assignRole('organization');
    }

    public function test_organization_user_can_view_their_evaluation_list(): void
    {
        // Create paper evaluations for the organization
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => '0001',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/List')
            ->has('evaluationGroups', 1)
        );
    }

    public function test_admin_can_view_any_organization_evaluation_list(): void
    {
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => '0001',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/List')
            ->has('evaluationGroups', 1)
        );
    }

    public function test_evaluation_list_groups_by_personal_folio(): void
    {
        $personalFolio = '0001';

        // Create multiple evaluation types for same personal folio
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_i',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_iii_answers' => [
                '01' => 'A',
                '02' => 'B',
            ],
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_v',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('evaluationGroups', 1)
            ->where('evaluationGroups.0.personal_folio', $personalFolio)
            ->has('evaluationGroups.0.evaluation_types', 3)
        );
    }

    public function test_user_can_view_detailed_results_for_personal_folio(): void
    {
        $personalFolio = '0001';

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_iii_answers' => [
                '01' => 'A',
                '02' => 'B',
            ],
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('organization.results.detail', [
                'organization' => $this->organization->id,
                'personalFolio' => $personalFolio,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/Detail')
            ->has('evaluation')
            ->has('guideIIIResults')
            ->has('results.0.dimension.nombre')
        );
    }

    public function test_detailed_results_include_all_evaluation_types(): void
    {
        $personalFolio = '0002';

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_i',
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_i_answers' => ['question1' => 'yes'],
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_iii_answers' => ['01' => 'A'],
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_v',
            'source' => 'paper',
            'processing_status' => 'completed',
            'demographic_data' => ['age' => '30'],
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('organization.results.detail', [
                'organization' => $this->organization->id,
                'personalFolio' => $personalFolio,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('guideIResults')
            ->has('guideIIIResults')
            ->has('guideVResults')
        );
    }

    public function test_organization_user_cannot_view_other_organization_results(): void
    {
        $otherOrg = Organization::factory()->create();

        PaperEvaluation::factory()->create([
            'organization_id' => $otherOrg->id,
            'personal_folio' => '0001',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('organization.results.list', ['organization' => $otherOrg->id]));

        $response->assertStatus(403);
    }

    public function test_only_completed_paper_evaluations_are_shown(): void
    {
        $personalFolio = '0001';

        // Completed evaluation
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        // Pending evaluation (should not be shown)
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => '0002',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'pending',
        ]);

        // Failed evaluation (should not be shown)
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => '0003',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'failed',
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('evaluationGroups', 1)
        );
    }

    public function test_work_center_detail_route_filters_by_source(): void
    {
        $personalFolio = '0009';
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'completed',
            'referencia_iii_answers' => ['01' => 'A'],
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_iii_answers' => ['01' => 'B'],
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('work-centers.results.detail', [
                'workCenter' => $workCenter->id,
                'personalFolio' => $personalFolio,
                'source' => 'paper',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/Detail')
            ->where('personalFolio', $personalFolio)
            ->has('guideIIIResults')
        );
    }

    public function test_work_center_detail_route_returns_404_when_source_does_not_match(): void
    {
        $personalFolio = '0010';
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'online',
            'processing_status' => 'completed',
            'referencia_iii_answers' => ['01' => 'A'],
        ]);

        $this->actingAs($this->orgUser)
            ->get(route('work-centers.results.detail', [
                'workCenter' => $workCenter->id,
                'personalFolio' => $personalFolio,
                'source' => 'paper',
            ]))
            ->assertStatus(404);
    }

    public function test_work_center_detail_uses_raw_data_when_referencia_iii_answers_are_empty(): void
    {
        $personalFolio = '0011';
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $rawData = [];
        foreach (range(1, 72) as $questionNumber) {
            $rawData[(string) $questionNumber] = [
                'value' => 'A',
            ];
        }

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_iii_answers' => null,
            'raw_data' => $rawData,
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('work-centers.results.detail', [
                'workCenter' => $workCenter->id,
                'personalFolio' => $personalFolio,
                'source' => 'paper',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/Detail')
            ->where('evaluation.has_guide_iii', true)
            ->has('guideIIIResults.answers')
            ->has('results.0.item')
        );
    }

    public function test_work_center_detail_uses_flat_raw_data_for_guide_i_answers(): void
    {
        $personalFolio = '0012';
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $rawData = [];
        foreach (range(65, 78) as $questionNumber) {
            $rawData[(string) $questionNumber] = [
                'value' => 'A',
            ];
        }

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_i',
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_i_answers' => null,
            'raw_data' => $rawData,
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('work-centers.results.detail', [
                'workCenter' => $workCenter->id,
                'personalFolio' => $personalFolio,
                'source' => 'paper',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/Detail')
            ->where('evaluation.has_guide_i', true)
            ->has('guideIResults.answers')
        );
    }

    public function test_work_center_detail_formats_guide_v_demographic_values_from_omr_objects(): void
    {
        $personalFolio = '0013';
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => $personalFolio,
            'evaluation_type' => 'referencia_v',
            'source' => 'paper',
            'processing_status' => 'completed',
            'demographic_data' => [
                'age' => ['value' => '38'],
                'gender' => ['value' => 'Masculino'],
                'position_type' => ['value' => 'Operativo'],
            ],
        ]);

        $response = $this->actingAs($this->orgUser)
            ->get(route('work-centers.results.detail', [
                'workCenter' => $workCenter->id,
                'personalFolio' => $personalFolio,
                'source' => 'paper',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/Detail')
            ->where('guideVResults.demographic_data.Edad', '38')
            ->where('guideVResults.demographic_data.Sexo', 'Masculino')
            ->where('guideVResults.demographic_data.Tipo de Puesto', 'Operativo')
        );
    }
}
