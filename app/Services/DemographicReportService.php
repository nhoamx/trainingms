<?php

namespace App\Services;

use App\Models\Evaluation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DemographicReportService
{
    /**
     * Obtiene la distribución demográfica de personas por nivel de riesgo
     * usando datos de las guías V (demográficos) y III (evaluación psicosocial)
     *
     * @param int|null $organizationId
     * @return Collection
     */
    public function getDemographicDistribution($organizationId = null): Collection
    {
        try {
            // Si no se proporciona organizationId, usar el del usuario autenticado
            if (!$organizationId) {
                $organizationId = Auth::user()->organization_id;
            }

            // Obtener todas las evaluaciones de la organización con datos demográficos y de riesgo
            $evaluations = $this->getCombinedEvaluationData($organizationId);

            if ($evaluations->isEmpty()) {
                return collect();
            }

            // Procesar por cada campo demográfico
            $demographicFields = [
                'sexo' => 'Género',
                'edad' => 'Edad',
                'tipo_puesto' => 'Tipo de Puesto',
                'estado_civil' => 'Estado Civil',
                'tipo_jornada' => 'Tipo de Jornada',
                'tipo_personal' => 'Tipo de Personal',
                'rotacion_turnos' => 'Rotación de Turnos',
                'tipo_contratacion' => 'Tipo de Contratación',
                'tiempo_puesto_actual' => 'Antigüedad en el Puesto Actual',
                'ultimo_nivel_estudio' => 'Último Nivel de Estudio',
                'experiencia_vida_laboral' => 'Experiencia Vida Laboral',
                'departamento_seccion_area' => 'Departamento/Sección/Área',
                'ocupacion_profesion_puesto' => 'Ocupación/Profesión/Puesto'
            ];

            $result = collect();

            foreach ($demographicFields as $field => $title) {
                $distribution = $this->processDemographicField($evaluations, $field, $title);
                if (!$distribution->isEmpty()) {
                    // Crear datos para gráficas (conteo total por demografía)
                    $chartData = $this->createChartData($distribution);

                    $result->push([
                        'field' => $field,
                        'title' => $title,
                        'data' => $distribution, // Para las tablas (con niveles de riesgo)
                        'chart_data' => $chartData // Para las gráficas (conteo total)
                    ]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Error al obtener la distribución demográfica: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Obtiene los datos combinados de evaluaciones V (demográficos) y III (riesgo psicosocial)
     */
    private function getCombinedEvaluationData($organizationId): Collection
    {
        // Obtener evaluaciones de guía V (datos demográficos)
        $guideVData = Evaluation::where('organization_id', $organizationId)
            ->where('reference_guide', 'V')
            ->whereNotNull('personal_id')
            ->whereNotNull('data')
            ->get()
            ->groupBy('personal_id')
            ->map(function ($evaluations) {
                return $evaluations->sortByDesc('created_at')->first();
            });

        // Obtener niveles de riesgo de guía III para cada personal_id
        $sql = "SELECT
            e.personal_id,
            CASE
                WHEN SUM(q.value) <= 49 THEN 'Nulo'
                WHEN SUM(q.value) BETWEEN 50 AND 75 THEN 'Bajo'
                WHEN SUM(q.value) BETWEEN 76 AND 99 THEN 'Medio'
                WHEN SUM(q.value) BETWEEN 100 AND 139 THEN 'Alto'
                ELSE 'Muy Alto'
            END AS nivel_riesgo
        FROM questions q
        JOIN evaluations e ON q.evaluation_id = e.id
        WHERE q.reference_guide = 'III'
          AND q.value IS NOT NULL
          AND e.organization_id = ?
        GROUP BY e.personal_id
        HAVING SUM(q.value) IS NOT NULL";

        $riskLevels = collect(DB::select($sql, [$organizationId]))
            ->keyBy('personal_id');

        // Combinar datos demográficos con niveles de riesgo
        return $guideVData->map(function ($evaluation) use ($riskLevels) {
            $personalId = $evaluation->personal_id;
            $riskData = $riskLevels->get($personalId);

            if ($riskData) {
                return [
                    'personal_id' => $personalId,
                    'demographic_data' => $evaluation->data,
                    'risk_level' => $riskData->nivel_riesgo
                ];
            }

            return null;
        })->filter()->values();
    }

    /**
     * Procesa un campo demográfico específico y retorna su distribución por nivel de riesgo
     */
    private function processDemographicField(Collection $evaluations, string $field, string $title): Collection
    {
        $riskLevels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];

        // Extraer valores del campo demográfico
        $fieldData = $evaluations->map(function ($item) use ($field) {
            $value = data_get($item['demographic_data'], $field);
            return [
                'value' => $this->normalizeFieldValue($field, $value),
                'risk_level' => $item['risk_level'],
                'personal_id' => $item['personal_id']
            ];
        })->filter(function ($item) {
            return !empty($item['value']);
        });

        // Agrupar por valor del campo
        $grouped = $fieldData->groupBy('value');

        return $grouped->map(function ($items, $value) use ($riskLevels) {
            $riskDistribution = array_fill_keys($riskLevels, 0);
            $personalByRisk = array_fill_keys($riskLevels, []);

            foreach ($items as $item) {
                $riskDistribution[$item['risk_level']]++;
                // Agregar personal_id para compatibilidad con componentes existentes
                if (isset($item['personal_id'])) {
                    $personalByRisk[$item['risk_level']][] = $item['personal_id'];
                }
            }

            return [
                'name' => $value,
                'risk_levels' => $riskDistribution,
                'personal_by_risk' => $personalByRisk,
                'total' => $items->count()
            ];
        })->values();
    }

    /**
     * Crea datos para gráficas que muestran el conteo total por demografía
     */
    private function createChartData(Collection $distribution): array
    {
        return $distribution->map(function ($item) {
            return [
                'name' => $item['name'],
                'value' => $item['total'],
                'total' => $item['total']
            ];
        })->toArray();
    }

    /**
     * Normaliza valores de campos demográficos para agrupar correctamente
     */
    private function normalizeFieldValue(string $field, $value): string
    {
        if (empty($value)) {
            return '';
        }

        // Normalización específica por campo
        switch ($field) {
            case 'nivel_estudios':
                // Si es un array, tomar el primer nivel (ej: ["Primaria", "Terminada"] -> "Primaria Terminada")
                if (is_array($value)) {
                    return implode(' ', $value);
                }
                return $value;

            case 'estado_civil':
                // Normalizar "Unión libre" vs "Unión Libre"
                if (strtolower($value) === 'union libre') {
                    return 'Unión Libre';
                }
                return $value;

            case 'datos_laborales.tipo_contratacion':
                // Mapear valores específicos
                $contractMapping = [
                    'Por tiempo determinado (temporal)' => 'Temporal',
                    'Tiempo indeterminado' => 'Tiempo Indeterminado',
                    'Por obra o proyecto' => 'Temporal',
                    'Honorarios' => 'Temporal'
                ];
                return $contractMapping[$value] ?? $value;

            case 'datos_laborales.tipo_jornada':
                // Normalizar jornadas laborales
                if (strpos(strtolower($value), 'nocturno') !== false) {
                    return 'Fijo Nocturno (entre las 20:00 y 6:00 hrs)';
                } elseif (strpos(strtolower($value), 'diurno') !== false) {
                    return 'Fijo Diurno (entre las 6:00 y 20:00 hrs)';
                } elseif (strpos(strtolower($value), 'mixto') !== false) {
                    return 'Fijo Mixto (combinación de nocturno y diurno)';
                }
                return $value;

            case 'datos_laborales.experiencia.tiempo_puesto_actual':
                // Normalizar rangos de tiempo
                $timeMapping = [
                    'Menos de 6 meses' => 'menos de 6 meses',
                    'Entre 6 meses y 1 año' => 'entre 6 meses y 1 año',
                    'Entre 1 a 4 años' => 'entre 1 y 4 años',
                    'Entre 5 a 9 años' => 'entre 5 y 9 años',
                    'Entre 10 a 14 años' => 'entre 10 y 14 años',
                    'Entre 15 a 19 años' => 'entre 15 y 19 años',
                    'Entre 20 a 24 años' => 'entre 20 y 24 años',
                    '25 años o más' => '25 años o más'
                ];
                return $timeMapping[$value] ?? $value;

            case 'datos_laborales.departamento_seccion_area':
                // Mapear departamentos comunes a categorías estándar
                $areaMapping = [
                    'produccion' => 'Producción / Procesos',
                    'lean' => 'Lean / Mejora Continua',
                    'planeacion' => 'Planeación / Supply Chain',
                    'ingenieria' => 'Ingeniería',
                    'seguridad' => 'Seguridad e Higiene / EHS',
                    'calidad' => 'Calidad',
                    'intendencia' => 'Intendencia',
                    'almacen' => 'Almacén',
                    'sistemas' => 'Sistemas / IT / Soporte Técnico',
                    'administrativa' => 'Administrativa',
                    'soporte' => 'Soporte Técnico',
                    'distribucion' => 'Centro de Distribución',
                    'mantenimiento' => 'Mantenimiento'
                ];

                $normalizedValue = strtolower(trim($value));
                foreach ($areaMapping as $key => $mapped) {
                    if (strpos($normalizedValue, $key) !== false) {
                        return $mapped;
                    }
                }
                return 'Otros';

            case 'datos_laborales.ocupacion_puesto':
                // Mapear puestos comunes
                $positionMapping = [
                    'almacenista' => 'Almacenista',
                    'analista' => 'Analista',
                    'coordinador' => 'Coordinador',
                    'ingeniero' => 'Ingeniero',
                    'intendencia' => 'Intendencia',
                    'materialista' => 'Materialista',
                    'operador' => 'Operador',
                    'planeador' => 'Planeador',
                    'supervisor' => 'Supervisor',
                    'tecnico' => 'Técnico'
                ];

                $normalizedValue = strtolower(trim($value));
                foreach ($positionMapping as $key => $mapped) {
                    if (strpos($normalizedValue, $key) !== false) {
                        return $mapped;
                    }
                }
                return 'Otros';

            default:
                return is_string($value) ? $value : (string) $value;
        }
    }
}
