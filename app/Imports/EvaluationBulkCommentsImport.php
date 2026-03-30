<?php

namespace App\Imports;

use App\Models\EvaluationComment;
use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EvaluationBulkCommentsImport implements ToCollection, WithHeadingRow
{
    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    protected string $organizationId;

    protected ?string $workCenterId;

    /**
     * Callback to report progress
     */
    protected $progressCallback = null;

    /**
     * @param  string  $organizationId  UUID de la organización para filtrar evaluaciones
     * @param  callable|null  $progressCallback  Optional callback to report progress
     */
    public function __construct(string $organizationId, ?callable $progressCallback = null, ?string $workCenterId = null)
    {
        $this->organizationId = $organizationId;
        $this->progressCallback = $progressCallback;
        $this->workCenterId = $workCenterId;
    }

    /**
     * Report progress if callback is set
     */
    protected function reportProgress(int $processedRows, int $totalRows): void
    {
        if ($this->progressCallback) {
            call_user_func($this->progressCallback, $processedRows, $totalRows, $this->updatedCount, $this->skippedCount);
        }
    }

    /**
     * Procesar las filas del archivo Excel
     */
    public function collection(Collection $rows): void
    {
        Log::info('=== BULK COMMENTS IMPORT STARTED ===');
        Log::info('Total rows to process: '.$rows->count());
        Log::info('Organization ID: '.$this->organizationId);

        if ($rows->isEmpty()) {
            Log::warning('No rows to process');

            return;
        }

        $headers = $rows->first()->keys()->toArray();
        Log::info('Excel headers detected', ['headers' => $headers]);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index is 0-based and we have a header row

            try {
                Log::info("Processing row {$rowNumber}", ['raw_row' => $row->toArray()]);

                // Normalize values - trim whitespace
                $row = $row->map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                });

                // Skip completely empty rows
                if ($row->filter()->isEmpty()) {
                    Log::info("Row {$rowNumber} is completely empty, skipping");

                    continue;
                }

                // Extract folio personal candidates to support 4/5 digits and leading zeros.
                $folioMatchData = $this->extractPersonalFolioMatchData($row);
                $personalFolioCandidates = $folioMatchData['candidates'];

                if (empty($personalFolioCandidates)) {
                    $this->errors[] = "Fila {$rowNumber}: Folio Personal es requerido";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - missing personal_folio");

                    continue;
                }

                Log::info("Row {$rowNumber} personal folio candidates", ['candidates' => $personalFolioCandidates]);

                // Find the Likert evaluation for this personal folio
                $query = PaperEvaluation::whereIn('personal_folio', $personalFolioCandidates)
                    ->where('organization_id', $this->organizationId)
                    ->where('evaluation_type', 'likert')
                    ->where('processing_status', 'completed');

                if ($this->workCenterId !== null) {
                    $query->where('work_center_id', $this->workCenterId);
                }

                $evaluation = $query->first();

                if (! $evaluation) {
                    $folioLabel = $folioMatchData['raw'] ?? $personalFolioCandidates[0];
                    $this->errors[] = "Fila {$rowNumber}: No se encontró evaluación Likert para el folio {$folioLabel}";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - no Likert evaluation found");

                    continue;
                }

                // Process comments for this evaluation
                $commentsProcessed = $this->processComments($evaluation, $row, $rowNumber);

                if ($commentsProcessed) {
                    $this->updatedCount++;
                    Log::info("Row {$rowNumber} marked as updated");
                } else {
                    $this->skippedCount++;
                    Log::info("Row {$rowNumber} skipped - no comments to process");
                }

                // Report progress every row
                $this->reportProgress($index + 1, $rows->count());
            } catch (\Exception $e) {
                Log::error("Error processing row {$rowNumber}: ".$e->getMessage(), [
                    'exception' => $e->getTraceAsString(),
                ]);
                $this->errors[] = "Fila {$rowNumber}: Error al procesar - {$e->getMessage()}";
                $this->skippedCount++;

                // Report progress even on error
                $this->reportProgress($index + 1, $rows->count());
            }
        }

        Log::info('=== BULK COMMENTS IMPORT FINISHED ===', [
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => count($this->errors),
        ]);
    }

    /**
     * Extract personal folio from row using various possible column names
     */
    protected function extractPersonalFolioMatchData(Collection $row): array
    {
        $possibleKeys = ['folio_personal', 'folio', 'numero'];

        foreach ($possibleKeys as $key) {
            if (! isset($row[$key]) || $row[$key] === null || $row[$key] === '') {
                continue;
            }

            $rawValue = trim((string) $row[$key]);
            if ($rawValue === '') {
                continue;
            }

            return [
                'raw' => $rawValue,
                'candidates' => $this->buildFolioCandidates($rawValue),
            ];
        }

        return [
            'raw' => null,
            'candidates' => [],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function buildFolioCandidates(string $rawValue): array
    {
        $candidates = [];

        $trimmed = trim($rawValue);
        if ($trimmed !== '') {
            $candidates[] = $trimmed;
        }

        $digitsOnly = preg_replace('/\D+/', '', $trimmed);
        if ($digitsOnly !== null && $digitsOnly !== '') {
            $candidates[] = $digitsOnly;

            $withoutLeadingZeros = ltrim($digitsOnly, '0');
            if ($withoutLeadingZeros === '') {
                $withoutLeadingZeros = '0';
            }

            $candidates[] = $withoutLeadingZeros;

            foreach ([4, 5] as $length) {
                if (strlen($withoutLeadingZeros) <= $length) {
                    $candidates[] = str_pad($withoutLeadingZeros, $length, '0', STR_PAD_LEFT);
                }
            }
        }

        return array_values(array_unique(array_filter($candidates, function ($candidate) {
            return $candidate !== '';
        })));
    }

    /**
     * Process a single comment row for an evaluation
     */
    protected function processComments(PaperEvaluation $evaluation, Collection $row, int $rowNumber): bool
    {
        // Extract comment and factor from row - flexible with column names
        $comment = $row['comentario'] ?? $row['comentarios'] ?? $row['comment'] ?? '';
        $factor = $row['factor'] ?? $row['factores'] ?? '';

        // Skip if no comment or factor provided
        if (empty($comment) || empty($factor)) {
            Log::info("Row {$rowNumber} has no comment or factor to process");

            return false;
        }

        // Allow multiple comments per folio/factor while preventing exact duplicates.
        EvaluationComment::firstOrCreate([
            'paper_evaluation_id' => $evaluation->id,
            'factor' => trim($factor),
            'comment' => trim($comment),
        ]);

        Log::info('Created/updated comment for evaluation', [
            'row' => $rowNumber,
            'evaluation_id' => $evaluation->id,
            'factor' => $factor,
        ]);

        return true;
    }

    /**
     * Get updated count
     */
    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    /**
     * Get skipped count
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
