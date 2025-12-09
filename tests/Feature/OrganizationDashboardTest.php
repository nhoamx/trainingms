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
                    ->has('areas')
                    ->has('shifts')
                    ->has('total_evaluations')
                )
            )
            ->has('evaluations')
        );
    }
}
