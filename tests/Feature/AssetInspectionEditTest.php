<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetInspection;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssetInspectionEditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'organization', 'guard_name' => 'web']);
    }

    public function test_admin_can_view_edit_inspection_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        $inspection = AssetInspection::factory()->create(['asset_id' => $asset->id]);

        $response = $this->actingAs($admin)
            ->get(route('assets.inspections.edit', $inspection->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/EditInspection')
            ->has('asset')
            ->has('inspection')
            ->has('checklist')
        );
    }

    public function test_admin_can_update_inspection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        $inspection = AssetInspection::factory()->create([
            'asset_id' => $asset->id,
            'inspector_name' => 'Original Inspector',
        ]);

        $updatedData = [
            'inspector_name' => 'Updated Inspector',
            'inspection_date' => '2026-01-20',
            'extinguisher_weight' => '5.5 kg',
            'checklist_results' => [
                '1' => ['date' => '2026-01-20', 'status' => 'issue', 'result' => 'Test issue'],
            ],
            'anomalies_followup' => 'Updated notes',
        ];

        $response = $this->actingAs($admin)
            ->put(route('assets.inspections.update', $inspection->id), $updatedData);

        $response->assertRedirect(route('organizations.assets.edit', [
            'organization' => $organization->id,
            'asset' => $asset->id,
        ]));

        $this->assertDatabaseHas('asset_inspections', [
            'id' => $inspection->id,
            'inspector_name' => 'Updated Inspector',
            'anomalies_followup' => 'Updated notes',
        ]);
    }

    public function test_admin_can_delete_inspection(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        $inspection = AssetInspection::factory()->create(['asset_id' => $asset->id]);

        $response = $this->actingAs($admin)
            ->delete(route('assets.inspections.destroy', $inspection->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('asset_inspections', ['id' => $inspection->id]);
    }

    public function test_non_admin_cannot_edit_inspection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('organization');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        $inspection = AssetInspection::factory()->create(['asset_id' => $asset->id]);

        $response = $this->actingAs($user)
            ->get(route('assets.inspections.edit', $inspection->id));

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_update_inspection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('organization');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);
        $inspection = AssetInspection::factory()->create(['asset_id' => $asset->id]);

        $response = $this->actingAs($user)
            ->put(route('assets.inspections.update', $inspection->id), [
                'inspector_name' => 'Updated',
                'inspection_date' => '2026-01-20',
                'checklist_results' => [],
            ]);

        $response->assertForbidden();
    }

    public function test_edit_page_loads_inspections_with_pagination(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();
        $asset = Asset::factory()->create(['organization_id' => $organization->id]);

        // Crear 15 inspecciones
        AssetInspection::factory()->count(15)->create(['asset_id' => $asset->id]);

        $response = $this->actingAs($admin)
            ->get(route('organizations.assets.edit', [
                'organization' => $organization->id,
                'asset' => $asset->id,
            ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Edit')
            ->has('inspections.data', 10) // Primera página con 10 items
            ->has('inspections.links')
        );
    }
}
