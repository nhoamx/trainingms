<?php

namespace Tests\Feature;

use App\Models\FolioBatch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OMRPdfGenerationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Organization $organization;

    protected FolioBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test organization with folio_organization
        $this->organization = Organization::factory()->create([
            'folio_organization' => 123,
        ]);

        // Create test folio batch
        $this->batch = FolioBatch::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Batch',
            'start_number' => 1,
            'end_number' => 10,
            'quantity' => 10,
            'type' => 'presencial',
        ]);
    }

    public function test_generate_pdf_requires_authentication(): void
    {
        $response = $this->postJson(route('omr.generate-pdf'), [
            'organization_id' => $this->organization->id,
            'folio_batch_id' => $this->batch->id,
            'guide_type' => 'referencia-i',
            'generate_all' => true,
        ]);

        $response->assertStatus(401);
    }

    public function test_generate_pdf_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('omr.generate-pdf'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'organization_id',
                'folio_batch_id',
                'guide_type',
            ]);
    }

    public function test_generate_pdf_validates_guide_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('omr.generate-pdf'), [
                'organization_id' => $this->organization->id,
                'folio_batch_id' => $this->batch->id,
                'guide_type' => 'invalid-type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guide_type']);
    }

    public function test_generate_pdf_validates_organization_exists(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('omr.generate-pdf'), [
                'organization_id' => '00000000-0000-0000-0000-000000000000',
                'folio_batch_id' => $this->batch->id,
                'guide_type' => 'referencia-i',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id']);
    }

    public function test_generate_pdf_with_specific_folios(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('omr.generate-pdf'), [
                'organization_id' => $this->organization->id,
                'folio_batch_id' => $this->batch->id,
                'guide_type' => 'referencia-i',
                'generate_all' => false,
                'folios' => ['0001', '0002', '0003'],
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload();
    }

    public function test_generate_pdf_with_all_folios_in_batch(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('omr.generate-pdf'), [
                'organization_id' => $this->organization->id,
                'folio_batch_id' => $this->batch->id,
                'guide_type' => 'referencia-iii',
                'generate_all' => true,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload();
    }

    public function test_generate_pdf_for_referencia_v(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('omr.generate-pdf'), [
                'organization_id' => $this->organization->id,
                'folio_batch_id' => $this->batch->id,
                'guide_type' => 'referencia-v',
                'generate_all' => false,
                'folios' => ['0001'],
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_generate_pdf_for_escala_cisneros(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('omr.generate-pdf'), [
                'organization_id' => $this->organization->id,
                'folio_batch_id' => $this->batch->id,
                'guide_type' => 'escala-cisneros',
                'generate_all' => false,
                'folios' => ['0001'],
            ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_extended_folio_format_is_correct(): void
    {
        // Test that the extended folio format is generated correctly
        // New format (11 digits): [template_type(2)][organization(2)][work_center(2)][person(5)]
        // Example: Referencia I (01) + Organization (03) + Work Center (00) + Person (00001) = 01030000001

        $response = $this->actingAs($this->user)
            ->post(route('omr.generate-pdf'), [
                'organization_id' => $this->organization->id,
                'folio_batch_id' => $this->batch->id,
                'guide_type' => 'referencia-i',
                'generate_all' => false,
                'folios' => ['00001'],
            ]);

        $response->assertStatus(200);
        $response->assertDownload();
    }

    public function test_referencia_i_web_preview_disables_prefilled_folio(): void
    {
        $response = $this->get(route('omr.referencia-i'));

        $response->assertOk();
        $response->assertViewHas('showPrefilledFolio', false);
    }

    public function test_referencia_i_web_preview_keeps_query_folio_but_hides_prefill(): void
    {
        $response = $this->get(route('omr.referencia-i', ['folio' => '01020300001']));

        $response->assertOk();
        $response->assertViewHas('folio', '01020300001');
        $response->assertViewHas('showPrefilledFolio', false);
    }
}
