<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\OrganizationDataService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrganizationDataServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected OrganizationDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrganizationDataService::class);
    }

    public function test_demographic_details_returns_correct_structure(): void
    {
        // Create organization
        $organization = Organization::factory()->create();

        // Create paper evaluation with demographic data
        $paperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'evaluation_type' => 'likert',
            'processing_status' => 'completed',
        ]);

        DemographicData::factory()->create([
            'paper_evaluation_id' => $paperEvaluation->id,
            'gender' => 'Masculino',
            'contract_type' => 'Directo',
            'position' => 'Operador',
            'department' => 'Producción',
            'work_schedule' => 'Primero',
        ]);

        // Get demographic details
        $details = $this->service->getDemographicDetails($organization, 'likert');

        // Assert structure has correct keys
        $this->assertIsArray($details);
        $this->assertArrayHasKey('genders', $details);
        $this->assertArrayHasKey('contract_types', $details);
        $this->assertArrayHasKey('positions', $details);
        $this->assertArrayHasKey('areas', $details);
        $this->assertArrayHasKey('shifts', $details);
        $this->assertArrayHasKey('total_evaluations', $details);

        // Assert all values are arrays except total_evaluations
        $this->assertIsArray($details['genders']);
        $this->assertIsArray($details['contract_types']);
        $this->assertIsArray($details['positions']);
        $this->assertIsArray($details['areas']);
        $this->assertIsArray($details['shifts']);
        $this->assertIsInt($details['total_evaluations']);

        // Assert values are populated correctly
        $this->assertContains('Masculino', $details['genders']);
        $this->assertContains('Directo', $details['contract_types']);
        $this->assertContains('Operador', $details['positions']);
        $this->assertContains('Producción', $details['areas']);
        $this->assertContains('Primero', $details['shifts']);
        $this->assertEquals(1, $details['total_evaluations']);
    }

    public function test_demographic_details_returns_empty_arrays_when_no_data(): void
    {
        // Create organization without evaluations
        $organization = Organization::factory()->create();

        // Get demographic details
        $details = $this->service->getDemographicDetails($organization, 'likert');

        // Assert structure is correct with empty arrays
        $this->assertEquals([], $details['genders']);
        $this->assertEquals([], $details['contract_types']);
        $this->assertEquals([], $details['positions']);
        $this->assertEquals([], $details['areas']);
        $this->assertEquals([], $details['shifts']);
        $this->assertEquals(0, $details['total_evaluations']);
    }

    public function test_dashboard_data_for_nom002_includes_assets_with_correct_structure(): void
    {
        // Create organization
        $organization = Organization::factory()->create();

        // Create assets (fire extinguishers) for this organization
        $asset1 = Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
            'consecutive_number' => '001',
            'location' => 'Piso 1 - Entrada',
            'asset_type' => 'ABC',
        ]);

        $asset2 = Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
            'consecutive_number' => '002',
            'location' => null, // Test null location
            'asset_type' => 'CO2',
        ]);

        // Get dashboard data for nom002
        $dashboardData = $this->service->getDashboardData($organization, 'nom002');

        // Assert assets key exists
        $this->assertArrayHasKey('assets', $dashboardData);
        $this->assertIsArray($dashboardData['assets']);
        $this->assertCount(2, $dashboardData['assets']);

        // Assert first asset has correct structure and data
        $firstAsset = $dashboardData['assets'][0];
        $this->assertArrayHasKey('id', $firstAsset);
        $this->assertArrayHasKey('location', $firstAsset);
        $this->assertArrayHasKey('consecutive_number', $firstAsset);
        $this->assertArrayHasKey('type', $firstAsset);
        $this->assertArrayHasKey('status', $firstAsset);
        $this->assertArrayHasKey('reportUrl', $firstAsset);

        // Assert values are correct
        $this->assertEquals($asset1->id, $firstAsset['id']);
        $this->assertEquals('Piso 1 - Entrada', $firstAsset['location']);
        $this->assertEquals('001', $firstAsset['consecutive_number']);
        $this->assertEquals('ABC', $firstAsset['type']);
        $this->assertEquals('Pendiente', $firstAsset['status']); // No inspection yet

        // Assert second asset handles null location correctly
        $secondAsset = $dashboardData['assets'][1];
        $this->assertEquals('Sin ubicación', $secondAsset['location']);
        $this->assertEquals('002', $secondAsset['consecutive_number']);
    }

    public function test_dashboard_data_for_nom002_orders_assets_by_consecutive_number(): void
    {
        // Create organization
        $organization = Organization::factory()->create();

        // Create assets in non-sequential order
        Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
            'consecutive_number' => '010',
            'location' => 'Location 10',
        ]);

        Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
            'consecutive_number' => '002',
            'location' => 'Location 2',
        ]);

        Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
            'consecutive_number' => '005',
            'location' => 'Location 5',
        ]);

        // Get dashboard data for nom002
        $dashboardData = $this->service->getDashboardData($organization, 'nom002');

        // Assert assets are ordered by consecutive_number
        $this->assertCount(3, $dashboardData['assets']);
        $this->assertEquals('002', $dashboardData['assets'][0]['consecutive_number']);
        $this->assertEquals('005', $dashboardData['assets'][1]['consecutive_number']);
        $this->assertEquals('010', $dashboardData['assets'][2]['consecutive_number']);
    }

    public function test_dashboard_data_for_likert_does_not_include_assets(): void
    {
        // Create organization with assets
        $organization = Organization::factory()->create();

        Asset::factory()->create([
            'organization_id' => $organization->id,
            'asset_category' => 'extintor',
        ]);

        // Get dashboard data for likert (not nom002)
        $dashboardData = $this->service->getDashboardData($organization, 'likert');

        // Assert assets key does not exist for likert
        $this->assertArrayNotHasKey('assets', $dashboardData);
    }
}
