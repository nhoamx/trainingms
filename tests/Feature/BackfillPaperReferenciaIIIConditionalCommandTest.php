<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BackfillPaperReferenciaIIIConditionalCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_dry_run_does_not_persist_changes(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'raw_data' => [
                'customer_service_conditional' => [
                    'condition' => ['value' => 'SI'],
                ],
                'customer_service_questions' => [
                    '65' => ['value' => 'A'],
                    '66' => ['value' => 'B'],
                    '67' => ['value' => 'C'],
                    '68' => ['value' => 'D'],
                ],
                'conditional_management' => [
                    'condition' => ['value' => 'NO'],
                ],
            ],
            'referencia_iii_conditional' => null,
        ]);

        $this->artisan('evaluations:backfill-paper-referencia-iii-conditional', [
            '--work-center' => $workCenter->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 paper Referencia III records to inspect.')
            ->expectsOutput('DRY RUN - No changes were made.')
            ->assertSuccessful();

        $evaluation->refresh();
        $this->assertNull($evaluation->referencia_iii_conditional);
    }

    public function test_command_backfills_from_raw_data_when_missing_conditionals(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'raw_data' => [
                'customer_service_conditional' => [
                    'condition' => ['value' => 'SI'],
                ],
                'customer_service_questions' => [
                    '65' => ['value' => 'A'],
                    '66' => ['value' => 'B'],
                    '67' => ['value' => 'C'],
                    '68' => ['value' => 'D'],
                ],
                'conditional_management' => [
                    'condition' => ['value' => 'NO'],
                ],
                'management_questions' => [
                    '69' => ['value' => 'E'],
                    '70' => ['value' => 'D'],
                    '71' => ['value' => 'C'],
                    '72' => ['value' => 'B'],
                ],
            ],
            'referencia_iii_conditional' => null,
        ]);

        $this->artisan('evaluations:backfill-paper-referencia-iii-conditional', [
            '--work-center' => $workCenter->id,
            '--force' => true,
        ])
            ->expectsOutput('Found 1 paper Referencia III records to inspect.')
            ->assertSuccessful();

        $evaluation->refresh();
        $this->assertNotNull($evaluation->referencia_iii_conditional);
        $this->assertEquals('SI', $evaluation->referencia_iii_conditional['customer_service']['condition']);
        $this->assertEquals('A', $evaluation->referencia_iii_conditional['customer_service']['questions']['65']);
        $this->assertEquals('NO', $evaluation->referencia_iii_conditional['management']['condition']);
        $this->assertEquals([], $evaluation->referencia_iii_conditional['management']['questions']);
    }

    public function test_command_only_targets_selected_work_center(): void
    {
        $organization = Organization::factory()->create();
        $targetWorkCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $otherWorkCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $targetEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $targetWorkCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'raw_data' => [
                'referencia_iii' => [
                    'condition_customer_service' => true,
                    '65' => 'A',
                    '66' => 'B',
                    '67' => 'C',
                    '68' => 'D',
                    'condition_management' => false,
                ],
            ],
            'referencia_iii_conditional' => null,
        ]);

        $otherEvaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $otherWorkCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'raw_data' => [
                'referencia_iii' => [
                    'condition_customer_service' => true,
                    '65' => 'E',
                    '66' => 'D',
                    '67' => 'C',
                    '68' => 'B',
                    'condition_management' => false,
                ],
            ],
            'referencia_iii_conditional' => null,
        ]);

        $this->artisan('evaluations:backfill-paper-referencia-iii-conditional', [
            '--work-center' => $targetWorkCenter->id,
            '--force' => true,
        ])->assertSuccessful();

        $targetEvaluation->refresh();
        $otherEvaluation->refresh();

        $this->assertNotNull($targetEvaluation->referencia_iii_conditional);
        $this->assertNull($otherEvaluation->referencia_iii_conditional);
    }

    public function test_command_extracts_conditionals_from_flat_omr_mapping_sections(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'evaluation_type_code' => '02',
            'evaluation_type' => 'referencia_iii',
            'raw_data' => [
                '65' => ['mapping_section' => 'conditional_1', 'value' => 'SI'],
                '66' => ['mapping_section' => 'conditional_1_followup', 'value' => 'A'],
                '67' => ['mapping_section' => 'conditional_1_followup', 'value' => 'B'],
                '68' => ['mapping_section' => 'conditional_1_followup', 'value' => 'C'],
                '69' => ['mapping_section' => 'conditional_1_followup', 'value' => 'D'],
                '70' => ['mapping_section' => 'conditional_2', 'value' => 'NO'],
                '71' => ['mapping_section' => 'conditional_2_followup', 'value' => 'E'],
                '72' => ['mapping_section' => 'conditional_2_followup', 'value' => 'D'],
                '73' => ['mapping_section' => 'conditional_2_followup', 'value' => 'C'],
                '74' => ['mapping_section' => 'conditional_2_followup', 'value' => 'B'],
            ],
            'referencia_iii_conditional' => null,
        ]);

        $this->artisan('evaluations:backfill-paper-referencia-iii-conditional', [
            '--organization' => $organization->id,
            '--force' => true,
        ])->assertSuccessful();

        $evaluation->refresh();

        $this->assertSame('SI', $evaluation->referencia_iii_conditional['customer_service']['condition']);
        $this->assertSame('A', $evaluation->referencia_iii_conditional['customer_service']['questions']['65']);
        $this->assertSame('D', $evaluation->referencia_iii_conditional['customer_service']['questions']['68']);
        $this->assertSame('NO', $evaluation->referencia_iii_conditional['management']['condition']);
        $this->assertSame([], $evaluation->referencia_iii_conditional['management']['questions']);
    }
}
