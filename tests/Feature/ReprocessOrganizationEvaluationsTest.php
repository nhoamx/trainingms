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
}
