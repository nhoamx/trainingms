<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Support\Collection;

class WorkCenterOrganizationReportService
{
    /**
     * @return Collection<int, array{id: string, code: string, name: string, is_primary: bool, evaluations: array<int, array{folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>}>
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
     * @return Collection<string, array<int, array{folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>>
     */
    private function getEvaluationsByWorkCenter(Organization $organization, Collection $workCenters): Collection
    {
        return PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->whereIn('work_center_id', $workCenters->pluck('id'))
            ->select(['work_center_id', 'folio', 'evaluee_name', 'source', 'raw_data'])
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
     * @param  Collection<string, array<int, array{folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>>  $evaluationsByWorkCenter
     * @return array{id: string, code: string, name: string, is_primary: bool, evaluations: array<int, array{folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>}
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
     * @return array{folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}
     */
    private function mapEvaluation(PaperEvaluation $evaluation): array
    {
        $rawData = is_array($evaluation->raw_data) ? $evaluation->raw_data : [];
        $referenciaI = is_array($rawData['referencia_i'] ?? null) ? $rawData['referencia_i'] : null;
        $referenciaV = is_array($rawData['referencia_v'] ?? null) ? $rawData['referencia_v'] : null;
        $acontecimientosTraumaticos = is_array($referenciaI)
            ? ($referenciaI['acontecimientos_traumaticos'] ?? null)
            : null;

        if (is_array($referenciaI)) {
            unset($referenciaI['acontecimientos_traumaticos']);
        }

        return [
            'folio' => (string) $evaluation->folio,
            'evaluee_name' => (string) ($evaluation->evaluee_name ?? ''),
            'source' => (string) $evaluation->source,
            'referencia_iii' => $this->mapReferenciaIii($rawData['referencia_iii'] ?? []),
            'referencia_i_acontecimientos_traumaticos' => $acontecimientosTraumaticos,
            'referencia_i' => $referenciaI,
            'referencia_v' => $this->mapReferenciaV($referenciaV),
        ];
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
        return [
            'edad' => $referencia['edad'] ?? '',
            'sexo' => $referencia['sexo'] ?? '',
            'estado_civil' => $referencia['estado_civil'] ?? '',
            'nivel_estudios' => $referencia['nivel_estudios'] ?? '',
            'tiempo_puesto_actual' => $referencia['datos_laborales']['experiencia']['tiempo_puesto_actual'] ?? '',
            'tiempo_experiencia_laboral' => $referencia['datos_laborales']['experiencia']['tiempo_experiencia_laboral'] ?? '',
            'tipo_de_puesto' => $referencia['datos_laborales']['tipo_puesto'] ?? '',
            'tipo_jornada' => $referencia['datos_laborales']['tipo_jornada'] ?? '',
            'tipo_personal' => $referencia['datos_laborales']['tipo_personal'] ?? '',
            'rotacion_turnos' => $referencia['datos_laborales']['rotacion_turnos'] ?? '',
            'ocupacion_puesto' => $referencia['datos_laborales']['ocupacion_puesto'] ?? '',
            'tipo_contratacion' => $referencia['datos_laborales']['tipo_contratacion'] ?? '',
            'departamento_seccion_area' => $referencia['datos_laborales']['departamento_seccion_area'] ?? '',

        ];
    }
}
