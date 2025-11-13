<?php

namespace App\Services;

use App\Models\PaperEvaluation;

/**
 * Service for generating executive report data for NOM-035 compliance
 * Provides quantitative and qualitative analysis for executive-level reporting
 */
class ExecutiveReportService
{
    public function __construct(
        protected PaperEvaluationReportService $paperReportService,
        protected PaperEvaluationScoreService $scoreService
    ) {}

    /**
     * Get complete executive report data for an organization
     */
    public function getExecutiveReportData(string $organizationId): array
    {
        return [
            // 1. Análisis Cuantitativo de los Factores de Riesgo Psicosocial, Referencia: Calificación Final
            'analisis_cuantitativo_final' => $this->getAnalisisCuantitativoFinal($organizationId),

            // 2. Análisis Cuantitativo de Actos de Violencia Laboral
            'analisis_violencia_laboral' => $this->getAnalisisViolenciaLaboral($organizationId),

            // 3. Evaluación del Entorno Organizacional
            'evaluacion_entorno' => $this->getEvaluacionEntornoOrganizacional($organizationId),

            // 4. Análisis Cuantitativo de los Factores de Riesgo Psicosocial, Referencia: Dimensión (Tabla 6 - promedio por pregunta)
            'analisis_dimensiones' => $this->getAnalisisDimensiones($organizationId),

            // 5. Análisis Cualitativo de los Factores de Riesgo Psicosocial, Referencia: Calificación Final (Tabla 6)
            'analisis_cualitativo' => $this->getAnalisisCualitativo($organizationId),

            // 6. Identificación de los Trabajadores con Factores de Riesgo Psicosocial
            'identificacion_trabajadores_riesgo' => $this->getIdentificacionTrabajadoresRiesgo($organizationId),

            // 7. Identificación de los Trabajadores que fueron sujetos a Acontecimientos Traumáticos Severos
            'identificacion_trabajadores_trauma' => $this->getIdentificacionTrabajadoresTrauma($organizationId),

            // 8. Identificación de los Trabajadores Sujetos a Actos de Violencia Laboral
            'identificacion_trabajadores_violencia' => $this->getIdentificacionTrabajadoresViolencia($organizationId),

            // 9. Identificación de los Trabajadores con Factores de Riesgo Psicosocial por Calificación Final
            'identificacion_por_calificacion' => $this->getIdentificacionPorCalificacion($organizationId),

            // 10. Análisis Cuantitativo de los Dominios, Referencia: Calificación Final (Tabla 6)
            'analisis_dominios' => $this->getAnalisisDominios($organizationId),

            // 11. Identificación de los Trabajadores con Factores de Riesgo Psicosocial, Referencia: Categoría
            'identificacion_por_categoria' => $this->getIdentificacionPorCategoria($organizationId),
        ];
    }

    /**
     * 1. Análisis Cuantitativo de los Factores de Riesgo Psicosocial, Referencia: Calificación Final
     */
    protected function getAnalisisCuantitativoFinal(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        if ($evaluations->isEmpty()) {
            return $this->getEmptyFinalRiskDistribution();
        }

        $distribution = [
            'Nulo' => 0,
            'Bajo' => 0,
            'Medio' => 0,
            'Alto' => 0,
            'Muy Alto' => 0,
        ];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);
            $distribution[$riskLevel]++;
        }

        $total = $evaluations->count();

        return [
            'distribution' => $distribution,
            'total' => $total,
            'percentages' => $this->calculatePercentages($distribution, $total),
            'por_areas' => $this->getDistributionByAreas($organizationId),
            'por_puestos' => $this->getDistributionByPuestos($organizationId),
        ];
    }

    /**
     * 2. Análisis Cuantitativo de Actos de Violencia Laboral
     * Based on questions 57-64 from Referencia III
     */
    protected function getAnalisisViolenciaLaboral(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        if ($evaluations->isEmpty()) {
            return $this->getEmptyDistribution();
        }

        $distribution = [
            'Nulo' => 0,
            'Bajo' => 0,
            'Medio' => 0,
            'Alto' => 0,
            'Muy Alto' => 0,
        ];

        // Initialize question statistics for questions 57-64
        // Now we'll count by risk level per question score
        $questionStats = [];
        for ($q = 57; $q <= 64; $q++) {
            $questionStats[$q] = [
                'Nulo' => 0,
                'Bajo' => 0,
                'Medio' => 0,
                'Alto' => 0,
                'Muy Alto' => 0,
            ];
        }

        // Risk levels for individual question scores (0-4 points per question)
        $questionRiskLevels = [
            0 => 'Nulo',      // 0 points = Nulo
            1 => 'Bajo',      // 1 point = Bajo
            2 => 'Medio',     // 2 points = Medio
            3 => 'Alto',      // 3 points = Alto
            4 => 'Muy Alto',  // 4 points = Muy Alto
        ];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);

            // Find "Violencia" domain within the domains array
            if (isset($scores['domains'])) {
                foreach ($scores['domains'] as $domainKey => $domainData) {
                    if (stripos($domainData['name'], 'Violencia') !== false) {
                        $riskLevel = $this->getDomainRiskLevel($domainData['name'], $domainData['score']);
                        $distribution[$riskLevel]++;
                        break;
                    }
                }
            }

            // Collect statistics for each question (57-64) by individual score
            if (isset($scores['dimensions'])) {
                foreach ($scores['dimensions'] as $dimensionKey => $dimensionData) {
                    if (stripos($dimensionData['name'], 'Violencia laboral') !== false) {
                        foreach ($dimensionData['items'] as $item) {
                            $questionNum = $item['question_number'];
                            $score = $item['score']; // 0-4 points

                            if (isset($questionStats[$questionNum]) && isset($questionRiskLevels[$score])) {
                                $riskLevel = $questionRiskLevels[$score];
                                $questionStats[$questionNum][$riskLevel]++;
                            }
                        }
                        break;
                    }
                }
            }
        }

        $total = $evaluations->count();

        return [
            'distribution' => $distribution,
            'total' => $total,
            'percentages' => $this->calculatePercentages($distribution, $total),
            'question_stats' => $questionStats,
        ];
    }

    /**
     * 3. Evaluación del Entorno Organizacional
     */
    protected function getEvaluacionEntornoOrganizacional(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        if ($evaluations->isEmpty()) {
            return $this->getEmptyDistribution();
        }

        // Entorno Organizacional is a category in Referencia III
        $distribution = [
            'Nulo' => 0,
            'Bajo' => 0,
            'Medio' => 0,
            'Alto' => 0,
            'Muy Alto' => 0,
        ];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);

            // Find "Entorno organizacional" category
            foreach ($scores['categories'] as $categoryName => $categoryData) {
                if (stripos($categoryName, 'Entorno organizacional') !== false) {
                    $riskLevel = $this->getCategoryRiskLevel($categoryName, $categoryData['score']);
                    $distribution[$riskLevel]++;
                    break;
                }
            }
        }

        $total = $evaluations->count();

        return [
            'distribution' => $distribution,
            'total' => $total,
            'percentages' => $this->calculatePercentages($distribution, $total),
        ];
    }

    /**
     * 4. Análisis Cuantitativo de los Factores de Riesgo Psicosocial, Referencia: Dimensión (promedio por pregunta)
     */
    protected function getAnalisisDimensiones(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        if ($evaluations->isEmpty()) {
            return [];
        }

        // Get detailed results which include dimensions and items
        $dimensionScores = [];

        foreach ($evaluations as $evaluation) {
            $detailedResults = $this->scoreService->getDetailedResults($evaluation);

            foreach ($detailedResults as $row) {
                $dimensionName = $row['dimension'];
                $itemNumero = $row['item_numero'] ?? $row['item'];
                $puntaje = $row['puntaje'];

                if (! isset($dimensionScores[$dimensionName])) {
                    $dimensionScores[$dimensionName] = [];
                }

                if (! isset($dimensionScores[$dimensionName][$itemNumero])) {
                    $dimensionScores[$dimensionName][$itemNumero] = [
                        'scores' => [],
                        'item_text' => $row['item'],
                    ];
                }

                $dimensionScores[$dimensionName][$itemNumero]['scores'][] = $puntaje;
            }
        }

        // Calculate averages
        $result = [];
        foreach ($dimensionScores as $dimensionName => $items) {
            $dimensionData = [];

            foreach ($items as $itemNumero => $itemData) {
                $average = count($itemData['scores']) > 0
                    ? array_sum($itemData['scores']) / count($itemData['scores'])
                    : 0;
                $intAverage = (int) round($average); // entero para mostrar y colorear
                $riskLevel = match ($intAverage) {
                    0 => 'Nulo',
                    1 => 'Bajo',
                    2 => 'Medio',
                    3 => 'Alto',
                    4 => 'Muy Alto',
                    default => 'Nulo',
                };

                $dimensionData[] = [
                    'item_numero' => $itemNumero,
                    'item_text' => $itemData['item_text'],
                    'average_score' => $intAverage,
                    'risk_level' => $riskLevel,
                    'count' => count($itemData['scores']),
                ];
            }

            $result[$dimensionName] = $dimensionData;
        }

        return $result;
    }

    /**
     * 5. Análisis Cualitativo de los Factores de Riesgo Psicosocial (por género, funciones, áreas, jornada, puestos)
     */
    protected function getAnalisisCualitativo(string $organizationId): array
    {
        return [
            'por_genero' => $this->getDistributionByGender($organizationId),
            'por_funciones' => $this->getDistributionByFunctions($organizationId),
            'por_areas' => $this->getDistributionByAreas($organizationId),
            'por_jornada' => $this->getDistributionByJornada($organizationId),
            'por_puestos' => $this->getDistributionByPuestos($organizationId),
        ];
    }

    /**
     * 6. Identificación de los Trabajadores con Factores de Riesgo Psicosocial
     */
    protected function getIdentificacionTrabajadoresRiesgo(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        $riskWorkers = [
            'Medio' => [],
            'Alto' => [],
            'Muy Alto' => [],
        ];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);

            if (in_array($riskLevel, ['Medio', 'Alto', 'Muy Alto'])) {
                $riskWorkers[$riskLevel][] = [
                    'personal_folio' => $evaluation->personal_folio,
                    'name' => $evaluation->evaluee_name,
                    'score' => $scores['total_score'],
                ];
            }
        }

        return [
            'trabajadores' => $riskWorkers,
            'total_medio' => count($riskWorkers['Medio']),
            'total_alto' => count($riskWorkers['Alto']),
            'total_muy_alto' => count($riskWorkers['Muy Alto']),
            'total_riesgo' => count($riskWorkers['Medio']) + count($riskWorkers['Alto']) + count($riskWorkers['Muy Alto']),
        ];
    }

    /**
     * 7. Identificación de los Trabajadores que fueron sujetos a Acontecimientos Traumáticos Severos
     */
    protected function getIdentificacionTrabajadoresTrauma(string $organizationId): array
    {
        $evaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->whereNotNull('citsats_s1')
            ->get();

        $affectedWorkers = [];

        foreach ($evaluations as $evaluation) {
            $citsatsData = $evaluation->citsats_s1;
            $hasTrauma = false;

            if (is_array($citsatsData)) {
                foreach ($citsatsData as $answer) {
                    if (strtoupper($answer) === 'SI') {
                        $hasTrauma = true;
                        break;
                    }
                }
            }

            if ($hasTrauma) {
                $affectedWorkers[] = [
                    'personal_folio' => $evaluation->personal_folio,
                    'name' => $evaluation->evaluee_name,
                    'events' => $citsatsData,
                ];
            }
        }

        return [
            'trabajadores' => $affectedWorkers,
            'total_affected' => count($affectedWorkers),
        ];
    }

    /**
     * 8. Identificación de los Trabajadores Sujetos a Actos de Violencia Laboral
     */
    protected function getIdentificacionTrabajadoresViolencia(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        $affectedWorkers = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);

            // 1) Prefer Cisneros scale if present (explicit acts of violence)
            $cisneros = $evaluation->cisneros_answers ?? [];
            if (! empty($cisneros)) {
                $hasViolence = false;
                $violenceEvents = [];
                foreach ($cisneros as $questionId => $answer) {
                    if (is_string($answer) && strtoupper($answer) === 'SI') {
                        $hasViolence = true;
                        $violenceEvents[$questionId] = $answer;
                    }
                }
                if ($hasViolence) {
                    $affectedWorkers[] = [
                        'personal_folio' => $evaluation->personal_folio,
                        'name' => $evaluation->evaluee_name,
                        'fuente' => 'cisneros',
                        'nivel_riesgo' => null,
                        'puntaje_dominio' => null,
                        'events' => $violenceEvents,
                    ];

                    continue; // Already classified via Cisneros
                }
            }

            // 2) Fallback to Referencia III domain "Violencia" risk level (questions 57-64)
            $domainRiskLevel = null;
            $domainScore = null;
            $dimensionEvents = [];

            // Find domain "Violencia" and its dimension "Violencia laboral"
            if (isset($scores['domains'])) {
                foreach ($scores['domains'] as $domainData) {
                    if (stripos($domainData['name'], 'Violencia') !== false) {
                        $domainScore = $domainData['score'];
                        $domainRiskLevel = $this->getDomainRiskLevel($domainData['name'], $domainData['score']);
                        break;
                    }
                }
            }

            if ($domainRiskLevel && $domainRiskLevel !== 'Nulo') {
                // Collect answers for dimension items (57-64) for context
                if (isset($scores['dimensions'])) {
                    foreach ($scores['dimensions'] as $dimensionData) {
                        if (stripos($dimensionData['name'], 'Violencia laboral') !== false) {
                            foreach ($dimensionData['items'] as $item) {
                                // Consider an "evento" only when the scored answer indicates presencia (score >= 2)
                                if (isset($item['score']) && $item['score'] >= 2) {
                                    $dimensionEvents[$item['question_number']] = [
                                        'answer' => $item['answer'],
                                        'score' => $item['score'],
                                    ];
                                }
                            }
                            break;
                        }
                    }
                }

                $affectedWorkers[] = [
                    'personal_folio' => $evaluation->personal_folio,
                    'name' => $evaluation->evaluee_name,
                    'fuente' => 'referencia_iii',
                    'nivel_riesgo' => $domainRiskLevel,
                    'puntaje_dominio' => $domainScore,
                    'events' => $dimensionEvents,
                ];
            }
        }

        return [
            'trabajadores' => $affectedWorkers,
            'total_affected' => count($affectedWorkers),
            'detalle_niveles' => [
                'Medio' => count(array_filter($affectedWorkers, fn ($w) => $w['nivel_riesgo'] === 'Medio')),
                'Alto' => count(array_filter($affectedWorkers, fn ($w) => $w['nivel_riesgo'] === 'Alto')),
                'Muy Alto' => count(array_filter($affectedWorkers, fn ($w) => $w['nivel_riesgo'] === 'Muy Alto')),
            ],
        ];
    }

    /**
     * 9. Identificación de los Trabajadores con Factores de Riesgo Psicosocial por Calificación Final
     */
    protected function getIdentificacionPorCalificacion(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        $workers = [
            'Nulo' => [],
            'Bajo' => [],
            'Medio' => [],
            'Alto' => [],
            'Muy Alto' => [],
        ];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);

            $workers[$riskLevel][] = [
                'personal_folio' => $evaluation->personal_folio,
                'name' => $evaluation->evaluee_name,
                'score' => $scores['total_score'],
                'risk_factor' => $this->getRiskFactorCategory($riskLevel),
            ];
        }

        return [
            'trabajadores' => $workers,
            'counts' => [
                'Nulo' => count($workers['Nulo']),
                'Bajo' => count($workers['Bajo']),
                'Medio' => count($workers['Medio']),
                'Alto' => count($workers['Alto']),
                'Muy Alto' => count($workers['Muy Alto']),
            ],
        ];
    }

    /**
     * 10. Análisis Cuantitativo de los Dominios, Referencia: Calificación Final (Tabla 6)
     */
    protected function getAnalisisDominios(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        if ($evaluations->isEmpty()) {
            return [];
        }

        $domainDistribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);

            foreach ($scores['domains'] as $domainKey => $domainData) {
                $domainName = $domainData['name'];
                $riskLevel = $this->getDomainRiskLevel($domainName, $domainData['score']);

                if (! isset($domainDistribution[$domainName])) {
                    $domainDistribution[$domainName] = [
                        'Nulo' => 0,
                        'Bajo' => 0,
                        'Medio' => 0,
                        'Alto' => 0,
                        'Muy Alto' => 0,
                    ];
                }

                $domainDistribution[$domainName][$riskLevel]++;
            }
        }

        $total = $evaluations->count();

        // Add percentages for each domain
        $result = [];
        foreach ($domainDistribution as $domainName => $distribution) {
            $result[$domainName] = [
                'distribution' => $distribution,
                'percentages' => $this->calculatePercentages($distribution, $total),
            ];
        }

        return $result;
    }

    /**
     * 11. Identificación de los Trabajadores con Factores de Riesgo Psicosocial, Referencia: Categoría
     */
    protected function getIdentificacionPorCategoria(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);

        if ($evaluations->isEmpty()) {
            return [];
        }

        $categoryWorkers = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);

            foreach ($scores['categories'] as $categoryName => $categoryData) {
                $riskLevel = $this->getCategoryRiskLevel($categoryName, $categoryData['score']);

                if (! isset($categoryWorkers[$categoryName])) {
                    $categoryWorkers[$categoryName] = [
                        'Nulo' => [],
                        'Bajo' => [],
                        'Medio' => [],
                        'Alto' => [],
                        'Muy Alto' => [],
                    ];
                }

                $categoryWorkers[$categoryName][$riskLevel][] = [
                    'personal_folio' => $evaluation->personal_folio,
                    'name' => $evaluation->evaluee_name,
                    'score' => $categoryData['score'],
                ];
            }
        }

        // Add counts
        $result = [];
        foreach ($categoryWorkers as $categoryName => $workers) {
            $result[$categoryName] = [
                'trabajadores' => $workers,
                'counts' => [
                    'Nulo' => count($workers['Nulo']),
                    'Bajo' => count($workers['Bajo']),
                    'Medio' => count($workers['Medio']),
                    'Alto' => count($workers['Alto']),
                    'Muy Alto' => count($workers['Muy Alto']),
                ],
            ];
        }

        return $result;
    }

    // Helper methods

    protected function getCompletedEvaluations(string $organizationId)
    {
        return PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->get();
    }

    protected function getDistributionByGender(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);
        $referenciaVEvaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('evaluation_type', 'referencia_v')
            ->get()
            ->keyBy('personal_folio');

        $distribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);

            $referenciaV = $referenciaVEvaluations->get($evaluation->personal_folio);
            $gender = 'No especificado';

            if ($referenciaV) {
                $demographicData = $referenciaV->demographic_data ?? [];
                $sexo = $demographicData['sexo'] ?? null;

                if (is_array($sexo)) {
                    $gender = $sexo['value'] ?? $sexo['label'] ?? 'No especificado';
                } elseif ($sexo) {
                    $gender = $sexo;
                }
            }

            if (! isset($distribution[$gender])) {
                $distribution[$gender] = $this->getEmptyDistribution()['distribution'];
            }

            $distribution[$gender][$riskLevel]++;
        }

        return $distribution;
    }

    protected function getDistributionByFunctions(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);
        $referenciaVEvaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('evaluation_type', 'referencia_v')
            ->get()
            ->keyBy('personal_folio');

        $distribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);

            $referenciaV = $referenciaVEvaluations->get($evaluation->personal_folio);
            $function = 'No especificado';

            if ($referenciaV) {
                $demographicData = $referenciaV->demographic_data ?? [];
                $tipoContrato = $demographicData['tipo_contrato'] ?? null;

                if (is_array($tipoContrato)) {
                    $function = $tipoContrato['value'] ?? $tipoContrato['label'] ?? 'No especificado';
                } elseif ($tipoContrato) {
                    $function = $tipoContrato;
                }
            }

            if (! isset($distribution[$function])) {
                $distribution[$function] = $this->getEmptyDistribution()['distribution'];
            }

            $distribution[$function][$riskLevel]++;
        }

        return $distribution;
    }

    protected function getDistributionByAreas(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);
        $referenciaVEvaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('evaluation_type', 'referencia_v')
            ->get()
            ->keyBy('personal_folio');

        $distribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);

            $area = $this->extractArea($evaluation, $referenciaVEvaluations);

            if (! isset($distribution[$area])) {
                $distribution[$area] = $this->getEmptyDistribution()['distribution'];
            }

            $distribution[$area][$riskLevel]++;
        }

        return $distribution;
    }

    protected function getDistributionByJornada(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);
        $referenciaVEvaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('evaluation_type', 'referencia_v')
            ->get()
            ->keyBy('personal_folio');

        $distribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);

            $referenciaV = $referenciaVEvaluations->get($evaluation->personal_folio);
            $jornada = 'No especificado';

            if ($referenciaV) {
                $demographicData = $referenciaV->demographic_data ?? [];
                $tipoJornada = $demographicData['tipo_jornada'] ?? null;

                if (is_array($tipoJornada)) {
                    $jornada = $tipoJornada['value'] ?? $tipoJornada['label'] ?? 'No especificado';
                } elseif ($tipoJornada) {
                    $jornada = $tipoJornada;
                }
            }

            if (! isset($distribution[$jornada])) {
                $distribution[$jornada] = $this->getEmptyDistribution()['distribution'];
            }

            $distribution[$jornada][$riskLevel]++;
        }

        return $distribution;
    }

    protected function getDistributionByPuestos(string $organizationId): array
    {
        $evaluations = $this->getCompletedEvaluations($organizationId);
        $referenciaVEvaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('evaluation_type', 'referencia_v')
            ->get()
            ->keyBy('personal_folio');

        $distribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);

            $puesto = $this->extractPuesto($evaluation, $referenciaVEvaluations);

            if (! isset($distribution[$puesto])) {
                $distribution[$puesto] = $this->getEmptyDistribution()['distribution'];
            }

            $distribution[$puesto][$riskLevel]++;
        }

        return $distribution;
    }

    protected function extractArea($evaluation, $referenciaVEvaluations): string
    {
        $referenciaV = $referenciaVEvaluations->get($evaluation->personal_folio);

        if ($referenciaV && isset($referenciaV->demographic_data['departamento'])) {
            $departamento = $referenciaV->demographic_data['departamento'];

            if (is_array($departamento)) {
                $area = trim(($departamento['fila1'] ?? '').' '.($departamento['fila2'] ?? ''));

                return $area ?: 'Sin área';
            }

            return $departamento ?: 'Sin área';
        }

        return 'Sin área';
    }

    protected function extractPuesto($evaluation, $referenciaVEvaluations): string
    {
        $referenciaV = $referenciaVEvaluations->get($evaluation->personal_folio);

        if ($referenciaV && isset($referenciaV->demographic_data['ocupacion'])) {
            $ocupacion = $referenciaV->demographic_data['ocupacion'];

            if (is_array($ocupacion)) {
                $puesto = trim(($ocupacion['fila1'] ?? '').' '.($ocupacion['fila2'] ?? ''));

                return $puesto ?: 'Sin puesto';
            }

            return $ocupacion ?: 'Sin puesto';
        }

        return 'Sin puesto';
    }

    protected function getCategoryRiskLevel(string $categoryName, int $score): string
    {
        return $this->scoreService->calculateCategoryRiskLevel($categoryName, $score);
    }

    protected function getDomainRiskLevel(string $domainName, int $score): string
    {
        return $this->scoreService->calculateDomainRiskLevel($domainName, $score);
    }

    protected function getRiskFactorCategory(string $riskLevel): string
    {
        return match ($riskLevel) {
            'Muy Alto', 'Alto' => 'Factor de Riesgo Alto',
            'Medio' => 'Factor de Riesgo Medio',
            'Bajo' => 'Factor de Riesgo Bajo',
            default => 'Sin Riesgo',
        };
    }

    protected function calculatePercentages(array $distribution, int $total): array
    {
        if ($total === 0) {
            return array_fill_keys(array_keys($distribution), 0);
        }

        $percentages = [];
        foreach ($distribution as $level => $count) {
            $percentages[$level] = round(($count / $total) * 100, 2);
        }

        return $percentages;
    }

    protected function getEmptyFinalRiskDistribution(): array
    {
        return [
            'distribution' => [
                'Nulo' => 0,
                'Bajo' => 0,
                'Medio' => 0,
                'Alto' => 0,
                'Muy Alto' => 0,
            ],
            'total' => 0,
            'percentages' => [
                'Nulo' => 0,
                'Bajo' => 0,
                'Medio' => 0,
                'Alto' => 0,
                'Muy Alto' => 0,
            ],
        ];
    }

    protected function getEmptyDistribution(): array
    {
        return [
            'distribution' => [
                'Nulo' => 0,
                'Bajo' => 0,
                'Medio' => 0,
                'Alto' => 0,
                'Muy Alto' => 0,
            ],
        ];
    }
}
