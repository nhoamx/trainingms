<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use App\Services\OrganizationReportCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterNom035RefIDashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_work_center_user_can_view_their_ref_i_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
            ->has('dashboardData')
            ->has('dashboardData.organization')
            ->has('dashboardData.work_center')
            ->has('dashboardData.company_data')
            ->has('aggregatedStats')
            ->has('participants')
            ->has('executiveSummary')
            ->has('analysisData')
            ->has('questionStatistics')
            ->has('blockStatistics')
            ->has('atsPanoramaStatistics')
            ->has('acontecimientoParticipants')
            ->has('clinicalAssessmentParticipants')
            ->has('preventionActions')
        );
    }

    public function test_admin_can_view_any_work_center_ref_i_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
        );
    }

    public function test_work_center_user_cannot_view_unassigned_center_ref_i(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_ref_i_dashboard(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $response = $this->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertRedirect(route('login'));
    }

    public function test_ref_i_dashboard_returns_correct_data_structure(): void
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
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
            ->has('dashboardData.company_data.general')
            ->has('dashboardData.company_data.address')
            ->has('dashboardData.company_data.contact')
            ->has('dashboardData.company_data.responsible')
            ->has('dashboardData.company_data.workforce')
            ->has('dashboardData.company_data.sample')
            ->has('dashboardData.company_data.committee')
            ->has('aggregatedStats.total_participants')
            ->has('aggregatedStats.total_questions')
            ->has('aggregatedStats.demographic_distribution')
            ->has('executiveSummary.total_participants')
            ->has('executiveSummary.evaluation_type')
            ->has('analysisData.evaluations')
            ->has('analysisData.demographics.generos')
            ->has('analysisData.demographics.puestos')
            ->has('analysisData.demographics.areas')
            ->has('analysisData.demographics.turnos')
            ->has('questionStatistics.questions')
            ->has('blockStatistics.blocks')
            ->has('atsPanoramaStatistics.items')
            ->has('acontecimientoParticipants.participants')
            ->has('clinicalAssessmentParticipants.participants')
            ->has('preventionActions')
        );
    }

    public function test_ref_i_dashboard_shows_work_center_specific_evaluations(): void
    {
        $organization = Organization::factory()->create();

        $workCenter1 = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $workCenter2 = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter1->id,
        ]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter2->id,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter1));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
            ->where('aggregatedStats.total_participants', 1)
        );
    }

    public function test_ref_i_dashboard_returns_empty_stats_when_no_evaluations(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
            ->where('aggregatedStats.total_participants', 0)
            ->where('participants', [])
        );
    }

    public function test_ref_i_dashboard_includes_participant_list_with_demographics(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => '0001',
        ]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'personal_folio' => '0002',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
            ->where('aggregatedStats.total_participants', 2)
            ->has('participants', 2)
            ->has('participants.0.id')
            ->has('participants.0.personal_folio')
            ->has('participants.0.folio')
            ->has('participants.0.evaluation_type')
            ->has('participants.0.demographics')
        );
    }

    public function test_ref_i_dashboard_excludes_incomplete_evaluations(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        // Completed evaluation
        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        // Pending evaluation
        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'pending',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
            ->where('aggregatedStats.total_participants', 1)
            ->has('participants', 1)
        );
    }

    public function test_ref_i_dashboard_includes_presentation_date_in_acontecimiento_participants(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'citsats_s1' => [
                '1' => 'SI',
                '2' => 'NO',
                '3' => 'NO',
                '4' => 'NO',
                '5' => 'NO',
                '6' => 'NO',
            ],
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter));

        $response->assertInertia(fn ($page) => $page
            ->component('WorkCenters/Nom035RefIDashboard')
            ->has('acontecimientoParticipants.participants', 1)
            ->has('acontecimientoParticipants.participants.0.created_at')
        );
    }

    public function test_ref_i_dashboard_filters_by_source_parameter(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'online',
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', ['workCenter' => $workCenter, 'source' => 'online']))
            ->assertInertia(fn ($page) => $page
                ->where('aggregatedStats.total_participants', 1)
                ->where('selectedSource', 'online')
            );

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', ['workCenter' => $workCenter, 'source' => 'paper']))
            ->assertInertia(fn ($page) => $page
                ->where('aggregatedStats.total_participants', 1)
                ->where('selectedSource', 'paper')
            );

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', $workCenter))
            ->assertInertia(fn ($page) => $page
                ->where('aggregatedStats.total_participants', 2)
                ->where('selectedSource', null)
            );

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', ['workCenter' => $workCenter, 'source' => 'all']))
            ->assertInertia(fn ($page) => $page
                ->where('aggregatedStats.total_participants', 2)
                ->where('selectedSource', null)
            );
    }

    public function test_ref_i_dashboard_supports_paper_ocr_answer_payloads(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->referenciaI()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_i_answers' => [
                '1' => ['value' => 'SI'],
                '2' => ['value' => 'NO'],
                '3' => ['value' => 'SI'],
                '4' => ['value' => 'NO'],
                '5' => ['value' => 'NO'],
                '6' => ['value' => 'NO'],
                '7' => ['value' => 'NO'],
                '8' => ['value' => 'NO'],
                '9' => ['value' => 'NO'],
                '10' => ['value' => 'SI'],
                '11' => ['value' => 'NO'],
                '12' => ['value' => 'NO'],
                '13' => ['value' => 'NO'],
                '14' => ['value' => 'NO'],
            ],
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $this->actingAs($user)
            ->get(route('work-centers.dashboard.nom-035-ref-i', ['workCenter' => $workCenter, 'source' => 'paper']))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('WorkCenters/Nom035RefIDashboard')
                ->where('aggregatedStats.total_participants', 1)
                ->where('selectedSource', 'paper')
                ->where('aggregatedStats.answer_distribution.pregunta_1.yes_count', 1)
                ->where('aggregatedStats.answer_distribution.pregunta_2.no_count', 1)
            );
    }

    public function test_ref_i_cache_key_is_isolated_by_source(): void
    {
        /** @var OrganizationReportCacheService $cacheService */
        $cacheService = app(OrganizationReportCacheService::class);

        $keyAll = $cacheService->getWcNom035RefIStatsCacheKey('wc-1');
        $keyOnline = $cacheService->getWcNom035RefIStatsCacheKey('wc-1', 'online');
        $keyPaper = $cacheService->getWcNom035RefIStatsCacheKey('wc-1', 'paper');

        $this->assertNotSame($keyAll, $keyOnline);
        $this->assertNotSame($keyAll, $keyPaper);
        $this->assertNotSame($keyOnline, $keyPaper);
    }
}
