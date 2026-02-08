<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetInspection;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BatchCreateInspectionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles necesarios
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'organization', 'guard_name' => 'web']);
    }

    public function test_admin_can_create_batch_inspections_for_all_assets(): void
    {
        $admin = User::factory()->create(['name' => 'Test Inspector']);
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();

        // Crear 3 extintores
        $assets = Asset::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
        ]);

        $inspectionDate = '2026-01-18';

        $response = $this->actingAs($admin)
            ->post(route('organizations.assets.batch-inspections', $organization->id), [
                'inspection_date' => $inspectionDate,
            ]);

        $response->assertRedirect(route('organizations.assets.index', $organization));
        $response->assertSessionHas('success');

        // Verificar que se crearon 3 inspecciones
        $this->assertDatabaseCount('asset_inspections', 3);

        foreach ($assets as $asset) {
            $inspection = AssetInspection::where('asset_id', $asset->id)
                ->whereDate('inspection_date', $inspectionDate)
                ->first();

            $this->assertNotNull($inspection);
            $this->assertEquals('Test Inspector', $inspection->inspector_name);
            $this->assertIsArray($inspection->checklist_results);
            $this->assertCount(27, $inspection->checklist_results);

            // Verificar que todos los items están en estado 'ok'
            foreach ($inspection->checklist_results as $item) {
                $this->assertEquals('ok', $item['status']);
                $this->assertEquals($inspectionDate, $item['date']);
            }
        }
    }

    public function test_batch_inspections_skip_assets_with_existing_inspection_on_same_date(): void
    {
        $admin = User::factory()->create(['name' => 'Test Inspector']);
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();

        // Crear 2 extintores
        $asset1 = Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
        ]);
        $asset2 = Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
        ]);

        $inspectionDate = '2026-01-18';

        // Crear una inspección previa para asset1 en la misma fecha
        AssetInspection::factory()->create([
            'asset_id' => $asset1->id,
            'inspection_date' => $inspectionDate,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('organizations.assets.batch-inspections', $organization->id), [
                'inspection_date' => $inspectionDate,
            ]);

        $response->assertRedirect();

        // Solo debe haber 2 inspecciones: la existente + 1 nueva
        $this->assertDatabaseCount('asset_inspections', 2);

        // Verificar que asset2 tiene su nueva inspección
        $newInspection = AssetInspection::where('asset_id', $asset2->id)
            ->whereDate('inspection_date', $inspectionDate)
            ->first();
        $this->assertNotNull($newInspection);
    }

    public function test_non_admin_cannot_create_batch_inspections(): void
    {
        $user = User::factory()->create();
        $user->assignRole('organization');

        $organization = Organization::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('organizations.assets.batch-inspections', $organization->id), [
                'inspection_date' => '2026-01-18',
            ]);

        $response->assertForbidden();
    }

    public function test_validation_requires_inspection_date(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();

        $response = $this->actingAs($admin)
            ->post(route('organizations.assets.batch-inspections', $organization->id), [
                'inspection_date' => '',
            ]);

        $response->assertSessionHasErrors(['inspection_date']);
    }
}
