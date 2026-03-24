<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BackfillPaperRefIPairsCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_requires_work_center_option(): void
    {
        $this->artisan('evaluations:backfill-paper-ref-i-pairs', [
            '--dry-run' => true,
        ])
            ->expectsOutput('The --work-center option is required.')
            ->assertExitCode(2);
    }

    public function test_command_dry_run_only_inspects_selected_work_center(): void
    {
        $organization = Organization::factory()->create();
        $targetWorkCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $otherWorkCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $targetWorkCenter->id,
            'folio' => '02010900021',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'organization_code' => '01',
            'work_center_code' => '09',
            'personal_folio' => '00021',
            'processing_status' => 'completed',
            'source' => 'paper',
            'citsats_s1' => ['1' => 'NO', '2' => 'NO', '3' => 'NO', '4' => 'NO', '5' => 'NO', '6' => 'NO'],
        ]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $otherWorkCenter->id,
            'folio' => '02010900022',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'organization_code' => '01',
            'work_center_code' => '09',
            'personal_folio' => '00022',
            'processing_status' => 'completed',
            'source' => 'paper',
            'citsats_s1' => ['1' => 'NO', '2' => 'NO', '3' => 'NO', '4' => 'NO', '5' => 'NO', '6' => 'NO'],
        ]);

        $initialRefIPaperCount = PaperEvaluation::query()
            ->where('source', 'paper')
            ->where('evaluation_type', 'referencia_i')
            ->count();

        $this->artisan('evaluations:backfill-paper-ref-i-pairs', [
            '--work-center' => $targetWorkCenter->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 paper Referencia III records to inspect.')
            ->expectsOutput('DRY RUN - No changes were made.')
            ->assertSuccessful();

        $this->assertSame($initialRefIPaperCount, PaperEvaluation::query()
            ->where('source', 'paper')
            ->where('evaluation_type', 'referencia_i')
            ->count());
    }

    public function test_command_creates_missing_ref_i_pair_for_selected_work_center(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $refIII = PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'folio' => '02010900023',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'organization_code' => '01',
            'work_center_code' => '09',
            'personal_folio' => '00023',
            'processing_status' => 'completed',
            'source' => 'paper',
            'citsats_s1' => ['1' => 'NO', '2' => 'NO', '3' => 'NO', '4' => 'NO', '5' => 'NO', '6' => 'NO'],
        ]);

        $this->artisan('evaluations:backfill-paper-ref-i-pairs', [
            '--work-center' => $workCenter->id,
            '--force' => true,
        ])
            ->expectsOutput('Found 1 paper Referencia III records to inspect.')
            ->expectsOutput('Created 1 missing Referencia I records.')
            ->assertSuccessful();

        $refI = PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('source', 'paper')
            ->where('personal_folio', '00023')
            ->first();

        $this->assertNotNull($refI);
        $this->assertSame('01010900023', $refI->folio);
        $this->assertSame('02010900023', $refI->related_evaluation_folio);

        $refIII->refresh();
        $this->assertSame('01010900023', $refIII->related_evaluation_folio);
    }
}
