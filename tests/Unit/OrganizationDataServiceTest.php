<?php

namespace Tests\Unit;

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
}
