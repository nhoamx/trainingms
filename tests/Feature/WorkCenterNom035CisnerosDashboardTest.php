<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterNom035CisnerosDashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_cisneros_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-cisneros', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035CisnerosDashboard')
            ->has('dashboardData')
            ->has('dashboardData.organization')
            ->has('dashboardData.work_center')
            ->has('cisnerosEvaluationsCount')
            ->has('cisnerosSummary')
            ->has('authorsChart')
            ->has('frequencyChart')
            ->has('participants')
            ->has('responsesTable')
            ->where('cisnerosEvaluationsCount', 0)
            ->where('cisnerosSummary.total_evaluations', 0)
            ->where('participants', [])
            ->where('responsesTable', [])
        );
    }

    public function test_cisneros_dashboard_counts_only_completed_cisneros_evaluations(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->cisneros()->count(2)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->cisneros()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'pending',
        ]);

        PaperEvaluation::factory()->referenciaI()->count(3)->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-cisneros', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->where('cisnerosEvaluationsCount', 2)
            ->where('cisnerosSummary.total_evaluations', 2)
        );
    }

    public function test_cisneros_dashboard_builds_charts_and_response_table(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->cisneros()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'folio' => '049990001',
            'personal_folio' => '0001',
            'cisneros_answers' => [
                '1' => ['persona' => 'A', 'frecuencia' => 3],
                '2' => ['persona' => 'B', 'frecuencia' => 1],
                '44' => true,
            ],
        ]);

        PaperEvaluation::factory()->cisneros()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'folio' => '049990002',
            'personal_folio' => '0002',
            'cisneros_answers' => [
                '1' => ['persona' => 'C', 'frecuencia' => 6],
                '44' => false,
            ],
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-cisneros', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->where('cisnerosSummary.total_evaluations', 2)
            ->where('cisnerosSummary.victim_yes', 1)
            ->where('cisnerosSummary.victim_no', 1)
            ->has('authorsChart', 3)
            ->has('frequencyChart', 7)
            ->has('participants', 2)
            ->where('participants.0.folio', '049990001')
            ->has('responsesTable', 3)
            ->where('responsesTable.0.folio', '049990001')
            ->where('responsesTable.0.question_number', 1)
        );
    }

    public function test_work_center_user_cannot_view_unassigned_center_cisneros_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-cisneros', $workCenter));

        $response->assertForbidden();
    }
}
