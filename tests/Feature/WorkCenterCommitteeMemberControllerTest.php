<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use App\Models\WorkCenterCommitteeMember;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterCommitteeMemberControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_store_work_center_committee_member(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->post(route('work-centers.committee-members.store', $workCenter), [
            'name' => 'Integrante Demo',
            'department_area' => 'Seguridad',
            'position' => 'Supervisor',
            'factor' => 'Liderazgo',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('work_center_committee_members', [
            'work_center_id' => $workCenter->id,
            'name' => 'Integrante Demo',
            'department_area' => 'Seguridad',
            'position' => 'Supervisor',
            'factor' => 'Liderazgo',
        ]);
    }

    public function test_super_admin_can_store_work_center_committee_member(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['super-admin']);

        $response = $this->actingAs($user)->post(route('work-centers.committee-members.store', $workCenter), [
            'name' => 'Miembro Super Admin',
            'department_area' => 'Operaciones',
            'position' => 'Coordinador',
            'factor' => 'Cargas de trabajo',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('work_center_committee_members', [
            'work_center_id' => $workCenter->id,
            'name' => 'Miembro Super Admin',
        ]);
    }

    public function test_assigned_work_center_user_cannot_store_committee_member(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)->post(route('work-centers.committee-members.store', $workCenter), [
            'name' => 'No autorizado',
            'department_area' => 'Operaciones',
            'position' => 'Coordinador',
            'factor' => 'Cargas de trabajo',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('work_center_committee_members', [
            'work_center_id' => $workCenter->id,
            'name' => 'No autorizado',
        ]);
    }

    public function test_admin_can_delete_work_center_committee_member(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $member = WorkCenterCommitteeMember::query()->create([
            'work_center_id' => $workCenter->id,
            'name' => 'Miembro eliminar',
            'department_area' => 'RH',
            'position' => 'Analista',
            'factor' => 'Ambiente',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->delete(route('work-centers.committee-members.destroy', [$workCenter, $member]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('work_center_committee_members', [
            'id' => $member->id,
        ]);
    }

    public function test_work_center_user_cannot_delete_work_center_committee_member(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $member = WorkCenterCommitteeMember::query()->create([
            'work_center_id' => $workCenter->id,
            'name' => 'Miembro protegido',
            'department_area' => 'RH',
            'position' => 'Analista',
            'factor' => 'Ambiente',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)
            ->delete(route('work-centers.committee-members.destroy', [$workCenter, $member]));

        $response->assertForbidden();

        $this->assertDatabaseHas('work_center_committee_members', [
            'id' => $member->id,
        ]);
    }

    public function test_cannot_delete_member_from_another_work_center(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $anotherWorkCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $member = WorkCenterCommitteeMember::query()->create([
            'work_center_id' => $anotherWorkCenter->id,
            'name' => 'Miembro externo',
            'department_area' => 'RH',
            'position' => 'Analista',
            'factor' => 'Ambiente',
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->delete(route('work-centers.committee-members.destroy', [$workCenter, $member]));

        $response->assertForbidden();

        $this->assertDatabaseHas('work_center_committee_members', [
            'id' => $member->id,
        ]);
    }
}
