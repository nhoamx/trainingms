<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReprocessOrganizationEvaluationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up paper evaluations for isolated tests
        PaperEvaluation::query()->delete();
    }

    public function test_command_fails_with_invalid_organization_id(): void
    {
        $this->artisan('evaluations:reprocess', ['organization' => 'invalid-uuid'])
            ->expectsOutput('Organization with ID invalid-uuid not found.')
            ->assertExitCode(1);
    }

    public function test_command_shows_no_evaluations_message_when_none_exist(): void
    {
        $organization = Organization::factory()->create();

        $this->artisan('evaluations:reprocess', ['organization' => $organization->id])
            ->expectsOutput("Organization: {$organization->name} ({$organization->folio_organization})")
            ->expectsOutput('No paper evaluations found for the specified criteria.')
            ->assertExitCode(0);
    }

    public function test_command_dry_run_shows_evaluations_without_processing(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '999',
        ]);

        // Create a paper evaluation
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059990001',
            'organization_code' => '999',
            'personal_folio' => '0001',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 evaluations to reprocess.')
            ->expectsOutput('DRY RUN - No changes will be made.')
            ->assertExitCode(0);
    }

    public function test_command_filters_by_evaluation_type(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '998',
        ]);

        // Create different evaluation types
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059980001',
            'organization_code' => '998',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        PaperEvaluation::factory()->referenciaIII()->create([
            'organization_id' => $organization->id,
            'folio' => '029980002',
            'organization_code' => '998',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        // Filter by likert
        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--type' => 'likert',
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 evaluations to reprocess.')
            ->assertExitCode(0);
    }

    public function test_command_respects_limit_option(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '997',
        ]);

        // Create multiple evaluations
        for ($i = 1; $i <= 5; $i++) {
            PaperEvaluation::factory()->likert()->create([
                'organization_id' => $organization->id,
                'folio' => "05997000{$i}",
                'organization_code' => '997',
                'personal_folio' => "000{$i}",
                'source' => 'paper',
                'processing_status' => 'completed',
            ]);
        }

        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--limit' => 2,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 2 evaluations to reprocess.')
            ->assertExitCode(0);
    }

    public function test_command_only_processes_paper_source_evaluations(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '996',
        ]);

        // Create paper evaluation
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059960001',
            'organization_code' => '996',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        // Create online evaluation (should not be included)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059960002',
            'organization_code' => '996',
            'source' => 'online',
            'processing_status' => 'completed',
        ]);

        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 evaluations to reprocess.')
            ->assertExitCode(0);
    }

    public function test_command_only_processes_completed_evaluations(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '995',
        ]);

        // Create completed evaluation
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059950001',
            'organization_code' => '995',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        // Create pending evaluation (should not be included)
        PaperEvaluation::factory()->likert()->pending()->create([
            'organization_id' => $organization->id,
            'folio' => '059950002',
            'organization_code' => '995',
            'source' => 'paper',
        ]);

        // Create failed evaluation (should not be included)
        PaperEvaluation::factory()->likert()->failed()->create([
            'organization_id' => $organization->id,
            'folio' => '059950003',
            'organization_code' => '995',
            'source' => 'paper',
        ]);

        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--dry-run' => true,
        ])
            ->expectsOutput('Found 1 evaluations to reprocess.')
            ->assertExitCode(0);
    }

    public function test_command_filters_by_climate_level(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '994',
        ]);

        // Create evaluation with high score (Totalmente de Acuerdo: 76-92)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059940001',
            'organization_code' => '994',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, 'A'), // All A = 4 pts each = 92 total
            ],
        ]);

        // Create evaluation with low score (Totalmente Desacuerdo: 23-40)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059940002',
            'organization_code' => '994',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, 'D'), // All D = 1 pt each = 23 total
            ],
        ]);

        // Filter by "Totalmente de Acuerdo" only
        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--climate' => 'ta',
            '--dry-run' => true,
        ])
            ->expectsOutput('Filtering by climate levels: Totalmente de Acuerdo')
            ->expectsOutput('Found 1 evaluations to reprocess.')
            ->assertExitCode(0);
    }

    public function test_command_filters_by_multiple_climate_levels(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '993',
        ]);

        // Create evaluation with high score (TA)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059930001',
            'organization_code' => '993',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, 'A'),
            ],
        ]);

        // Create evaluation with low score (TD)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059930002',
            'organization_code' => '993',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, 'D'),
            ],
        ]);

        // Filter by both TA and TD
        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--climate' => 'ta,td',
            '--dry-run' => true,
        ])
            ->expectsOutput('Filtering by climate levels: Totalmente de Acuerdo, Totalmente Desacuerdo')
            ->expectsOutput('Found 2 evaluations to reprocess.')
            ->assertExitCode(0);
    }

    public function test_command_shows_warning_for_invalid_climate_codes(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '992',
        ]);

        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059920001',
            'organization_code' => '992',
            'source' => 'paper',
            'processing_status' => 'completed',
        ]);

        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--climate' => 'invalid',
            '--dry-run' => true,
        ])
            ->expectsOutput('Invalid climate level codes. Use: ta (Totalmente Acuerdo), da (De Acuerdo), d (Desacuerdo), td (Totalmente Desacuerdo), e (empty/score 0)')
            ->assertExitCode(0);
    }

    public function test_command_filters_empty_evaluations_with_e_code(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '993',
        ]);

        // Evaluation with all empty answers (score 0)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059930001',
            'organization_code' => '993',
            'personal_folio' => '0001',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, null),
            ],
        ]);

        // Evaluation with no questions at all
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059930002',
            'organization_code' => '993',
            'personal_folio' => '0002',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => [],
            ],
        ]);

        // Evaluation with valid answers (should be excluded)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059930003',
            'organization_code' => '993',
            'personal_folio' => '0003',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, 'A'),
            ],
        ]);

        // Filter only empty evaluations
        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--climate' => 'e',
            '--dry-run' => true,
        ])
            ->expectsOutput('Filtering by climate levels: Empty/Score 0')
            ->expectsOutput('Found 2 evaluations to reprocess.')
            ->assertExitCode(0);
    }

    public function test_command_filters_empty_combined_with_climate_levels(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '994',
        ]);

        // Evaluation with empty answers
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059940001',
            'organization_code' => '994',
            'personal_folio' => '0001',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, null),
            ],
        ]);

        // Evaluation with TD score (all D answers)
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059940002',
            'organization_code' => '994',
            'personal_folio' => '0002',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, 'D'),
            ],
        ]);

        // Evaluation with TA score (all A answers) - should be excluded
        PaperEvaluation::factory()->likert()->create([
            'organization_id' => $organization->id,
            'folio' => '059940003',
            'organization_code' => '994',
            'personal_folio' => '0003',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => [
                'questions' => array_fill(1, 23, 'A'),
            ],
        ]);

        // Filter empty and TD evaluations
        $this->artisan('evaluations:reprocess', [
            'organization' => $organization->id,
            '--climate' => 'td,e',
            '--dry-run' => true,
        ])
            ->expectsOutput('Filtering by climate levels: Totalmente Desacuerdo, Empty/Score 0')
            ->expectsOutput('Found 2 evaluations to reprocess.')
            ->assertExitCode(0);
    }
}
