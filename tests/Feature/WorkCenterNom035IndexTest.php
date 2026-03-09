<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterNom035IndexTest extends TestCase
{
    use DatabaseTransactions;

    public function test_work_center_user_can_view_index_page(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035DashboardIndex')
            ->has('dashboardData')
            ->has('dashboardData.organization')
            ->has('dashboardData.work_center')
            ->has('dashboardData.company_data')
            ->has('workCenter')
            ->has('organization')
            ->has('instruments')
            ->has('totalEvaluations')
            ->has('evaluations')
            ->has('availableEvaluationTypes')
            ->has('committeeMembers')
            ->has('constitutiveAct')
            ->has('sensitizationVideos')
        );
    }

    public function test_admin_can_view_any_work_center_index_page(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035DashboardIndex')
        );
    }

    public function test_work_center_user_cannot_view_unassigned_center_index(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $response = $this->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertRedirect(route('login'));
    }

    public function test_index_page_returns_correct_data_structure(): void
    {
        $organization = Organization::factory()->create(['name' => 'Test Org']);
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Centro Norte',
            'code' => '0001',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035DashboardIndex')
            ->has('dashboardData.company_data.general')
            ->has('dashboardData.company_data.address')
            ->has('dashboardData.company_data.contact')
            ->has('dashboardData.company_data.responsible')
            ->has('dashboardData.company_data.workforce')
            ->has('dashboardData.company_data.sample')
            ->has('dashboardData.company_data.committee')
            ->where('workCenter.id', $workCenter->id)
            ->where('workCenter.name', 'Centro Norte')
            ->where('workCenter.code', '0001')
            ->where('organization.name', 'Test Org')
            ->has('instruments', 3)
            ->has('instruments.0.key')
            ->has('instruments.0.label')
            ->has('instruments.0.count')
            ->has('instruments.0.route')
            ->has('instruments.1.key')
            ->has('instruments.2.key')
            ->has('evaluations')
            ->has('availableEvaluationTypes')
            ->has('committeeMembers')
            ->has('constitutiveAct')
            ->has('sensitizationVideos')
        );
    }

    public function test_index_page_shows_correct_evaluation_counts(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->count(3)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
        ]);

        PaperEvaluation::factory()->referenciaIII()->count(5)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035DashboardIndex')
            ->where('totalEvaluations', 8)
            ->where('instruments.0.key', 'referencia_iii')
            ->where('instruments.0.count', 5)
            ->where('instruments.1.key', 'referencia_i')
            ->where('instruments.1.count', 3)
        );
    }

    public function test_index_page_shows_zero_counts_when_no_evaluations(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035DashboardIndex')
            ->where('totalEvaluations', 0)
            ->where('instruments.0.count', 0)
            ->where('instruments.1.count', 0)
            ->where('instruments.2.count', 0)
        );
    }

    public function test_index_page_excludes_pending_evaluations_from_counts(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'pending',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->where('totalEvaluations', 1)
            ->where('instruments.1.count', 1)
        );
    }

    public function test_index_page_only_counts_evaluations_for_current_work_center(): void
    {
        $organization = Organization::factory()->create();
        $workCenter1 = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $workCenter2 = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->count(2)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter1->id,
        ]);

        PaperEvaluation::factory()->referenciaI()->count(5)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter2->id,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter1));

        $response->assertInertia(fn ($page) => $page
            ->where('totalEvaluations', 2)
        );
    }

    public function test_index_page_counts_unique_participants_across_ref_i_and_ref_iii(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => '0099',
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => '0099',
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => '0100',
            'processing_status' => 'completed',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-index', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->where('totalEvaluations', 2)
            ->where('instruments.0.count', 2)
            ->where('instruments.1.count', 1)
        );
    }
}
