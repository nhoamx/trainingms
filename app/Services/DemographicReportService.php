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
        // Obtener datos demográficos desde la tabla questions (guía V)
        $demographicDataSql = "SELECT
            e.personal_id,
            q.question,
            q.answer
        FROM questions q
        JOIN evaluations e ON q.evaluation_id = e.id
        WHERE q.reference_guide = 'V'
          AND e.organization_id = ?
          AND q.answer IS NOT NULL
        ORDER BY e.personal_id, e.created_at DESC";

        $demographicQuestions = collect(DB::select($demographicDataSql, [$organizationId]));

        // Agrupar por personal_id y crear array de datos demográficos
        $guideVData = $demographicQuestions->groupBy('personal_id')
            ->map(function ($questions) {
                $data = [];
                foreach ($questions as $question) {
                    $data[$question->question] = $question->answer;
                }
                return $data;
            });

        // Obtener niveles de riesgo de guía III para cada personal_id
        $riskLevelsSql = "SELECT
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

        $riskLevels = collect(DB::select($riskLevelsSql, [$organizationId]))
            ->keyBy('personal_id');

        // Combinar datos demográficos con niveles de riesgo
        return $guideVData->map(function ($demographicData, $personalId) use ($riskLevels) {
            $riskData = $riskLevels->get($personalId);

            if ($riskData) {
                return [
                    'personal_id' => $personalId,
                    'demographic_data' => $demographicData,
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
            case 'tipo_puesto':
                // Normalizar tipo de puesto
                $jobTypeMapping = [
                    'operativo' => 'Operativo',
                    'supervisor' => 'Supervisor',
                    'prof_tecnoci' => 'Profesional/Técnico',
                    'gerente' => 'Gerente'
                ];
                $normalizedValue = strtolower(trim($value));
                return $jobTypeMapping[$normalizedValue] ?? ucfirst($value);
            case 'edad':
                // Convertir edad individual a rangos de edad
                // Si el valor contiene punto (ej: "25.35"), tomar solo la parte entera
                $ageValue = is_string($value) && strpos($value, '.') !== false 
                    ? intval(explode('.', $value)[0]) 
                    : intval($value);
                
                if ($ageValue >= 15 && $ageValue <= 19) return '15–19';
                if ($ageValue >= 20 && $ageValue <= 24) return '20–24';
                if ($ageValue >= 25 && $ageValue <= 29) return '25–29';
                if ($ageValue >= 30 && $ageValue <= 34) return '30–34';
                if ($ageValue >= 35 && $ageValue <= 39) return '35–39';
                if ($ageValue >= 40 && $ageValue <= 44) return '40–44';
                if ($ageValue >= 45 && $ageValue <= 49) return '45–49';
                if ($ageValue >= 50 && $ageValue <= 54) return '50–54';
                if ($ageValue >= 55 && $ageValue <= 59) return '55–59';
                if ($ageValue >= 60) return '60-mas';
                
                // Si no está en ningún rango válido, retornar vacío
                return '';

            case 'sexo':
                // Normalizar género
                $genderMapping = [
                    'masculino' => 'Masculino',
                    'femenino' => 'Femenino',
                    'hombre' => 'Masculino',
                    'mujer' => 'Femenino',
                    'male' => 'Masculino',
                    'female' => 'Femenino'
                ];
                $normalizedValue = strtolower(trim($value));
                return $genderMapping[$normalizedValue] ?? ucfirst($value);

            case 'estado_civil':
                // Normalizar estado civil
                $civilStatusMapping = [
                    'casado' => 'Casado',
                    'soltero' => 'Soltero',
                    'union_libre' => 'Unión Libre',
                    'union libre' => 'Unión Libre',
                    'divorciado' => 'Divorciado',
                    'separado' => 'Divorciado',
                    'viudo' => 'Otro',
                    'otro' => 'Otro'
                ];
                $normalizedValue = strtolower(trim($value));
                return $civilStatusMapping[$normalizedValue] ?? ucfirst($value);

            case 'ultimo_nivel_estudio':
                // Normalizar nivel de estudios
                $educationMapping = [
                    'sin_formacion' => 'Sin Formación',
                    'primaria_terminada' => 'Primaria Terminada',
                    'primaria_incompleta' => 'Primaria Incompleta',
                    'secundaria_terminada' => 'Secundaria Terminada',
                    'secundaria_incompleta' => 'Secundaria Incompleta',
                    'preparatoria_terminada' => 'Preparatoria Terminada',
                    'preparatoria_incompleta' => 'Preparatoria Incompleta',
                    'tecnico_terminado' => 'Técnico Terminado',
                    'licenciatura_terminada' => 'Licenciatura Terminada',
                    'licenciatura_incompleta' => 'Licenciatura Incompleta',
                    'maestria_terminada' => 'Maestría Terminada',
                    // Nuevos mapeos agregados
                    'preparatoria_terminado' => 'Preparatoria Terminada',
                    'secundaria_terminado' => 'Secundaria Terminada',
                    'primaria_terminado' => 'Primaria Terminada',
                    'secundaria_inconcluso' => 'Secundaria Incompleta',
                    'tecnico_superior_terminado' => 'Técnico Terminado',
                    'licenciatura_inconcluso' => 'Licenciatura Incompleta',
                    'preparatoria_inconcluso' => 'Preparatoria Incompleta',
                    'primaria_inconcluso' => 'Primaria Incompleta',
                    'licenciatura_terminado' => 'Licenciatura Terminada',
                    'maestria_terminado' => 'Maestría Terminada',
                    'tecnico_superior_inconcluso' => 'Técnico Terminado',
                    // Variantes con espacios
                    'sin formacion' => 'Sin Formación',
                    'primaria terminada' => 'Primaria Terminada',
                    'primaria incompleta' => 'Primaria Incompleta',
                    'secundaria terminada' => 'Secundaria Terminada',
                    'secundaria incompleta' => 'Secundaria Incompleta',
                    'preparatoria terminada' => 'Preparatoria Terminada',
                    'preparatoria incompleta' => 'Preparatoria Incompleta',
                    'tecnico terminado' => 'Técnico Terminado',
                    'licenciatura terminada' => 'Licenciatura Terminada',
                    'licenciatura incompleta' => 'Licenciatura Incompleta',
                    'maestria terminada' => 'Maestría Terminada'
                ];
                $normalizedValue = strtolower(trim($value));
                return $educationMapping[$normalizedValue] ?? $value;

            case 'tipo_personal':
                // Normalizar tipo de personal
                $personnelTypeMapping = [
                    'sindicalizado' => 'Sindicalizado',
                    'confianza' => 'Confianza',
                    'temporal' => 'Temporal',
                    'base' => 'Sindicalizado'
                ];
                $normalizedValue = strtolower(trim($value));
                return $personnelTypeMapping[$normalizedValue] ?? ucfirst($value);

            case 'tipo_contratacion':
                // Normalizar tipo de contratación
                $contractMapping = [
                    'temporal' => 'Temporal',
                    'tiempo_indeterminado' => 'Tiempo Indeterminado',
                    'tiempo indeterminado' => 'Tiempo Indeterminado',
                    'por_tiempo_determinado' => 'Temporal',
                    'por tiempo determinado' => 'Temporal',
                    'honorarios' => 'Temporal',
                    'obra_proyecto' => 'Temporal',
                    'obra proyecto' => 'Temporal',
                    'indeterminado' => 'Tiempo Indeterminado',
                ];
                $normalizedValue = strtolower(trim($value));
                return $contractMapping[$normalizedValue] ?? $value;

            case 'tipo_jornada':
                // Normalizar tipo de jornada
                $scheduleMapping = [
                    'fijo_diurno' => 'Fijo Diurno (entre las 6:00 y 20:00 hrs)',
                    'fijo_nocturno' => 'Fijo Nocturno (entre las 20:00 y 6:00 hrs)',
                    'fijo_mixto' => 'Fijo Mixto (combinación de nocturno y diurno)',
                    'fijo_6_20' => 'Fijo Diurno (entre las 6:00 y 20:00 hrs)',
                    'fijo_20_6' => 'Fijo Nocturno (entre las 20:00 y 6:00 hrs)',
                    'fijo diurno' => 'Fijo Diurno (entre las 6:00 y 20:00 hrs)',
                    'fijo nocturno' => 'Fijo Nocturno (entre las 20:00 y 6:00 hrs)',
                    'fijo mixto' => 'Fijo Mixto (combinación de nocturno y diurno)',
                    'diurno' => 'Fijo Diurno (entre las 6:00 y 20:00 hrs)',
                    'nocturno' => 'Fijo Nocturno (entre las 20:00 y 6:00 hrs)',
                    'mixto' => 'Fijo Mixto (combinación de nocturno y diurno)'
                ];
                $normalizedValue = strtolower(trim($value));
                return $scheduleMapping[$normalizedValue] ?? $value;

            case 'rotacion_turnos':
                // Normalizar rotación de turnos
                $rotationMapping = [
                    'si' => 'Sí',
                    'no' => 'No',
                    'yes' => 'Sí',
                    'true' => 'Sí',
                    'false' => 'No',
                    '1' => 'Sí',
                    '0' => 'No'
                ];
                $normalizedValue = strtolower(trim($value));
                return $rotationMapping[$normalizedValue] ?? ucfirst($value);

            case 'tiempo_puesto_actual':
                // Normalizar antigüedad en el puesto
                $seniorityMapping = [
                    'menos_de_6_meses' => 'menos de 6 meses',
                    '0-6_meses' => 'menos de 6 meses',
                    '6-12_meses' => 'entre 6 meses y 1 año',
                    'entre_6_meses_y_1_ano' => 'entre 6 meses y 1 año',
                    'entre_1_y_4_anos' => 'entre 1 y 4 años',
                    '1-4-anos' => 'entre 1 y 4 años',
                    'entre_5_y_9_anos' => 'entre 5 y 9 años',
                    '5-9-anos' => 'entre 5 y 9 años',
                    'entre_10_y_14_anos' => 'entre 10 y 14 años',
                    '10-14-anos' => 'entre 10 y 14 años',
                    'entre_15_y_19_anos' => 'entre 15 y 19 años',
                    '15-19-anos' => 'entre 15 y 19 años',
                    'entre_20_y_24_anos' => 'entre 20 y 24 años',
                    '25_anos_o_mas' => '25 años o más',
                    '25-anos_o_mas' => '25 años o más',
                    'menos de 6 meses' => 'menos de 6 meses',
                    'entre 6 meses y 1 año' => 'entre 6 meses y 1 año',
                    'entre 1 y 4 años' => 'entre 1 y 4 años',
                    'entre 5 y 9 años' => 'entre 5 y 9 años',
                    'entre 10 y 14 años' => 'entre 10 y 14 años',
                    'entre 15 y 19 años' => 'entre 15 y 19 años',
                    'entre 20 y 24 años' => 'entre 20 y 24 años',
                    '25 años o más' => '25 años o más'
                ];
                $normalizedValue = strtolower(trim($value));
                return $seniorityMapping[$normalizedValue] ?? $value;

            case 'experiencia_vida_laboral':
                // Similar a tiempo_puesto_actual
                $experienceMapping = [
                    'menos_de_6_meses' => 'menos de 6 meses',
                    '0-6_meses' => 'menos de 6 meses',
                    '6-12_meses' => 'entre 6 meses y 1 año',
                    'entre_6_meses_y_1_ano' => 'entre 6 meses y 1 año',
                    'entre_1_y_4_anos' => 'entre 1 y 4 años',
                    '1-4-anos' => 'entre 1 y 4 años',
                    'entre_5_y_9_anos' => 'entre 5 y 9 años',
                    '5-9-anos' => 'entre 5 y 9 años',
                    'entre_10_y_14_anos' => 'entre 10 y 14 años',
                    '10-14-anos' => 'entre 10 y 14 años',
                    'entre_15_y_19_anos' => 'entre 15 y 19 años',
                    '15-19-anos' => 'entre 15 y 19 años',
                    'entre_20_y_24_anos' => 'entre 20 y 24 años',
                    '20-24-anos' => 'entre 20 y 24 años',
                    '25_anos_o_mas' => '25 años o más',
                    '25-anos_o_mas' => '25 años o más',
                    'menos de 6 meses' => 'menos de 6 meses',
                    'entre 6 meses y 1 año' => 'entre 6 meses y 1 año',
                    'entre 1 y 4 años' => 'entre 1 y 4 años',
                    'entre 5 y 9 años' => 'entre 5 y 9 años',
                    'entre 10 y 14 años' => 'entre 10 y 14 años',
                    'entre 15 y 19 años' => 'entre 15 y 19 años',
                    'entre 20 y 24 años' => 'entre 20 y 24 años',
                    '25 años o más' => '25 años o más'
                ];
                $normalizedValue = strtolower(trim($value));
                return $experienceMapping[$normalizedValue] ?? $value;

            case 'nivel_estudios':
                // Si es un array, tomar el primer nivel (ej: ["Primaria", "Terminada"] -> "Primaria Terminada")
                if (is_array($value)) {
                    return implode(' ', $value);
                }
                return $value;

            case 'departamento_seccion_area':
                // No normalizar - mantener valores originales según requerimiento
                return $value;

            case 'ocupacion_profesion_puesto':
                // No normalizar - mantener valores originales según requerimiento
                return $value;

            default:
                return is_string($value) ? $value : (string) $value;
        }
    }
}
