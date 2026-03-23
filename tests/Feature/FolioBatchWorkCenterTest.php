<?php

namespace Tests\Feature;

use App\Enums\WorkCenterType;
use App\Models\FolioBatch;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FolioBatchWorkCenterTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected User $user;

    protected Organization $organization;

    protected WorkCenter $workCenter;

    protected function setUp(): void
    {
        parent::setUp();

        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $this->organization = Organization::factory()->create([
            'folio_organization' => 30,
        ]);

        $this->workCenter = WorkCenter::factory()->create([
            'organization_id' => $this->organization->id,
            'code' => '0001',
            'is_primary' => true,
            'type' => WorkCenterType::Headquarters->value,
        ]);
    }

    /**
     * Test creating a folio batch with work_center_id
     */
    public function test_can_create_folio_batch_with_work_center(): void
    {
        $response = $this->actingAs($this->user)->post(route('folio-batches.store'), [
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
            'name' => 'Lote CT Principal',
            'quantity' => 50,
            'type' => 'presencial',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('folio_batches', [
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
            'name' => 'Lote CT Principal',
            'quantity' => 50,
            'start_number' => 1,
            'end_number' => 50,
        ]);
    }

    /**
     * Test folio batch requires work_center_id
     */
    public function test_folio_batch_requires_work_center_id(): void
    {
        $response = $this->actingAs($this->user)->post(route('folio-batches.store'), [
            'organization_id' => $this->organization->id,
            'name' => 'Lote sin CT',
            'quantity' => 10,
            'type' => 'presencial',
        ]);

        $response->assertSessionHasErrors('work_center_id');
    }

    /**
     * Test work_center_id must belong to the same organization
     */
    public function test_work_center_must_belong_to_organization(): void
    {
        $otherOrg = Organization::factory()->create();
        $otherWorkCenter = WorkCenter::factory()->create([
            'organization_id' => $otherOrg->id,
            'code' => '0001',
        ]);

        $response = $this->actingAs($this->user)->post(route('folio-batches.store'), [
            'organization_id' => $this->organization->id,
            'work_center_id' => $otherWorkCenter->id,
            'name' => 'Lote CT ajeno',
            'quantity' => 10,
            'type' => 'presencial',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test folio sequence is independent per work center
     */
    public function test_folio_sequence_is_per_work_center(): void
    {
        $secondWorkCenter = WorkCenter::factory()->create([
            'organization_id' => $this->organization->id,
            'code' => '0002',
            'is_primary' => false,
            'type' => WorkCenterType::Plant->value,
        ]);

        $this->actingAs($this->user)->post(route('folio-batches.store'), [
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
            'name' => 'Lote CT 1',
            'quantity' => 50,
            'type' => 'presencial',
        ]);

        $this->actingAs($this->user)->post(route('folio-batches.store'), [
            'organization_id' => $this->organization->id,
            'work_center_id' => $secondWorkCenter->id,
            'name' => 'Lote CT 2',
            'quantity' => 30,
            'type' => 'presencial',
        ]);

        $batch1 = FolioBatch::where('work_center_id', $this->workCenter->id)->first();
        $batch2 = FolioBatch::where('work_center_id', $secondWorkCenter->id)->first();

        $this->assertEquals(1, $batch1->start_number);
        $this->assertEquals(50, $batch1->end_number);

        $this->assertEquals(1, $batch2->start_number);
        $this->assertEquals(30, $batch2->end_number);
    }

    /**
     * Test sequential batches within same work center
     */
    public function test_sequential_batches_within_same_work_center(): void
    {
        $this->actingAs($this->user)->post(route('folio-batches.store'), [
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
            'name' => 'Primer lote',
            'quantity' => 100,
            'type' => 'presencial',
        ]);

        $this->actingAs($this->user)->post(route('folio-batches.store'), [
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
            'name' => 'Segundo lote',
            'quantity' => 50,
            'type' => 'presencial',
        ]);

        $batches = FolioBatch::where('work_center_id', $this->workCenter->id)
            ->orderBy('start_number')
            ->get();

        $this->assertCount(2, $batches);
        $this->assertEquals(1, $batches[0]->start_number);
        $this->assertEquals(100, $batches[0]->end_number);
        $this->assertEquals(101, $batches[1]->start_number);
        $this->assertEquals(150, $batches[1]->end_number);
    }

    /**
     * Test FolioBatch model has workCenter relationship
     */
    public function test_folio_batch_belongs_to_work_center(): void
    {
        $batch = FolioBatch::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
        ]);

        $this->assertInstanceOf(WorkCenter::class, $batch->workCenter);
        $this->assertEquals($this->workCenter->id, $batch->workCenter->id);
    }

    /**
     * Test FolioBatch with null work_center_id for legacy compatibility
     */
    public function test_folio_batch_allows_null_work_center(): void
    {
        $batch = FolioBatch::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => null,
        ]);

        $this->assertNull($batch->workCenter);
        $this->assertDatabaseHas('folio_batches', [
            'id' => $batch->id,
            'work_center_id' => null,
        ]);
    }

    /**
     * Test organization edit page loads work centers for folios
     */
    public function test_edit_page_loads_work_centers_with_folio_batches(): void
    {
        FolioBatch::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('organizations.edit', $this->organization));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('organization.folio_batches.0.work_center')
            ->has('organization.work_centers')
        );
    }

    public function test_can_delete_unused_folio_batch(): void
    {
        $batch = FolioBatch::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
        ]);

        $batch->folios()->create([
            'folio_number' => '0001',
            'numeric_value' => 1,
            'used' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('folio-batches.destroy', $batch->id));

        $response->assertOk()
            ->assertJson([
                'message' => 'Lote de folios eliminado correctamente',
            ]);

        $this->assertDatabaseMissing('folio_batches', [
            'id' => $batch->id,
        ]);

        $this->assertDatabaseMissing('folios', [
            'folio_batch_id' => $batch->id,
        ]);
    }

    public function test_cannot_delete_folio_batch_with_used_folios(): void
    {
        $batch = FolioBatch::factory()->create([
            'organization_id' => $this->organization->id,
            'work_center_id' => $this->workCenter->id,
        ]);

        $batch->folios()->create([
            'folio_number' => '0001',
            'numeric_value' => 1,
            'used' => true,
            'used_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('folio-batches.destroy', $batch->id));

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'No se puede eliminar el lote porque tiene folios que ya han sido utilizados.',
            ]);

        $this->assertDatabaseHas('folio_batches', [
            'id' => $batch->id,
        ]);
    }
}
