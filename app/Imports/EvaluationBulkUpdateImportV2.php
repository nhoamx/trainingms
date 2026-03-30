<?php

namespace App\Imports;

use App\Models\EvaluationComment;
use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EvaluationBulkUpdateImportV2 extends EvaluationBulkUpdateImport
{
    /**
     * @param  string  $organizationId  UUID de la organizacion para filtrar evaluaciones
     * @param  string|null  $source  Tipo de fuente ('paper', 'online', o null para ambos)
     * @param  callable|null  $progressCallback  Optional callback to report progress (processedRows, totalRows)
     * @param  string|null  $workCenterId  UUID del centro de trabajo para filtrar evaluaciones
     * @param  string|null  $evaluationType  Tipo de evaluacion para filtrar ('likert', 'referencia_iii', etc.)
     */
    public function __construct(
        string $organizationId,
        ?string $source = null,
        ?callable $progressCallback = null,
        ?string $workCenterId = null,
        ?string $evaluationType = null,
    ) {
        parent::__construct($organizationId, $source, $progressCallback, $workCenterId, $evaluationType);

        $this->knownFields = array_merge($this->knownFields, [
            'nombre_del_evaluado' => ['type' => 'evaluee_name'],
            'contratacion' => ['type' => 'demographic', 'field' => 'contract_type'],
            'tipo_contrato' => ['type' => 'demographic', 'field' => 'contract_type'],
            'comentarios_adicionales' => ['type' => 'evaluation_comment'],
            'factor_de_comentarios' => ['type' => 'evaluation_comment_factor'],
        ]);
    }

    /**
     * Process Excel rows for the current folio process (supports 4 and 5 digits).
     */
    public function collection(Collection $rows)
    {
        Log::info('=== BULK UPDATE IMPORT V2 STARTED ===');
        Log::info('Total rows to process: '.$rows->count());
        Log::info('Organization ID: '.$this->organizationId);
        Log::info('Source filter: '.($this->source ?? 'all'));

        if ($rows->isEmpty()) {
            Log::warning('No rows to process');

            return;
        }

        $headers = $rows->first()->keys()->toArray();
        Log::info('Excel headers detected', ['headers' => $headers]);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                Log::info("Processing row {$rowNumber}", ['raw_row' => $row->toArray()]);

                $row = $row->map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                });

                if ($row->filter()->isEmpty()) {
                    Log::info("Row {$rowNumber} is completely empty, skipping");

                    continue;
                }

                $folioMatchData = $this->extractPersonalFolioMatchData($row);
                $personalFolioCandidates = $folioMatchData['candidates'];

                if (empty($personalFolioCandidates)) {
                    $this->errors[] = "Fila {$rowNumber}: Folio Personal es requerido";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - missing personal_folio");

                    continue;
                }

                Log::info("Row {$rowNumber} personal folio candidates", [
                    'raw' => $folioMatchData['raw'],
                    'candidates' => $personalFolioCandidates,
                ]);

                $query = PaperEvaluation::query()
                    ->whereIn('personal_folio', $personalFolioCandidates)
                    ->where('organization_id', $this->organizationId)
                    ->where('processing_status', 'completed')
                    ->with(['demographicData', 'customFields']);

                if ($this->workCenterId) {
                    $query->where('work_center_id', $this->workCenterId);
                }

                if ($this->evaluationType) {
                    $query->where('evaluation_type', $this->evaluationType);
                }

                if ($this->source) {
                    $query->where('source', $this->source);
                } else {
                    $query->whereIn('source', ['paper', 'online']);
                }

                $evaluations = $query->get();

                Log::info("Row {$rowNumber} found evaluations", [
                    'count' => $evaluations->count(),
                    'evaluation_types' => $evaluations->pluck('evaluation_type')->toArray(),
                ]);

                if ($evaluations->isEmpty()) {
                    $folioLabel = $folioMatchData['raw'] ?? $personalFolioCandidates[0];
                    $this->errors[] = "Fila {$rowNumber}: No se encontraron evaluaciones para el folio {$folioLabel}";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - no evaluations found", [
                        'candidates' => $personalFolioCandidates,
                    ]);

                    continue;
                }

                $updated = false;

                foreach ($evaluations as $evaluation) {
                    $evaluationUpdated = $this->processEvaluation($evaluation, $row, $headers, $rowNumber);
                    if ($evaluationUpdated) {
                        $updated = true;
                    }
                }

                if ($updated) {
                    $this->updatedCount++;
                    Log::info("Row {$rowNumber} marked as updated");
                } else {
                    $this->skippedCount++;
                    Log::info("Row {$rowNumber} skipped - no changes made");
                }

                $this->reportProgress($index + 1, $rows->count());
            } catch (\Exception $e) {
                Log::error("Error processing row {$rowNumber}: ".$e->getMessage(), [
                    'exception' => $e->getTraceAsString(),
                ]);
                $this->errors[] = "Fila {$rowNumber}: Error al procesar - {$e->getMessage()}";
                $this->skippedCount++;

                $this->reportProgress($index + 1, $rows->count());
            }
        }

        Log::info('=== BULK UPDATE IMPORT V2 FINISHED ===', [
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => count($this->errors),
        ]);
    }

    /**
     * @return array{raw: string|null, candidates: array<int, string>}
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
     * Process a single evaluation with support for EvaluationComment updates.
     */
    protected function processEvaluation(PaperEvaluation $evaluation, Collection $row, array $headers, int $rowNumber): bool
    {
        $updated = false;
        $commentValue = null;
        $factorValue = null;

        Log::info('Processing evaluation', [
            'row' => $rowNumber,
            'evaluation_type' => $evaluation->evaluation_type,
            'has_demographic_data_model' => $evaluation->demographicData !== null,
        ]);

        foreach ($row as $columnKey => $value) {
            if (empty($value) && $value !== '0') {
                continue;
            }

            $normalizedKey = $this->normalizeKey($columnKey);
            $fieldInfo = $this->knownFields[$normalizedKey] ?? null;

            if ($fieldInfo) {
                switch ($fieldInfo['type']) {
                    case 'identifier':
                    case 'skip':
                        continue 2;

                    case 'evaluee_name':
                        if ($value !== $evaluation->evaluee_name) {
                            $evaluation->evaluee_name = $value;
                            $evaluation->save();
                            $updated = true;
                            Log::info("Updated evaluee_name for row {$rowNumber}");
                        }
                        break;

                    case 'demographic':
                        $demographicUpdated = $this->updateDemographicField(
                            $evaluation,
                            $fieldInfo['field'],
                            $value,
                            $rowNumber
                        );
                        if ($demographicUpdated) {
                            $updated = true;
                        }
                        break;

                    case 'likert_answer':
                        $answerUpdated = $this->updateLikertAnswer(
                            $evaluation,
                            $fieldInfo['question'],
                            $value,
                            $rowNumber
                        );
                        if ($answerUpdated) {
                            $updated = true;
                        }
                        break;

                    case 'evaluation_comment':
                        $commentValue = (string) $value;
                        break;

                    case 'evaluation_comment_factor':
                        $factorValue = (string) $value;
                        break;
                }
            } else {
                $customFieldUpdated = $this->updateCustomField(
                    $evaluation,
                    $columnKey,
                    $value,
                    $rowNumber
                );
                if ($customFieldUpdated) {
                    $updated = true;
                }
            }
        }

        $commentUpdated = $this->updateEvaluationComments(
            $evaluation,
            $commentValue,
            $factorValue,
            $rowNumber
        );
        if ($commentUpdated) {
            $updated = true;
        }

        return $updated;
    }

    protected function updateEvaluationComments(PaperEvaluation $evaluation, ?string $commentValue, ?string $factorValue, int $rowNumber): bool
    {
        $rawComments = trim((string) $commentValue);
        $rawFactors = trim((string) $factorValue);

        if ($rawComments === '' && $rawFactors === '') {
            return false;
        }

        $updated = false;
        $comments = $this->splitCommentLines($rawComments);
        $factors = $this->splitCommentLines($rawFactors);

        if (! empty($comments) && ! empty($factors)) {
            foreach ($comments as $index => $comment) {
                $factor = $factors[$index] ?? 'General';
                if ($this->upsertEvaluationComment($evaluation, $factor, $comment)) {
                    $updated = true;
                }
            }
        } else {
            foreach ($comments as $line) {
                $factor = 'General';
                $comment = $line;

                if (str_contains($line, ':')) {
                    [$parsedFactor, $parsedComment] = explode(':', $line, 2);
                    $factor = trim($parsedFactor) !== '' ? trim($parsedFactor) : 'General';
                    $comment = trim($parsedComment);
                }

                if ($this->upsertEvaluationComment($evaluation, $factor, $comment)) {
                    $updated = true;
                }
            }
        }

        if ($updated) {
            Log::info("Updated EvaluationComment records from compact column for row {$rowNumber}", [
                'paper_evaluation_id' => $evaluation->id,
            ]);
        }

        return $updated;
    }

    /**
     * @return array<int, string>
     */
    protected function splitCommentLines(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\r\n|\r|\n/', $value) ?: [];

        return array_values(array_filter(array_map(function ($part) {
            return trim($part);
        }, $parts), function ($part) {
            return $part !== '';
        }));
    }

    protected function upsertEvaluationComment(PaperEvaluation $evaluation, string $factor, string $comment): bool
    {
        $factor = trim($factor) !== '' ? trim($factor) : 'General';
        $comment = trim($comment);

        if ($comment === '') {
            return false;
        }

        $existing = EvaluationComment::query()
            ->where('paper_evaluation_id', $evaluation->id)
            ->where('factor', $factor)
            ->first();

        if ($existing && $existing->comment === $comment) {
            return false;
        }

        EvaluationComment::updateOrCreate(
            [
                'paper_evaluation_id' => $evaluation->id,
                'factor' => $factor,
            ],
            [
                'comment' => $comment,
            ]
        );

        return true;
    }
}
