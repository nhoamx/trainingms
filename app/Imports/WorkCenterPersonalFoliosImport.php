<?php

namespace App\Imports;

use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WorkCenterPersonalFoliosImport implements ToCollection, WithHeadingRow
{
    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected array $errors = [];

    /** @var callable|null */
    protected $progressCallback = null;

    public function __construct(
        private readonly string $organizationId,
        private readonly ?string $workCenterId = null,
        ?callable $progressCallback = null,
    ) {
        $this->progressCallback = $progressCallback;
    }

    public function collection(Collection $rows): void
    {
        $totalRows = $rows->count();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $workCenterId = $this->stringValue($row, ['id_centro_de_trabajo', 'id_centro', 'work_center_id']);
            $folioRaw = $this->stringValue($row, ['folio_personal', 'folio']);
            $sourceRaw = $this->stringValue($row, ['source', 'fuente', 'origen']);
            $hasNameColumn = $this->hasAnyColumn($row, ['nombre', 'nombre_del_evaluado']);
            $name = $this->stringValue($row, ['nombre', 'nombre_del_evaluado'], allowEmpty: true);
            $normalizedSource = $this->normalizeSource($sourceRaw);

            if ($this->isRowEmpty($workCenterId, $folioRaw, $sourceRaw, $name)) {
                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            if ($workCenterId === null) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: ID Centro de trabajo es requerido";

                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            if ($folioRaw === null) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: Folio Personal es requerido";

                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            if ($sourceRaw === null || $normalizedSource === null) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: Source debe ser paper u online";

                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            if (! $hasNameColumn) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: Nombre es requerido";

                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            $candidates = $this->buildFolioCandidates($folioRaw);

            if (empty($candidates)) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: Folio Personal inválido";

                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            $evaluations = PaperEvaluation::query()
                ->where('organization_id', $this->organizationId)
                ->where('work_center_id', $workCenterId)
                ->where('source', $normalizedSource)
                ->where('processing_status', 'completed')
                ->whereIn('personal_folio', $candidates)
                ->get();

            if ($this->workCenterId !== null && $workCenterId !== $this->workCenterId) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: El centro de trabajo no coincide con el filtro seleccionado";

                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            if ($evaluations->isEmpty()) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: No se encontraron evaluaciones para centro/folio";

                $this->reportProgress($index + 1, $totalRows);

                continue;
            }

            $rowUpdated = false;
            foreach ($evaluations as $evaluation) {
                if ($evaluation->evaluee_name !== $name) {
                    $evaluation->evaluee_name = $name;
                    $evaluation->save();
                    $rowUpdated = true;
                }
            }

            if ($rowUpdated) {
                $this->updatedCount++;
            } else {
                $this->skippedCount++;
            }

            $this->reportProgress($index + 1, $totalRows);
        }
    }

    protected function reportProgress(int $processedRows, int $totalRows): void
    {
        if ($this->progressCallback === null) {
            return;
        }

        call_user_func($this->progressCallback, $processedRows, $totalRows, $this->updatedCount, $this->skippedCount);
    }

    protected function isRowEmpty(?string $workCenterId, ?string $folioRaw, ?string $sourceRaw, ?string $name): bool
    {
        return $workCenterId === null && $folioRaw === null && $sourceRaw === null && $name === null;
    }

    protected function normalizeSource(?string $source): ?string
    {
        if ($source === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($source));

        return match ($normalized) {
            'paper', 'presencial' => 'paper',
            'online', 'en_linea', 'en linea', 'en línea' => 'online',
            default => null,
        };
    }

    protected function stringValue(Collection $row, array $keys, bool $allowEmpty = false): ?string
    {
        foreach ($keys as $key) {
            if (! $row->has($key)) {
                continue;
            }

            $raw = $row->get($key);
            if ($raw === null) {
                return $allowEmpty ? '' : null;
            }

            $value = is_string($raw) ? trim($raw) : trim((string) $raw);

            if ($value === '') {
                return $allowEmpty ? '' : null;
            }

            return $value;
        }

        return null;
    }

    protected function hasAnyColumn(Collection $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if ($row->has($key)) {
                return true;
            }
        }

        return false;
    }

    protected function buildFolioCandidates(string $rawValue): array
    {
        $trimmed = trim($rawValue);
        if ($trimmed === '') {
            return [];
        }

        $candidates = [$trimmed];
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

        return array_values(array_unique(array_filter($candidates, fn ($candidate) => $candidate !== '')));
    }

    public function getSummary(): array
    {
        return [
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }
}
