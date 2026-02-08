<?php

namespace Tests\Feature;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationWorkCenterCreationTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role if it doesn't exist
        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        // Create user with admin role
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        Storage::fake('public');
    }

    /**
     * Helper method to add wc_ prefix to work center fields for wizard compatibility
     */
    protected function prepareWizardData(array $data): array
    {
        $workCenterFields = ['type', 'legal_name', 'tax_id', 'employer_registration',
            'street_address', 'neighborhood', 'postal_code', 'municipality', 'state'];

        $result = [];
        $hasOrgName = isset($data['name']);

        // Process all fields except 'name'
        foreach ($data as $key => $value) {
            if ($key === 'name') {
                continue; // Handle name separately
            }

            if (in_array($key, $workCenterFields)) {
                $result["wc_{$key}"] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        // Handle 'name' field
        if ($hasOrgName) {
            // Both org and work center can use the same name
            $result['name'] = $data['name'];
            $result['wc_name'] = $data['name'];
        }

        return $result;
    }

    /**
     * Test organization creation with work center data (new wizard flow)
     */
    public function test_creates_organization_with_work_center_data_new_flow(): void
    {
        $data = [
            // Organization corporate data
            'name' => 'Planta Principal Test',  // Shared name for both
            'actividad_principal' => 'Manufactura de productos',
            // Work center specific fields
            'type' => WorkCenterType::Plant->value,
            // Fiscal data
            'legal_name' => 'Planta Principal Test S.A. de C.V.',
            'tax_id' => 'PPT850101XYZ',
            'employer_registration' => 'A1234567890',
            // Address
            'street_address' => 'Av. Industrial 123',
            'neighborhood' => 'Zona Industrial',
            'postal_code' => '12345',
            'municipality' => 'Ciudad Test',
            'state' => 'Estado Test',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        // Verify organization created
        $this->assertDatabaseHas('organizations', [
            'name' => 'Planta Principal Test',
            'actividad_principal' => 'Manufactura de productos',
        ]);

        $organization = Organization::where('name', 'Planta Principal Test')->first();

        // Verify folio was auto-generated
        $this->assertNotNull($organization->folio_organization);

        // Verify primary work center created with correct data
        $this->assertDatabaseHas('work_centers', [
            'organization_id' => $organization->id,
            'code' => '0001',
            'name' => 'Planta Principal Test',
            'type' => WorkCenterType::Plant->value,
            'is_primary' => true,
            'legal_name' => 'Planta Principal Test S.A. de C.V.',
            'tax_id' => 'PPT850101XYZ',
            'employer_registration' => 'A1234567890',
            'street_address' => 'Av. Industrial 123',
            'neighborhood' => 'Zona Industrial',
            'postal_code' => '12345',
            'municipality' => 'Ciudad Test',
            'state' => 'Estado Test',
        ]);

        $workCenter = $organization->workCenters()->first();
        $this->assertTrue($workCenter->is_primary);
        $this->assertTrue($workCenter->type === WorkCenterType::Plant);
    }

    /**
     * Test folio auto-generation when not provided
     */
    public function test_folio_auto_generates_when_not_provided(): void
    {
        $data = [
            'name' => 'Test Org Sin Folio',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Test Org Sin Folio S.A. de C.V.',
            'tax_id' => 'TOS850101XYZ',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::where('name', 'Test Org Sin Folio')->first();

        // Verify folio was generated
        $this->assertNotNull($organization->folio_organization);
        $this->assertIsInt($organization->folio_organization);

        // Verify folio is reasonable (timestamp-based should be large number)
        $this->assertGreaterThan(1000000, $organization->folio_organization);
    }

    /**
     * Test multiple organizations get unique folios
     */
    public function test_multiple_organizations_get_unique_folios(): void
    {
        $data1 = [
            'name' => 'Org 1',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Org 1 S.A. de C.V.',
            'tax_id' => 'ORG850101XY1',
        ];
        $data2 = [
            'name' => 'Org 2',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Org 2 S.A. de C.V.',
            'tax_id' => 'ORG850101XY2',
        ];

        $this->actingAs($this->user)->post(route('organizations.store'), $this->prepareWizardData($data1));
        $this->actingAs($this->user)->post(route('organizations.store'), $this->prepareWizardData($data2));

        $org1 = Organization::where('name', 'Org 1')->first();
        $org2 = Organization::where('name', 'Org 2')->first();

        $this->assertNotNull($org1->folio_organization);
        $this->assertNotNull($org2->folio_organization);
        $this->assertNotEquals($org1->folio_organization, $org2->folio_organization);
    }

    /**
     * Test required fields validation
     */
    public function test_validates_required_fields(): void
    {
        // Test without name (required field)
        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), []);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test optional fields pass validation
     */
    public function test_optional_fields_are_optional(): void
    {
        $minimalData = [
            'name' => 'Minimal Org',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Minimal Org S.A. de C.V.',
            'tax_id' => 'MIN850101XYZ',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($minimalData));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('organizations', [
            'name' => 'Minimal Org',
        ]);
    }

    /**
     * Test logo upload works correctly
     */
    public function test_uploads_logo_correctly(): void
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.png', 500, 500);

        $data = [
            'name' => 'Test Org con Logo',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Test Org con Logo S.A. de C.V.',
            'tax_id' => 'TOL850101XYZ',
            'logo' => $logo,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::where('name', 'Test Org con Logo')->first();

        // Verify logo path stored in database
        $this->assertNotNull($organization->logo);
        $this->assertStringContainsString('organizations/', $organization->logo);

        // Verify file was stored in public disk
        Storage::disk('public')->assertExists($organization->logo);
    }

    /**
     * Test logo validation rejects invalid files
     */
    public function test_logo_validation_rejects_invalid_files(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $data = [
            'name' => 'Test Org',
            'logo' => $invalidFile,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertSessionHasErrors('logo');
    }

    /**
     * Test logo validation rejects oversized files
     */
    public function test_logo_validation_rejects_oversized_files(): void
    {
        $oversizedFile = UploadedFile::fake()->image('huge.jpg')->size(15000); // 15MB

        $data = [
            'name' => 'Test Org',
            'logo' => $oversizedFile,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertSessionHasErrors('logo');
    }

    /**
     * Test work center code auto-generation
     */
    public function test_work_center_code_auto_generates(): void
    {
        $data = [
            'name' => 'Test Work Center',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Test Work Center S.A. de C.V.',
            'tax_id' => 'TWC850101XYZ',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::where('name', 'Test Work Center')->first();
        $this->assertNotNull($organization, 'Organization should be created');

        $workCenter = $organization->workCenters()->first();
        $this->assertNotNull($workCenter, 'Work center should exist');
        $this->assertEquals('0001', $workCenter->code);
    }

    /**
     * Test slug auto-generation for organization
     */
    public function test_organization_slug_auto_generates(): void
    {
        $data = [
            'name' => 'Test Organization Name',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Test Organization Name S.A. de C.V.',
            'tax_id' => 'TON850101XYZ',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::where('name', 'Test Organization Name')->first();

        $this->assertEquals('test-organization-name', $organization->slug);
    }

    /**
     * Test slug uniqueness handling
     */
    public function test_organization_slug_handles_duplicates(): void
    {
        // Create first organization
        $data1 = [
            'name' => 'Duplicate Name',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Duplicate Name S.A. de C.V.',
            'tax_id' => 'DUP850101XY1',
        ];
        $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data1));

        // Create second organization with same name
        $data2 = [
            'name' => 'Duplicate Name',
            'type' => WorkCenterType::Headquarters->value,
            'legal_name' => 'Duplicate Name Dos S.A. de C.V.',
            'tax_id' => 'DUP850101XY2',
        ];
        $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data2));

        $org1 = Organization::where('slug', 'duplicate-name')->first();
        $org2 = Organization::where('slug', 'duplicate-name-1')->first();

        $this->assertNotNull($org1);
        $this->assertNotNull($org2);
        $this->assertNotEquals($org1->slug, $org2->slug);
    }

    /**
     * Test work center slug auto-generation
     */
    public function test_work_center_slug_auto_generates(): void
    {
        $data = [
            'name' => 'Test Work Center Location',
            'type' => WorkCenterType::Branch->value,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::where('name', 'Test Work Center Location')->first();
        $workCenter = $organization->workCenters()->first();

        $this->assertNotNull($workCenter->slug);
        $this->assertStringContainsString('test-work-center-location', $workCenter->slug);
    }

    /**
     * Test primary work center type defaults to headquarters
     */
    public function test_primary_work_center_defaults_to_headquarters_type(): void
    {
        $data = [
            'name' => 'Test HQ Org',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::where('name', 'Test HQ Org')->first();
        $primaryWorkCenter = $organization->primaryWorkCenter;

        $this->assertNotNull($primaryWorkCenter);
        // Compare as enums since the type field is cast to enum
        $this->assertTrue($primaryWorkCenter->type === WorkCenterType::Headquarters);
    }

    /**
     * Test organization relationships are properly loaded
     */
    public function test_organization_relationships_load_correctly(): void
    {
        $data = [
            'name' => 'Main Office',
            'type' => WorkCenterType::Office->value,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::with('workCenters')->where('name', 'Main Office')->first();

        $this->assertNotNull($organization->workCenters);
        $this->assertCount(1, $organization->workCenters);
        $this->assertInstanceOf(WorkCenter::class, $organization->workCenters->first());
    }

    /**
     * Test work center belongs to organization
     */
    public function test_work_center_belongs_to_organization(): void
    {
        $data = [
            'name' => 'Owner Org',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('organizations.store'), $this->prepareWizardData($data));

        $response->assertRedirect();

        $organization = Organization::where('name', 'Owner Org')->first();
        $workCenter = $organization->workCenters()->first();

        $this->assertNotNull($workCenter->organization);
        $this->assertEquals($organization->id, $workCenter->organization_id);
        $this->assertEquals($organization->name, $workCenter->organization->name);
    }
}
