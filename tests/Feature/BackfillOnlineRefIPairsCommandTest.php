<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BackfillOnlineRefIPairsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_dry_run_reports_candidates_without_creating_records(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        PaperEvaluation::factory()->online()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'folio' => '02010900011',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'organization_code' => '01',
            'work_center_code' => '09',
            'personal_folio' => '00011',
            'processing_status' => 'completed',
            'source' => 'online',
            'citsats_s1' => ['1' => false, '2' => false, '3' => false, '4' => false, '5' => false, '6' => false],
            'raw_data' => ['referencia_i' => ['acontecimientos_traumaticos' => ['1' => false]]],
        ]);

        $this->artisan('evaluations:backfill-online-ref-i-pairs', [
            '--organization' => $organization->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 online Referencia III records to inspect.')
            ->expectsOutput('DRY RUN - No changes were made.')
            ->assertSuccessful();

        $this->assertSame(0, PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->where('evaluation_type', 'referencia_i')
            ->count());
    }

    public function test_command_creates_missing_ref_i_pair_for_online_ref_iii(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $refIII = PaperEvaluation::factory()->online()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'folio' => '02010900012',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'organization_code' => '01',
            'work_center_code' => '09',
            'personal_folio' => '00012',
            'processing_status' => 'completed',
            'source' => 'online',
            'citsats_s1' => ['1' => false, '2' => false, '3' => false, '4' => false, '5' => false, '6' => false],
            'raw_data' => ['referencia_i' => ['acontecimientos_traumaticos' => ['1' => false]]],
        ]);

        $this->artisan('evaluations:backfill-online-ref-i-pairs', [
            '--organization' => $organization->id,
            '--force' => true,
        ])
            ->expectsOutput('Found 1 online Referencia III records to inspect.')
            ->expectsOutput('Created 1 missing Referencia I records.')
            ->assertSuccessful();

        $refI = PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('personal_folio', '00012')
            ->first();

        $this->assertNotNull($refI);
        $this->assertSame('01010900012', $refI->folio);
        $this->assertSame('02010900012', $refI->related_evaluation_folio);

        $refIII->refresh();
        $this->assertSame('01010900012', $refIII->related_evaluation_folio);
    }
}
