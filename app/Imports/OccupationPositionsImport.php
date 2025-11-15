<?php

namespace App\Imports;

use App\Models\Organization;
use App\Services\OccupationPositionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class OccupationPositionsImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected int $createdCount = 0;

    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    public function __construct(
        protected Organization $organization,
        protected OccupationPositionService $occupationService
    ) {}

    /**
     * Procesar las filas del archivo Excel
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 porque el índice es 0 y tenemos fila de encabezados

            try {
                // Normalizar las claves y limpiar espacios
                $row = $row->map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                });

                $identifier = $row['identificador'] ?? null;
                $name = $row['nombre_del_puesto'] ?? null;

                // Validar que tengamos el nombre
                if (empty($name)) {
                    $this->errors[] = "Fila {$rowNumber}: El nombre del puesto es requerido";
                    $this->skippedCount++;

                    continue;
                }

                // Si tiene identificador, buscar el puesto existente
                if (! empty($identifier)) {
                    $existingPosition = $this->organization->occupationPositions()
                        ->where('identifier', $identifier)
                        ->first();

                    if ($existingPosition) {
                        // Actualizar solo si el nombre cambió
                        if ($existingPosition->name !== $name) {
                            $existingPosition->name = $name;
                            $existingPosition->save();
                            $this->updatedCount++;
                        } else {
                            $this->skippedCount++;
                        }

                        continue;
                    }
                }

                // Si no existe o no tiene identificador, crear nuevo puesto
                $this->occupationService->createPosition($this->organization, $name);
                $this->createdCount++;
            } catch (\Exception $e) {
                $this->errors[] = "Fila {$rowNumber}: {$e->getMessage()}";
                $this->skippedCount++;
            }
        }
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            'nombre_del_puesto' => 'required|string|max:255',
            'identificador' => 'nullable|string|max:10',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function customValidationMessages(): array
    {
        return [
            'nombre_del_puesto.required' => 'El nombre del puesto es requerido',
            'nombre_del_puesto.string' => 'El nombre del puesto debe ser texto',
            'nombre_del_puesto.max' => 'El nombre del puesto no debe exceder 255 caracteres',
        ];
    }

    /**
     * Obtener el resumen de la importación
     */
    public function getSummary(): array
    {
        return [
            'created' => $this->createdCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }
}
