<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetsImport implements ToCollection, WithHeadingRow
{
    protected int $createdCount = 0;

    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    protected $progressCallback = null;

    protected Organization $organization;

    public function __construct(Organization $organization, ?callable $progressCallback = null)
    {
        $this->organization = $organization;
        $this->progressCallback = $progressCallback;
    }

    public function collection(Collection $rows)
    {
        $totalRows = $rows->count();
        $processedRows = 0;

        foreach ($rows as $index => $row) {
            $processedRows++;
            $rowNumber = $index + 2; // +2 because Excel is 1-indexed and has header row

            try {
                // Normalizar las claves del row a snake_case
                $normalizedRow = collect($row)->mapWithKeys(function ($value, $key) {
                    return [Str::snake($key) => $value];
                })->toArray();

                // Log para debug
                Log::debug("Procesando fila {$rowNumber}", [
                    'keys' => array_keys($normalizedRow),
                    'values' => $normalizedRow,
                ]);

                // Validar datos requeridos
                $consecutiveNumber = $this->normalizeValue($normalizedRow['numero_consecutivo'] ?? null);
                $serialNumber = $this->normalizeValue($normalizedRow['numero_de_serie'] ?? null);

                Log::debug("Valores normalizados fila {$rowNumber}", [
                    'consecutive_number' => $consecutiveNumber,
                    'serial_number' => $serialNumber,
                ]);

                if (empty($consecutiveNumber)) {
                    $error = "Fila {$rowNumber}: Número consecutivo es requerido";
                    $this->errors[] = $error;
                    $this->skippedCount++;
                    Log::warning($error, ['row' => $normalizedRow]);
                    $this->reportProgress($processedRows, $totalRows);

                    continue;
                }

                // Preparar datos del asset
                $assetData = [
                    'organization_id' => $this->organization->id,
                    'asset_category' => 'extintor',
                    'consecutive_number' => $consecutiveNumber,
                    'serial_number' => $serialNumber,
                    'location' => $this->normalizeValue($normalizedRow['ubicacion'] ?? null),
                    'capacity' => $this->normalizeValue($normalizedRow['capacidad'] ?? null),
                    'asset_type' => $this->normalizeValue($normalizedRow['tipo_de_extintor'] ?? null),
                    'fire_class' => $this->normalizeValue($normalizedRow['clase_de_fuego'] ?? null),
                ];

                // Buscar asset existente por consecutive_number
                $existingAsset = Asset::where('organization_id', $this->organization->id)
                    ->where('consecutive_number', $consecutiveNumber)
                    ->first();

                if ($existingAsset) {
                    // Actualizar asset existente
                    $existingAsset->update($assetData);
                    $this->updatedCount++;
                    Log::info("Asset actualizado: {$consecutiveNumber}", ['row' => $rowNumber]);
                } else {
                    // Crear nuevo asset
                    Asset::create($assetData);
                    $this->createdCount++;
                    Log::info("Asset creado: {$consecutiveNumber}", ['row' => $rowNumber]);
                }
            } catch (\Exception $e) {
                $this->errors[] = "Fila {$rowNumber}: {$e->getMessage()}";
                $this->skippedCount++;
                Log::error("Error procesando asset en fila {$rowNumber}", [
                    'error' => $e->getMessage(),
                    'row' => $row->toArray(),
                ]);
            }

            $this->reportProgress($processedRows, $totalRows);
        }
    }

    protected function normalizeValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    protected function reportProgress(int $processedRows, int $totalRows): void
    {
        if ($this->progressCallback) {
            call_user_func(
                $this->progressCallback,
                $processedRows,
                $totalRows,
                $this->createdCount,
                $this->updatedCount,
                $this->skippedCount
            );
        }
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
