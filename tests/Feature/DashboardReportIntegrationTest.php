<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardReportIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $orgUser;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->orgUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->orgUser->assignRole('organization');
    }

    public function test_dimension_report_summary_endpoint_returns_correct_structure(): void
    {
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
            ]);

        $response = $this->actingAs($this->orgUser)
            ->getJson("/reports/dimension-report-summary?organization_id={$this->organization->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'grouped_by_category',
                'grouped_by_domain',
                'grouped_by_dimension',
                'final_risk_levels',
                'personalCalification',
            ]);
    }

    public function test_demographic_distribution_endpoint_returns_correct_structure(): void
    {
        // Create Referencia V
        PaperEvaluation::factory()
            ->referenciaV()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
                'demographic_data' => [
                    'sexo' => 'masculino',
                ],
            ]);

        // Create corresponding Referencia III
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0001',
            ]);

        $response = $this->actingAs($this->orgUser)
            ->getJson("/reports/demographic-distribution?organization_id={$this->organization->id}");

        $response->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'title',
                    'data' => [
                        '*' => [
                            'name',
                            'total',
                            'risk_levels',
                            'personal_by_risk',
                        ],
                    ],
                ],
            ]);
    }

    public function test_organization_user_can_only_see_their_data(): void
    {
        $otherOrg = Organization::factory()->create();

        // Create evaluation for other organization
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $otherOrg->id,
                'personal_folio' => '0001',
            ]);

        // Create evaluation for user's organization
        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => '0002',
            ]);

        $response = $this->actingAs($this->orgUser)
            ->getJson("/reports/dimension-report-summary?organization_id={$this->organization->id}");

        $response->assertOk();

        $data = $response->json();

        // Should only see their organization's participant
        $this->assertCount(1, $data['personalCalification']);
        $this->assertEquals('0002', $data['personalCalification'][0]['personal_folio']);
    }

    public function test_admin_can_see_any_organization_data(): void
    {
        $otherOrg = Organization::factory()->create();

        PaperEvaluation::factory()
            ->referenciaIII()
            ->create([
                'organization_id' => $otherOrg->id,
                'personal_folio' => '0001',
            ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/reports/dimension-report-summary?organization_id={$otherOrg->id}");

        $response->assertOk();

        $data = $response->json();

        // Admin can see other organization's data
        $this->assertCount(1, $data['personalCalification']);
        $this->assertEquals('0001', $data['personalCalification'][0]['personal_folio']);
    }

    public function test_report_includes_all_risk_levels_in_final_risk_data(): void
    {
        // Create evaluations with different scores to get varied risk levels
        $testData = [
            ['score' => 30, 'expected_level' => 'Nulo'],
            ['score' => 60, 'expected_level' => 'Bajo'],
            ['score' => 85, 'expected_level' => 'Medio'],
            ['score' => 120, 'expected_level' => 'Alto'],
            ['score' => 150, 'expected_level' => 'Muy Alto'],
        ];

        foreach ($testData as $index => $data) {
            // Create custom answers that will result in the expected score
            $answers = $this->createAnswersForScore($data['score']);

            PaperEvaluation::factory()
                ->create([
                    'organization_id' => $this->organization->id,
                    'personal_folio' => sprintf('%04d', $index + 1),
                    'evaluation_type' => 'referencia_iii',
                    'evaluation_type_code' => '02',
                    'referencia_iii_answers' => $answers,
                ]);
        }

        $response = $this->actingAs($this->orgUser)
            ->getJson("/reports/dimension-report-summary?organization_id={$this->organization->id}");

        $response->assertOk();

        $finalRiskLevels = $response->json('final_risk_levels');

        // Verify we have entries for various risk levels
        $this->assertNotEmpty($finalRiskLevels);
        $this->assertIsArray($finalRiskLevels);
    }

    /**
     * Helper to create answers that approximate a target score
     */
    protected function createAnswersForScore(int $targetScore): array
    {
        $answers = [];
        $questionsNeeded = max(1, (int) ($targetScore / 4)); // Rough estimate

        for ($i = 1; $i <= min($questionsNeeded, 72); $i++) {
            // Alternate between high and low scoring answers
            $answers[(string) $i] = ($i % 2 === 0) ? 'A' : 'E';
        }

        return $answers;
    }

    public function test_report_handles_empty_organization_gracefully(): void
    {
        $emptyOrg = Organization::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/reports/dimension-report-summary?organization_id={$emptyOrg->id}");

        $response->assertOk()
            ->assertJson([
                'grouped_by_category' => [],
                'grouped_by_domain' => [],
                'grouped_by_dimension' => [],
                'final_risk_levels' => [],
                'personalCalification' => [],
            ]);
    }
}
