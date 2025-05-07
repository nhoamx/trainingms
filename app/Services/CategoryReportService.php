<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Category;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CategoryReportService
{
    /**
     * Obtiene el conteo de personas únicas por categoría y tipo de respuesta
     * Implementa el Query #1 modificado de la guía III: Conteo de personas por categoría y tipo de respuesta
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getCategoryRiskLevelDistribution(string $referenceGuide = 'III'): Collection
    {
        /*
         * Obtiene la distribución de personas por nivel de riesgo y categoría según la NOM-035 (Guía III).
         * Devuelve una colección donde cada elemento es:
         *   [
         *     'category_name' => string,
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
            $sql = "SELECT
                r.categoria_nombre,
                r.nivel AS nivel_riesgo,
                COUNT(e.personal_id) AS total_personas
            FROM (
                SELECT DISTINCT 
                    c.name AS categoria_nombre,
                    nivel.nivel
                FROM categories c
                CROSS JOIN (
                    SELECT 'Nulo' AS nivel UNION ALL
                    SELECT 'Bajo' UNION ALL
                    SELECT 'Medio' UNION ALL
                    SELECT 'Alto' UNION ALL
                    SELECT 'Muy Alto'
                ) nivel
            ) r
            LEFT JOIN (
                SELECT
                    q.category_id,
                    e.personal_id,
                    c.name AS categoria_nombre,
                    SUM(q.value) AS puntuacion_total,
                    CASE
                        WHEN SUM(q.value) <= 49 THEN 'Nulo'
                        WHEN SUM(q.value) BETWEEN 50 AND 75 THEN 'Bajo'
                        WHEN SUM(q.value) BETWEEN 76 AND 99 THEN 'Medio'
                        WHEN SUM(q.value) BETWEEN 100 AND 139 THEN 'Alto'
                        ELSE 'Muy Alto'
                    END AS nivel_riesgo
                FROM questions q
                JOIN evaluations e ON q.evaluation_id = e.id
                JOIN categories c ON q.category_id = c.id
                WHERE q.reference_guide = ?
                  AND q.value IS NOT NULL
                  AND e.organization_id = ?
                GROUP BY e.personal_id, q.category_id, c.name
            ) e
              ON r.categoria_nombre = e.categoria_nombre AND r.nivel = e.nivel_riesgo
            GROUP BY r.categoria_nombre, r.nivel
            ORDER BY r.categoria_nombre,
                     FIELD(r.nivel, 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto');";

            $results = DB::select($sql, [$referenceGuide, $organizationId]);

            // Procesar resultados: asegurar que todos los niveles estén presentes para cada categoría
            $niveles = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
            $data = [];
            foreach ($results as $row) {
                $cat = $row->categoria_nombre;
                if (!isset($data[$cat])) {
                    // Inicializa todos los niveles en cero
                    $data[$cat] = [
                        'category_name' => $cat,
                        'risk_levels' => array_fill_keys($niveles, 0)
                    ];
                }
                $data[$cat]['risk_levels'][$row->nivel_riesgo] = (int) $row->total_personas;
            }
            // Si alguna categoría no tiene todos los niveles, los completa en cero
            foreach ($data as &$catData) {
                foreach ($niveles as $nivel) {
                    if (!isset($catData['risk_levels'][$nivel])) {
                        $catData['risk_levels'][$nivel] = 0;
                    }
                }
            }
            unset($catData);
            // Devuelve colección indexada
            return collect(array_values($data));
        } catch (\Throwable $e) {
            Log::error("Error al obtener la distribución de personas por nivel de riesgo y categoría: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Obtiene la suma total del valor de respuestas por categoría
     * Implementa el Query #2 de la guía III: Suma total del valor de respuestas por categoría
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getCategoryTotalScores(string $referenceGuide = 'III'): Collection
    {
        try {
            // Obtenemos todos los IDs de personal con evaluaciones de la guía III para consistencia
            $guideIIIPersonalIds = Evaluation::where('reference_guide', $referenceGuide)
                ->whereNotNull('personal_id')
                ->pluck('personal_id')
                ->unique()
                ->filter();

            if ($guideIIIPersonalIds->isEmpty()) {
                Log::warning("No se encontraron IDs de personal con evaluaciones de la Guía {$referenceGuide}.");
                return collect();
            }

            // Ejecutar la consulta que obtiene la suma total por categoría
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('categories', 'questions.category_id', '=', 'categories.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.value')
                ->whereIn('questions.personal_id', $guideIIIPersonalIds)
                ->select(
                    'categories.id as category_id',
                    'categories.name as category_name',
                    DB::raw('SUM(questions.value) as total_score'),
                    DB::raw('COUNT(*) as question_count')
                )
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_score')
                ->get();

            // Procesar los resultados para añadir información adicional como promedios
            $processedResults = $results->map(function ($item) {
                $avgScore = $item->question_count > 0 ? $item->total_score / $item->question_count : 0;

                return [
                    'id' => $item->category_id,
                    'name' => $item->category_name,
                    'total_score' => $item->total_score,
                    'question_count' => $item->question_count,
                    'avg_score' => round($avgScore, 2)
                ];
            });

            return $processedResults;
        } catch (\Exception $e) {
            Log::error("Error al obtener la suma total de respuestas por categoría: " . $e->getMessage());
            return collect();
        }
    }
}
