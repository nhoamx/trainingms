<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use App\Models\WorkCenterPreventionAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterPreventionActionControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_store_prevention_action_for_work_center(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->post(route('work-centers.prevention-actions.store', $workCenter), [
            'instrument_type' => 'referencia_iii',
            'title' => 'Capacitación en manejo de carga laboral',
            'description' => 'Sesiones semanales para mandos medios.',
            'responsible' => 'RH',
            'status' => 'en_proceso',
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'sort_order' => 1,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('work_center_prevention_actions', [
            'work_center_id' => $workCenter->id,
            'instrument_type' => 'referencia_iii',
            'title' => 'Capacitación en manejo de carga laboral',
            'status' => 'en_proceso',
        ]);
    }

    public function test_work_center_user_cannot_store_prevention_action(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)->post(route('work-centers.prevention-actions.store', $workCenter), [
            'instrument_type' => 'referencia_iii',
            'title' => 'Acción no permitida',
            'status' => 'pendiente',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('work_center_prevention_actions', [
            'work_center_id' => $workCenter->id,
            'title' => 'Acción no permitida',
        ]);
    }

    public function test_admin_can_delete_prevention_action(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $action = WorkCenterPreventionAction::query()->create([
            'work_center_id' => $workCenter->id,
            'instrument_type' => 'referencia_i',
            'title' => 'Seguimiento ATS',
            'description' => 'Seguimiento semanal.',
            'responsible' => 'Salud Ocupacional',
            'status' => 'pendiente',
            'sort_order' => 0,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->delete(route('work-centers.prevention-actions.destroy', [$workCenter, $action]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('work_center_prevention_actions', [
            'id' => $action->id,
        ]);
    }
}
