<?php

namespace Tests\Feature;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Quiz;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WorkCenterTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Test work center creation
     */
    public function test_can_create_work_center(): void
    {
        $organization = Organization::factory()->create();

        $workCenter = WorkCenter::create([
            'organization_id' => $organization->id,
            'code' => '0001',
            'name' => 'Main Center',
            'type' => WorkCenterType::Headquarters->value,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('work_centers', [
            'id' => $workCenter->id,
            'organization_id' => $organization->id,
            'code' => '0001',
            'name' => 'Main Center',
            'type' => WorkCenterType::Headquarters->value,
            'is_primary' => true,
        ]);
    }

    /**
     * Test work center belongs to organization
     */
    public function test_work_center_belongs_to_organization(): void
    {
        $workCenter = WorkCenter::factory()->create();

        $this->assertInstanceOf(Organization::class, $workCenter->organization);
        $this->assertEquals($workCenter->organization_id, $workCenter->organization->id);
    }

    /**
     * Test organization has many work centers
     */
    public function test_organization_has_many_work_centers(): void
    {
        $organization = Organization::factory()->create();

        WorkCenter::factory()->count(3)->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertCount(3, $organization->workCenters);
    }

    /**
     * Test organization can have primary work center
     */
    public function test_organization_has_primary_work_center(): void
    {
        $organization = Organization::factory()->create();

        $primaryCenter = WorkCenter::factory()->primary()->create([
            'organization_id' => $organization->id,
        ]);

        WorkCenter::factory()->count(2)->create([
            'organization_id' => $organization->id,
            'is_primary' => false,
        ]);

        $this->assertNotNull($organization->primaryWorkCenter);
        $this->assertTrue($organization->primaryWorkCenter->is_primary);
        $this->assertEquals($primaryCenter->id, $organization->primaryWorkCenter->id);
    }

    /**
     * Test work center has many paper evaluations
     */
    public function test_work_center_has_many_paper_evaluations(): void
    {
        $workCenter = WorkCenter::factory()->create();

        PaperEvaluation::factory()->count(5)->create([
            'work_center_id' => $workCenter->id,
            'organization_id' => $workCenter->organization_id,
        ]);

        $this->assertCount(5, $workCenter->paperEvaluations);
    }

    /**
     * Test work center has many quizzes
     */
    public function test_work_center_has_many_quizzes(): void
    {
        $workCenter = WorkCenter::factory()->create();

        Quiz::factory()->count(3)->create([
            'work_center_id' => $workCenter->id,
            'organization_id' => $workCenter->organization_id,
        ]);

        $this->assertCount(3, $workCenter->quizzes);
    }

    /**
     * Test primary scope returns only primary centers
     */
    public function test_primary_scope_filters_correctly(): void
    {
        $organization = Organization::factory()->create();

        WorkCenter::factory()->primary()->create([
            'organization_id' => $organization->id,
        ]);

        WorkCenter::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'is_primary' => false,
        ]);

        $primaryCenters = WorkCenter::primary()
            ->where('organization_id', $organization->id)
            ->get();

        $this->assertCount(1, $primaryCenters);
        $this->assertTrue($primaryCenters->first()->is_primary);
    }

    /**
     * Test full name attribute
     */
    public function test_full_name_attribute_format(): void
    {
        $organization = Organization::factory()->create(['name' => 'ACME Corp']);

        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Planta Norte',
        ]);

        $this->assertEquals('ACME Corp - Planta Norte', $workCenter->full_name);
    }

    /**
     * Test work center can be soft deleted
     */
    public function test_work_center_can_be_soft_deleted(): void
    {
        $workCenter = WorkCenter::factory()->create();

        $workCenter->delete();

        $this->assertSoftDeleted('work_centers', [
            'id' => $workCenter->id,
        ]);
    }

    /**
     * Test work center code uniqueness per organization
     */
    public function test_work_center_code_is_unique_per_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        WorkCenter::factory()->create([
            'organization_id' => $org1->id,
            'code' => '0001',
        ]);

        // Same code in different organization should work
        $workCenter2 = WorkCenter::factory()->create([
            'organization_id' => $org2->id,
            'code' => '0001',
        ]);

        $this->assertDatabaseHas('work_centers', [
            'organization_id' => $org2->id,
            'code' => '0001',
        ]);
    }

    /**
     * Test work center type enum values
     */
    public function test_work_center_accepts_valid_types(): void
    {
        foreach (WorkCenterType::cases() as $type) {
            $workCenter = WorkCenter::factory()->create(['type' => $type->value]);

            $this->assertDatabaseHas('work_centers', [
                'id' => $workCenter->id,
                'type' => $type->value,
            ]);
        }
    }

    /**
     * Test work center factory states
     */
    public function test_factory_states_work_correctly(): void
    {
        $primary = WorkCenter::factory()->primary()->create();
        $plant = WorkCenter::factory()->plant()->create();
        $branch = WorkCenter::factory()->branch()->create();

        $this->assertTrue($primary->is_primary);
        $this->assertEquals(WorkCenterType::Headquarters, $primary->type);

        $this->assertEquals(WorkCenterType::Plant, $plant->type);
        $this->assertStringContainsString('Plant', $plant->name);

        $this->assertEquals(WorkCenterType::Branch, $branch->type);
        $this->assertStringContainsString('Branch', $branch->name);
    }
}
