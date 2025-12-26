<?php

namespace Tests\Feature;

use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrganizationDashboardTest extends TestCase
{
    use DatabaseTransactions;

    public function test_organization_user_can_view_their_organization_dashboard(): void
    {
        // Create an organization
        $organization = Organization::factory()->create();

        // Create a user with organization role assigned to this organization
        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);

        // Create some demographic data
        $evaluation = PaperEvaluation::factory()->create(['organization_id' => $organization->id]);
        DemographicData::factory()->create(['paper_evaluation_id' => $evaluation->id]);

        // Verify policy can authorize
        $this->assertTrue($user->can('viewOrganizationDashboard', $organization));

        // Make request
        $response = $this->actingAs($user)
            ->get(route('organization.dashboard', $organization));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/Dashboard')
            ->has('dashboardData')
            ->has('dashboardData.organization')
            ->has('dashboardData.company_data')
            ->has('dashboardData.demographic_summary')
        );
    }

    public function test_organization_user_cannot_view_another_organization_dashboard(): void
    {
        // Create two organizations
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        // Create a user assigned to organization 1
        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization1->id]);

        // Try to access organization 2's dashboard
        $response = $this->actingAs($user)
            ->get(route('organization.dashboard', $organization2));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_dashboard(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->get(route('organization.dashboard', $organization));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_returns_correct_data_structure(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'razon_social' => 'Test Razón Social',
            'rfc' => 'RFC123456789',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)
            ->get(route('organization.dashboard', $organization));

        $response->assertInertia(fn ($page) => $page
            ->has('dashboardData', fn ($data) => $data
                ->has('organization', fn ($org) => $org
                    ->where('id', $organization->id)
                    ->where('name', 'Test Organization')
                    ->has('logo')
                )
                ->has('company_data', fn ($company) => $company
                    ->has('general')
                    ->has('address')
                    ->has('contact')
                    ->has('responsible')
                    ->has('logo')
                )
                ->has('demographic_summary')
                ->has('demographic_details', fn ($details) => $details
                    ->has('genders')
                    ->has('contract_types')
                    ->has('positions')
                    ->has('departments')
                    ->has('work_schedules')
                    ->has('total_evaluations')
                )
            )
            ->has('evaluations')
        );
    }

    public function test_dashboard_includes_evaluation_comments(): void
    {
        $organization = Organization::factory()->create();

        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);

        // Create a Likert evaluation with comments
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
            'likert_answers' => [
                'ambiente' => ['q1' => 3, 'q2' => 2],
                'factores' => ['q1' => 4, 'q2' => 1],
                'liderazgo' => ['q1' => 2, 'q2' => 3],
                'cargas' => ['q1' => 1, 'q2' => 4],
                'control' => ['q1' => 3, 'q2' => 2],
                'jornada' => ['q1' => 2, 'q2' => 1],
                'entorno' => ['q1' => 4, 'q2' => 3],
            ],
        ]);

        DemographicData::factory()->create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => 'Masculino',
        ]);

        // Create comments for the evaluation
        $evaluation->comments()->createMany([
            ['factor' => 'Ambiente de trabajo', 'comment' => 'Comentario 1'],
            ['factor' => 'Factores propios de la actividad', 'comment' => 'Comentario 2'],
            ['factor' => 'Liderazgo', 'comment' => 'Comentario 3'],
        ]);

        $response = $this->actingAs($user)
            ->get(route('organization.dashboard', $organization));

        $response->assertInertia(fn ($page) => $page
            ->has('evaluations', 1)
            ->has('evaluations.0', fn ($eval) => $eval
                ->has('id')
                ->has('demographicData')
                ->has('dimensions')
                ->has('comments', 3)
                ->has('total_score')
                ->has('interpretation')
                ->has('comments.0', fn ($comment) => $comment
                    ->has('factor')
                    ->has('comment')
                )
            )
        );
    }

    public function test_caliza_organization_renders_nom035_dashboard(): void
    {
        // Create Caliza organization with the specific ID from config
        $calizaId = config('organizations.caliza.id');
        $organization = Organization::factory()->create([
            'id' => $calizaId,
            'name' => 'CORPORACION INDUSTRIAL DE CALIZA',
        ]);

        // Create a user assigned to Caliza organization
        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);

        // Create a NOM-035 evaluation (referencia_iii)
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'processing_status' => 'completed',
            'referencia_iii_answers' => ['q1' => 1, 'q2' => 2],
        ]);

        DemographicData::factory()->create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => 'Masculino',
        ]);

        $response = $this->actingAs($user)
            ->get(route('organization.dashboard', $organization));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/CalizaDashboard')
            ->has('dashboardData')
            ->has('evaluations')
            ->where('title', 'NOM-035-STPS-2018')
        );
    }

    public function test_non_caliza_organization_renders_likert_dashboard(): void
    {
        // Create a different organization (not Caliza)
        $organization = Organization::factory()->create([
            'name' => 'Test Organization',
        ]);

        // Create a user assigned to this organization
        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);

        // Create a Likert evaluation
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
            'likert_answers' => [
                'ambiente' => ['q1' => 3, 'q2' => 2],
                'factores' => ['q1' => 4, 'q2' => 1],
                'liderazgo' => ['q1' => 2, 'q2' => 3],
                'cargas' => ['q1' => 1, 'q2' => 4],
                'control' => ['q1' => 3, 'q2' => 2],
                'jornada' => ['q1' => 2, 'q2' => 1],
                'entorno' => ['q1' => 4, 'q2' => 3],
            ],
        ]);

        DemographicData::factory()->create([
            'paper_evaluation_id' => $evaluation->id,
            'gender' => 'Masculino',
        ]);

        $response = $this->actingAs($user)
            ->get(route('organization.dashboard', $organization));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/Dashboard')
            ->has('dashboardData')
            ->has('evaluations')
            ->where('title', 'Clima Laboral')
        );
    }
}
