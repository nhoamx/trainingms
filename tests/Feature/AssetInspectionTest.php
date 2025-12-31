<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetInspection;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetInspectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_asset_inspection_page(): void
    {
        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->get(route('assets.inspect', $asset));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Inspect')
            ->has('asset')
            ->where('isAuthenticated', false)
            ->where('isInspector', false)
        );
    }

    public function test_inspector_can_access_inspection_form(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user)->get(route('assets.inspections.create', $asset));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/CreateInspection')
            ->has('asset')
            ->has('checklist')
            ->where('canInspect', true)
        );
    }

    public function test_non_inspector_cannot_access_inspection_form(): void
    {
        $user = User::factory()->create();
        // No asignar rol de admin

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user)->get(route('assets.inspections.create', $asset));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('canInspect', false)
        );
    }

    public function test_can_create_inspection_as_inspector(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $checklistResults = [];
        for ($i = 1; $i <= 27; $i++) {
            $checklistResults[$i] = [
                'date' => now()->format('Y-m-d'),
                'result' => 'OK - Test '.$i,
            ];
        }

        $inspectionData = [
            'inspector_name' => 'Juan Pérez',
            'inspection_date' => now()->format('Y-m-d'),
            'checklist_results' => $checklistResults,
            'anomalies_followup' => 'Sin anomalías detectadas',
        ];

        $response = $this->actingAs($user)->post(route('assets.inspections.store', $asset), $inspectionData);

        $response->assertRedirect(route('assets.inspect', $asset));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('asset_inspections', [
            'asset_id' => $asset->id,
            'inspector_name' => 'Juan Pérez',
            'anomalies_followup' => 'Sin anomalías detectadas',
        ]);
    }

    public function test_non_inspector_cannot_create_inspection(): void
    {
        $user = User::factory()->create();
        // No asignar rol

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $inspectionData = [
            'inspector_name' => 'Juan Pérez',
            'inspection_date' => now()->format('Y-m-d'),
            'checklist_results' => [1 => ['date' => now()->format('Y-m-d'), 'result' => 'OK']],
        ];

        $response = $this->actingAs($user)->post(route('assets.inspections.store', $asset), $inspectionData);

        $response->assertStatus(403);
    }

    public function test_inspection_requires_inspector_name(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolesSeeder']);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $inspectionData = [
            'inspector_name' => '',
            'inspection_date' => now()->format('Y-m-d'),
            'checklist_results' => [1 => ['date' => now()->format('Y-m-d'), 'result' => 'OK']],
        ];

        $response = $this->actingAs($user)->post(route('assets.inspections.store', $asset), $inspectionData);

        $response->assertSessionHasErrors('inspector_name');
    }

    public function test_asset_can_have_multiple_inspections(): void
    {
        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $inspection1 = AssetInspection::factory()->create([
            'asset_id' => $asset->id,
            'inspection_date' => now()->subDays(7),
        ]);

        $inspection2 = AssetInspection::factory()->create([
            'asset_id' => $asset->id,
            'inspection_date' => now()->subDays(3),
        ]);

        $this->assertEquals(2, $asset->inspections()->count());
    }
}
