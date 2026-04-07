<?php

namespace Tests\Feature;

use App\Models\DemographicData;
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
            ->has('generalReport')
            ->has('violenceLaborStatistics')
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
            ->has('generalReport.rows')
            ->has('violenceLaborStatistics.questions')
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
            ->where('generalReport.total_evaluations', 0)
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

    public function test_dashboard_exposes_violence_labor_questions_57_to_64(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'referencia_iii_answers' => [
                57 => 'A',
                58 => 'B',
                59 => 'C',
                60 => 'D',
                61 => 'E',
                62 => 'A',
                63 => 'B',
                64 => 'C',
            ],
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->where('violenceLaborStatistics.question_numbers', [57, 58, 59, 60, 61, 62, 63, 64])
            ->where('violenceLaborStatistics.total_evaluated', 1)
            ->has('violenceLaborStatistics.questions', 8)
        );
    }

    public function test_dashboard_filters_general_report_by_demographic_query_params(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $maleEvaluation = PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        DemographicData::query()->create([
            'paper_evaluation_id' => $maleEvaluation->id,
            'gender' => 'Masculino',
            'position' => 'Operador',
            'department' => 'Produccion',
            'work_schedule' => 'Matutino',
        ]);

        $femaleEvaluation = PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        DemographicData::query()->create([
            'paper_evaluation_id' => $femaleEvaluation->id,
            'gender' => 'Femenino',
            'position' => 'Supervisora',
            'department' => 'Calidad',
            'work_schedule' => 'Vespertino',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', [
                'workCenter' => $workCenter,
                'genero' => 'Masculino',
            ]));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIIIDashboard')
            ->where('generalReport.total_evaluations', 1)
        );
    }

    public function test_dashboard_ref_iii_filters_by_source_parameter(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaIII()->online()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'online',
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', ['workCenter' => $workCenter, 'source' => 'online']))
            ->assertInertia(fn ($page) => $page
                ->where('domainStatistics.total_evaluations', 1)
                ->where('selectedSource', 'online')
            );

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', ['workCenter' => $workCenter, 'source' => 'paper']))
            ->assertInertia(fn ($page) => $page
                ->where('domainStatistics.total_evaluations', 1)
                ->where('selectedSource', 'paper')
            );

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035', ['workCenter' => $workCenter, 'source' => 'all']))
            ->assertInertia(fn ($page) => $page
                ->where('domainStatistics.total_evaluations', 2)
                ->where('selectedSource', null)
            );
    }
}
