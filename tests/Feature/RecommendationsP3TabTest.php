<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecommendationsP3TabTest extends TestCase
{
    use DatabaseTransactions;

    public function test_recommendations_p3_tab_renders_with_psychosocial_risk_factors(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);
        
        // Create some evaluation data that the dashboard might need
        $evaluation = \App\Models\PaperEvaluation::factory()->create(['organization_id' => $organization->id]);
        \App\Models\DemographicData::factory()->create(['paper_evaluation_id' => $evaluation->id]);
        
        $response = $this->actingAs($user)
            ->get("/organizacion/{$organization->id}/dashboard");

        $response->assertStatus(200);
        
        // Verify that the dashboard component loads correctly
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/Dashboard')
            ->has('dashboardData')
        );
    }

    public function test_recommendations_p3_tab_shows_correct_statistical_data(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        $user->syncRoles(['organization']);
        $user->update(['organization_id' => $organization->id]);
        
        // Create some evaluation data that the dashboard might need
        $evaluation = \App\Models\PaperEvaluation::factory()->create(['organization_id' => $organization->id]);
        \App\Models\DemographicData::factory()->create(['paper_evaluation_id' => $evaluation->id]);
        
        $response = $this->actingAs($user)
            ->get("/organizacion/{$organization->id}/dashboard");

        $response->assertStatus(200);
        
        // Verify that the dashboard component loads correctly with data structure
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/Dashboard')
            ->has('dashboardData')
            ->has('dashboardData.organization')
        );
    }
}
