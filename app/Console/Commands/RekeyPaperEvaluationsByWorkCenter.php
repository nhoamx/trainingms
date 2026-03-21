<?php

namespace App\Console\Commands;

use App\Models\PaperEvaluation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RekeyPaperEvaluationsByWorkCenter extends Command
{
    protected $signature = 'paper-evaluations:rekey-by-work-center
        {--organization= : Filter by organization UUID}
        {--work-center= : Filter by work center UUID}
        {--source=all : Filter by source (all|paper|online|hybrid)}
        {--conflict=skip : Conflict strategy (skip|fail)}
        {--dry-run : Analyze and report changes without writing}
        {--apply : Apply updates}';

    protected $description = 'Re-sequences personal folios for 11-digit PaperEvaluation folios per work center from 00001 upward.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isApplyMode = (bool) $this->option('apply');
        $isDryRun = ! $isApplyMode || (bool) $this->option('dry-run');
        $organizationId = $this->option('organization');
        $workCenterId = $this->option('work-center');
        $source = (string) $this->option('source');
        $conflictStrategy = (string) $this->option('conflict');

        if (! in_array($source, ['all', 'paper', 'online', 'hybrid'], true)) {
            $this->error('Invalid --source option. Allowed: all, paper, online, hybrid.');

            return self::FAILURE;
        }

        if (! in_array($conflictStrategy, ['skip', 'fail'], true)) {
            $this->error('Invalid --conflict option. Allowed: skip, fail.');

            return self::FAILURE;
        }

        $stats = [
            'inspected' => 0,
            'eligible_new_format' => 0,
            'unchanged' => 0,
            'updated' => 0,
            'skipped_conflict' => 0,
            'ignored_legacy_format' => 0,
            'groups_processed' => 0,
            'errors' => 0,
        ];

        $sampleChanges = [];

        $query = PaperEvaluation::query()->orderBy('id');

        if ($source !== 'all') {
            $query->where('source', $source);
        }

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if ($workCenterId) {
            $query->where('work_center_id', $workCenterId);
        }

        $this->info($isDryRun ? 'Running in DRY RUN mode (no writes).' : 'Running in APPLY mode.');

        $evaluations = $query->get();
        $stats['inspected'] = $evaluations->count();

        $legacyCount = $evaluations->filter(function (PaperEvaluation $evaluation) {
            return ! preg_match('/^\d{11}$/', (string) $evaluation->folio);
        })->count();
        $stats['ignored_legacy_format'] = $legacyCount;

        $eligibleEvaluations = $evaluations
            ->filter(function (PaperEvaluation $evaluation) {
                return preg_match('/^\d{11}$/', (string) $evaluation->folio) === 1;
            })
            ->values();

        $parsedEligible = collect();
        foreach ($eligibleEvaluations as $evaluation) {
            try {
                $parts = PaperEvaluation::parseFolio((string) $evaluation->folio);
                $parsedEligible->push([
                    'evaluation' => $evaluation,
                    'parts' => $parts,
                ]);
            } catch (\Throwable) {
                $stats['errors']++;
            }
        }

        $stats['eligible_new_format'] = $parsedEligible->count();

        $grouped = $parsedEligible->groupBy(function (array $item) {
            return $item['parts']['organization_code'].'-'.$item['parts']['work_center_code'];
        });

        foreach ($grouped as $group) {
            $stats['groups_processed']++;

            $personalFolios = $group
                ->map(function (array $item) {
                    return $item['parts']['personal_folio'];
                })
                ->unique()
                ->sort(function (string $left, string $right) {
                    return (int) $left <=> (int) $right;
                })
                ->values();

            $personalFolioMap = [];
            foreach ($personalFolios as $index => $oldPersonalFolio) {
                $personalFolioMap[$oldPersonalFolio] = str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);
            }

            foreach ($group as $item) {
                $evaluation = $item['evaluation'];
                $parts = $item['parts'];
                $newPersonalFolio = $personalFolioMap[$parts['personal_folio']];
                $newFolio = PaperEvaluation::generateFolio(
                    $parts['evaluation_type_code'],
                    $parts['organization_code'],
                    $newPersonalFolio,
                    $parts['work_center_code']
                );

                $target = [
                    'folio' => $newFolio,
                    'organization_code' => $parts['organization_code'],
                    'work_center_code' => $parts['work_center_code'],
                    'personal_folio' => $newPersonalFolio,
                ];

                if (
                    $evaluation->folio === $target['folio']
                    && $evaluation->personal_folio === $target['personal_folio']
                    && $evaluation->organization_code === $target['organization_code']
                    && $evaluation->work_center_code === $target['work_center_code']
                ) {
                    $stats['unchanged']++;

                    continue;
                }

                $conflictExists = PaperEvaluation::query()
                    ->where('folio', $target['folio'])
                    ->where('id', '!=', $evaluation->id)
                    ->exists();

                if ($conflictExists) {
                    if ($conflictStrategy === 'fail') {
                        $this->error("Conflict detected for folio {$target['folio']} (record {$evaluation->id}).");

                        return self::FAILURE;
                    }

                    $stats['skipped_conflict']++;

                    continue;
                }

                if (count($sampleChanges) < 25) {
                    $sampleChanges[] = [
                        'id' => $evaluation->id,
                        'old_folio' => $evaluation->folio,
                        'new_folio' => $target['folio'],
                    ];
                }

                if ($isDryRun) {
                    $stats['updated']++;

                    continue;
                }

                try {
                    DB::transaction(function () use ($evaluation, $target) {
                        $oldFolio = $evaluation->folio;

                        $evaluation->update($target);

                        PaperEvaluation::query()
                            ->where('related_evaluation_folio', $oldFolio)
                            ->update(['related_evaluation_folio' => $target['folio']]);
                    });

                    $stats['updated']++;
                } catch (\Throwable $throwable) {
                    $stats['errors']++;
                    $this->error("Failed to rekey record {$evaluation->id}: {$throwable->getMessage()}");
                }
            }
        }

        if (! empty($sampleChanges)) {
            $this->table(['ID', 'Old Folio', 'New Folio'], $sampleChanges);
        }

        $this->line('Inspected: '.$stats['inspected']);
        $this->line('Eligible new format: '.$stats['eligible_new_format']);
        $this->line('Updated: '.$stats['updated']);
        $this->line('Unchanged: '.$stats['unchanged']);
        $this->line('Skipped conflicts: '.$stats['skipped_conflict']);
        $this->line('Ignored legacy format: '.$stats['ignored_legacy_format']);
        $this->line('Groups processed: '.$stats['groups_processed']);
        $this->line('Errors: '.$stats['errors']);

        $reportPath = 'reports/folio-rekey-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($reportPath, json_encode([
            'mode' => $isDryRun ? 'dry-run' : 'apply',
            'generated_at' => now()->toIso8601String(),
            'stats' => $stats,
            'sample_changes' => $sampleChanges,
        ], JSON_PRETTY_PRINT));

        $this->info('Report generated at storage/app/'.$reportPath);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
