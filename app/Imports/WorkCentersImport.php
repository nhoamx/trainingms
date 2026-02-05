<?php

namespace App\Imports;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WorkCentersImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected int $createdCount = 0;

    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    public function __construct(protected Organization $organization) {}

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

                $code = $row['codigo'] ?? null;
                $name = $row['nombre'] ?? null;
                $type = $row['tipo'] ?? null;

                // Validar que tengamos los campos requeridos
                if (empty($name)) {
                    $this->errors[] = "Fila {$rowNumber}: El nombre del centro de trabajo es requerido";
                    $this->skippedCount++;

                    continue;
                }

                if (empty($type)) {
                    $this->errors[] = "Fila {$rowNumber}: El tipo de centro de trabajo es requerido";
                    $this->skippedCount++;

                    continue;
                }

                // Validar que el tipo sea válido
                $validTypes = ['matriz', 'planta', 'sucursal', 'almacen', 'oficina', 'otro'];
                $typeNormalized = strtolower($type);

                if (! in_array($typeNormalized, $validTypes)) {
                    $this->errors[] = "Fila {$rowNumber}: Tipo inválido '{$type}'. Tipos válidos: ".implode(', ', $validTypes);
                    $this->skippedCount++;

                    continue;
                }

                // Mapear el tipo español al enum
                $typeEnum = match ($typeNormalized) {
                    'matriz' => WorkCenterType::Headquarters,
                    'planta' => WorkCenterType::Plant,
                    'sucursal' => WorkCenterType::Branch,
                    'almacen' => WorkCenterType::Warehouse,
                    'oficina' => WorkCenterType::Office,
                    'otro' => WorkCenterType::Other,
                };

                // Si el código está vacío, generar uno automáticamente
                if (empty($code)) {
                    $maxCode = $this->organization->workCenters()->max('code');
                    $nextNumber = $maxCode ? intval($maxCode) + 1 : 2; // Empezar desde 0002 (el primario es 0001)
                    $code = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                } else {
                    // Asegurarse de que el código tenga 4 dígitos
                    $code = str_pad($code, 4, '0', STR_PAD_LEFT);
                }

                // Si tiene código, buscar el centro existente (pero no permitir actualizar el primario)
                $existingCenter = $this->organization->workCenters()
                    ->where('code', $code)
                    ->first();

                if ($existingCenter) {
                    // No permitir actualizar el centro primario
                    if ($existingCenter->is_primary) {
                        $this->errors[] = "Fila {$rowNumber}: No se puede actualizar el centro primario (código {$code})";
                        $this->skippedCount++;

                        continue;
                    }

                    // Actualizar el centro existente
                    $existingCenter->update([
                        'name' => $name,
                        'type' => $typeEnum,
                        'legal_name' => $row['razon_social'] ?? $existingCenter->legal_name,
                        'tax_id' => $row['rfc'] ?? $existingCenter->tax_id,
                        'employer_registration' => $row['registro_patronal'] ?? $existingCenter->employer_registration,
                        'street_address' => $row['calle_numero'] ?? $existingCenter->street_address,
                        'neighborhood' => $row['colonia'] ?? $existingCenter->neighborhood,
                        'postal_code' => $row['codigo_postal'] ?? $existingCenter->postal_code,
                        'municipality' => $row['municipio'] ?? $existingCenter->municipality,
                        'state' => $row['estado'] ?? $existingCenter->state,
                        'phone' => $row['telefono'] ?? $existingCenter->phone,
                        'email' => $row['email'] ?? $existingCenter->email,
                        'notes' => $row['notas'] ?? $existingCenter->notes,
                    ]);

                    $this->updatedCount++;

                    continue;
                }

                // Verificar que el código no esté duplicado
                $duplicateCheck = $this->organization->workCenters()
                    ->where('code', $code)
                    ->exists();

                if ($duplicateCheck) {
                    $this->errors[] = "Fila {$rowNumber}: Ya existe un centro con el código {$code}";
                    $this->skippedCount++;

                    continue;
                }

                // Crear nuevo centro de trabajo
                WorkCenter::create([
                    'organization_id' => $this->organization->id,
                    'code' => $code,
                    'name' => $name,
                    'type' => $typeEnum,
                    'is_primary' => false,
                    'legal_name' => $row['razon_social'] ?? null,
                    'tax_id' => $row['rfc'] ?? null,
                    'employer_registration' => $row['registro_patronal'] ?? null,
                    'street_address' => $row['calle_numero'] ?? null,
                    'neighborhood' => $row['colonia'] ?? null,
                    'postal_code' => $row['codigo_postal'] ?? null,
                    'municipality' => $row['municipio'] ?? null,
                    'state' => $row['estado'] ?? null,
                    'phone' => $row['telefono'] ?? null,
                    'email' => $row['email'] ?? null,
                    'notes' => $row['notas'] ?? null,
                ]);

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
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|string',
            'codigo' => 'nullable|string|max:4',
            'razon_social' => 'nullable|string|max:255',
            'rfc' => 'nullable|string|max:13',
            'registro_patronal' => 'nullable|string|max:20',
            'calle_numero' => 'nullable|string|max:255',
            'colonia' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable|string|max:10',
            'municipio' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:100',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notas' => 'nullable|string',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function customValidationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre del centro de trabajo es requerido',
            'nombre.string' => 'El nombre debe ser texto',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'tipo.required' => 'El tipo de centro de trabajo es requerido',
            'tipo.string' => 'El tipo debe ser texto',
            'email.email' => 'El email debe tener un formato válido',
            'rfc.max' => 'El RFC no puede exceder 13 caracteres',
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
