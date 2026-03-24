<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RekeyPaperEvaluationsByWorkCenterCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_dry_run_does_not_modify_records(): void
    {
        [$organization, $paperEvaluationA, $paperEvaluationB, $legacyPaperEvaluation, $onlineEvaluation] = $this->seedEvaluations();

        $this->artisan('paper-evaluations:rekey-by-work-center', [
            '--organization' => $organization->id,
            '--source' => 'paper',
            '--dry-run' => true,
        ])
            ->expectsOutput('Running in DRY RUN mode (no writes).')
            ->assertSuccessful();

        $this->assertSame('02230700003', $paperEvaluationA->fresh()->folio);
        $this->assertSame('03230700007', $paperEvaluationB->fresh()->folio);
        $this->assertSame('03070007', $legacyPaperEvaluation->fresh()->folio);
        $this->assertSame('02230700090', $onlineEvaluation->fresh()->folio);
    }

    public function test_command_apply_resequences_new_format_paper_folios_consecutively(): void
    {
        [$organization, $paperEvaluationA, $paperEvaluationB, $legacyPaperEvaluation, $onlineEvaluation] = $this->seedEvaluations();

        $this->artisan('paper-evaluations:rekey-by-work-center', [
            '--organization' => $organization->id,
            '--source' => 'paper',
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('paper_evaluations', [
            'id' => $paperEvaluationA->id,
            'folio' => '02230700001',
            'work_center_code' => '07',
            'personal_folio' => '00001',
        ]);

        $this->assertDatabaseHas('paper_evaluations', [
            'id' => $paperEvaluationB->id,
            'folio' => '03230700002',
            'work_center_code' => '07',
            'personal_folio' => '00002',
        ]);

        $this->assertSame('03070007', $legacyPaperEvaluation->fresh()->folio);
        $this->assertSame('02230700090', $onlineEvaluation->fresh()->folio);
    }

    public function test_command_apply_can_target_online_source(): void
    {
        [$organization, $paperEvaluationA, $paperEvaluationB, $legacyPaperEvaluation, $onlineEvaluation] = $this->seedEvaluations();

        $this->artisan('paper-evaluations:rekey-by-work-center', [
            '--organization' => $organization->id,
            '--source' => 'online',
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('paper_evaluations', [
            'id' => $onlineEvaluation->id,
            'folio' => '02230700001',
            'personal_folio' => '00001',
        ]);

        $this->assertSame('02230700003', $paperEvaluationA->fresh()->folio);
        $this->assertSame('03230700007', $paperEvaluationB->fresh()->folio);
        $this->assertSame('03070007', $legacyPaperEvaluation->fresh()->folio);
    }

    public function test_command_apply_does_not_treat_other_source_folio_as_conflict(): void
    {
        [$organization, $paperEvaluationA, $paperEvaluationB, $legacyPaperEvaluation, $onlineEvaluation] = $this->seedEvaluations();

        $onlineEvaluation->update([
            'folio' => '02230700001',
            'personal_folio' => '00001',
        ]);

        $this->artisan('paper-evaluations:rekey-by-work-center', [
            '--organization' => $organization->id,
            '--source' => 'paper',
            '--apply' => true,
        ])->assertSuccessful();

        $this->assertSame('02230700001', $paperEvaluationA->fresh()->folio);
        $this->assertSame('02230700001', $onlineEvaluation->fresh()->folio);
        $this->assertSame('03230700002', $paperEvaluationB->fresh()->folio);
        $this->assertSame('03070007', $legacyPaperEvaluation->fresh()->folio);
    }

    /**
     * @return array{Organization, PaperEvaluation, PaperEvaluation, PaperEvaluation, PaperEvaluation}
     */
    private function seedEvaluations(): array
    {
        $organization = Organization::factory()->create(['folio_organization' => '23']);
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'code' => '07',
        ]);

        $paperEvaluationA = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'organization_code' => '23',
            'work_center_code' => '07',
            'personal_folio' => '00003',
            'folio' => '02230700003',
            'source' => 'paper',
        ]);

        $paperEvaluationB = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type_code' => '03',
            'evaluation_type' => 'referencia_v',
            'organization_code' => '23',
            'work_center_code' => '07',
            'personal_folio' => '00007',
            'folio' => '03230700007',
            'source' => 'paper',
        ]);

        $legacyPaperEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type_code' => '03',
            'evaluation_type' => 'referencia_v',
            'organization_code' => '307',
            'work_center_code' => null,
            'personal_folio' => '0007',
            'folio' => '03070007',
            'source' => 'paper',
        ]);

        $onlineEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'organization_code' => '23',
            'work_center_code' => '07',
            'personal_folio' => '00090',
            'folio' => '02230700090',
            'source' => 'online',
        ]);

        return [$organization, $paperEvaluationA, $paperEvaluationB, $legacyPaperEvaluation, $onlineEvaluation];
    }
}
