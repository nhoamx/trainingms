<?php

namespace App\Console\Commands;

use App\Models\PaperEvaluation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BackfillPaperReferenciaIIIConditionalCommand extends Command
{
    protected $signature = 'evaluations:backfill-paper-referencia-iii-conditional
        {--organization= : Optional organization UUID filter}
        {--work-center= : Optional work center UUID filter}
        {--limit= : Optional limit of target records}
        {--dry-run : Analyze candidates without persisting changes}
        {--force : Execute without confirmation prompt}';

    protected $description = 'Backfills missing referencia_iii_conditional from raw_data for paper Referencia III evaluations.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = $this->baseQuery();

        $organizationId = $this->option('organization');
        if (is_string($organizationId) && trim($organizationId) !== '') {
            $query->where('organization_id', trim($organizationId));
        }

        $workCenterId = $this->option('work-center');
        if (is_string($workCenterId) && trim($workCenterId) !== '') {
            $query->where('work_center_id', trim($workCenterId));
        }

        $limit = $this->option('limit');
        if (is_numeric($limit) && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $candidates = $query->get();
        $targets = $candidates->filter(fn (PaperEvaluation $evaluation): bool => $this->needsBackfill($evaluation))->values();
        $total = $targets->count();

        $this->info("Found {$total} paper Referencia III records to inspect.");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $stats = [
            'inspected' => $total,
            'updated' => 0,
            'skipped_without_extractable_data' => 0,
            'errors' => 0,
        ];

        $sample = [];
        foreach ($targets->take(20) as $evaluation) {
            $normalized = $this->extractConditionalFromRawData(is_array($evaluation->raw_data) ? $evaluation->raw_data : []);
            $sample[] = [
                'ID' => $evaluation->id,
                'Folio' => (string) $evaluation->folio,
                'Extractable' => $normalized === null ? 'NO' : 'SI',
            ];
        }

        if ($sample !== []) {
            $this->table(['ID', 'Folio', 'Extractable'], $sample);
        }

        $isDryRun = (bool) $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN - No changes were made.');

            return self::SUCCESS;
        }

        if (! (bool) $this->option('force') && ! $this->confirm('Do you want to continue and persist the backfill updates?')) {
            $this->warn('Operation cancelled.');

            return self::INVALID;
        }

        $this->line('Applying updates...');

        $queryForUpdate = $this->baseQuery();

        if (is_string($organizationId) && trim($organizationId) !== '') {
            $queryForUpdate->where('organization_id', trim($organizationId));
        }

        if (is_string($workCenterId) && trim($workCenterId) !== '') {
            $queryForUpdate->where('work_center_id', trim($workCenterId));
        }

        if (is_numeric($limit) && (int) $limit > 0) {
            $queryForUpdate->limit((int) $limit);
        }

        $queryForUpdate->chunkById(200, function (Collection $evaluations) use (&$stats): void {
            foreach ($evaluations as $evaluation) {
                if (! $this->needsBackfill($evaluation)) {
                    continue;
                }

                try {
                    $normalized = $this->extractConditionalFromRawData(is_array($evaluation->raw_data) ? $evaluation->raw_data : []);
                    if ($normalized === null) {
                        $stats['skipped_without_extractable_data']++;

                        continue;
                    }

                    $current = is_array($evaluation->referencia_iii_conditional) ? $evaluation->referencia_iii_conditional : [];
                    $merged = $this->mergeConditionals($current, $normalized);

                    if (! $this->hasValidConditionalSection($merged, 'customer_service') && ! $this->hasValidConditionalSection($merged, 'management')) {
                        $stats['skipped_without_extractable_data']++;

                        continue;
                    }

                    DB::transaction(function () use ($evaluation, $merged): void {
                        $evaluation->update([
                            'referencia_iii_conditional' => $merged,
                        ]);
                    });

                    $stats['updated']++;
                } catch (\Throwable) {
                    $stats['errors']++;
                }
            }
        }, 'id');

        $this->line('Inspected: '.$stats['inspected']);
        $this->line('Updated: '.$stats['updated']);
        $this->line('Skipped (no extractable conditional data): '.$stats['skipped_without_extractable_data']);
        $this->line('Errors: '.$stats['errors']);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function baseQuery(): Builder
    {
        return PaperEvaluation::query()
            ->where('source', 'paper')
            ->where('evaluation_type', 'referencia_iii')
            ->where('processing_status', 'completed')
            ->whereNotNull('raw_data')
            ->orderBy('id');
    }

    private function needsBackfill(PaperEvaluation $evaluation): bool
    {
        $conditional = is_array($evaluation->referencia_iii_conditional) ? $evaluation->referencia_iii_conditional : [];

        return ! $this->hasValidConditionalSection($conditional, 'customer_service')
            || ! $this->hasValidConditionalSection($conditional, 'management');
    }

    /**
     * @param  array<string, mixed>  $conditional
     */
    private function hasValidConditionalSection(array $conditional, string $sectionKey): bool
    {
        if (! isset($conditional[$sectionKey]) || ! is_array($conditional[$sectionKey])) {
            return false;
        }

        $condition = $conditional[$sectionKey]['condition'] ?? null;

        return is_string($condition) && in_array($condition, ['SI', 'NO'], true);
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $fromRaw
     * @return array<string, mixed>
     */
    private function mergeConditionals(array $current, array $fromRaw): array
    {
        $result = [];

        foreach (['customer_service', 'management'] as $sectionKey) {
            if ($this->hasValidConditionalSection($fromRaw, $sectionKey)) {
                $result[$sectionKey] = $fromRaw[$sectionKey];

                continue;
            }

            if ($this->hasValidConditionalSection($current, $sectionKey)) {
                $result[$sectionKey] = $current[$sectionKey];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $rawData
     * @return array<string, array{condition:string,questions:array<string, mixed>}>|null
     */
    private function extractConditionalFromRawData(array $rawData): ?array
    {
        $referenciaIII = isset($rawData['referencia_iii']) && is_array($rawData['referencia_iii'])
            ? $rawData['referencia_iii']
            : [];

        $flatOmrConditional = $this->extractConditionalFromFlatOmrRawData($rawData);

        $customerConditionRaw = $this->firstNonNull([
            $this->extractValue($referenciaIII['customer_service']['condition'] ?? null),
            $this->extractValue($referenciaIII['condition_customer_service'] ?? null),
            $this->extractValue($rawData['customer_service_conditional']['condition'] ?? null),
            $flatOmrConditional['customer_service']['condition'] ?? null,
        ]);

        $managementConditionRaw = $this->firstNonNull([
            $this->extractValue($referenciaIII['management']['condition'] ?? null),
            $this->extractValue($referenciaIII['condition_management'] ?? null),
            $this->extractValue($rawData['conditional_management']['condition'] ?? null),
            $flatOmrConditional['management']['condition'] ?? null,
        ]);

        $customerCondition = $this->normalizeConditionToSiNo($customerConditionRaw);
        $managementCondition = $this->normalizeConditionToSiNo($managementConditionRaw);

        $result = [];

        if ($customerCondition !== null) {
            $result['customer_service'] = [
                'condition' => $customerCondition,
                'questions' => $customerCondition === 'SI'
                    ? $this->firstNonEmptyArray([
                        $this->extractQuestions($referenciaIII, $rawData, 'customer_service', 'customer_service_questions', 65, 68),
                        $flatOmrConditional['customer_service']['questions'] ?? [],
                    ])
                    : [],
            ];
        }

        if ($managementCondition !== null) {
            $result['management'] = [
                'condition' => $managementCondition,
                'questions' => $managementCondition === 'SI'
                    ? $this->firstNonEmptyArray([
                        $this->extractQuestions($referenciaIII, $rawData, 'management', 'management_questions', 69, 72),
                        $flatOmrConditional['management']['questions'] ?? [],
                    ])
                    : [],
            ];
        }

        return $result === [] ? null : $result;
    }

    /**
     * @param  array<string, mixed>  $rawData
     * @return array<string, array{condition:mixed,questions:array<string, mixed>}>
     */
    private function extractConditionalFromFlatOmrRawData(array $rawData): array
    {
        $conditionalOne = [];
        $conditionalOneFollowup = [];
        $conditionalTwo = [];
        $conditionalTwoFollowup = [];

        foreach ($rawData as $rawKey => $rawAnswer) {
            if (! is_array($rawAnswer) || ! isset($rawAnswer['mapping_section'])) {
                continue;
            }

            if (! is_numeric((string) $rawKey)) {
                continue;
            }

            $section = (string) $rawAnswer['mapping_section'];
            $numericKey = (int) $rawKey;

            if ($section === 'conditional_1') {
                $conditionalOne[$numericKey] = $rawAnswer;

                continue;
            }

            if ($section === 'conditional_1_followup') {
                $conditionalOneFollowup[$numericKey] = $rawAnswer;

                continue;
            }

            if ($section === 'conditional_2') {
                $conditionalTwo[$numericKey] = $rawAnswer;

                continue;
            }

            if ($section === 'conditional_2_followup') {
                $conditionalTwoFollowup[$numericKey] = $rawAnswer;
            }
        }

        ksort($conditionalOne);
        ksort($conditionalOneFollowup);
        ksort($conditionalTwo);
        ksort($conditionalTwoFollowup);

        return [
            'customer_service' => [
                'condition' => $this->extractValueFromEntry(reset($conditionalOne) ?: null),
                'questions' => $this->mapFollowupEntriesToQuestions($conditionalOneFollowup, 65),
            ],
            'management' => [
                'condition' => $this->extractValueFromEntry(reset($conditionalTwo) ?: null),
                'questions' => $this->mapFollowupEntriesToQuestions($conditionalTwoFollowup, 69),
            ],
        ];
    }

    /**
     * @param  array<int, mixed>  $entries
     * @return array<string, mixed>
     */
    private function mapFollowupEntriesToQuestions(array $entries, int $questionStart): array
    {
        $mapped = [];
        $offset = 0;

        foreach ($entries as $entry) {
            $value = $this->extractValueFromEntry($entry);
            if ($value === null || $value === '') {
                $offset++;

                continue;
            }

            if ($offset >= 4) {
                break;
            }

            $mapped[(string) ($questionStart + $offset)] = $value;
            $offset++;
        }

        return $mapped;
    }

    private function extractValueFromEntry(mixed $entry): mixed
    {
        if (! is_array($entry)) {
            return null;
        }

        return $this->extractValue($entry['value'] ?? null);
    }

    /**
     * @param  array<int, array<string, mixed>>  $arrays
     * @return array<string, mixed>
     */
    private function firstNonEmptyArray(array $arrays): array
    {
        foreach ($arrays as $array) {
            if (is_array($array) && $array !== []) {
                return $array;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $referenciaIII
     * @param  array<string, mixed>  $rawData
     * @return array<string, mixed>
     */
    private function extractQuestions(
        array $referenciaIII,
        array $rawData,
        string $nestedSection,
        string $topLevelSection,
        int $start,
        int $end
    ): array {
        $questions = [];

        $nestedQuestions = isset($referenciaIII[$nestedSection]['questions']) && is_array($referenciaIII[$nestedSection]['questions'])
            ? $referenciaIII[$nestedSection]['questions']
            : [];

        $topLevelQuestions = isset($rawData[$topLevelSection]) && is_array($rawData[$topLevelSection])
            ? $rawData[$topLevelSection]
            : [];

        for ($number = $start; $number <= $end; $number++) {
            $key = (string) $number;

            $value = $this->firstNonNull([
                $this->extractValue($referenciaIII[$number] ?? null),
                $this->extractValue($referenciaIII[$key] ?? null),
                $this->extractValue($referenciaIII[$nestedSection][$number] ?? null),
                $this->extractValue($referenciaIII[$nestedSection][$key] ?? null),
                $this->extractValue($nestedQuestions[$number] ?? null),
                $this->extractValue($nestedQuestions[$key] ?? null),
                $this->extractValue($topLevelQuestions[$number] ?? null),
                $this->extractValue($topLevelQuestions[$key] ?? null),
            ]);

            if ($value === null || $value === '') {
                continue;
            }

            $questions[$key] = $value;
        }

        return $questions;
    }

    private function extractValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    private function firstNonNull(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function normalizeConditionToSiNo(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'SI' : 'NO';
        }

        if (is_int($value)) {
            if ($value === 1) {
                return 'SI';
            }

            if ($value === 0) {
                return 'NO';
            }

            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim(strtr($value, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
        ])));

        if (in_array($normalized, ['SI', 'S', 'YES', 'Y', 'TRUE', '1'], true)) {
            return 'SI';
        }

        if (in_array($normalized, ['NO', 'N', 'FALSE', '0'], true)) {
            return 'NO';
        }

        return null;
    }
}
