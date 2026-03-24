<?php

namespace App\Console\Commands;

use App\Models\PaperEvaluation;
use App\Services\DemographicDataService;
use Illuminate\Console\Command;

class BackfillPaperDemographicData extends Command
{
    protected $signature = 'evaluations:backfill-paper-demographic-data
        {--work-center= : Optional work center UUID filter}
        {--organization= : Optional organization UUID filter}
        {--limit= : Optional limit of target records}
        {--dry-run : Show candidates without writing changes}
        {--force : Execute without confirmation prompt}';

    protected $description = 'Backfill missing DemographicData for paper Referencia I/III evaluations using related paper Referencia V data.';

    public function handle(DemographicDataService $demographicDataService): int
    {
        $workCenterId = $this->option('work-center');
        $organizationId = $this->option('organization');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = PaperEvaluation::query()
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->whereIn('evaluation_type', ['referencia_i', 'referencia_iii'])
            ->where(function ($builder) {
                $builder->whereDoesntHave('demographicData')
                    ->orWhereHas('demographicData', function ($query) {
                        $query->whereNull('gender')
                            ->whereNull('position')
                            ->whereNull('department')
                            ->whereNull('work_schedule');
                    });
            })
            ->orderBy('id');

        if (is_string($workCenterId) && trim($workCenterId) !== '') {
            $query->where('work_center_id', trim($workCenterId));
        }

        if (is_string($organizationId) && trim($organizationId) !== '') {
            $query->where('organization_id', trim($organizationId));
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $targets = $query->get();

        if ($targets->isEmpty()) {
            $this->info('No paper Referencia I/III records requiring demographic backfill found for the selected filters.');

            return self::SUCCESS;
        }

        if (! $force && ! $dryRun && ! $this->confirm('This command will write data to demographic_data. Continue?')) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info("Found {$targets->count()} target records to inspect.");

        $candidates = [];
        $backfilledCount = 0;
        $missingSourceCount = 0;
        $invalidSourceDataCount = 0;

        foreach ($targets as $target) {
            $sourceRefV = PaperEvaluation::query()
                ->where('source', 'paper')
                ->where('processing_status', 'completed')
                ->where('evaluation_type', 'referencia_v')
                ->where('organization_id', $target->organization_id)
                ->where('work_center_id', $target->work_center_id)
                ->where('personal_folio', $target->personal_folio)
                ->whereNotNull('demographic_data')
                ->orderByDesc('processed_at')
                ->orderByDesc('id')
                ->first();

            if (! $sourceRefV instanceof PaperEvaluation) {
                $missingSourceCount++;

                continue;
            }

            if (! is_array($sourceRefV->demographic_data) || empty($sourceRefV->demographic_data)) {
                $invalidSourceDataCount++;

                continue;
            }

            $candidates[] = [
                'target_id' => $target->id,
                'target_folio' => $target->folio,
                'target_type' => $target->evaluation_type,
                'source_ref_v_folio' => $sourceRefV->folio,
                'personal_folio' => $target->personal_folio,
            ];

            if ($dryRun) {
                continue;
            }

            $target->update([
                'demographic_data' => $sourceRefV->demographic_data,
            ]);

            $demographicDataService->updateOrCreate($target, $sourceRefV->demographic_data);
            $backfilledCount++;
        }

        if ($candidates !== []) {
            $this->table(
                ['Personal Folio', 'Target Type', 'Target Folio', 'Source Ref V Folio'],
                collect($candidates)->map(fn (array $candidate) => [
                    $candidate['personal_folio'],
                    $candidate['target_type'],
                    $candidate['target_folio'],
                    $candidate['source_ref_v_folio'],
                ])->toArray()
            );
        }

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were made.');
            $this->line('Candidates to backfill: '.count($candidates));
            $this->line("Missing Ref V source: {$missingSourceCount}");
            $this->line("Invalid Ref V demographic_data: {$invalidSourceDataCount}");

            return self::SUCCESS;
        }

        $this->info("Backfilled {$backfilledCount} records into demographic_data.");
        $this->line("Missing Ref V source: {$missingSourceCount}");
        $this->line("Invalid Ref V demographic_data: {$invalidSourceDataCount}");

        return self::SUCCESS;
    }
}
