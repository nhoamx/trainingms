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
     * @return Collection
     */
    public function getDemographicDistribution(): Collection
    {
        try {
            $organizationId = Auth::user()->organization_id;
            
            // Obtener todas las evaluaciones de la organización con datos demográficos y de riesgo
            $evaluations = $this->getCombinedEvaluationData($organizationId);
            
            if ($evaluations->isEmpty()) {
                return collect();
            }

            // Procesar por cada campo demográfico
            $demographicFields = [
                'sexo' => 'Género',
                'edad' => 'Rango de Edad', 
                'estado_civil' => 'Estado Civil',
                'nivel_estudios' => 'Nivel de Estudios',
                'datos_laborales.ocupacion_puesto' => 'Puesto',
                'datos_laborales.tipo_contratacion' => 'Tipo de Contratación',
                'datos_laborales.tipo_personal' => 'Tipo de Personal',
                'datos_laborales.tipo_jornada' => 'Tipo de Jornada Laboral',
                'datos_laborales.rotacion_turnos' => 'Rotación de Turnos',
                'datos_laborales.departamento_seccion_area' => 'Área',
                'datos_laborales.experiencia.tiempo_puesto_actual' => 'Antigüedad en el Puesto Actual'
            ];

            $result = collect();

            foreach ($demographicFields as $field => $title) {
                $distribution = $this->processDemographicField($evaluations, $field, $title);
                if (!$distribution->isEmpty()) {
                    $result->push([
                        'field' => $field,
                        'title' => $title,
                        'data' => $distribution
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
                'risk_level' => $item['risk_level']
            ];
        })->filter(function ($item) {
            return !empty($item['value']);
        });

        // Agrupar por valor del campo
        $grouped = $fieldData->groupBy('value');
        
        return $grouped->map(function ($items, $value) use ($riskLevels) {
            $riskDistribution = array_fill_keys($riskLevels, 0);
            
            foreach ($items as $item) {
                $riskDistribution[$item['risk_level']]++;
            }
            
            return [
                'name' => $value,
                'risk_levels' => $riskDistribution,
                'total' => $items->count()
            ];
        })->values();
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
                
            default:
                return is_string($value) ? $value : (string) $value;
        }
    }
}
