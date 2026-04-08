<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkCenterOrganizationReportControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_request_work_center_report_endpoint(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Excel::fake();
        Carbon::setTestNow('2026-04-07 12:00:00');

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create([
            'name' => 'Organizacion Demo',
        ]);
        WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('organizations.report.download', [
                'organization' => $organization->id,
                'reportType' => 'respuestas',
            ]));

        $response->assertOk();
        Excel::assertDownloaded('respuestas_organizacion_organizacion-demo_20260407_120000.xlsx');
        Carbon::setTestNow();
    }

    public function test_non_admin_cannot_access_work_center_report_endpoint(): void
    {
        Role::firstOrCreate(['name' => 'organization', 'guard_name' => 'web']);

        /** @var User $organizationUser */
        $organizationUser = User::factory()->create();
        $organizationUser->assignRole('organization');

        $organization = Organization::factory()->create();
        WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($organizationUser)
            ->get(route('organizations.report.download', [
                'organization' => $organization->id,
                'reportType' => 'respuestas',
            ]));

        $response->assertForbidden();
    }

    public function test_admin_can_only_see_work_centers_from_requested_organization(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Excel::fake();
        Carbon::setTestNow('2026-04-07 13:00:00');

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create([
            'name' => 'Organizacion Scope',
        ]);
        WorkCenter::factory()->count(2)->create([
            'organization_id' => $organization->id,
        ]);

        $anotherOrganization = Organization::factory()->create();
        WorkCenter::factory()->create([
            'organization_id' => $anotherOrganization->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('organizations.report.download', [
                'organization' => $organization->id,
                'reportType' => 'respuestas',
            ]));

        $response->assertOk();
        Excel::assertDownloaded('respuestas_organizacion_organizacion-scope_20260407_130000.xlsx');
        Carbon::setTestNow();
    }

    public function test_old_report_type_names_no_longer_match_this_endpoint(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $organization = Organization::factory()->create();

        foreach (['demografico', 'general', 'ejecutivo'] as $reportType) {
            $response = $this->actingAs($admin)
                ->get("/organizaciones/{$organization->id}/reporte/{$reportType}");

            $response->assertNotFound();
        }
    }
}
