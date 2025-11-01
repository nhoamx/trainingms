<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\PaperEvaluationReportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaperEvaluationReportServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected PaperEvaluationReportService $service;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaperEvaluationReportService::class);
        $this->organization = Organization::factory()->create();
    }

    public function test_returns_empty_structure_when_no_evaluations(): void
    {
        $result = $this->service->getReportSummaryByOrganization($this->organization->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('grouped_by_category', $result);
        $this->assertArrayHasKey('grouped_by_domain', $result);
        $this->assertArrayHasKey('grouped_by_dimension', $result);
        $this->assertArrayHasKey('final_risk_levels', $result);
        $this->assertArrayHasKey('personalCalification', $result);

        $this->assertEmpty($result['grouped_by_category']);
        $this->assertEmpty($result['grouped_by_domain']);
        $this->assertEmpty($result['grouped_by_dimension']);
    }

    public function test_aggregates_category_data_correctly(): void
    {
        // Create test evaluations
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
            ]);

        $result = $this->service->getReportSummaryByOrganization($this->organization->id);

        $this->assertNotEmpty($result['grouped_by_category']);
        $this->assertIsArray($result['grouped_by_category']);

        $firstCategory = $result['grouped_by_category'][0];
        $this->assertArrayHasKey('categoria', $firstCategory);
        $this->assertArrayHasKey('nivel_riesgo', $firstCategory);
        $this->assertArrayHasKey('conteo', $firstCategory);
        $this->assertArrayHasKey('personal', $firstCategory);
    }

    public function test_aggregates_domain_data_correctly(): void
    {
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
            ]);

        $result = $this->service->getReportSummaryByOrganization($this->organization->id);

        $this->assertNotEmpty($result['grouped_by_domain']);
        $this->assertIsArray($result['grouped_by_domain']);

        $firstDomain = $result['grouped_by_domain'][0];
        $this->assertArrayHasKey('dominio', $firstDomain);
        $this->assertArrayHasKey('nivel_riesgo', $firstDomain);
        $this->assertArrayHasKey('conteo', $firstDomain);
        $this->assertArrayHasKey('personal', $firstDomain);
    }

    public function test_aggregates_final_risk_levels_correctly(): void
    {
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
            ]);

        $result = $this->service->getReportSummaryByOrganization($this->organization->id);

        $this->assertIsArray($result['final_risk_levels']);

        $totalCount = array_sum(array_column($result['final_risk_levels'], 'conteo'));
        $this->assertEquals(1, $totalCount);
    }

    public function test_aggregates_participant_scores_correctly(): void
    {
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
            ]);

        $result = $this->service->getReportSummaryByOrganization($this->organization->id);

        $this->assertNotEmpty($result['personalCalification']);
        $this->assertCount(1, $result['personalCalification']);

        $participant = $result['personalCalification'][0];
        $this->assertArrayHasKey('personal_folio', $participant);
        $this->assertArrayHasKey('calificacion', $participant);
        $this->assertArrayHasKey('nivel_riesgo', $participant);
        $this->assertEquals('0001', $participant['personal_folio']);
    }

    public function test_demographic_distribution_returns_empty_when_no_data(): void
    {
        $result = $this->service->getDemographicDistribution($this->organization->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_demographic_distribution_aggregates_correctly(): void
    {
        // Create Referencia V evaluation
        PaperEvaluation::factory()
            ->referenciaV()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
                'demographic_data' => [
                    'sexo' => 'masculino',
                    'estado_civil' => 'soltero',
                ],
            ]);

        // Create corresponding Referencia III for risk level
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
            ]);

        $result = $this->service->getDemographicDistribution($this->organization->id);

        $this->assertNotEmpty($result);
        $this->assertIsArray($result);

        // Should have sections for each demographic category
        $this->assertGreaterThan(0, count($result));

        // Check structure
        $firstSection = $result[0];
        $this->assertArrayHasKey('title', $firstSection);
        $this->assertArrayHasKey('data', $firstSection);
    }

    public function test_only_processes_completed_paper_evaluations(): void
    {
        // Create pending evaluation (should be ignored)
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
                'processing_status' => 'pending',
            ]);

        // Create completed evaluation
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0002',
                'processing_status' => 'completed',
            ]);

        $result = $this->service->getReportSummaryByOrganization($this->organization->id);

        // Should only count the completed evaluation
        $this->assertCount(1, $result['personalCalification']);
        $this->assertEquals('0002', $result['personalCalification'][0]['personal_folio']);
    }
}
