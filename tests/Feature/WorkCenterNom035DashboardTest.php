<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterNom035DashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_work_center_user_can_view_their_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIIIDashboard')
            ->has('dashboardData')
            ->has('dashboardData.organization')
            ->has('dashboardData.work_center')
            ->has('dashboardData.company_data')
            ->has('domainStatistics')
            ->has('categoryStatistics')
            ->has('dimensionStatistics')
            ->has('globalStatistics')
            ->has('evaluations')
            ->has('preventionActions')
        );
    }

    public function test_admin_can_view_any_work_center_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIIIDashboard')
        );
    }

    public function test_work_center_user_cannot_view_unassigned_center(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $response = $this->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_returns_correct_data_structure(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization',
        ]);
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Centro Norte',
            'code' => '0001',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIIIDashboard')
            ->has('dashboardData.company_data.general')
            ->has('dashboardData.company_data.address')
            ->has('dashboardData.company_data.contact')
            ->has('dashboardData.company_data.responsible')
            ->has('dashboardData.company_data.workforce')
            ->has('dashboardData.company_data.sample')
            ->has('dashboardData.company_data.committee')
            ->has('questionStatistics')
            ->has('blockStatistics')
            ->has('analysisData')
            ->has('availableEvaluationTypes')
            ->has('preventionActions')
        );
    }

    public function test_dashboard_shows_work_center_specific_evaluations(): void
    {
        $organization = Organization::factory()->create();

        $workCenter1 = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $workCenter2 = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter1->id,
        ]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter2->id,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter1));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIIIDashboard')
            ->where('domainStatistics.total_evaluations', 1)
        );
    }

    public function test_dashboard_returns_empty_statistics_when_no_evaluations(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIIIDashboard')
            ->where('domainStatistics.total_evaluations', 0)
            ->where('globalStatistics.total_evaluations', 0)
        );
    }

    public function test_organization_user_can_view_their_work_center_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertStatus(200);
    }

    public function test_organization_user_cannot_view_other_org_work_center(): void
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization2->id]);

        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization1->id]);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertForbidden();
    }
}
