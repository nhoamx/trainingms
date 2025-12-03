<?php

namespace App\Imports;

use App\Models\DemographicData;
use App\Models\EvaluationCustomField;
use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EvaluationBulkUpdateImport implements ToCollection, WithHeadingRow
{
    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    protected int $organizationId;

    protected ?string $source;

    /**
     * Known fields that map to existing database columns.
     * key = possible Excel header (snake_case), value = mapping info
     */
    protected array $knownFields = [
        // Personal folio identifiers
        'folio_personal' => ['type' => 'identifier'],
        'folio' => ['type' => 'identifier'],
        'numero' => ['type' => 'identifier'],

        // Name field
        'nombre' => ['type' => 'evaluee_name'],
        'nombre_completo' => ['type' => 'evaluee_name'],

        // Demographic fields (stored in DemographicData model or demographic_data JSON)
        'puesto' => ['type' => 'demographic', 'field' => 'position'],
        'departamento' => ['type' => 'demographic', 'field' => 'department'],
        'area' => ['type' => 'demographic', 'field' => 'department'],
        'edad' => ['type' => 'demographic', 'field' => 'age'],
        'genero' => ['type' => 'demographic', 'field' => 'gender'],
        'turno' => ['type' => 'demographic', 'field' => 'work_schedule'],
        'tipo_de_empleado' => ['type' => 'demographic', 'field' => 'contract_type'],

        // Skip these columns (metadata, not data)
        'no' => ['type' => 'skip'],
    ];

    /**
     * @param  int  $organizationId  ID de la organización para filtrar evaluaciones
     * @param  string|null  $source  Tipo de fuente ('paper', 'online', o null para ambos)
     */
    public function __construct(int $organizationId, ?string $source = null)
    {
        $this->organizationId = $organizationId;
        $this->source = $source;
    }

    /**
     * Procesar las filas del archivo Excel
     */
    public function collection(Collection $rows)
    {
        Log::info('=== BULK UPDATE IMPORT STARTED ===');
        Log::info('Total rows to process: '.$rows->count());
        Log::info('Organization ID: '.$this->organizationId);
        Log::info('Source filter: '.($this->source ?? 'all'));

        // Get headers from first row to identify custom fields
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

                // Find personal folio from various possible column names
                $personalFolio = $this->extractPersonalFolio($row);

                // Validar que al menos tengamos el folio personal
                if (empty($personalFolio)) {
                    $this->errors[] = "Fila {$rowNumber}: Folio Personal es requerido";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - missing personal_folio");

                    continue;
                }

                Log::info("Row {$rowNumber} personal folio: {$personalFolio}");

                // Get all evaluations with this personal folio for this organization
                $query = PaperEvaluation::where('personal_folio', $personalFolio)
                    ->where('organization_id', $this->organizationId)
                    ->where('processing_status', 'completed')
                    ->with(['demographicData', 'customFields']);

                // Filter by source if specified
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
                    $this->errors[] = "Fila {$rowNumber}: No se encontraron evaluaciones para el folio {$personalFolio}";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - no evaluations found");

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
            } catch (\Exception $e) {
                Log::error("Error processing row {$rowNumber}: ".$e->getMessage(), [
                    'exception' => $e->getTraceAsString(),
                ]);
                $this->errors[] = "Fila {$rowNumber}: Error al procesar - {$e->getMessage()}";
                $this->skippedCount++;
            }
        }

        Log::info('=== BULK UPDATE IMPORT FINISHED ===', [
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
     * Process a single evaluation with the row data
     */
    protected function processEvaluation(PaperEvaluation $evaluation, Collection $row, array $headers, int $rowNumber): bool
    {
        $updated = false;

        Log::info('Processing evaluation', [
            'row' => $rowNumber,
            'evaluation_type' => $evaluation->evaluation_type,
            'has_demographic_data_model' => $evaluation->demographicData !== null,
        ]);

        // Process each column in the row
        foreach ($row as $columnKey => $value) {
            if (empty($value) && $value !== '0') {
                continue;
            }

            $normalizedKey = $this->normalizeKey($columnKey);
            $fieldInfo = $this->knownFields[$normalizedKey] ?? null;

            if ($fieldInfo) {
                // Handle known fields
                switch ($fieldInfo['type']) {
                    case 'identifier':
                    case 'skip':
                        // Skip identifiers and metadata columns
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
                }
            } else {
                // Unknown field - treat as custom field
                $customFieldUpdated = $this->updateCustomField(
                    $evaluation,
                    $columnKey, // Original header label
                    $value,
                    $rowNumber
                );
                if ($customFieldUpdated) {
                    $updated = true;
                }
            }
        }

        return $updated;
    }

    /**
     * Normalize a column key to snake_case for comparison
     */
    protected function normalizeKey(string $key): string
    {
        return EvaluationCustomField::labelToKey($key);
    }

    /**
     * Update a demographic field
     */
    protected function updateDemographicField(PaperEvaluation $evaluation, string $field, $value, int $rowNumber): bool
    {
        $updated = false;

        // Handle DemographicData model (for Likert evaluations)
        if ($evaluation->demographicData) {
            $currentValue = $evaluation->demographicData->{$field} ?? null;

            if ($value !== $currentValue) {
                $evaluation->demographicData->{$field} = $value;
                $evaluation->demographicData->save();
                $updated = true;
                Log::info("Updated DemographicData.{$field} for row {$rowNumber}");
            }
        }

        // Handle referencia_v JSON demographic_data
        if ($evaluation->evaluation_type === 'referencia_v') {
            $demographicData = $evaluation->demographic_data ?? [];
            $isPaperFormat = ! isset($demographicData['datos_laborales']);

            $fieldMap = [
                'position' => $isPaperFormat ? 'ocupacion' : 'ocupacion_puesto',
                'department' => $isPaperFormat ? 'departamento' : 'departamento_seccion_area',
            ];

            if (isset($fieldMap[$field])) {
                $jsonField = $fieldMap[$field];

                if ($isPaperFormat) {
                    $existing = $demographicData[$jsonField] ?? [];
                    $demographicData[$jsonField] = [
                        'fila1' => $value,
                        'fila2' => is_array($existing) ? ($existing['fila2'] ?? null) : null,
                    ];
                } else {
                    if (! isset($demographicData['datos_laborales'])) {
                        $demographicData['datos_laborales'] = [];
                    }
                    $demographicData['datos_laborales'][$jsonField] = $value;
                }

                $evaluation->demographic_data = $demographicData;
                $evaluation->save();
                $updated = true;
                Log::info("Updated demographic_data.{$jsonField} for row {$rowNumber}");
            }
        }

        return $updated;
    }

    /**
     * Update or create a custom field
     */
    protected function updateCustomField(PaperEvaluation $evaluation, string $originalLabel, $value, int $rowNumber): bool
    {
        $key = EvaluationCustomField::labelToKey($originalLabel);

        // Check if this custom field already exists with the same value
        $existingField = $evaluation->customFields()->where('key', $key)->first();

        if ($existingField && $existingField->value === (string) $value) {
            return false; // No change needed
        }

        // Update or create the custom field
        $evaluation->setCustomField($key, $originalLabel, (string) $value);

        Log::info("Updated/created custom field '{$key}' for row {$rowNumber}", [
            'key' => $key,
            'label' => $originalLabel,
            'value' => $value,
        ]);

        return true;
    }

    /**
     * Obtener la cantidad de registros actualizados
     */
    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    /**
     * Obtener la cantidad de registros omitidos
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Obtener los errores encontrados
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
