<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Support\Collection;

class WorkCenterOrganizationReportService
{
    /**
     * @var array<int, string>
     */
    private const ATS_KEYS = ['1', '2', '3', '4', '5', '6'];

    /**
     * @return Collection<int, array{id: string, code: string, name: string, is_primary: bool, evaluations: array<int, array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>}>
     */
    public function getOrganizationWorkCenters(Organization $organization): Collection
    {
        $workCenters = $this->getWorkCenters($organization);
        $evaluationsByWorkCenter = $this->getEvaluationsByWorkCenter($organization, $workCenters);

        return $workCenters
            ->map(fn (WorkCenter $workCenter): array => $this->mapWorkCenter($workCenter, $evaluationsByWorkCenter))
            ->values();
    }

    /**
     * Parallel normalized implementation for comparison/migration.
     *
     * Returns the same output shape as getOrganizationWorkCenters(), but builds
     * answer maps from evaluation_answers instead of raw_data JSON columns.
     *
     * @return Collection<int, array{id: string, code: string, name: string, is_primary: bool, evaluations: array<int, array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>}>
     */
    public function getOrganizationWorkCentersFromNormalizedAnswers(Organization $organization): Collection
    {
        $workCenters = $this->getWorkCenters($organization);
        $evaluationsByWorkCenter = $this->getNormalizedEvaluationsByWorkCenter($organization, $workCenters);

        return $workCenters
            ->map(fn (WorkCenter $workCenter): array => $this->mapWorkCenter($workCenter, $evaluationsByWorkCenter))
            ->values();
    }

    /**
     * @return Collection<int, WorkCenter>
     */
    private function getWorkCenters(Organization $organization): Collection
    {
        return $organization->workCenters()
            ->select(['id', 'code', 'name', 'is_primary'])
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, WorkCenter>  $workCenters
     * @return Collection<string, array<int, array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>>
     */
    private function getEvaluationsByWorkCenter(Organization $organization, Collection $workCenters): Collection
    {
        return PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->whereIn('work_center_id', $workCenters->pluck('id'))
            ->select([
                'id',
                'work_center_id',
                'folio',
                'personal_folio',
                'evaluee_name',
                'source',
                'raw_data',
                'demographic_data',
                'citsats_s1',
            ])
            ->with([
                'evaluationAnswers' => function ($query) {
                    $query->select(['paper_evaluation_id', 'instrument', 'question_key', 'answer_value']);
                },
            ])
            ->orderBy('folio')
            ->get()
            ->groupBy('work_center_id')
            ->map(function (Collection $evaluations): array {
                return $evaluations
                    ->map(fn (PaperEvaluation $evaluation): array => $this->mapEvaluation($evaluation))
                    ->values()
                    ->all();
            });
    }

    /**
     * @param  Collection<int, WorkCenter>  $workCenters
     * @return Collection<string, array<int, array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>>
     */
    private function getNormalizedEvaluationsByWorkCenter(Organization $organization, Collection $workCenters): Collection
    {
        return PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->whereIn('work_center_id', $workCenters->pluck('id'))
            ->select([
                'id',
                'work_center_id',
                'folio',
                'personal_folio',
                'evaluee_name',
                'source',
                'raw_data',
                'demographic_data',
                'citsats_s1',
                'evaluation_type',
            ])
            ->with([
                'evaluationAnswers' => function ($query) {
                    $query->select(['paper_evaluation_id', 'instrument', 'question_key', 'answer_value']);
                },
            ])
            ->orderBy('folio')
            ->get()
            ->groupBy('work_center_id')
            ->map(function (Collection $evaluations): array {
                return $evaluations
                    ->map(fn (PaperEvaluation $evaluation): array => $this->mapNormalizedEvaluation($evaluation))
                    ->values()
                    ->all();
            });
    }

    /**
     * @param  Collection<string, array<int, array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>>  $evaluationsByWorkCenter
     * @return array{id: string, code: string, name: string, is_primary: bool, evaluations: array<int, array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>}
     */
    private function mapWorkCenter(WorkCenter $workCenter, Collection $evaluationsByWorkCenter): array
    {
        return [
            'id' => (string) $workCenter->id,
            'code' => (string) $workCenter->code,
            'name' => (string) $workCenter->name,
            'is_primary' => (bool) $workCenter->is_primary,
            'evaluations' => $evaluationsByWorkCenter->get($workCenter->id, []),
        ];
    }

    /**
     * @return array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_iii_condition_cs: mixed, referencia_iii_condition_mgmt: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}
     */
    private function mapEvaluation(PaperEvaluation $evaluation): array
    {
        $answersByInstrument = $this->indexAnswersByInstrument($evaluation);
        $rawData = is_array($evaluation->raw_data) ? $evaluation->raw_data : [];

        $referenciaIiiAnswers = $answersByInstrument['referencia_iii'] ?? [];
        $referenciaIii = $this->mapReferenciaIii($answersByInstrument['referencia_iii'] ?? []);
        $referenciaI = $this->normalizeAnswerMap($answersByInstrument['referencia_i'] ?? []);
        $referenciaV = $this->extractReferenciaV($rawData, $evaluation);

        $atsFromNormalized = [];
        foreach (self::ATS_KEYS as $key) {
            if (array_key_exists($key, $referenciaI) && $referenciaI[$key] !== null && $referenciaI[$key] !== '') {
                $atsFromNormalized[$key] = $referenciaI[$key];
            }
        }

        $acontecimientosTraumaticos = ! empty($atsFromNormalized)
            ? $this->normalizeAnswerMap($atsFromNormalized)
            : (is_array($evaluation->citsats_s1) ? $this->normalizeAnswerMap($evaluation->citsats_s1) : []);

        return [
            'folio' => (string) $evaluation->folio,
            'personal_folio' => (string) ($evaluation->personal_folio ?? ''),
            'evaluee_name' => (string) ($evaluation->evaluee_name ?? ''),
            'source' => (string) $evaluation->source,
            'referencia_iii' => $referenciaIii,
            'referencia_iii_condition_cs' => $this->normalizeAnswerDisplayValue($referenciaIiiAnswers['condition_cs'] ?? ''),
            'referencia_iii_condition_mgmt' => $this->normalizeAnswerDisplayValue($referenciaIiiAnswers['condition_mgmt'] ?? ''),
            'referencia_i_acontecimientos_traumaticos' => $acontecimientosTraumaticos,
            'referencia_i' => $referenciaI,
            'referencia_v' => $this->mapReferenciaV($referenciaV),
        ];
    }

    /**
     * @return array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_iii_condition_cs: mixed, referencia_iii_condition_mgmt: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}
     */
    private function mapNormalizedEvaluation(PaperEvaluation $evaluation): array
    {
        $answersByInstrument = $this->indexAnswersByInstrument($evaluation);

        $referenciaIiiAnswers = $answersByInstrument['referencia_iii'] ?? [];
        $referenciaIii = $this->mapReferenciaIii($answersByInstrument['referencia_iii'] ?? []);
        $referenciaI = $this->normalizeAnswerMap($answersByInstrument['referencia_i'] ?? []);

        $atsFromNormalized = [];
        foreach (self::ATS_KEYS as $key) {
            if (array_key_exists($key, $referenciaI) && $referenciaI[$key] !== null && $referenciaI[$key] !== '') {
                $atsFromNormalized[$key] = $referenciaI[$key];
            }
        }

        $ats = ! empty($atsFromNormalized)
            ? $this->normalizeAnswerMap($atsFromNormalized)
            : (is_array($evaluation->citsats_s1) ? $this->normalizeAnswerMap($evaluation->citsats_s1) : []);

        $rawData = is_array($evaluation->raw_data) ? $evaluation->raw_data : [];
        $referenciaV = $this->extractReferenciaV($rawData, $evaluation);

        return [
            'folio' => (string) $evaluation->folio,
            'personal_folio' => (string) ($evaluation->personal_folio ?? ''),
            'evaluee_name' => (string) ($evaluation->evaluee_name ?? ''),
            'source' => (string) $evaluation->source,
            'referencia_iii' => $referenciaIii,
            'referencia_iii_condition_cs' => $this->normalizeAnswerDisplayValue($referenciaIiiAnswers['condition_cs'] ?? ''),
            'referencia_iii_condition_mgmt' => $this->normalizeAnswerDisplayValue($referenciaIiiAnswers['condition_mgmt'] ?? ''),
            'referencia_i_acontecimientos_traumaticos' => $ats,
            'referencia_i' => $referenciaI,
            'referencia_v' => $this->mapReferenciaV($referenciaV),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function indexAnswersByInstrument(PaperEvaluation $evaluation): array
    {
        $indexed = [];

        foreach ($evaluation->evaluationAnswers as $answer) {
            $instrument = is_object($answer->instrument) && property_exists($answer->instrument, 'value')
                ? (string) $answer->instrument->value
                : (string) $answer->instrument;
            $questionKey = $this->normalizeAnswerKey($answer->question_key);

            if (! isset($indexed[$instrument])) {
                $indexed[$instrument] = [];
            }

            $indexed[$instrument][$questionKey] = $answer->answer_value;
        }

        return $indexed;
    }

    /**
     * @param  array<string, mixed>  $rawData
     * @return array<string, mixed>
     */
    private function extractReferenciaIii(array $rawData, PaperEvaluation $evaluation): array
    {
        $fromRawData = $rawData['referencia_iii'] ?? null;
        if (is_array($fromRawData)) {
            return $fromRawData;
        }

        $fromRawFlat = $this->extractFlatQuestionValues($rawData, 1, 72);
        if (count($fromRawFlat) > 0) {
            return $fromRawFlat;
        }

        $fromColumn = is_array($evaluation->referencia_iii_answers) ? $evaluation->referencia_iii_answers : [];

        if (is_array($fromColumn['referencia_iii'] ?? null)) {
            return $fromColumn['referencia_iii'];
        }

        return $fromColumn;
    }

    /**
     * @param  array<string, mixed>  $rawData
     * @return array<string, mixed>
     */
    private function extractReferenciaI(array $rawData, PaperEvaluation $evaluation): array
    {
        $fromRawData = $rawData['referencia_i'] ?? null;
        if (is_array($fromRawData)) {
            return $fromRawData;
        }

        return is_array($evaluation->referencia_i_answers) ? $evaluation->referencia_i_answers : [];
    }

    /**
     * @param  array<string, mixed>  $rawData
     * @return array<string, mixed>
     */
    private function extractReferenciaV(array $rawData, PaperEvaluation $evaluation): array
    {
        $fromRawData = $rawData['referencia_v'] ?? null;
        if (is_array($fromRawData)) {
            return $fromRawData;
        }

        return is_array($evaluation->demographic_data) ? $evaluation->demographic_data : [];
    }

    /**
     * @param  array<string, mixed>  $referencia
     * @return array<string, mixed>
     */
    private function mapReferenciaIii($referencia): array
    {
        if (! is_array($referencia)) {
            return [];
        }

        $mappedReferencia = [];
        $hasAtLeastOneAnswer = false;

        foreach (range(1, 72) as $questionNumber) {
            $numericKey = (string) $questionNumber;
            $prefixedKey = "{$questionNumber}";
            $value = $referencia[$numericKey] ?? $referencia[$prefixedKey] ?? '';

            if ($value !== '' && $value !== null) {
                $hasAtLeastOneAnswer = true;
            }

            $mappedReferencia[$prefixedKey] = $value;
        }

        return $hasAtLeastOneAnswer ? $mappedReferencia : [];
    }

    /**
     * @param  array<string, mixed>  $referencia
     * @return array<string, mixed>
     */
    private function mapReferenciaV($referencia): array
    {
        $edad = $this->extractEdad($referencia);

        return [
            'edad' => $edad,
            'sexo' => $this->extractScalarValue($referencia, ['sexo', 'gender']),
            'estado_civil' => $this->extractScalarValue($referencia, ['estado_civil', 'marital_status']),
            'nivel_estudios' => $this->extractScalarValue($referencia, ['nivel_estudios', 'education_level']),
            'tiempo_puesto_actual' => $this->extractScalarValue($referencia, ['datos_laborales.experiencia.tiempo_puesto_actual', 'time_in_current_position']),
            'tiempo_experiencia_laboral' => $this->extractScalarValue($referencia, ['datos_laborales.experiencia.tiempo_experiencia_laboral', 'work_experience']),
            'tipo_de_puesto' => $this->extractScalarValue($referencia, ['datos_laborales.tipo_puesto', 'position_type']),
            'tipo_jornada' => $this->extractScalarValue($referencia, ['datos_laborales.tipo_jornada', 'work_schedule']),
            'tipo_personal' => $this->extractScalarValue($referencia, ['datos_laborales.tipo_personal', 'personnel_type']),
            'rotacion_turnos' => $this->extractScalarValue($referencia, ['datos_laborales.rotacion_turnos', 'shift_rotation']),
            'ocupacion_puesto' => $this->extractScalarValue($referencia, ['datos_laborales.ocupacion_puesto']),
            'tipo_contratacion' => $this->extractScalarValue($referencia, ['datos_laborales.tipo_contratacion', 'contract_type']),
            'departamento_seccion_area' => $this->extractScalarValue($referencia, ['datos_laborales.departamento_seccion_area']),

        ];
    }

    /**
     * @param  array<string|int, mixed>  $source
     * @return array<string, mixed>
     */
    private function extractFlatQuestionValues(array $source, int $start, int $end): array
    {
        $result = [];

        foreach (range($start, $end) as $questionNumber) {
            $key = (string) $questionNumber;

            if (! array_key_exists($key, $source) && ! array_key_exists($questionNumber, $source)) {
                continue;
            }

            $rawValue = $source[$key] ?? $source[$questionNumber] ?? null;
            $value = $this->unwrapValue($rawValue);

            if ($value === '' || $value === null) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $referencia
     */
    private function extractEdad(array $referencia): string
    {
        $edad = $referencia['edad'] ?? $referencia['age'] ?? '';

        if (is_array($edad)) {
            if (array_key_exists('value', $edad)) {
                return (string) $this->unwrapValue($edad['value']);
            }

            $decenas = (string) ($edad['decenas'] ?? $edad['tens'] ?? '');
            $unidades = (string) ($edad['unidades'] ?? $edad['units'] ?? '');

            return trim($decenas.$unidades);
        }

        return (string) $edad;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<int, string>  $paths
     */
    private function extractScalarValue(array $source, array $paths): string
    {
        foreach ($paths as $path) {
            $value = data_get($source, $path);
            $unwrapped = $this->unwrapValue($value);

            if ($unwrapped !== '' && $unwrapped !== null) {
                return (string) $unwrapped;
            }
        }

        return '';
    }

    private function unwrapValue(mixed $value): mixed
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function normalizeAnswerMap(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $key => $value) {
            $normalized[$this->normalizeAnswerKey($key)] = $this->normalizeAnswerDisplayValue(
                $this->unwrapValue($value)
            );
        }

        return $normalized;
    }

    private function normalizeAnswerDisplayValue(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'SI' : 'NO';
        }

        if (! is_string($value)) {
            return $value;
        }

        $normalizedValue = strtolower(trim($value));

        return match ($normalizedValue) {
            'true', '1' => 'SI',
            'false', '0' => 'NO',
            default => $value,
        };
    }

    private function normalizeAnswerKey(string|int $key): string
    {
        $normalizedKey = (string) $key;

        if (preg_match('/^(?:pregunta|question)_?(\d+)$/i', $normalizedKey, $matches) === 1) {
            return (string) ((int) $matches[1]);
        }

        return $normalizedKey;
    }
}
