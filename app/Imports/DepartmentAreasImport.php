<?php

namespace App\Imports;

use App\Models\Organization;
use App\Services\DepartmentAreaService;
use App\Support\OmrIdentifierSequence;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DepartmentAreasImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected int $createdCount = 0;

    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    public function __construct(
        protected Organization $organization,
        protected DepartmentAreaService $departmentService
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
                $normalizedIdentifier = $identifier ? OmrIdentifierSequence::normalize($identifier) : null;
                $name = $row['nombre_del_departamento'] ?? null;

                // Validar que tengamos el nombre
                if (empty($name)) {
                    $this->errors[] = "Fila {$rowNumber}: El nombre del departamento es requerido";
                    $this->skippedCount++;

                    continue;
                }

                // Si tiene identificador, buscar el departamento existente
                if (! empty($normalizedIdentifier)) {
                    $existingArea = $this->organization->departmentAreas()
                        ->where('identifier', $normalizedIdentifier)
                        ->first();

                    if ($existingArea) {
                        // Actualizar solo si el nombre cambió
                        if ($existingArea->name !== $name) {
                            $existingArea->name = $name;
                            $existingArea->save();
                            $this->updatedCount++;
                        } else {
                            $this->skippedCount++;
                        }

                        continue;
                    }
                }

                // Si no existe o no tiene identificador, crear nuevo departamento
                $this->departmentService->createArea($this->organization, $name, $normalizedIdentifier);
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
            'nombre_del_departamento' => 'required|string|max:255',
            'identificador' => [
                'nullable',
                'string',
                'max:12',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (! OmrIdentifierSequence::isValid((string) $value)) {
                        $fail(OmrIdentifierSequence::validationMessage());
                    }
                },
            ],
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function customValidationMessages(): array
    {
        return [
            'nombre_del_departamento.required' => 'El nombre del departamento es requerido',
            'nombre_del_departamento.string' => 'El nombre del departamento debe ser texto',
            'nombre_del_departamento.max' => 'El nombre del departamento no debe exceder 255 caracteres',
            'identificador.max' => 'El identificador no debe exceder 12 caracteres',
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
