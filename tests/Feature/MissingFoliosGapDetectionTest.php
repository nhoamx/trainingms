<?php

namespace Tests\Feature;

use App\Models\FolioBatch;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MissingFoliosGapDetectionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create organization
        $this->organization = Organization::factory()->create();

        // Create organization user
        $this->user = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->user->assignRole('organization');

        // Create folio batch for the organization
        FolioBatch::create([
            'organization_id' => $this->organization->id,
            'name' => 'Lote 1',
            'description' => 'Test batch',
            'start_number' => 1,
            'end_number' => 100,
            'quantity' => 100,
            'type' => 'presencial',
            'active' => true,
        ]);
    }

    public function test_detects_gaps_in_uploaded_folios(): void
    {
        // Create evaluations: 0001-0039, skip 0040-0045, then 0046-0064, skip 0065-0080, then 0081-0100
        
        // First range: 0001-0039
        for ($i = 1; $i <= 39; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 0040-0045 (6 folios)

        // Second range: 0046-0064
        for ($i = 46; $i <= 64; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 0065-0080 (16 folios)

        // Third range: 0081-0100
        for ($i = 81; $i <= 100; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        
        // Verify that missing folios are detected
        $response->assertInertia(fn ($page) => $page
            ->has('missingFolios', 1)
            ->where('missingFolios.0.count', 22) // 6 + 16 = 22 missing folios
            ->where('missingFolios.0.batch_name', 'Lote 1')
        );

        // Verify the gaps contain the expected folios
        $missingFolios = $response->viewData('page')['props']['missingFolios'][0]['folios'];
        
        // Check first gap: 0040-0045
        $this->assertContains('0040', $missingFolios);
        $this->assertContains('0041', $missingFolios);
        $this->assertContains('0042', $missingFolios);
        $this->assertContains('0043', $missingFolios);
        $this->assertContains('0044', $missingFolios);
        $this->assertContains('0045', $missingFolios);

        // Check second gap: 0065-0080
        $this->assertContains('0065', $missingFolios);
        $this->assertContains('0070', $missingFolios);
        $this->assertContains('0075', $missingFolios);
        $this->assertContains('0080', $missingFolios);
    }

    public function test_no_missing_folios_when_sequence_is_continuous(): void
    {
        // Create continuous sequence: 0001-0050
        for ($i = 1; $i <= 50; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        
        // No gaps should be detected
        $response->assertInertia(fn ($page) => $page
            ->has('missingFolios', 0)
        );
    }

    public function test_no_missing_folios_with_only_one_evaluation(): void
    {
        // Create only one evaluation
        PaperEvaluation::factory()->create([
            'organization_id' => $this->organization->id,
            'personal_folio' => '0050',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        
        // No gaps should be detected with only 1 evaluation
        $response->assertInertia(fn ($page) => $page
            ->has('missingFolios', 0)
        );
    }

    public function test_detects_single_gap_in_sequence(): void
    {
        // Create evaluations: 0001-0010, skip 0011, then 0012-0020
        
        // First range: 0001-0010
        for ($i = 1; $i <= 10; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 0011

        // Second range: 0012-0020
        for ($i = 12; $i <= 20; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        
        // Verify that the single missing folio is detected
        $response->assertInertia(fn ($page) => $page
            ->has('missingFolios', 1)
            ->where('missingFolios.0.count', 1)
        );

        $missingFolios = $response->viewData('page')['props']['missingFolios'][0]['folios'];
        $this->assertContains('0011', $missingFolios);
    }

    public function test_ignores_folios_outside_uploaded_range(): void
    {
        // Create evaluations only in range 0050-0060
        for ($i = 50; $i <= 60; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        
        // No gaps should be detected because we only look within the uploaded range
        $response->assertInertia(fn ($page) => $page
            ->has('missingFolios', 0)
        );
    }
}
