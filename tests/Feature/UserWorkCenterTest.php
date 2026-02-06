<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserWorkCenterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $organizationUser;

    protected User $workCenterUser;

    protected Organization $org;

    protected WorkCenter $center1;

    protected WorkCenter $center2;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles necesarios
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $orgRole = Role::firstOrCreate(['name' => 'organization', 'guard_name' => 'web']);
        $wcRole = Role::firstOrCreate(['name' => 'work_center_user', 'guard_name' => 'web']);

        // Crear organización
        $this->org = Organization::factory()->create([
            'name' => 'Test Organization',
        ]);

        // Crear work centers
        $this->center1 = WorkCenter::factory()->create([
            'organization_id' => $this->org->id,
            'code' => '0001',
            'name' => 'Centro 1',
            'is_primary' => true,
        ]);

        $this->center2 = WorkCenter::factory()->create([
            'organization_id' => $this->org->id,
            'code' => '0002',
            'name' => 'Centro 2',
            'is_primary' => false,
        ]);

        // Crear usuarios con roles
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->organizationUser = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $this->organizationUser->assignRole($orgRole);

        $this->workCenterUser = User::factory()->create([
            'organization_id' => $this->org->id,
        ]);
        $this->workCenterUser->assignRole($wcRole);
        $this->workCenterUser->workCenters()->attach($this->center1->id);
    }

    public function test_admin_has_access_to_all_work_centers(): void
    {
        $accessibleCenters = $this->admin->accessibleWorkCenters()->get();

        $this->assertTrue($accessibleCenters->contains($this->center1));
        $this->assertTrue($accessibleCenters->contains($this->center2));
    }

    public function test_organization_user_has_access_to_all_org_centers(): void
    {
        $accessibleCenters = $this->organizationUser->accessibleWorkCenters()->get();

        $this->assertCount(2, $accessibleCenters);
        $this->assertTrue($accessibleCenters->contains($this->center1));
        $this->assertTrue($accessibleCenters->contains($this->center2));
    }

    public function test_work_center_user_has_access_only_to_assigned_centers(): void
    {
        $accessibleCenters = $this->workCenterUser->accessibleWorkCenters()->get();

        $this->assertCount(1, $accessibleCenters);
        $this->assertTrue($accessibleCenters->contains($this->center1));
        $this->assertFalse($accessibleCenters->contains($this->center2));
    }

    public function test_work_center_user_can_have_multiple_centers(): void
    {
        $this->workCenterUser->workCenters()->attach($this->center2->id);

        $accessibleCenters = $this->workCenterUser->accessibleWorkCenters()->get();

        $this->assertCount(2, $accessibleCenters);
        $this->assertTrue($accessibleCenters->contains($this->center1));
        $this->assertTrue($accessibleCenters->contains($this->center2));
    }

    public function test_has_access_to_work_center_method_works(): void
    {
        $this->assertTrue($this->workCenterUser->hasAccessToWorkCenter($this->center1));
        $this->assertFalse($this->workCenterUser->hasAccessToWorkCenter($this->center2));

        $this->assertTrue($this->organizationUser->hasAccessToWorkCenter($this->center1));
        $this->assertTrue($this->organizationUser->hasAccessToWorkCenter($this->center2));
    }

    public function test_creating_work_center_user_with_centers(): void
    {
        $role = Role::where('name', 'work_center_user')->first();

        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'New User',
                'email' => 'newuser@test.com',
                'role' => $role->id,
                'organization' => $this->org->id,
                'work_centers' => [$this->center1->id, $this->center2->id],
            ]);

        $response->assertRedirect(route('users.index'));

        $newUser = User::where('email', 'newuser@test.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals($this->org->id, $newUser->organization_id);
        $this->assertCount(2, $newUser->workCenters);
    }

    public function test_updating_work_center_user_centers(): void
    {
        $role = Role::where('name', 'work_center_user')->first();

        // Usuario inicialmente tiene solo center1
        $this->assertCount(1, $this->workCenterUser->workCenters);

        $response = $this->actingAs($this->admin)
            ->put(route('users.update', $this->workCenterUser), [
                'name' => $this->workCenterUser->name,
                'email' => $this->workCenterUser->email,
                'role' => $role->id,
                'organization' => $this->org->id,
                'work_centers' => [$this->center2->id], // Cambiar a center2
            ]);

        $response->assertRedirect(route('users.index'));

        $this->workCenterUser->refresh();
        $this->assertCount(1, $this->workCenterUser->workCenters);
        $this->assertTrue($this->workCenterUser->workCenters->contains($this->center2));
        $this->assertFalse($this->workCenterUser->workCenters->contains($this->center1));
    }

    public function test_changing_role_to_organization_removes_work_centers(): void
    {
        $orgRole = Role::where('name', 'organization')->first();

        // Usuario inicialmente tiene work centers asignados
        $this->assertCount(1, $this->workCenterUser->workCenters);

        $response = $this->actingAs($this->admin)
            ->put(route('users.update', $this->workCenterUser), [
                'name' => $this->workCenterUser->name,
                'email' => $this->workCenterUser->email,
                'role' => $orgRole->id,
                'organization' => $this->org->id,
            ]);

        $response->assertRedirect(route('users.index'));

        $this->workCenterUser->refresh();
        $this->assertCount(0, $this->workCenterUser->workCenters);
        $this->assertTrue($this->workCenterUser->hasRole('organization'));
    }

    public function test_api_endpoint_returns_work_centers_for_organization(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/api/organizations/{$this->org->id}/work-centers");

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['label' => '0001 - Centro 1']);
        $response->assertJsonFragment(['label' => '0002 - Centro 2']);
    }
}
