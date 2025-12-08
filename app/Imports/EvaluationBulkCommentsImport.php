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

    /**
     * Callback to report progress
     */
    protected $progressCallback = null;

    /**
     * @param  string  $organizationId  UUID de la organización para filtrar evaluaciones
     * @param  callable|null  $progressCallback  Optional callback to report progress
     */
    public function __construct(string $organizationId, ?callable $progressCallback = null)
    {
        $this->organizationId = $organizationId;
        $this->progressCallback = $progressCallback;
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

                // Extract folio personal
                $personalFolio = $this->extractPersonalFolio($row);

                if (empty($personalFolio)) {
                    $this->errors[] = "Fila {$rowNumber}: Folio Personal es requerido";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - missing personal_folio");

                    continue;
                }

                Log::info("Row {$rowNumber} personal folio: {$personalFolio}");

                // Find the Likert evaluation for this personal folio
                $evaluation = PaperEvaluation::where('personal_folio', $personalFolio)
                    ->where('organization_id', $this->organizationId)
                    ->where('evaluation_type', 'likert')
                    ->where('processing_status', 'completed')
                    ->first();

                if (! $evaluation) {
                    $this->errors[] = "Fila {$rowNumber}: No se encontró evaluación Likert para el folio {$personalFolio}";
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
    protected function extractPersonalFolio(Collection $row): ?string
    {
        // Try different possible column names for personal folio
        $possibleKeys = ['folio_personal', 'folio', 'numero'];

        foreach ($possibleKeys as $key) {
            if (isset($row[$key]) && ! empty($row[$key])) {
                $value = $row[$key];
                // Pad to 4 digits if numeric
                if (is_numeric($value)) {
                    return str_pad((string) intval($value), 4, '0', STR_PAD_LEFT);
                }

                return (string) $value;
            }
        }

        return null;
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

        // Create or update the comment for this evaluation and factor
        EvaluationComment::updateOrCreate(
            [
                'paper_evaluation_id' => $evaluation->id,
                'factor' => trim($factor),
            ],
            [
                'comment' => trim($comment),
            ]
        );

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
