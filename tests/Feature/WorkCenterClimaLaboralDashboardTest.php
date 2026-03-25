<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterClimaLaboralDashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_clima_laboral_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->likert()->count(3)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.clima-laboral', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/ClimaLaboralDashboard')
            ->where('workCenter.id', $workCenter->id)
            ->where('dashboardData.organization.id', $organization->id)
            ->has('evaluations', 3)
            ->has('dashboardData.demographic_details')
        );
    }

    public function test_work_center_user_can_view_clima_laboral_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.clima-laboral', $workCenter));

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $workCenter = WorkCenter::factory()->create();

        $this->get(route('work-centers.dashboard.clima-laboral', $workCenter))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_only_includes_completed_likert_evaluations(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->likert()->count(2)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'pending',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.clima-laboral', $workCenter))
            ->assertInertia(fn ($page) => $page
                ->has('evaluations', 2)
            );
    }
}
