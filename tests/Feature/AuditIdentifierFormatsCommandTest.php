<?php

namespace Tests\Feature;

use App\Models\DepartmentArea;
use App\Models\OccupationPosition;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditIdentifierFormatsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_generates_summary_report_with_classification_and_collisions(): void
    {
        Storage::fake('local');

        $organization = Organization::factory()->create();

        OccupationPosition::create([
            'organization_id' => $organization->id,
            'identifier' => '1a',
            'name' => 'Puesto Nuevo',
        ]);

        OccupationPosition::create([
            'organization_id' => $organization->id,
            'identifier' => '1_a',
            'name' => 'Puesto Legacy',
        ]);

        OccupationPosition::create([
            'organization_id' => $organization->id,
            'identifier' => 'foo',
            'name' => 'Puesto Invalido',
        ]);

        DepartmentArea::create([
            'organization_id' => $organization->id,
            'identifier' => '1a',
            'name' => 'Area A',
        ]);

        DepartmentArea::create([
            'organization_id' => $organization->id,
            'identifier' => '1a',
            'name' => 'Area B',
        ]);

        $reportPath = 'reports/test-identifier-audit.json';

        $this->artisan('identifiers:audit', [
            '--organization' => $organization->id,
            '--export' => $reportPath,
        ])->assertExitCode(0);

        $this->assertTrue(Storage::disk('local')->exists($reportPath));

        $report = json_decode(Storage::disk('local')->get($reportPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(3, $report['summary']['positions']['total']);
        $this->assertSame(1, $report['summary']['positions']['nuevo_formato']);
        $this->assertSame(1, $report['summary']['positions']['legacy']);
        $this->assertSame(1, $report['summary']['positions']['invalido']);

        $this->assertSame(2, $report['summary']['departments']['total']);
        $this->assertSame(2, $report['summary']['departments']['nuevo_formato']);
        $this->assertSame(1, $report['summary']['departments']['collisions']);

        $this->assertSame(1, $report['summary']['totals']['collisions']);
        $this->assertNotEmpty($report['invalid_or_legacy']);
        $this->assertNotEmpty($report['collisions']);
    }
}
