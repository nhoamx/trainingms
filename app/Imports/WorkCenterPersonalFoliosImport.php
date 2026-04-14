<?php

namespace App\Imports;

use App\Models\DemographicData;
use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WorkCenterPersonalFoliosImport implements ToCollection, WithHeadingRow
{
    protected const DEMOGRAPHIC_COLUMN_ALIASES = [
        'gender' => ['genero', 'género', 'sexo', 'gender'],
        'age' => ['edad', 'age'],
        'marital_status' => ['estado_civil', 'marital_status'],
        'education_level' => ['nivel_de_estudios', 'nivel_estudios', 'education_level'],
        'position' => ['puesto', 'ocupacion', 'ocupacion_puesto', 'position'],
        'department' => ['departamento', 'departamento_seccion_area', 'area', 'department'],
        'position_type' => ['tipo_de_puesto', 'tipo_puesto', 'position_type'],
        'contract_type' => ['tipo_de_contratacion', 'tipo_contratacion', 'contract_type'],
        'personnel_type' => ['tipo_de_personal', 'tipo_personal', 'personnel_type'],
        'work_schedule' => ['jornada', 'tipo_jornada', 'work_schedule'],
        'shift_rotation' => ['rotacion_de_turnos', 'rotacion_turnos', 'shift_rotation'],
        'time_in_current_position' => ['tiempo_en_puesto_actual', 'tiempo_puesto_actual', 'time_in_current_position'],
        'work_experience' => ['experiencia_laboral', 'tiempo_experiencia_laboral', 'work_experience'],
    ];

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
            $demographicColumns = $this->extractDemographicColumns($row);
            $normalizedSource = $this->normalizeSource($sourceRaw);

            if ($this->isRowEmpty($workCenterId, $folioRaw, $sourceRaw, $name, $demographicColumns)) {
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

            if (! $hasNameColumn && $demographicColumns === []) {
                $this->skippedCount++;
                $this->errors[] = "Fila {$rowNumber}: Debe existir al menos una columna editable (Nombre o datos demograficos)";

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
                $evaluationUpdated = false;

                if ($hasNameColumn && $evaluation->evaluee_name !== $name) {
                    $evaluation->evaluee_name = $name;
                    $evaluationUpdated = true;
                }

                if ($this->updateDemographicFields($evaluation, $demographicColumns)) {
                    $evaluationUpdated = true;
                }

                if ($evaluationUpdated && $evaluation->isDirty()) {
                    $evaluation->save();
                }

                if ($evaluationUpdated) {
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

    protected function isRowEmpty(?string $workCenterId, ?string $folioRaw, ?string $sourceRaw, ?string $name, array $demographicColumns): bool
    {
        return $workCenterId === null
            && $folioRaw === null
            && $sourceRaw === null
            && $name === null
            && $demographicColumns === [];
    }

    protected function extractDemographicColumns(Collection $row): array
    {
        $demographicValues = [];

        foreach (self::DEMOGRAPHIC_COLUMN_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (! $row->has($alias)) {
                    continue;
                }

                $raw = $row->get($alias);
                if ($raw === null) {
                    $demographicValues[$field] = null;

                    break;
                }

                $value = is_string($raw) ? trim($raw) : trim((string) $raw);
                $demographicValues[$field] = $value === '' ? null : $value;

                break;
            }
        }

        return $demographicValues;
    }

    protected function updateDemographicFields(PaperEvaluation $evaluation, array $demographicColumns): bool
    {
        if ($demographicColumns === []) {
            return false;
        }

        $demographicData = $evaluation->demographicData;
        $demographicUpdated = false;

        foreach ($demographicColumns as $field => $value) {
            if ($demographicData === null) {
                $demographicData = new DemographicData([
                    'paper_evaluation_id' => $evaluation->id,
                ]);
            }

            if ($demographicData->{$field} !== $value) {
                $demographicData->{$field} = $value;
                $demographicUpdated = true;
            }
        }

        if ($demographicUpdated) {
            $demographicData->paper_evaluation_id = $evaluation->id;
            $demographicData->save();
            $evaluation->setRelation('demographicData', $demographicData);
            $this->syncEvaluationDemographicJson($evaluation, $demographicColumns);
        }

        return $demographicUpdated;
    }

    protected function syncEvaluationDemographicJson(PaperEvaluation $evaluation, array $demographicColumns): void
    {
        $demographicData = $evaluation->demographic_data;
        if (! is_array($demographicData)) {
            if ($evaluation->evaluation_type !== 'referencia_v') {
                return;
            }

            $demographicData = [];
        }

        $usesNestedStructure = isset($demographicData['datos_laborales']) && is_array($demographicData['datos_laborales']);

        foreach ($demographicColumns as $field => $value) {
            if ($usesNestedStructure) {
                $this->syncNestedDemographicField($demographicData, $field, $value);

                continue;
            }

            $this->syncFlatDemographicField($demographicData, $field, $value);
        }

        $evaluation->demographic_data = $demographicData;
    }

    protected function syncNestedDemographicField(array &$demographicData, string $field, ?string $value): void
    {
        $topLevelFieldMap = [
            'gender' => 'sexo',
            'age' => 'edad',
            'marital_status' => 'estado_civil',
            'education_level' => 'nivel_estudios',
        ];

        $laborFieldMap = [
            'position' => 'ocupacion_puesto',
            'department' => 'departamento_seccion_area',
            'position_type' => 'tipo_puesto',
            'contract_type' => 'tipo_contratacion',
            'personnel_type' => 'tipo_personal',
            'work_schedule' => 'tipo_jornada',
            'shift_rotation' => 'rotacion_turnos',
        ];

        if (isset($topLevelFieldMap[$field])) {
            $demographicData[$topLevelFieldMap[$field]] = $value;

            return;
        }

        if (isset($laborFieldMap[$field])) {
            $demographicData['datos_laborales'] ??= [];
            $demographicData['datos_laborales'][$laborFieldMap[$field]] = $value;

            return;
        }

        if (in_array($field, ['time_in_current_position', 'work_experience'], true)) {
            $demographicData['datos_laborales'] ??= [];
            $demographicData['datos_laborales']['experiencia'] ??= [];
            $demographicData['datos_laborales']['experiencia'][$field === 'time_in_current_position' ? 'tiempo_puesto_actual' : 'tiempo_experiencia_laboral'] = $value;
        }
    }

    protected function syncFlatDemographicField(array &$demographicData, string $field, ?string $value): void
    {
        $flatFieldMap = [
            'gender' => 'gender',
            'age' => 'age',
            'marital_status' => 'marital_status',
            'education_level' => 'education_level',
            'position_type' => 'position_type',
            'contract_type' => 'contract_type',
            'personnel_type' => 'personnel_type',
            'work_schedule' => 'work_schedule',
            'shift_rotation' => 'shift_rotation',
            'time_in_current_position' => 'time_in_current_position',
            'work_experience' => 'work_experience',
        ];

        if ($field === 'position' || $field === 'department') {
            $jsonField = $field === 'position' ? 'ocupacion' : 'departamento';
            $existing = $demographicData[$jsonField] ?? [];
            $demographicData[$jsonField] = [
                'fila1' => $value,
                'fila2' => is_array($existing) ? ($existing['fila2'] ?? null) : null,
            ];

            return;
        }

        if (isset($flatFieldMap[$field])) {
            $demographicData[$flatFieldMap[$field]] = $value;
        }
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
