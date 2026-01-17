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

    protected User $adminUser;

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

        // Create admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

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
        // Create evaluations with gaps:
        // Range 1: folios 1-39 (39 folios)
        // GAP: folios 40-45 (6 folios missing)
        // Range 2: folios 46-64 (19 folios)
        // GAP: folios 65-80 (16 folios missing)
        // Range 3: folios 81-100 (20 folios)
        // Total: 78 uploaded, 22 missing in gaps

        // First range: 1-39
        for ($i = 1; $i <= 39; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 40-45 (6 folios)

        // Second range: 46-64
        for ($i = 46; $i <= 64; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 65-80 (16 folios)

        // Third range: 81-100
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
            ->has('missingFolios.0.folios', 22)
        );
    }

    public function test_missing_folios_visible_to_admin(): void
    {
        // Create evaluations with a gap
        for ($i = 1; $i <= 10; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 11

        for ($i = 12; $i <= 20; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.results.list', ['organization' => $this->organization->id]));

        $response->assertStatus(200);

        // Verify admin sees isAdmin flag
        $response->assertInertia(fn ($page) => $page
            ->where('isAdmin', true)
            ->has('missingFolios', 1)
        );
    }

    public function test_organization_user_sees_missing_folios_data(): void
    {
        // Create evaluations with a gap
        for ($i = 1; $i <= 10; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 11

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

        // Organization users see the data but not the isAdmin flag
        $response->assertInertia(fn ($page) => $page
            ->where('isAdmin', false)
            ->has('missingFolios', 1)
        );
    }

    public function test_admin_can_download_gap_folios(): void
    {
        // Create evaluations with gaps
        for ($i = 1; $i <= 10; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        // Skip 11

        for ($i = 12; $i <= 15; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.results.download-gap-folios', ['organization' => $this->organization->id]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Verify CSV content contains the missing folio
        $content = $response->streamedContent();
        $this->assertStringContainsString('0011', $content);
    }

    public function test_organization_user_cannot_download_gap_folios(): void
    {
        // Create evaluations with gaps
        for ($i = 1; $i <= 10; $i++) {
            PaperEvaluation::factory()->create([
                'organization_id' => $this->organization->id,
                'personal_folio' => str_pad($i, 4, '0', STR_PAD_LEFT),
                'evaluation_type' => 'referencia_iii',
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('organization.results.download-gap-folios', ['organization' => $this->organization->id]));

        // Should be forbidden (403)
        $response->assertStatus(403);
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
            ->has('missingFolios.0.folios', 1)
        );
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
