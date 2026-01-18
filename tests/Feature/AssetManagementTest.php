<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Instrument;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('email', 'alfredo@nhoamx.com')->first()
            ?? User::factory()->create();

        $this->organization = Organization::factory()->create();
    }

    public function test_admin_can_view_assets_index(): void
    {
        Asset::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organizations.assets.index', $this->organization));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Index')
            ->has('assets', 3)
            ->has('organization')
        );
    }

    public function test_admin_can_view_create_asset_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organizations.assets.create', $this->organization));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Create')
            ->has('organization')
        );
    }

    public function test_admin_can_view_edit_asset_page(): void
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organizations.assets.edit', [
                'organization' => $this->organization,
                'asset' => $asset,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Edit')
            ->has('organization')
            ->has('asset')
        );
    }

    public function test_can_generate_qr_code_for_asset(): void
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organizations.assets.qr', [
                'organization' => $this->organization,
                'asset' => $asset,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_can_download_qr_code_for_asset(): void
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organizations.assets.qr.download', [
                'organization' => $this->organization,
                'asset' => $asset,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertHeader('Content-Disposition');
    }

    public function test_public_can_view_asset_inspection_page(): void
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->get(route('assets.inspect', $asset));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Assets/Inspect')
            ->has('asset')
            ->has('checklist')
        );
    }

    public function test_organization_can_have_instruments(): void
    {
        $instrument = Instrument::firstOrCreate(
            ['name' => 'nom_002'],
            ['description' => 'NOM-002 Test']
        );

        $this->organization->instruments()->attach($instrument);

        $this->assertTrue($this->organization->hasInstrument('nom_002'));
        $this->assertFalse($this->organization->hasInstrument('nom_999'));
    }

    public function test_asset_factory_creates_valid_asset(): void
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'organization_id' => $this->organization->id,
        ]);

        $this->assertNotNull($asset->serial_number);
        $this->assertNotNull($asset->location);
    }

    public function test_asset_belongs_to_organization(): void
    {
        $asset = Asset::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $this->assertTrue($asset->organization->is($this->organization));
    }

    public function test_consecutive_number_is_unique_per_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // Crear asset con consecutive_number "001" en org1
        $asset1 = Asset::factory()->create([
            'organization_id' => $org1->id,
            'consecutive_number' => '001',
        ]);

        // Debería permitir el mismo consecutive_number en org2
        $asset2 = Asset::factory()->create([
            'organization_id' => $org2->id,
            'consecutive_number' => '001',
        ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset1->id,
            'organization_id' => $org1->id,
            'consecutive_number' => '001',
        ]);

        $this->assertDatabaseHas('assets', [
            'id' => $asset2->id,
            'organization_id' => $org2->id,
            'consecutive_number' => '001',
        ]);

        // Intentar crear otro asset con "001" en org1 debería fallar
        $this->expectException(\Illuminate\Database\QueryException::class);
        Asset::factory()->create([
            'organization_id' => $org1->id,
            'consecutive_number' => '001',
        ]);
    }

    public function test_cannot_create_asset_with_duplicate_consecutive_number_in_same_organization(): void
    {
        Asset::factory()->create([
            'organization_id' => $this->organization->id,
            'consecutive_number' => '001',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('organizations.assets.store', $this->organization), [
                'asset_category' => 'extintor',
                'consecutive_number' => '001',
                'serial_number' => 'EXT-TEST-002',
                'location' => 'Test Location',
            ]);

        $response->assertSessionHasErrors(['consecutive_number']);
    }

    public function test_can_create_asset_with_same_consecutive_number_in_different_organizations(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Asset::factory()->create([
            'organization_id' => $org1->id,
            'consecutive_number' => '001',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('organizations.assets.store', $org2), [
                'asset_category' => 'extintor',
                'consecutive_number' => '001',
                'serial_number' => 'EXT-TEST-002',
                'location' => 'Test Location',
            ]);

        $response->assertRedirect(route('organizations.assets.index', $org2));
        $this->assertDatabaseHas('assets', [
            'organization_id' => $org2->id,
            'consecutive_number' => '001',
        ]);
    }

    public function test_can_download_import_template(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organizations.assets.template', $this->organization));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
