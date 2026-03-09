<?php

namespace Tests\Feature;

use App\Models\DepartmentArea;
use App\Models\OccupationPosition;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrateIdentifierFormatsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dry_run_reports_changes_without_modifying_records(): void
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();

        $position = OccupationPosition::create([
            'organization_id' => $organization->id,
            'identifier' => '1_a',
            'name' => 'Puesto Legacy',
        ]);

        $department = DepartmentArea::create([
            'organization_id' => $organization->id,
            'identifier' => '2_b',
            'name' => 'Area Legacy',
        ]);

        $deletedPosition = OccupationPosition::create([
            'organization_id' => $organization->id,
            'identifier' => '9_z',
            'name' => 'Puesto Eliminado',
        ]);
        $deletedPosition->delete();

        $reportPath = 'reports/test-identifier-migrate-dry-run.json';

        $this->artisan('identifiers:migrate', [
            '--dry-run' => true,
            '--organization' => $organization->id,
            '--export' => $reportPath,
        ])->assertExitCode(0);

        $this->assertTrue(Storage::disk('local')->exists('private/'.$reportPath));

        $position->refresh();
        $department->refresh();

        $this->assertSame('1_a', $position->identifier);
        $this->assertSame('2_b', $department->identifier);
        $this->assertSoftDeleted('occupation_positions', ['id' => $deletedPosition->id]);
    }

    public function test_command_deletes_soft_deleted_and_migrates_active_records(): void
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();

        $positionOne = OccupationPosition::create([
            'organization_id' => $organization->id,
            'identifier' => '1_a',
            'name' => 'Puesto 1',
        ]);

        $positionTwo = OccupationPosition::create([
            'organization_id' => $organization->id,
            'identifier' => 'foo',
            'name' => 'Puesto 2',
        ]);

        $department = DepartmentArea::create([
            'organization_id' => $organization->id,
            'identifier' => '1_z',
            'name' => 'Area 1',
        ]);

        $deletedDepartment = DepartmentArea::create([
            'organization_id' => $organization->id,
            'identifier' => '8_h',
            'name' => 'Area Eliminada',
        ]);
        $deletedDepartment->delete();

        $reportPath = 'reports/test-identifier-migrate.json';

        $this->artisan('identifiers:migrate', [
            '--organization' => $organization->id,
            '--export' => $reportPath,
        ])->assertExitCode(0);

        $positionOne->refresh();
        $positionTwo->refresh();
        $department->refresh();

        $this->assertSame('1a', $positionOne->identifier);
        $this->assertSame('1b', $positionTwo->identifier);
        $this->assertSame('1a', $department->identifier);

        $this->assertDatabaseMissing('department_areas', ['id' => $deletedDepartment->id]);

        $this->assertTrue(Storage::disk('local')->exists('private/'.$reportPath));
    }
}
