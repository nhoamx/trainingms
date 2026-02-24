<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminDashboardOrganizationsOverviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_dashboard_counts_online_completed_evaluations_in_organization_total(): void
    {
        $organization = Organization::factory()->create();

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'processing_status' => 'completed',
            'source' => 'online',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('organizations', function ($organizations) use ($organization): bool {
                $organizationData = collect($organizations)->firstWhere('id', $organization->id);

                if (! is_array($organizationData)) {
                    return false;
                }

                return ($organizationData['evaluations_count'] ?? null) === 1;
            })
        );
    }

    public function test_admin_dashboard_includes_work_center_count_and_last_evaluation_date(): void
    {
        $organization = Organization::factory()->create();
        $lastEvaluationAt = now()->subDay()->startOfSecond();

        WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);
        WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'processing_status' => 'completed',
            'source' => 'paper',
            'created_at' => $lastEvaluationAt,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('organizations', function ($organizations) use ($organization, $lastEvaluationAt): bool {
                $organizationData = collect($organizations)->firstWhere('id', $organization->id);

                if (! is_array($organizationData)) {
                    return false;
                }

                return ($organizationData['work_centers_count'] ?? null) === 2
                    && ($organizationData['has_nom_035'] ?? null) === true
                    && in_array('NOM-035', $organizationData['instrument_labels'] ?? [], true)
                    && ($organizationData['last_evaluation_at'] ?? null) === $lastEvaluationAt->toDateTimeString();
            })
        );
    }
}
