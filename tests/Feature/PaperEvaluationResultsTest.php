<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
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
}
