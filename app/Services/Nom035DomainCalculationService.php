<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PaperEvaluation;

class Nom035DomainCalculationService
{
    /**
     * Calcular estadísticas de dominios NOM-035 para una organización
     */
    public function calculateDomainStatistics(Organization $organization): array
    {
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $domainScores = [];
        $domainDistributions = [];

        // Preparar estructura para cada dominio (no categoría)
        foreach ($domainConfig as $categoryName => $domains) {
            foreach ($domains as $domainName => $dimensions) {
                $domainScores[$domainName] = [];
                $domainDistributions[$domainName] = [
                    'nulo' => 0,
                    'bajo' => 0,
                    'medio' => 0,
                    'alto' => 0,
                    'muy_alto' => 0,
                ];
            }
        }

        // Calcular puntajes por evaluación
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($domainConfig as $categoryName => $domains) {
                foreach ($domains as $domainName => $dimensions) {
                    // Pasar solo el dominio específico con sus dimensiones
                    $score = $this->calculateDomainScore($answers, [$domainName => $dimensions]);
                    $domainScores[$domainName][] = $score;

                    // Clasificar en nivel de riesgo
                    $level = $this->getRiskLevel($score, $domainName, $riskLevels);
                    $domainDistributions[$domainName][$level]++;
                }
            }
        }

        // Calcular promedios y preparar respuesta
        $result = [];
        foreach ($domainScores as $domainName => $scores) {
            $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
            $maxScore = $riskLevels['domains'][$domainName]['max_score'] ?? 0;
            $averageLevel = $this->getRiskLevel($average, $domainName, $riskLevels);

            $result[$domainName] = [
                'average_score' => round($average, 2),
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($average / $maxScore) * 100, 2) : 0,
                'risk_level' => $averageLevel,
                'risk_level_label' => $riskLevels['labels'][$averageLevel],
                'distribution' => $domainDistributions[$domainName],
                'total_evaluations' => count($scores),
            ];
        }

        return [
            'domains' => $result,
            'total_evaluations' => $evaluations->count(),
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Calcular estadísticas de categorías NOM-035 para una organización
     * NOTA: Calcula las 5 CATEGORÍAS REALES (Nivel 1) de NOM-035
     */
    public function calculateCategoryStatistics(Organization $organization): array
    {
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyCategoryStatistics();
        }

        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $categoryScores = [];
        $categoryDistributions = [];
        $categoryDomainCount = [];

        // Preparar estructura para cada CATEGORÍA REAL (las 5 grandes)
        foreach ($domainConfig as $categoryName => $domains) {
            $categoryScores[$categoryName] = [];
            $categoryDistributions[$categoryName] = [
                'nulo' => 0,
                'bajo' => 0,
                'medio' => 0,
                'alto' => 0,
                'muy_alto' => 0,
            ];
            $categoryDomainCount[$categoryName] = count($domains);
        }

        // Calcular puntajes por evaluación
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            // Iterar por cada CATEGORÍA REAL (las 5)
            foreach ($domainConfig as $categoryName => $domains) {
                $categoryScore = 0;

                // Sumar puntajes de todos los dominios dentro de esta categoría
                foreach ($domains as $domainName => $dimensions) {
                    $domainScore = $this->calculateDomainScore($answers, [$domainName => $dimensions]);
                    $categoryScore += $domainScore;
                }

                $categoryScores[$categoryName][] = $categoryScore;

                // Clasificar en nivel de riesgo usando los niveles de categoría
                $level = $this->getCategoryRiskLevel($categoryScore, $categoryName, $riskLevels);
                $categoryDistributions[$categoryName][$level]++;
            }
        }

        // Calcular promedios y preparar respuesta
        $result = [];
        foreach ($categoryScores as $categoryName => $scores) {
            $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

            // Obtener max_score de la configuración de categorías
            $maxScore = $this->getCategoryMaxScore($categoryName, $riskLevels);
            $averageLevel = $this->getCategoryRiskLevel($average, $categoryName, $riskLevels);

            $result[$categoryName] = [
                'average_score' => round($average, 2),
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($average / $maxScore) * 100, 2) : 0,
                'risk_level' => $averageLevel,
                'risk_level_label' => $riskLevels['labels'][$averageLevel],
                'distribution' => $categoryDistributions[$categoryName],
                'total_evaluations' => count($scores),
                'domain_count' => $categoryDomainCount[$categoryName],
            ];
        }

        return [
            'categories' => $result,
            'total_evaluations' => $evaluations->count(),
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Calcular estadísticas de dimensiones NOM-035 para una organización
     */
    public function calculateDimensionStatistics(Organization $organization): array
    {
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyDimensionStatistics();
        }

        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $dimensionScores = [];
        $dimensionDistributions = [];
        $dimensionDomains = [];
        $dimensionCategories = [];

        // Preparar estructura para cada dimensión
        foreach ($domainConfig as $categoryName => $domains) {
            foreach ($domains as $domainName => $dimensions) {
                foreach ($dimensions as $dimensionName => $questions) {
                    $dimensionScores[$dimensionName] = [];
                    $dimensionDistributions[$dimensionName] = [
                        'nulo' => 0,
                        'bajo' => 0,
                        'medio' => 0,
                        'alto' => 0,
                        'muy_alto' => 0,
                    ];
                    $dimensionDomains[$dimensionName] = $domainName;
                    $dimensionCategories[$dimensionName] = $categoryName;
                }
            }
        }

        // Calcular puntajes por evaluación
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($domainConfig as $categoryName => $domains) {
                foreach ($domains as $domainName => $dimensions) {
                    foreach ($dimensions as $dimensionName => $questions) {
                        $score = $this->calculateDimensionScore($answers, $questions);
                        $dimensionScores[$dimensionName][] = $score;

                        // Clasificar en nivel de riesgo usando los niveles de dimensión
                        $level = $this->getDimensionRiskLevel($score, $dimensionName, $riskLevels);
                        $dimensionDistributions[$dimensionName][$level]++;
                    }
                }
            }
        }

        // Calcular promedios y preparar respuesta
        $result = [];
        foreach ($dimensionScores as $dimensionName => $scores) {
            $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

            // Obtener el número de preguntas de esta dimensión
            $categoryName = $dimensionCategories[$dimensionName];
            $domainName = $dimensionDomains[$dimensionName];
            $questionCount = isset($domainConfig[$categoryName][$domainName][$dimensionName])
                ? count($domainConfig[$categoryName][$domainName][$dimensionName])
                : 0;
            $maxScore = $questionCount * 4;

            $averageLevel = $this->getDimensionRiskLevel($average, $dimensionName, $riskLevels);

            $result[$dimensionName] = [
                'average_score' => round($average, 2),
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($average / $maxScore) * 100, 2) : 0,
                'risk_level' => $averageLevel,
                'risk_level_label' => $riskLevels['labels'][$averageLevel],
                'distribution' => $dimensionDistributions[$dimensionName],
                'total_evaluations' => count($scores),
                'domain' => $dimensionDomains[$dimensionName],
                'category' => $dimensionCategories[$dimensionName],
            ];
        }

        return [
            'dimensions' => $result,
            'total_evaluations' => $evaluations->count(),
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Calcular estadísticas globales NOM-035 para una organización
     */
    public function calculateGlobalStatistics(Organization $organization): array
    {
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyGlobalStatistics();
        }

        $riskLevels = config('nom035_risk_levels');
        $globalScores = [];
        $globalDistribution = [
            'nulo' => 0,
            'bajo' => 0,
            'medio' => 0,
            'alto' => 0,
            'muy_alto' => 0,
        ];

        // Calcular puntaje total para cada evaluación
        foreach ($evaluations as $evaluation) {
            $totalScore = $this->calculateTotalScore($evaluation);
            $globalScores[] = $totalScore;

            // Clasificar en nivel de riesgo global
            $level = $this->getGlobalRiskLevel($totalScore, $riskLevels);
            $globalDistribution[$level]++;
        }

        // Calcular promedio
        $average = count($globalScores) > 0 ? array_sum($globalScores) / count($globalScores) : 0;
        $maxScore = $riskLevels['global']['max_score'];
        $averageLevel = $this->getGlobalRiskLevel($average, $riskLevels);

        return [
            'global' => [
                'average_score' => round($average, 2),
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($average / $maxScore) * 100, 2) : 0,
                'risk_level' => $averageLevel,
                'risk_level_label' => $riskLevels['labels'][$averageLevel],
                'distribution' => $globalDistribution,
                'total_evaluations' => count($globalScores),
            ],
            'total_evaluations' => $evaluations->count(),
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Calcular puntaje para un dominio específico
     */
    private function calculateDomainScore(array $answers, array $categories): int
    {
        $score = 0;
        $answerValues = config('answer_values');

        foreach ($categories as $categoryName => $subcategories) {
            foreach ($subcategories as $subcategoryName => $questions) {
                foreach ($questions as $questionNumber) {
                    $answer = $answers[$questionNumber] ?? null;

                    if ($answer === null) {
                        continue;
                    }

                    // Determinar si la pregunta está en grupo 1 o 2
                    $group = in_array(str_pad($questionNumber, 2, '0', STR_PAD_LEFT), $answerValues['group1']['questions'])
                        ? 'group1'
                        : 'group2';

                    $score += $answerValues[$group]['values'][$answer] ?? 0;
                }
            }
        }

        return $score;
    }

    /**
     * Calcular puntaje para una categoría específica
     */
    private function calculateCategoryScore(array $answers, array $subcategories): int
    {
        $score = 0;
        $answerValues = config('answer_values');

        foreach ($subcategories as $subcategoryName => $questions) {
            foreach ($questions as $questionNumber) {
                $answer = $answers[$questionNumber] ?? null;

                if ($answer === null) {
                    continue;
                }

                // Determinar si la pregunta está en grupo 1 o 2
                $group = in_array(str_pad($questionNumber, 2, '0', STR_PAD_LEFT), $answerValues['group1']['questions'])
                    ? 'group1'
                    : 'group2';

                $score += $answerValues[$group]['values'][$answer] ?? 0;
            }
        }

        return $score;
    }

    /**
     * Determinar nivel de riesgo según puntaje
     */
    private function getRiskLevel(float $score, string $domainName, array $riskLevels): string
    {
        $levels = $riskLevels['domains'][$domainName]['levels'];

        foreach ($levels as $levelName => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $levelName;
            }
        }

        return 'nulo';
    }

    /**
     * Obtener nivel de riesgo para una categoría
     */
    private function getCategoryRiskLevel(float $score, string $categoryName, array $riskLevels): string
    {
        // Usar niveles de categoría si existen, si no usar niveles de dominio
        if (isset($riskLevels['categories'][$categoryName]['levels'])) {
            $levels = $riskLevels['categories'][$categoryName]['levels'];
        } else {
            // Fallback a niveles de dominio si no existe configuración de categoría
            $levels = $riskLevels['domains'][$categoryName]['levels'] ?? [];
        }

        foreach ($levels as $levelName => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $levelName;
            }
        }

        return 'nulo';
    }

    /**
     * Obtener el max_score de una categoría desde la configuración
     */
    private function getCategoryMaxScore(string $categoryName, array $riskLevels): int
    {
        // Buscar en la configuración de categorías
        if (isset($riskLevels['categories'][$categoryName])) {
            // Si no hay max_score explícito, calcular desde los dominios
            $domainConfig = config('question_dimensions');
            $totalQuestions = 0;

            if (isset($domainConfig[$categoryName])) {
                foreach ($domainConfig[$categoryName] as $domainName => $dimensions) {
                    foreach ($dimensions as $dimensionName => $questions) {
                        $totalQuestions += count($questions);
                    }
                }
            }

            return $totalQuestions * 4; // 4 puntos máximo por pregunta
        }

        return 0;
    }

    /**
     * Calcular el max_score de una categoría sumando preguntas de sus dimensiones
     */
    private function calculateCategoryMaxScore(string $categoryName, array $domainConfig): int
    {
        $totalQuestions = 0;

        foreach ($domainConfig as $categories) {
            if (isset($categories[$categoryName])) {
                foreach ($categories[$categoryName] as $dimensions) {
                    $totalQuestions += count($dimensions);
                }
            }
        }

        return $totalQuestions * 4; // 4 puntos máximo por pregunta
    }

    /**
     * Calcular puntaje para una dimensión específica
     */
    private function calculateDimensionScore(array $answers, array $questions): int
    {
        $score = 0;
        $answerValues = config('answer_values');

        foreach ($questions as $questionNumber) {
            $answer = $answers[$questionNumber] ?? null;

            if ($answer === null) {
                continue;
            }

            // Determinar si la pregunta está en grupo 1 o 2
            $group = in_array(str_pad($questionNumber, 2, '0', STR_PAD_LEFT), $answerValues['group1']['questions'])
                ? 'group1'
                : 'group2';

            $score += $answerValues[$group]['values'][$answer] ?? 0;
        }

        return $score;
    }

    /**
     * Obtener nivel de riesgo para una dimensión
     */
    private function getDimensionRiskLevel(float $score, string $dimensionName, array $riskLevels): string
    {
        if (isset($riskLevels['dimensions'][$dimensionName]['levels'])) {
            $levels = $riskLevels['dimensions'][$dimensionName]['levels'];
        } else {
            // Fallback a nulo si no existe configuración
            return 'nulo';
        }

        foreach ($levels as $levelName => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $levelName;
            }
        }

        return 'nulo';
    }

    /**
     * Obtener nivel de riesgo global
     */
    private function getGlobalRiskLevel(float $score, array $riskLevels): string
    {
        $levels = $riskLevels['global']['levels'];

        foreach ($levels as $levelName => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $levelName;
            }
        }

        return 'nulo';
    }

    /**
     * Retornar estructura vacía cuando no hay evaluaciones
     */
    private function getEmptyStatistics(): array
    {
        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $domains = [];
        // Iterar correctamente sobre categorías -> dominios (no solo categorías)
        foreach ($domainConfig as $categoryName => $domainsInCategory) {
            foreach ($domainsInCategory as $domainName => $dimensions) {
                $domains[$domainName] = [
                    'average_score' => 0,
                    'max_score' => $riskLevels['domains'][$domainName]['max_score'] ?? 0,
                    'percentage' => 0,
                    'risk_level' => 'nulo',
                    'risk_level_label' => $riskLevels['labels']['nulo'],
                    'distribution' => [
                        'nulo' => 0,
                        'bajo' => 0,
                        'medio' => 0,
                        'alto' => 0,
                        'muy_alto' => 0,
                    ],
                    'total_evaluations' => 0,
                ];
            }
        }

        return [
            'domains' => $domains,
            'total_evaluations' => 0,
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Get evaluations with demographics and scores for analysis.
     */
    public function getEvaluationsWithDemographicsAndScores(Organization $organization): array
    {
        // Fetch evaluations with demographic data and conditional data
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereNotNull('referencia_iii_answers')
            ->where('processing_status', 'completed')
            ->with(['demographicData:id,paper_evaluation_id,gender,position,department,work_schedule,contract_type'])
            ->select(['id', 'folio', 'personal_folio', 'evaluee_name', 'referencia_iii_answers', 'referencia_iii_conditional', 'organization_id'])
            ->get();

        $evaluationsData = [];
        $availableDemographics = [
            'generos' => [],
            'puestos' => [],
            'areas' => [],
            'turnos' => [],
        ];

        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        foreach ($evaluations as $evaluation) {
            $demographics = [
                'genero' => $evaluation->demographicData->gender ?? 'No especificado',
                'puesto' => $evaluation->demographicData->position ?? 'No especificado',
                'area' => $evaluation->demographicData->department ?? 'No especificado',
                'turno' => $evaluation->demographicData->work_schedule ?? 'No especificado',
            ];

            // Collect unique demographic values
            if (! in_array($demographics['genero'], $availableDemographics['generos'])) {
                $availableDemographics['generos'][] = $demographics['genero'];
            }
            if (! in_array($demographics['puesto'], $availableDemographics['puestos'])) {
                $availableDemographics['puestos'][] = $demographics['puesto'];
            }
            if (! in_array($demographics['area'], $availableDemographics['areas'])) {
                $availableDemographics['areas'][] = $demographics['area'];
            }
            if (! in_array($demographics['turno'], $availableDemographics['turnos'])) {
                $availableDemographics['turnos'][] = $demographics['turno'];
            }

            // Calculate domain scores - iterating correctly through category → domain structure
            $domainScores = [];
            foreach ($domainConfig as $categoryName => $domains) {
                foreach ($domains as $domainName => $dimensions) {
                    $score = $this->calculateDomainScore(
                        $evaluation->referencia_iii_answers,
                        [$domainName => $dimensions]
                    );
                    $riskLevel = $this->getRiskLevel($score, $domainName, $riskLevels);

                    $domainScores[$domainName] = [
                        'score' => $score,
                        'risk_level' => $riskLevel,
                        'category' => $categoryName,
                    ];
                }
            }

            // Calculate category scores - same correct structure
            $categoryScores = [];
            foreach ($domainConfig as $categoryName => $domains) {
                // Calculate category score by aggregating all domains within this category
                $categoryDimensions = [];
                foreach ($domains as $domainName => $dimensions) {
                    $categoryDimensions = array_merge($categoryDimensions, $dimensions);
                }

                $score = $this->calculateCategoryScore(
                    $evaluation->referencia_iii_answers,
                    $categoryDimensions
                );

                // Get first domain name to determine risk level thresholds (categories use same scale as first domain)
                $firstDomainName = array_key_first($domains);
                $riskLevel = $this->getRiskLevel($score, $firstDomainName, $riskLevels);

                $categoryScores[$categoryName] = [
                    'score' => $score,
                    'risk_level' => $riskLevel,
                ];
            }

            // Calculate total score (same as calculateReferenciaIIIScores)
            $totalScore = $this->calculateTotalScore($evaluation);

            $evaluationsData[] = [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'personal_folio' => $evaluation->personal_folio,
                'evaluee_name' => $evaluation->evaluee_name,
                'demographics' => $demographics,
                'domain_scores' => $domainScores,
                'category_scores' => $categoryScores,
                'total_score' => $totalScore,
            ];
        }

        // Sort demographic values
        sort($availableDemographics['generos']);
        sort($availableDemographics['puestos']);
        sort($availableDemographics['areas']);
        sort($availableDemographics['turnos']);

        return [
            'evaluations' => $evaluationsData,
            'demographics' => $availableDemographics,
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Retornar estructura vacía de categorías cuando no hay evaluaciones
     */
    private function getEmptyCategoryStatistics(): array
    {
        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $categories = [];
        // Nota: Las variables tienen nombres confusos heredados, pero mantienen compatibilidad
        // $domainName = realmente es una CATEGORÍA en la estructura question_dimensions
        // $categoriesInDomain = realmente son los DOMINIOS
        // $categoryName = realmente es un DOMINIO
        foreach ($domainConfig as $domainName => $categoriesInDomain) {
            foreach (array_keys($categoriesInDomain) as $categoryName) {
                // Calcular max_score correctamente sumando todas las preguntas de las dimensiones
                $maxScore = $this->calculateCategoryMaxScore($categoryName, $domainConfig);

                $categories[$categoryName] = [
                    'average_score' => 0,
                    'max_score' => $maxScore,
                    'percentage' => 0,
                    'risk_level' => 'nulo',
                    'risk_level_label' => $riskLevels['labels']['nulo'],
                    'distribution' => [
                        'nulo' => 0,
                        'bajo' => 0,
                        'medio' => 0,
                        'alto' => 0,
                        'muy_alto' => 0,
                    ],
                    'total_evaluations' => 0,
                    'domain' => $domainName,
                ];
            }
        }

        return [
            'categories' => $categories,
            'total_evaluations' => 0,
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Retornar estructura vacía para dimensiones cuando no hay evaluaciones
     */
    private function getEmptyDimensionStatistics(): array
    {
        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $dimensions = [];
        foreach ($domainConfig as $categoryName => $domains) {
            foreach ($domains as $domainName => $dimensionsInDomain) {
                foreach ($dimensionsInDomain as $dimensionName => $questions) {
                    $dimensions[$dimensionName] = [
                        'average_score' => 0,
                        'max_score' => count($questions) * 4,
                        'percentage' => 0,
                        'risk_level' => 'nulo',
                        'risk_level_label' => $riskLevels['labels']['nulo'],
                        'distribution' => [
                            'nulo' => 0,
                            'bajo' => 0,
                            'medio' => 0,
                            'alto' => 0,
                            'muy_alto' => 0,
                        ],
                        'total_evaluations' => 0,
                        'domain' => $domainName,
                        'category' => $categoryName,
                    ];
                }
            }
        }

        return [
            'dimensions' => $dimensions,
            'total_evaluations' => 0,
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Retornar estructura vacía para global cuando no hay evaluaciones
     */
    private function getEmptyGlobalStatistics(): array
    {
        $riskLevels = config('nom035_risk_levels');

        return [
            'global' => [
                'average_score' => 0,
                'max_score' => $riskLevels['global']['max_score'],
                'percentage' => 0,
                'risk_level' => 'nulo',
                'risk_level_label' => $riskLevels['labels']['nulo'],
                'distribution' => [
                    'nulo' => 0,
                    'bajo' => 0,
                    'medio' => 0,
                    'alto' => 0,
                    'muy_alto' => 0,
                ],
                'total_evaluations' => 0,
            ],
            'total_evaluations' => 0,
            'colors' => $riskLevels['colors'],
            'labels' => $riskLevels['labels'],
        ];
    }

    /**
     * Calcular estadísticas por pregunta individual NOM-035 para una organización
     */
    public function calculateQuestionStatistics(Organization $organization): array
    {
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyQuestionStatistics();
        }

        $questionsConfig = config('referencia_iii.general');
        $domainConfig = config('question_dimensions');
        $answerValues = config('answer_values');

        // Mapeo de letras a etiquetas de respuesta
        $responseLabels = [
            'A' => 'siempre',
            'B' => 'casi_siempre',
            'C' => 'algunas_veces',
            'D' => 'casi_nunca',
            'E' => 'nunca',
        ];

        // Preparar estructura para estadísticas por pregunta
        $questionStats = [];
        foreach ($questionsConfig as $questionNumber => $questionText) {
            $questionStats[$questionNumber] = [
                'number' => $questionNumber,
                'text' => $questionText,
                'responses' => [
                    'siempre' => 0,
                    'casi_siempre' => 0,
                    'algunas_veces' => 0,
                    'casi_nunca' => 0,
                    'nunca' => 0,
                ],
                'scores' => [],
                'dimension' => '',
                'domain' => '',
                'category' => '',
            ];
        }

        // Mapear preguntas a su dimensión/dominio/categoría
        foreach ($domainConfig as $categoryName => $domains) {
            foreach ($domains as $domainName => $dimensions) {
                foreach ($dimensions as $dimensionName => $questions) {
                    foreach ($questions as $questionNumber) {
                        if (isset($questionStats[$questionNumber])) {
                            $questionStats[$questionNumber]['dimension'] = $dimensionName;
                            $questionStats[$questionNumber]['domain'] = $domainName;
                            $questionStats[$questionNumber]['category'] = $categoryName;
                        }
                    }
                }
            }
        }

        // Procesar respuestas de cada evaluación
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($answers as $questionNumber => $answer) {
                // Skip arrays (conditional answers container)
                if (is_array($answer)) {
                    continue;
                }

                if (isset($questionStats[$questionNumber]) && $answer !== null && isset($responseLabels[$answer])) {
                    // Contar respuestas usando el mapeo de letras
                    $responseLabel = $responseLabels[$answer];
                    $questionStats[$questionNumber]['responses'][$responseLabel]++;

                    // Guardar puntaje para esta pregunta
                    $questionKey = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
                    $group = in_array($questionKey, $answerValues['group1']['questions'])
                        ? 'group1'
                        : 'group2';

                    $score = $answerValues[$group]['values'][$answer] ?? 0;
                    $questionStats[$questionNumber]['scores'][] = $score;
                }
            }
        }

        // Calcular promedios y criticidad
        $result = [];
        foreach ($questionStats as $questionNumber => $stats) {
            if (count($stats['scores']) === 0) {
                continue; // Skip questions with no responses
            }

            $averageScore = array_sum($stats['scores']) / count($stats['scores']);
            $maxScore = 4; // Maximum score per question

            // Determinar criticidad basado en respuestas negativas
            $negativeResponses = ($stats['responses']['casi_nunca'] + $stats['responses']['nunca']);
            $totalResponses = array_sum($stats['responses']);
            $negativePercentage = $totalResponses > 0 ? ($negativeResponses / $totalResponses) * 100 : 0;

            // Clasificar criticidad
            $criticality = 'low';
            if ($negativePercentage >= 50) {
                $criticality = 'critical';
            } elseif ($negativePercentage >= 30) {
                $criticality = 'high';
            } elseif ($negativePercentage >= 15) {
                $criticality = 'medium';
            }

            $result[$questionNumber] = [
                'number' => $questionNumber,
                'text' => $stats['text'],
                'category' => $stats['category'],
                'domain' => $stats['domain'],
                'dimension' => $stats['dimension'],
                'responses' => $stats['responses'],
                'averageScore' => round($averageScore, 2),
                'maxScore' => $maxScore,
                'percentage' => round(($averageScore / $maxScore) * 100, 2),
                'criticality' => $criticality,
                'totalResponses' => $totalResponses,
            ];
        }

        // Sort by question number
        ksort($result);

        // Force object serialization by using stdClass for numeric keys
        // This prevents JSON from converting to array when keys are numeric
        return [
            'questions' => empty($result) ? new \stdClass : (object) $result,
            'total_evaluations' => $evaluations->count(),
        ];
    }

    /**
     * Retornar estructura vacía para preguntas cuando no hay evaluaciones
     */
    private function getEmptyQuestionStatistics(): array
    {
        return [
            'questions' => [],
            'total_evaluations' => 0,
        ];
    }

    /**
     * Calculate total score by summing all answer values (including conditional questions)
     * This matches the logic in PaperEvaluationScoreService::calculateReferenciaIIIScores()
     */
    private function calculateTotalScore(PaperEvaluation $evaluation): int
    {
        $answers = $evaluation->referencia_iii_answers ?? [];
        $conditionalAnswers = $evaluation->referencia_iii_conditional ?? [];
        $answerValues = config('answer_values');
        $totalScore = 0;

        // Check if person is a manager (answered "SI" to management question)
        $isManager = isset($conditionalAnswers['management']['condition'])
            && $conditionalAnswers['management']['condition'] === 'SI';

        // Get management questions if applicable
        $managementQuestions = [];
        if ($isManager && isset($conditionalAnswers['management']['questions'])) {
            $managementQuestions = $conditionalAnswers['management']['questions'];
        }

        // Process all regular questions (1-68)
        foreach ($answers as $questionNumber => $answer) {
            if ($answer === null || is_array($answer)) {
                continue;
            }

            // Skip management questions - they're handled separately
            if (in_array($questionNumber, [69, 70, 71, 72])) {
                continue;
            }

            // Determine if question is in group 1 or 2
            $questionKey = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
            $group = in_array($questionKey, $answerValues['group1']['questions'])
                ? 'group1'
                : 'group2';

            $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
        }

        // Process management questions (69-72) if person is a manager
        if ($isManager && ! empty($managementQuestions)) {
            foreach ($managementQuestions as $questionNumber => $answer) {
                if ($answer === null || is_array($answer)) {
                    continue;
                }

                $questionKey = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
                $group = in_array($questionKey, $answerValues['group1']['questions'])
                    ? 'group1'
                    : 'group2';

                $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
            }
        }

        return $totalScore;
    }

    /**
     * Calculate statistics for each question block from referencia_iii config
     */
    public function calculateBlockStatistics(Organization $organization): array
    {
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyBlockStatistics();
        }

        $blocks = config('referencia_iii.general_blocks', []);
        $responseLabels = [
            'A' => 'siempre',
            'B' => 'casi_siempre',
            'C' => 'algunas_veces',
            'D' => 'casi_nunca',
            'E' => 'nunca',
        ];

        $blockStats = [];

        foreach ($blocks as $blockIndex => $blockData) {
            $blockNumber = $blockIndex + 1;
            $questions = $blockData['questions'] ?? [];

            // Initialize block statistics
            $blockStats[$blockNumber] = [
                'block_number' => $blockNumber,
                'instructions' => $blockData['instructions'] ?? '',
                'question_count' => count($questions),
                'questions' => $questions,
                'responses' => [
                    'siempre' => 0,
                    'casi_siempre' => 0,
                    'algunas_veces' => 0,
                    'casi_nunca' => 0,
                    'nunca' => 0,
                ],
                'total_responses' => 0,
                'average_score' => 0,
                'negative_percentage' => 0,
                'criticality' => 'low',
            ];

            $totalScore = 0;
            $totalResponses = 0;

            // Process each evaluation
            foreach ($evaluations as $evaluation) {
                $answers = $evaluation->referencia_iii_answers ?? [];

                // Process each question in the block
                foreach ($questions as $questionNumber) {
                    if (isset($answers[$questionNumber])) {
                        $answer = $answers[$questionNumber];

                        // Skip if answer is not a letter (array values)
                        if (is_array($answer)) {
                            continue;
                        }

                        // Map letter to label
                        $label = $responseLabels[$answer] ?? null;
                        if ($label) {
                            $blockStats[$blockNumber]['responses'][$label]++;
                            $totalResponses++;

                            // Calculate score (assuming standard scoring)
                            $answerValues = config('answer_values');
                            $group = in_array($questionNumber, $answerValues['group1']['questions'])
                                ? 'group1'
                                : 'group2';
                            $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
                        }
                    }
                }
            }

            // Calculate averages and percentages
            if ($totalResponses > 0) {
                $blockStats[$blockNumber]['total_responses'] = $totalResponses;
                $blockStats[$blockNumber]['average_score'] = round($totalScore / $totalResponses, 2);

                $negativeCount = $blockStats[$blockNumber]['responses']['casi_nunca']
                    + $blockStats[$blockNumber]['responses']['nunca'];
                $blockStats[$blockNumber]['negative_percentage'] = round(
                    ($negativeCount / $totalResponses) * 100,
                    1
                );

                // Assign criticality level based on negative percentage
                $negativePercentage = $blockStats[$blockNumber]['negative_percentage'];
                if ($negativePercentage >= 50) {
                    $blockStats[$blockNumber]['criticality'] = 'critical';
                } elseif ($negativePercentage >= 30) {
                    $blockStats[$blockNumber]['criticality'] = 'high';
                } elseif ($negativePercentage >= 15) {
                    $blockStats[$blockNumber]['criticality'] = 'medium';
                } else {
                    $blockStats[$blockNumber]['criticality'] = 'low';
                }
            }
        }

        ksort($blockStats);

        return [
            'blocks' => empty($blockStats) ? new \stdClass : (object) $blockStats,
            'total_evaluations' => $evaluations->count(),
        ];
    }

    /**
     * Return empty structure for block statistics when there are no evaluations
     */
    private function getEmptyBlockStatistics(): array
    {
        return [
            'blocks' => new \stdClass,
            'total_evaluations' => 0,
        ];
    }
}
