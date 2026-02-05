<?php

namespace Tests\Feature;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationServiceTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected OrganizationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrganizationService::class);
    }

    /**
     * Test organization creation automatically creates primary work center
     */
    public function test_create_organization_automatically_creates_primary_work_center(): void
    {
        $data = [
            'name' => 'Test Organization',
            'razon_social' => 'Test SA de CV',
            'rfc' => 'TST123456ABC',
            'registro_patronal' => 'REG-12345',
            'calle_numero' => 'Main St 123',
            'colonia' => 'Centro',
            'codigo_postal' => '12345',
            'municipio' => 'Test City',
            'estado' => 'Test State',
            'contacto_email' => 'contact@test.com',
            'contacto_movil' => '5551234567',
        ];

        $organization = $this->service->createWithWorkCenter($data);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization',
            'rfc' => 'TST123456ABC',
        ]);

        $this->assertDatabaseHas('work_centers', [
            'organization_id' => $organization->id,
            'code' => '0001',
            'name' => 'Test Organization',
            'type' => WorkCenterType::Headquarters->value,
            'is_primary' => true,
            'legal_name' => 'Test SA de CV',
            'tax_id' => 'TST123456ABC',
            'employer_registration' => 'REG-12345',
            'street_address' => 'Main St 123',
            'neighborhood' => 'Centro',
            'postal_code' => '12345',
            'municipality' => 'Test City',
            'state' => 'Test State',
            'email' => 'contact@test.com',
            'phone' => '5551234567',
        ]);

        $this->assertCount(1, $organization->workCenters);
        $this->assertTrue($organization->workCenters->first()->is_primary);
    }

    /**
     * Test folio generation when not provided
     */
    public function test_generates_folio_when_not_provided(): void
    {
        $data = [
            'name' => 'Test Org Without Folio',
        ];

        $organization = $this->service->createWithWorkCenter($data);

        $this->assertNotNull($organization->folio_organization);
        $this->assertGreaterThanOrEqual(100, $organization->folio_organization);
        $this->assertLessThanOrEqual(999, $organization->folio_organization);
    }

    /**
     * Test organization update syncs primary work center data
     */
    public function test_update_organization_syncs_primary_work_center(): void
    {
        // Create organization with work center
        $organization = $this->service->createWithWorkCenter([
            'name' => 'Original Name',
            'razon_social' => 'Original SA',
            'rfc' => 'ORI123456ABC',
            'calle_numero' => 'Old Street 1',
            'contacto_email' => 'old@email.com',
        ]);

        $primaryCenter = $organization->workCenters()->where('is_primary', true)->first();

        // Update organization
        $updatedData = [
            'name' => 'Updated Name',
            'razon_social' => 'Updated SA',
            'rfc' => 'UPD123456ABC',
            'calle_numero' => 'New Street 999',
            'contacto_email' => 'new@email.com',
        ];

        $organization = $this->service->updateWithWorkCenter($organization, $updatedData);
        $primaryCenter->refresh();

        // Verify organization updated
        $this->assertEquals('Updated Name', $organization->name);
        $this->assertEquals('Updated SA', $organization->razon_social);

        // Verify work center synced
        $this->assertEquals('Updated Name', $primaryCenter->name);
        $this->assertEquals('Updated SA', $primaryCenter->legal_name);
        $this->assertEquals('UPD123456ABC', $primaryCenter->tax_id);
        $this->assertEquals('New Street 999', $primaryCenter->street_address);
        $this->assertEquals('new@email.com', $primaryCenter->email);
    }

    /**
     * Test update creates primary work center if missing
     */
    public function test_update_creates_primary_work_center_if_missing(): void
    {
        // Create organization manually without work center
        $organization = Organization::factory()->create([
            'name' => 'Org Without Center',
        ]);

        $this->assertCount(0, $organization->workCenters);

        // Update should create the missing primary work center
        $organization = $this->service->updateWithWorkCenter($organization, [
            'name' => 'Org With Center Now',
            'razon_social' => 'Test SA',
        ]);

        $this->assertCount(1, $organization->workCenters);
        $this->assertTrue($organization->workCenters->first()->is_primary);
        $this->assertEquals('Org With Center Now', $organization->workCenters->first()->name);
    }

    /**
     * Test logo upload is handled correctly
     */
    public function test_handles_logo_upload(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png');

        $organization = $this->service->createWithWorkCenter([
            'name' => 'Org With Logo',
        ], $logo);

        $this->assertNotNull($organization->logo);
        Storage::disk('public')->assertExists($organization->logo);
    }

    /**
     * Test all organization data fields are copied to work center
     */
    public function test_all_relevant_fields_copied_to_work_center(): void
    {
        $data = [
            'name' => 'Complete Org',
            'razon_social' => 'Complete SA de CV',
            'rfc' => 'COM123456ABC',
            'registro_patronal' => 'REG-98765',
            'calle_numero' => 'Complete St 456',
            'colonia' => 'Complete Colony',
            'codigo_postal' => '54321',
            'municipio' => 'Complete City',
            'estado' => 'Complete State',
            'contacto_email' => 'complete@test.com',
            'contacto_movil' => '5559876543',
            // These should NOT be copied to work center
            'total_trabajadores' => 100,
            'muestra_aplicada' => 50,
            'comite_integrantes' => 5,
        ];

        $organization = $this->service->createWithWorkCenter($data);
        $workCenter = $organization->workCenters()->first();

        // Fields that should be copied
        $this->assertEquals('Complete Org', $workCenter->name);
        $this->assertEquals('Complete SA de CV', $workCenter->legal_name);
        $this->assertEquals('COM123456ABC', $workCenter->tax_id);
        $this->assertEquals('REG-98765', $workCenter->employer_registration);
        $this->assertEquals('Complete St 456', $workCenter->street_address);
        $this->assertEquals('Complete Colony', $workCenter->neighborhood);
        $this->assertEquals('54321', $workCenter->postal_code);
        $this->assertEquals('Complete City', $workCenter->municipality);
        $this->assertEquals('Complete State', $workCenter->state);
        $this->assertEquals('complete@test.com', $workCenter->email);
        $this->assertEquals('5559876543', $workCenter->phone);

        // Work center specific fields
        $this->assertEquals('0001', $workCenter->code);
        $this->assertEquals(WorkCenterType::Headquarters, $workCenter->type);
        $this->assertTrue($workCenter->is_primary);
    }
}
