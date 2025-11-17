<?php

namespace App\Imports;

use App\Models\DemographicData;
use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class EvaluationBulkUpdateImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    /**
     * Procesar las filas del archivo Excel
     */
    public function collection(Collection $rows)
    {
        Log::info('=== BULK UPDATE IMPORT STARTED ===');
        Log::info('Total rows to process: ' . $rows->count());
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because index is 0-based and we have a header row

            try {
                Log::info("Processing row {$rowNumber}", ['raw_row' => $row->toArray()]);
                
                // Normalize the keys to lowercase and trim whitespace
                $row = $row->map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                });

                // Skip completely empty rows
                if ($row->filter()->isEmpty()) {
                    Log::info("Row {$rowNumber} is completely empty, skipping");
                    continue;
                }

                $personalFolio = $row['folio_personal'] ?? null;
                $nombre = $row['nombre'] ?? null;
                $puesto = $row['puesto'] ?? null;
                $area = $row['area'] ?? null;
                
                Log::info("Row {$rowNumber} extracted data", [
                    'personal_folio' => $personalFolio,
                    'nombre' => $nombre,
                    'puesto' => $puesto,
                    'area' => $area
                ]);

                // Validar que al menos tengamos el folio personal
                if (empty($personalFolio)) {
                    $this->errors[] = "Fila {$rowNumber}: Folio Personal es requerido";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - missing personal_folio");

                    continue;
                }

                // Get all evaluations with this personal folio
                $evaluations = PaperEvaluation::where('personal_folio', $personalFolio)
                    ->whereIn('source', ['paper', 'online'])
                    ->where('processing_status', 'completed')
                    ->with('demographicData') // Eager load DemographicData relationship
                    ->get();

                Log::info("Row {$rowNumber} found evaluations", [
                    'count' => $evaluations->count(),
                    'evaluation_types' => $evaluations->pluck('evaluation_type')->toArray()
                ]);

                if ($evaluations->isEmpty()) {
                    $this->errors[] = "Fila {$rowNumber}: No se encontraron evaluaciones para el folio {$personalFolio}";
                    $this->skippedCount++;
                    Log::warning("Row {$rowNumber} skipped - no evaluations found");

                    continue;
                }

                $updated = false;

                foreach ($evaluations as $evaluation) {
                    Log::info("Processing evaluation", [
                        'row' => $rowNumber,
                        'evaluation_type' => $evaluation->evaluation_type,
                        'has_demographic_data_model' => $evaluation->demographicData !== null
                    ]);
                    
                    // Update evaluee_name if provided
                    if (! empty($nombre) && $nombre !== $evaluation->evaluee_name) {
                        $evaluation->evaluee_name = $nombre;
                        $updated = true;
                        Log::info("Updated evaluee_name for row {$rowNumber}");
                    }

                    // Check if this evaluation has DemographicData model (for Likert evaluations)
                    if ($evaluation->demographicData) {
                        Log::info("Row {$rowNumber} has DemographicData model", [
                            'current_position' => $evaluation->demographicData->position,
                            'new_position' => $puesto,
                            'current_department' => $evaluation->demographicData->department,
                            'new_department' => $area
                        ]);
                        
                        // Update DemographicData model
                        if (! empty($puesto) && $puesto !== $evaluation->demographicData->position) {
                            $evaluation->demographicData->position = $puesto;
                            $evaluation->demographicData->save();
                            $updated = true;
                            Log::info("Updated DemographicData position for row {$rowNumber}");
                        }

                        if (! empty($area) && $area !== $evaluation->demographicData->department) {
                            $evaluation->demographicData->department = $area;
                            $evaluation->demographicData->save();
                            $updated = true;
                            Log::info("Updated DemographicData department for row {$rowNumber}");
                        }
                    }

                    // Update demographic data if it's a Referencia V evaluation
                    if ($evaluation->evaluation_type === 'referencia_v') {
                        $demographicData = $evaluation->demographic_data ?? [];

                        // Determine format (paper or online)
                        $isPaperFormat = ! isset($demographicData['datos_laborales']);

                        if ($isPaperFormat) {
                            // Paper format: update direct fields (as arrays with fila1, fila2)
                            if (! empty($puesto)) {
                                // Store as array with fila1 and fila2 (keeping existing fila2 if any)
                                $existingPuesto = $demographicData['ocupacion'] ?? [];
                                $demographicData['ocupacion'] = [
                                    'fila1' => $puesto,
                                    'fila2' => is_array($existingPuesto) ? ($existingPuesto['fila2'] ?? null) : null,
                                ];
                                $updated = true;
                            }

                            if (! empty($area)) {
                                // Store as array with fila1 and fila2 (keeping existing fila2 if any)
                                $existingArea = $demographicData['departamento'] ?? [];
                                $demographicData['departamento'] = [
                                    'fila1' => $area,
                                    'fila2' => is_array($existingArea) ? ($existingArea['fila2'] ?? null) : null,
                                ];
                                $updated = true;
                            }
                        } else {
                            // Online format: update nested datos_laborales
                            if (! isset($demographicData['datos_laborales'])) {
                                $demographicData['datos_laborales'] = [];
                            }

                            if (! empty($puesto)) {
                                $demographicData['datos_laborales']['ocupacion_puesto'] = $puesto;
                                $updated = true;
                            }

                            if (! empty($area)) {
                                $demographicData['datos_laborales']['departamento_seccion_area'] = $area;
                                $updated = true;
                            }
                        }

                        $evaluation->demographic_data = $demographicData;
                    }

                    if ($updated) {
                        $evaluation->save();
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
                    'exception' => $e->getTraceAsString()
                ]);
                $this->errors[] = "Fila {$rowNumber}: Error al procesar - {$e->getMessage()}";
                $this->skippedCount++;
            }
        }
        
        Log::info('=== BULK UPDATE IMPORT FINISHED ===', [
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => count($this->errors)
        ]);
    }

    /**
     * Reglas de validación para el archivo
     */
    public function rules(): array
    {
        return [
            'folio_personal' => 'required|string',
            'nombre' => 'nullable|string',
            'puesto' => 'nullable|string',
            'area' => 'nullable|string',
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    public function customValidationMessages(): array
    {
        return [
            'folio_personal.required' => 'El campo Folio Personal es requerido',
            'folio_personal.string' => 'El campo Folio Personal debe ser texto',
            'nombre.string' => 'El campo Nombre debe ser texto',
            'puesto.string' => 'El campo Puesto debe ser texto',
            'area.string' => 'El campo Area debe ser texto',
        ];
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
