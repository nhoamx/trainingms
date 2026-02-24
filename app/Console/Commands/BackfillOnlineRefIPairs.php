<?php

namespace App\Console\Commands;

use App\Models\PaperEvaluation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BackfillOnlineRefIPairs extends Command
{
    protected $signature = 'evaluations:backfill-online-ref-i-pairs
        {--organization= : Filter by organization UUID}
        {--work-center= : Filter by work center UUID}
        {--limit= : Limit number of Ref III records to inspect}
        {--dry-run : Show candidates without writing changes}
        {--force : Execute without confirmation prompt}';

    protected $description = 'Backfill missing online Referencia I records for completed online Referencia III records.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $organizationId = $this->option('organization');
        $workCenterId = $this->option('work-center');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = PaperEvaluation::query()
            ->where('source', 'online')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->whereNotNull('citsats_s1')
            ->orderBy('id');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if ($workCenterId) {
            $query->where('work_center_id', $workCenterId);
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        /** @var Collection<int, PaperEvaluation> $refIIIRecords */
        $refIIIRecords = $query->get();

        if ($refIIIRecords->isEmpty()) {
            $this->info('No online Referencia III records found for the selected filters.');

            return self::SUCCESS;
        }

        $this->info("Found {$refIIIRecords->count()} online Referencia III records to inspect.");

        $createdCount = 0;
        $alreadyPairedCount = 0;
        $invalidFolioCount = 0;
        $duplicateFolioCount = 0;
        $candidates = [];

        foreach ($refIIIRecords as $refIII) {
            if ($this->hasExistingRefIPair($refIII)) {
                $alreadyPairedCount++;

                continue;
            }

            $refIFolio = $this->buildRefIFolio($refIII->folio);

            if ($refIFolio === null) {
                $invalidFolioCount++;

                continue;
            }

            if (PaperEvaluation::query()->where('folio', $refIFolio)->exists()) {
                $duplicateFolioCount++;

                continue;
            }

            $candidates[] = [
                'id' => $refIII->id,
                'personal_folio' => $refIII->personal_folio,
                'ref_iii_folio' => $refIII->folio,
                'ref_i_folio' => $refIFolio,
            ];

            if ($dryRun) {
                continue;
            }
        }

        if (! empty($candidates)) {
            $this->table(
                ['Personal Folio', 'Ref III Folio', 'Ref I Folio'],
                collect($candidates)->map(fn (array $item) => [
                    $item['personal_folio'],
                    $item['ref_iii_folio'],
                    $item['ref_i_folio'],
                ])->toArray()
            );
        }

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were made.');
            $this->line('Candidates to create: '.count($candidates));
            $this->line("Already paired: {$alreadyPairedCount}");
            $this->line("Invalid folio: {$invalidFolioCount}");
            $this->line("Skipped existing Ref I folio: {$duplicateFolioCount}");

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm('Execute backfill with the candidates shown above?')) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        foreach ($candidates as $candidate) {
            $refIII = PaperEvaluation::query()->find($candidate['id']);

            if (! $refIII instanceof PaperEvaluation) {
                continue;
            }

            if ($this->hasExistingRefIPair($refIII)) {
                continue;
            }

            if (PaperEvaluation::query()->where('folio', $candidate['ref_i_folio'])->exists()) {
                continue;
            }

            $newRefI = $this->createRefIFromRefIII($refIII, $candidate['ref_i_folio']);
            $refIII->related_evaluation_folio = $newRefI->folio;
            $refIII->save();
            $createdCount++;
        }

        $this->info("Created {$createdCount} missing Referencia I records.");
        $this->line("Already paired: {$alreadyPairedCount}");
        $this->line("Invalid folio: {$invalidFolioCount}");
        $this->line("Skipped existing Ref I folio: {$duplicateFolioCount}");

        return self::SUCCESS;
    }

    private function hasExistingRefIPair(PaperEvaluation $refIII): bool
    {
        return PaperEvaluation::query()
            ->where('source', 'online')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_i')
            ->where('work_center_id', $refIII->work_center_id)
            ->where('personal_folio', $refIII->personal_folio)
            ->exists();
    }

    private function buildRefIFolio(string $refIIIFolio): ?string
    {
        try {
            $parts = PaperEvaluation::parseFolio($refIIIFolio);
        } catch (\Throwable) {
            return null;
        }

        if (strlen($refIIIFolio) === 11) {
            return sprintf('01%s%s%s', $parts['organization_code'], $parts['work_center_code'], $parts['personal_folio']);
        }

        if (strlen($refIIIFolio) === 9) {
            return sprintf('01%s%s', $parts['organization_code'], $parts['personal_folio']);
        }

        return null;
    }

    private function createRefIFromRefIII(PaperEvaluation $refIII, string $refIFolio): PaperEvaluation
    {
        $rawReferenciaI = data_get($refIII->raw_data, 'referencia_i', []);
        $refIAnswers = [];

        for ($i = 1; $i <= 14; $i++) {
            $key = (string) $i;
            if (array_key_exists($key, $rawReferenciaI)) {
                $refIAnswers[$key] = $rawReferenciaI[$key];
            }
        }

        $created = PaperEvaluation::create([
            'folio' => $refIFolio,
            'evaluation_type_code' => '01',
            'organization_code' => $refIII->organization_code,
            'work_center_code' => $refIII->work_center_code,
            'personal_folio' => $refIII->personal_folio,
            'organization_id' => $refIII->organization_id,
            'work_center_id' => $refIII->work_center_id,
            'evaluation_type' => 'referencia_i',
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'evaluee_name' => data_get($refIII->raw_data, 'evaluee_name'),
            'demographic_data' => $refIII->demographic_data,
            'referencia_i_answers' => ! empty($refIAnswers) ? $refIAnswers : null,
            'referencia_iii_answers' => null,
            'referencia_iii_conditional' => null,
            'citsats_s1' => $refIII->citsats_s1,
            'cisneros_answers' => null,
            'raw_data' => $refIII->raw_data,
        ]);

        $created->related_evaluation_folio = $refIII->folio;
        $created->save();

        return $created;
    }
}
