<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Domain;
use App\Models\Evaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DomainReportService
{

    /**
     * Obtiene la distribución de personas por dominio y nivel de riesgo (Nulo, Bajo, Medio, Alto, Muy Alto)
     * Implementa el query oficial de la NOM-035 para dominios (ver instrucciones)
     *
     * @param string $referenceGuide
     * @param int|null $organizationId
     * @return Collection
     */
    public function getDomainAnswerTypeDistribution(string $referenceGuide = 'III', $organizationId = null): Collection
    {
        /*
         * Obtiene la distribución de personas por nivel de riesgo y dominio según la NOM-035 (Guía III).
         * Devuelve una colección donde cada elemento es:
         *   [
         *     'domain_name' => string,
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
            if (!$organizationId && auth()->check() && auth()->user()->organization_id) {
                $organizationId = auth()->user()->organization_id;
            }
            if (!$organizationId) {
                Log::warning('No se proporcionó organization_id para el reporte de dominios.');
                return collect();
            }

            $sql = "SELECT
                r.dominio_nombre,
                r.nivel AS nivel_riesgo,
                COUNT(e.personal_id) AS total_personas
            FROM (
                SELECT DISTINCT 
                    d.name AS dominio_nombre,
                    nivel.nivel
                FROM domains d
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
                    q.domain_id,
                    e.personal_id,
                    d.name AS dominio_nombre,
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
                JOIN domains d ON q.domain_id = d.id
                WHERE q.reference_guide = ?
                  AND q.value IS NOT NULL
                  AND e.organization_id = ?
                GROUP BY e.personal_id, q.domain_id, d.name
            ) e
              ON r.dominio_nombre = e.dominio_nombre AND r.nivel = e.nivel_riesgo
            GROUP BY r.dominio_nombre, r.nivel
            ORDER BY r.dominio_nombre,
                     FIELD(r.nivel, 'Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto');";

            $results = DB::select($sql, [$referenceGuide, $organizationId]);

            // Procesar resultados: asegurar que todos los niveles estén presentes para cada dominio
            $niveles = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy Alto'];
            $data = [];
            foreach ($results as $row) {
                $dom = $row->dominio_nombre;
                if (!isset($data[$dom])) {
                    // Inicializa todos los niveles en cero
                    $data[$dom] = [
                        'domain_name' => $dom,
                        'risk_levels' => array_fill_keys($niveles, 0)
                    ];
                }
                $data[$dom]['risk_levels'][$row->nivel_riesgo] = (int) $row->total_personas;
            }
            // Si algún dominio no tiene todos los niveles, los completa en cero
            foreach ($data as &$domData) {
                foreach ($niveles as $nivel) {
                    if (!isset($domData['risk_levels'][$nivel])) {
                        $domData['risk_levels'][$nivel] = 0;
                    }
                }
            }
            unset($domData);
            // Devuelve colección indexada
            return collect(array_values($data));
        } catch (\Throwable $e) {
            Log::error("Error al obtener la distribución de personas por nivel de riesgo y dominio: " . $e->getMessage());
            return collect();
        }
    }

    /**
     * Procesa los resultados de la consulta para estructurarlos por dominio
     *
     * @param Collection $results
     * @return Collection
     */
    private function processDomainResults(Collection $results, string $referenceGuide, $guidePersonalIds): Collection
    {
        $domainData = collect();

        // Agrupamos los resultados por dominio
        $groupedResults = $results->groupBy('domain_id');

        foreach ($groupedResults as $domainId => $domainResponses) {
            $domainName = $domainResponses->first()->domain_name;

            // Inicializamos contadores para cada tipo de respuesta
            $responsesByType = [
                'A' => 0, // Muy alto
                'B' => 0, // Alto
                'C' => 0, // Medio
                'D' => 0, // Bajo
                'E' => 0, // Nulo
            ];

            // Sumamos las personas únicas por tipo de respuesta
            foreach ($domainResponses as $response) {
                if (isset($responsesByType[$response->answer])) {
                    $responsesByType[$response->answer] = $response->count;
                }
            }

            // Calcular el total de personas únicas que respondieron cualquier pregunta en este dominio
            $totalUniquePersonsInDomain = Question::where('reference_guide', $referenceGuide)
                ->where('domain_id', $domainId)
                ->whereNotNull('answer')
                ->whereIn('personal_id', $guidePersonalIds)
                ->distinct('evaluation_id')
                ->count('evaluation_id');

            // Calculamos porcentajes
            $percentages = [];
            foreach ($responsesByType as $type => $count) {
                $percentages[$type] = $totalUniquePersonsInDomain > 0 ? ($count / $totalUniquePersonsInDomain) * 100 : 0;
            }

            // Añadimos al resultado
            $domainData->push([
                'id' => $domainId,
                'name' => $domainName,
                'responses' => $responsesByType,
                'percentages' => $percentages,
                'total' => $totalUniquePersonsInDomain
            ]);
        }

        return $domainData;
    }

    /**
     * Obtiene la suma total del valor de respuestas por dominio
     * Implementa el Query #4 de la guía III: Suma total del valor de respuestas por dominio
     *
     * @param string $referenceGuide
     * @return Collection
     */
    public function getDomainTotalScores(string $referenceGuide = 'III'): Collection
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

            // Ejecutar la consulta que obtiene la suma total por dominio
            $results = Question::join('evaluations', 'questions.evaluation_id', '=', 'evaluations.id')
                ->join('domains', 'questions.domain_id', '=', 'domains.id')
                ->where('questions.reference_guide', $referenceGuide)
                ->whereNotNull('questions.value')
                ->whereIn('questions.personal_id', $guidePersonalIds)
                ->select(
                    'domains.id as domain_id',
                    'domains.name as domain_name',
                    DB::raw('SUM(questions.value) as total_score'),
                    DB::raw('COUNT(*) as question_count')
                )
                ->groupBy('domains.id', 'domains.name')
                ->orderByDesc('total_score')
                ->get();

            // Procesar los resultados para añadir información adicional como promedios
            $processedResults = $results->map(function ($item) {
                $avgScore = $item->question_count > 0 ? $item->total_score / $item->question_count : 0;
                
                return [
                    'id' => $item->domain_id,
                    'name' => $item->domain_name,
                    'total_score' => $item->total_score,
                    'question_count' => $item->question_count,
                    'avg_score' => round($avgScore, 2)
                ];
            });

            return $processedResults;
            
        } catch (\Exception $e) {
            Log::error("Error al obtener la suma total de respuestas por dominio: " . $e->getMessage());
            return collect();
        }
    }
}
