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
     * Normalizar un valor que puede venir como número o string desde Excel
     */
    private function normalizeToString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return trim($value);
        }

        return (string) $value;
    }

    /**
     * Parsear múltiples emails que pueden venir separados por comas, espacios o saltos de línea
     * Retorna un array de emails válidos o null si no hay emails
     */
    private function parseEmails(?string $emailString): ?array
    {
        if (empty($emailString)) {
            return null;
        }

        // Normalizar: reemplazar saltos de línea y múltiples espacios por un espacio
        $normalized = preg_replace('/[\r\n\t]+/', ' ', $emailString);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Separar por comas o espacios
        $emails = preg_split('/[,\s]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrar y validar emails
        $validEmails = [];
        foreach ($emails as $email) {
            $email = trim($email);
            // Validación básica de email
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            }
        }

        return count($validEmails) > 0 ? $validEmails : null;
    }

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

                // Obtener y normalizar campos
                $code = $this->normalizeToString($row['codigo'] ?? null);
                $name = $row['nombre'] ?? null;
                $type = $row['tipo'] ?? null;
                $rfc = $this->normalizeToString($row['rfc'] ?? null);
                $registroPatronal = $this->normalizeToString($row['registro_patronal'] ?? null);
                $codigoPostal = $this->normalizeToString($row['codigo_postal'] ?? null);
                $telefono = $this->normalizeToString($row['telefono'] ?? null);

                // Parsear emails (puede venir separado por comas, espacios, saltos de línea)
                $emails = $this->parseEmails($row['email'] ?? $row['emails'] ?? null);

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
                $validTypes = ['matriz', 'planta', 'sucursal', 'almacen', 'oficina', 'otro', 'plaza'];
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
                    'plaza' => WorkCenterType::Square,
                };

                // Si el código está vacío o es null, generar uno automáticamente
                if ($code === null || $code === '') {
                    $maxCode = $this->organization->workCenters()->max('code');
                    $nextNumber = $maxCode ? intval($maxCode) + 1 : 2; // Empezar desde 0002 (el primario es 0001)
                    $code = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
                } else {
                    // Normalizar: asegurarse de que el código tenga 4 dígitos con ceros a la izquierda
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
                        'tax_id' => $rfc ?? $existingCenter->tax_id,
                        'employer_registration' => $registroPatronal ?? $existingCenter->employer_registration,
                        'street_address' => $row['calle_numero'] ?? $existingCenter->street_address,
                        'neighborhood' => $row['colonia'] ?? $existingCenter->neighborhood,
                        'postal_code' => $codigoPostal ?? $existingCenter->postal_code,
                        'municipality' => $row['municipio'] ?? $existingCenter->municipality,
                        'state' => $row['estado'] ?? $existingCenter->state,
                        'phone' => $telefono ?? $existingCenter->phone,
                        'emails' => $emails ?? $existingCenter->emails,
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
                    'tax_id' => $rfc,
                    'employer_registration' => $registroPatronal,
                    'street_address' => $row['calle_numero'] ?? null,
                    'neighborhood' => $row['colonia'] ?? null,
                    'postal_code' => $codigoPostal,
                    'municipality' => $row['municipio'] ?? null,
                    'state' => $row['estado'] ?? null,
                    'phone' => $telefono,
                    'emails' => $emails,
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
            // Aceptar string o numeric porque Excel puede enviar números
            'codigo' => 'nullable',
            'razon_social' => 'nullable|string|max:255',
            'rfc' => 'nullable',
            'registro_patronal' => 'nullable',
            'calle_numero' => 'nullable|string|max:255',
            'colonia' => 'nullable|string|max:100',
            'codigo_postal' => 'nullable',
            'municipio' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:100',
            'telefono' => 'nullable',
            // Aceptar email o emails (validación manual en parseEmails)
            'email' => 'nullable',
            'emails' => 'nullable',
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
