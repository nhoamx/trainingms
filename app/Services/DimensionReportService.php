<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DimensionReportService
{
    /**
     * Obtiene el conteo de respuestas por dimensión y tipo de respuesta
     * Implementa el Query #5 de la guía III: Conteo de respuestas por dimensión y tipo de respuesta
     */
    public function getDimensionRiskLevelDistribution(string $referenceGuide = 'III'): Collection
    {
        /*
         * Obtiene la distribución de personas por nivel de riesgo y dimensión según la NOM-035 (Guía III).
         * Devuelve una colección donde cada elemento es:
         *   [
         *     'dimension_name' => string,
         *     'risk_levels' => [
         *         'Nulo' => int,
         *         'Bajo' => int,
         *         'Medio' => int,
         *         'Alto' => int,
         *         'Muy Alto' => int
         *     ]
         *   ]
         * Siempre incluye todos los niveles de riesgo, aunque sean cero.
         */
        try {
            $organizationId = auth()->user()->organization_id;
            $sql = "SELECT\n                r.dimension_nombre,\n                r.nivel AS nivel_riesgo,\n                COUNT(e.personal_id) AS total_personas\n            FROM (\n                SELECT DISTINCT \n                    d.name AS dimension_nombre,\n                    nivel.nivel\n                FROM dimensions d\n                CROSS JOIN (\n                    SELECT 'Nulo' AS nivel UNION ALL\n                    SELECT 'Bajo' UNION ALL\n                    SELECT 'Medio' UNION ALL\n                    SELECT 'Alto' UNION ALL\n                    SELECT 'Muy Alto'\n                ) nivel\n            ) r\n            LEFT JOIN (\n                SELECT\n                    q.dimension_id,\n                    e.personal_id,\n                    d.name AS dimension_nombre,\n                    SUM(q.value) AS puntuacion_total,\n                    CASE\n                        WHEN SUM(q.value) <= 49 THEN 'Nulo'\n                        WHEN SUM(q.value) BETWEEN 50 AND 75 THEN 'Bajo'\n                        WHEN SUM(q.value) BETWEEN 76 AND 99 THEN 'Medio'\n                        WHEN SUM(q.value) BETWEEN 100 AND 139 THEN 'Alto'\n                        ELSE 'Muy Alto'\n                    END AS nivel_riesgo\n                FROM questions q\n                JOIN evaluations e ON q.evaluation_id = e.id\n                JOIN dimensions d ON q.dimension_id = d.id\n                WHERE q.reference_guide = ?\n                  AND q.value IS NOT NULL\n                  AND e.organization_id = ?\n                GROUP BY e.personal_id, q.dimension_id, d.name\n            ) e\n              ON r.dimension_nombre = e.dimension_nombre AND r.nivel = e.nivel_riesgo\n            GROUP BY r.dimension_nombre, r.nivel\n            ORDER BY r.dimension_nombre,\n                     FIELD(r.nivel, 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto');";

            $results = DB::select($sql, [$referenceGuide, $organizationId]);

            // Procesar resultados: asegurar que todos los niveles estén presentes para cada dimensión
            $niveles = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
            $data = [];
            foreach ($results as $row) {
                $dim = $row->dimension_nombre;
                if (! isset($data[$dim])) {
                    // Inicializa todos los niveles en cero
                    $data[$dim] = [
                        'dimension_name' => $dim,
                        'risk_levels' => array_fill_keys($niveles, 0),
                    ];
                }
                $data[$dim]['risk_levels'][$row->nivel_riesgo] = (int) $row->total_personas;
            }
            // Si alguna dimensión no tiene todos los niveles, los completa en cero
            foreach ($data as &$dimData) {
                foreach ($niveles as $nivel) {
                    if (! isset($dimData['risk_levels'][$nivel])) {
                        $dimData['risk_levels'][$nivel] = 0;
                    }
                }
            }
            unset($dimData);

            // Devuelve colección indexada
            return collect(array_values($data));
        } catch (\Throwable $e) {
            Log::error('Error al obtener la distribución de personas por nivel de riesgo y dimensión: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Procesa los resultados de la consulta para estructurarlos por dimensión
     */
    private function processDimensionResults(Collection $results): Collection
    {
        $dimensionData = collect();

        // Agrupamos los resultados por dimensión
        $groupedResults = $results->groupBy('dimension_id');

        foreach ($groupedResults as $dimensionId => $dimensionResponses) {
            $dimensionName = $dimensionResponses->first()->dimension_name;

            // Inicializamos contadores para cada tipo de respuesta
            $responsesByType = [
                'A' => 0, // Siempre
                'B' => 0, // Casi siempre
                'C' => 0, // Algunas veces
                'D' => 0, // Casi nunca
                'E' => 0, // Nunca
            ];

            // Sumamos las respuestas por tipo
            foreach ($dimensionResponses as $response) {
                if (isset($responsesByType[$response->answer])) {
                    $responsesByType[$response->answer] = $response->total_responses;
                }
            }

            // Calculamos el total de respuestas para esta dimensión
            $totalResponses = array_sum($responsesByType);

            // Calculamos porcentajes
            $percentages = [];
            foreach ($responsesByType as $type => $count) {
                $percentages[$type] = $totalResponses > 0 ? ($count / $totalResponses) * 100 : 0;
            }

            // Añadimos al resultado
            $dimensionData->push([
                'id' => $dimensionId,
                'name' => $dimensionName,
                'responses' => $responsesByType,
                'percentages' => $percentages,
                'total' => $totalResponses,
            ]);
        }

        return $dimensionData;
    }

    /**
     * Obtiene la suma total del valor de respuestas por dimensión
     * Implementa el Query #6 de la guía III: Suma total del valor de respuestas por dimensión
     */
    public function getDimensionTotalScores(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía especificada
            $guidePersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guidePersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");

                return collect();
            }

            // Ejecutar la consulta que obtiene la suma total por dimensión
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('dimensions', 'questions.dimension_id', '=', 'dimensions.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.value')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'dimensions.id as dimension_id',
                    'dimensions.name as dimension_name',
                    DB::raw('SUM(questions.value) as total_score'),
                    DB::raw('COUNT(*) as question_count')
                )
                ->groupBy('dimensions.id', 'dimensions.name')
                ->orderByDesc('total_score')
                ->get();

            // Procesar los resultados para añadir información adicional como promedios
            $processedResults = $results->map(function ($item) {
                $avgScore = $item->question_count > 0 ? $item->total_score / $item->question_count : 0;

                return [
                    'id' => $item->dimension_id,
                    'name' => $item->dimension_name,
                    'total_score' => $item->total_score,
                    'question_count' => $item->question_count,
                    'avg_score' => round($avgScore, 2),
                ];
            });

            return $processedResults;

        } catch (\Exception $e) {
            Log::error('Error al obtener la suma total de respuestas por dimensión: '.$e->getMessage());

            return collect();
        }
    }
}
