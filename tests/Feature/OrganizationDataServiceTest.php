<?php

namespace Tests\Feature;

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

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrganizationDataService::class);
        $this->organization = Organization::factory()->create();
    }

    public function test_get_demographic_details_only_counts_completed_likert_evaluations(): void
    {
        // Create a completed Likert evaluation with demographic data
        $completedLikert = PaperEvaluation::factory()
            ->likert()
            ->for($this->organization)
            ->create(['processing_status' => 'completed']);

        DemographicData::create([
            'paper_evaluation_id' => $completedLikert->id,
            'gender' => 'Masculino',
            'contract_type' => 'Tiempo Indeterminado',
            'position' => 'Operativo',
            'department' => 'Producción',
            'work_schedule' => 'Matutino',
        ]);

        // Create a pending Likert evaluation (should NOT be counted)
        $pendingLikert = PaperEvaluation::factory()
            ->likert()
            ->pending()
            ->for($this->organization)
            ->create();

        DemographicData::create([
            'paper_evaluation_id' => $pendingLikert->id,
            'gender' => 'Femenino',
            'contract_type' => 'Por Tiempo Determinado',
            'position' => 'Administrativo',
            'department' => 'RRHH',
            'work_schedule' => 'Vespertino',
        ]);

        // Create a completed non-Likert evaluation (should NOT be counted)
        $completedReferenciaIII = PaperEvaluation::factory()
            ->referenciaIII()
            ->for($this->organization)
            ->create(['processing_status' => 'completed']);

        DemographicData::create([
            'paper_evaluation_id' => $completedReferenciaIII->id,
            'gender' => 'Femenino',
            'contract_type' => 'Por Obra',
            'position' => 'Supervisor',
            'department' => 'Calidad',
            'work_schedule' => 'Nocturno',
        ]);

        // Get demographic details
        $details = $this->service->getDemographicDetails($this->organization);

        // Should only count the completed Likert evaluation
        $this->assertEquals(1, $details['total_evaluations']);

        // Should only have demographic values from completed Likert
        $this->assertEquals(['Masculino'], $details['genders']);
        $this->assertEquals(['Tiempo Indeterminado'], $details['contract_types']);
        $this->assertEquals(['Operativo'], $details['positions']);
        $this->assertEquals(['Producción'], $details['areas']);
        $this->assertEquals(['Matutino'], $details['shifts']);
    }

    public function test_get_demographic_details_returns_empty_when_no_completed_likert_evaluations(): void
    {
        // Create a pending Likert evaluation
        $pendingLikert = PaperEvaluation::factory()
            ->likert()
            ->pending()
            ->for($this->organization)
            ->create();

        DemographicData::create([
            'paper_evaluation_id' => $pendingLikert->id,
            'gender' => 'Masculino',
            'contract_type' => 'Tiempo Indeterminado',
        ]);

        $details = $this->service->getDemographicDetails($this->organization);

        $this->assertEquals(0, $details['total_evaluations']);
        $this->assertEmpty($details['genders']);
    }
}
