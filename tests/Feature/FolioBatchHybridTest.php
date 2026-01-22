<?php

namespace Tests\Feature;

use App\Models\FolioBatch;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FolioBatchHybridTest extends TestCase
{
    use DatabaseTransactions;

    public function test_folio_batch_has_hibrido_type_constant(): void
    {
        $this->assertEquals('hibrido', FolioBatch::TYPE_HIBRIDO);
        $this->assertEquals('presencial', FolioBatch::TYPE_PRESENCIAL);
        $this->assertEquals('en_linea', FolioBatch::TYPE_EN_LINEA);
    }

    public function test_get_available_types_includes_hibrido(): void
    {
        $types = FolioBatch::getAvailableTypes();

        $this->assertIsArray($types);
        $this->assertContains('presencial', $types);
        $this->assertContains('en_linea', $types);
        $this->assertContains('hibrido', $types);
        $this->assertCount(3, $types);
    }

    public function test_is_hibrido_returns_true_for_hybrid_batch(): void
    {
        $organization = Organization::factory()->create();

        $batch = FolioBatch::factory()->create([
            'organization_id' => $organization->id,
            'type' => FolioBatch::TYPE_HIBRIDO,
        ]);

        $this->assertTrue($batch->isHibrido());
        $this->assertFalse($batch->isPresencial());
        $this->assertFalse($batch->isEnLinea());
    }

    public function test_is_presencial_returns_true_for_presencial_batch(): void
    {
        $organization = Organization::factory()->create();

        $batch = FolioBatch::factory()->create([
            'organization_id' => $organization->id,
            'type' => FolioBatch::TYPE_PRESENCIAL,
        ]);

        $this->assertTrue($batch->isPresencial());
        $this->assertFalse($batch->isHibrido());
        $this->assertFalse($batch->isEnLinea());
    }

    public function test_is_en_linea_returns_true_for_online_batch(): void
    {
        $organization = Organization::factory()->create();

        $batch = FolioBatch::factory()->create([
            'organization_id' => $organization->id,
            'type' => FolioBatch::TYPE_EN_LINEA,
        ]);

        $this->assertTrue($batch->isEnLinea());
        $this->assertFalse($batch->isHibrido());
        $this->assertFalse($batch->isPresencial());
    }

    public function test_can_create_hybrid_folio_batch(): void
    {
        $organization = Organization::factory()->create();

        $batch = FolioBatch::create([
            'organization_id' => $organization->id,
            'name' => 'Lote Híbrido Test',
            'description' => 'Testing hybrid batch creation',
            'start_number' => 1,
            'end_number' => 10,
            'quantity' => 10,
            'type' => FolioBatch::TYPE_HIBRIDO,
        ]);

        $this->assertDatabaseHas('folio_batches', [
            'id' => $batch->id,
            'type' => 'hibrido',
        ]);

        $this->assertEquals('hibrido', $batch->fresh()->type);
    }
}
