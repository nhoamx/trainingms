<?php

namespace App\Imports;

use App\Models\Organization;
use App\Services\OrganizationAddressService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class OrganizationBulkUpdateImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected int $updatedOrgsCount = 0;

    protected int $updatedAddressesCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    public function __construct(
        protected Organization $organization,
        protected OrganizationAddressService $addressService
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Excel row number (0-indexed + header)

            try {
                // Normalizar valores (trim whitespace)
                $row = $row->map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                });

                // Validar que el nombre comercial coincida con la organización actual
                $nombreComercial = $row['nombre_comercial'] ?? null;

                if (empty($nombreComercial)) {
                    $this->errors[] = "Fila {$rowNumber}: El nombre comercial es requerido";
                    $this->skippedCount++;

                    continue;
                }

                if (strcasecmp($this->organization->name, $nombreComercial) !== 0) {
                    $this->errors[] = "Fila {$rowNumber}: El nombre comercial '{$nombreComercial}' no coincide con la organización '{$this->organization->name}'";
                    $this->skippedCount++;

                    continue;
                }

                // Actualizar campos básicos de la organización si hay cambios
                $orgUpdated = $this->updateOrganizationFields($row);

                // Procesar dirección fiscal (columnas 9-14)
                $fiscalAddress = $this->extractAddress($row, 'fiscal');
                $fiscalCreated = false;

                if ($fiscalAddress) {
                    // La primera dirección será primary
                    $isPrimary = ! $this->organization->addresses()->exists();
                    $result = $this->addressService->updateOrCreateAddress(
                        $this->organization,
                        'fiscal',
                        $fiscalAddress,
                        $isPrimary
                    );
                    if ($result) {
                        $fiscalCreated = true;
                        $this->updatedAddressesCount++;
                    }
                }

                // Procesar dirección física (columnas 15-20)
                $fisicaAddress = $this->extractAddress($row, 'fisica');

                if ($fisicaAddress) {
                    // Si no se creó fiscal, esta será primary
                    $isPrimary = ! $fiscalCreated && ! $this->organization->addresses()->exists();
                    $result = $this->addressService->updateOrCreateAddress(
                        $this->organization,
                        'fisica',
                        $fisicaAddress,
                        $isPrimary
                    );
                    if ($result) {
                        $this->updatedAddressesCount++;
                    }
                }

                if ($orgUpdated || $fiscalCreated || $fisicaAddress) {
                    $this->updatedOrgsCount++;
                }

            } catch (\Exception $e) {
                $this->errors[] = "Fila {$rowNumber}: {$e->getMessage()}";
                $this->skippedCount++;
            }
        }
    }

    /**
     * Actualiza los campos básicos de la organización
     */
    protected function updateOrganizationFields(Collection $row): bool
    {
        $updated = false;
        $fields = [
            'razon_social' => 'razon_social',
            'rfc' => 'rfc',
            'registro_patronal' => 'registro_patronal',
            'actividad_principal' => 'actividad_economica',
            'total_trabajadores' => 'numero_de_trabajadores',
            'total_hombres' => 'hombres',
            'total_mujeres' => 'mujeres',
        ];

        foreach ($fields as $modelField => $excelField) {
            $value = $row[$excelField] ?? null;

            // Solo actualizar si el valor no está vacío y es diferente
            if ($value !== null && $value !== '' && $value != $this->organization->$modelField) {
                $this->organization->$modelField = $value;
                $updated = true;
            }
        }

        if ($updated) {
            $this->organization->save();
        }

        return $updated;
    }

    /**
     * Extrae los datos de dirección según el tipo
     */
    protected function extractAddress(Collection $row, string $type): ?array
    {
        // Mapeo de columnas según el tipo
        $columnMap = $type === 'fiscal' ? [
            'calle' => 'calle',
            'numero' => 'numero',
            'cp' => 'cp',
            'colonia' => 'colonia_parque_industrial',
            'estado' => 'estado',
            'municipio' => 'municipio',
        ] : [
            'calle' => 'calle_2',
            'numero' => 'numero_2',
            'cp' => 'cp_2',
            'colonia' => 'colonia_parque_industrial_2',
            'estado' => 'estado_2',
            'municipio' => 'municipio_2',
        ];

        // Extraer valores
        $calle = $row[$columnMap['calle']] ?? null;
        $numero = $row[$columnMap['numero']] ?? null;
        $cp = $row[$columnMap['cp']] ?? null;
        $colonia = $row[$columnMap['colonia']] ?? null;
        $estado = $row[$columnMap['estado']] ?? null;
        $municipio = $row[$columnMap['municipio']] ?? null;

        // Verificar si todos están vacíos
        if (empty($calle) && empty($numero) && empty($cp) && empty($colonia) && empty($estado) && empty($municipio)) {
            return null;
        }

        // Concatenar calle + número
        $calleNumero = trim(($calle ?? '').' '.($numero ?? ''));

        return [
            'calle_numero' => $calleNumero ?: null,
            'colonia' => $colonia,
            'codigo_postal' => $cp,
            'municipio' => $municipio,
            'estado' => $estado,
        ];
    }

    public function rules(): array
    {
        return [
            'nombre_comercial' => 'required|string|max:255',
            'razon_social' => 'nullable|string|max:255',
            'rfc' => 'nullable|string|max:13',
            'registro_patronal' => 'nullable|string|max:50',
            'actividad_economica' => 'nullable|string|max:255',
            'numero_de_trabajadores' => 'nullable|integer|min:0',
            'hombres' => 'nullable|integer|min:0',
            'mujeres' => 'nullable|integer|min:0',
            // Dirección fiscal
            'calle' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'cp' => 'nullable|string|max:10',
            'colonia_parque_industrial' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:100',
            'municipio' => 'nullable|string|max:100',
            // Dirección física
            'calle_2' => 'nullable|string|max:255',
            'numero_2' => 'nullable|string|max:50',
            'cp_2' => 'nullable|string|max:10',
            'colonia_parque_industrial_2' => 'nullable|string|max:255',
            'estado_2' => 'nullable|string|max:100',
            'municipio_2' => 'nullable|string|max:100',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre_comercial.required' => 'El nombre comercial es requerido',
            'nombre_comercial.max' => 'El nombre comercial no debe exceder 255 caracteres',
            'rfc.max' => 'El RFC no debe exceder 13 caracteres',
        ];
    }

    public function getSummary(): array
    {
        return [
            'updated' => $this->updatedOrgsCount,
            'addresses' => $this->updatedAddressesCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }
}
