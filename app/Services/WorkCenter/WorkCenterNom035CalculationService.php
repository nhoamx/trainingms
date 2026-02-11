<?php

namespace App\Services\WorkCenter;

use App\Models\PaperEvaluation;
use App\Models\WorkCenter;

class WorkCenterNom035CalculationService
{
    /**
     * Calcular estadísticas de dominios NOM-035 para un centro de trabajo
     */
    public function calculateDomainStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyStatistics();
        }

        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $domainScores = [];
        $domainDistributions = [];

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

        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($domainConfig as $categoryName => $domains) {
                foreach ($domains as $domainName => $dimensions) {
                    $score = $this->calculateDomainScore($answers, [$domainName => $dimensions]);
                    $domainScores[$domainName][] = $score;

                    $level = $this->getRiskLevel($score, $domainName, $riskLevels);
                    $domainDistributions[$domainName][$level]++;
                }
            }
        }

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
     * Calcular estadísticas de categorías NOM-035 para un centro de trabajo
     * NOTA: Calcula las 5 CATEGORÍAS REALES (Nivel 1) de NOM-035
     */
    public function calculateCategoryStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
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
     * Calcular estadísticas de dimensiones NOM-035 para un centro de trabajo
     */
    public function calculateDimensionStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
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

        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($domainConfig as $categoryName => $domains) {
                foreach ($domains as $domainName => $dimensions) {
                    foreach ($dimensions as $dimensionName => $questions) {
                        $score = $this->calculateDimensionScore($answers, $questions);
                        $dimensionScores[$dimensionName][] = $score;

                        $level = $this->getDimensionRiskLevel($score, $dimensionName, $riskLevels);
                        $dimensionDistributions[$dimensionName][$level]++;
                    }
                }
            }
        }

        $result = [];
        foreach ($dimensionScores as $dimensionName => $scores) {
            $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;

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
     * Calcular estadísticas globales NOM-035 para un centro de trabajo
     */
    public function calculateGlobalStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
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

        foreach ($evaluations as $evaluation) {
            $totalScore = $this->calculateTotalScore($evaluation);
            $globalScores[] = $totalScore;

            $level = $this->getGlobalRiskLevel($totalScore, $riskLevels);
            $globalDistribution[$level]++;
        }

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
     * Calcular estadísticas por pregunta individual NOM-035 para un centro de trabajo
     */
    public function calculateQuestionStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
            ->whereNotNull('referencia_iii_answers')
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyQuestionStatistics();
        }

        $questionsConfig = config('referencia_iii.general');
        $domainConfig = config('question_dimensions');
        $answerValues = config('answer_values');

        $responseLabels = [
            'A' => 'siempre',
            'B' => 'casi_siempre',
            'C' => 'algunas_veces',
            'D' => 'casi_nunca',
            'E' => 'nunca',
        ];

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

        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($answers as $questionNumber => $answer) {
                if (is_array($answer)) {
                    continue;
                }

                if (isset($questionStats[$questionNumber]) && $answer !== null && isset($responseLabels[$answer])) {
                    $responseLabel = $responseLabels[$answer];
                    $questionStats[$questionNumber]['responses'][$responseLabel]++;

                    $questionKey = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
                    $group = in_array($questionKey, $answerValues['group1']['questions'])
                        ? 'group1'
                        : 'group2';

                    $score = $answerValues[$group]['values'][$answer] ?? 0;
                    $questionStats[$questionNumber]['scores'][] = $score;
                }
            }
        }

        $result = [];
        foreach ($questionStats as $questionNumber => $stats) {
            if (count($stats['scores']) === 0) {
                continue;
            }

            $averageScore = array_sum($stats['scores']) / count($stats['scores']);
            $maxScore = 4;

            $negativeResponses = ($stats['responses']['casi_nunca'] + $stats['responses']['nunca']);
            $totalResponses = array_sum($stats['responses']);
            $negativePercentage = $totalResponses > 0 ? ($negativeResponses / $totalResponses) * 100 : 0;

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

        ksort($result);

        return [
            'questions' => empty($result) ? new \stdClass : (object) $result,
            'total_evaluations' => $evaluations->count(),
        ];
    }

    /**
     * Calculate statistics for each question block from referencia_iii config
     */
    public function calculateBlockStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
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

            foreach ($evaluations as $evaluation) {
                $answers = $evaluation->referencia_iii_answers ?? [];

                foreach ($questions as $questionNumber) {
                    if (isset($answers[$questionNumber])) {
                        $answer = $answers[$questionNumber];

                        if (is_array($answer)) {
                            continue;
                        }

                        $label = $responseLabels[$answer] ?? null;
                        if ($label) {
                            $blockStats[$blockNumber]['responses'][$label]++;
                            $totalResponses++;

                            $answerValues = config('answer_values');
                            $group = in_array($questionNumber, $answerValues['group1']['questions'])
                                ? 'group1'
                                : 'group2';
                            $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
                        }
                    }
                }
            }

            if ($totalResponses > 0) {
                $blockStats[$blockNumber]['total_responses'] = $totalResponses;
                $blockStats[$blockNumber]['average_score'] = round($totalScore / $totalResponses, 2);

                $negativeCount = $blockStats[$blockNumber]['responses']['casi_nunca']
                    + $blockStats[$blockNumber]['responses']['nunca'];
                $blockStats[$blockNumber]['negative_percentage'] = round(
                    ($negativeCount / $totalResponses) * 100,
                    1
                );

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
     * Get evaluations with demographics and scores for analysis
     */
    public function getEvaluationsWithDemographicsAndScores(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
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

            $categoryScores = [];
            foreach ($domainConfig as $categoryName => $domains) {
                $categoryDimensions = [];
                foreach ($domains as $domainName => $dimensions) {
                    $categoryDimensions = array_merge($categoryDimensions, $dimensions);
                }

                $score = $this->calculateCategoryScore(
                    $evaluation->referencia_iii_answers,
                    $categoryDimensions
                );

                $firstDomainName = array_key_first($domains);
                $riskLevel = $this->getRiskLevel($score, $firstDomainName, $riskLevels);

                $categoryScores[$categoryName] = [
                    'score' => $score,
                    'risk_level' => $riskLevel,
                ];
            }

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

                $group = in_array(str_pad($questionNumber, 2, '0', STR_PAD_LEFT), $answerValues['group1']['questions'])
                    ? 'group1'
                    : 'group2';

                $score += $answerValues[$group]['values'][$answer] ?? 0;
            }
        }

        return $score;
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

            $group = in_array(str_pad($questionNumber, 2, '0', STR_PAD_LEFT), $answerValues['group1']['questions'])
                ? 'group1'
                : 'group2';

            $score += $answerValues[$group]['values'][$answer] ?? 0;
        }

        return $score;
    }

    /**
     * Calculate total score by summing all answer values (including conditional questions)
     */
    private function calculateTotalScore(PaperEvaluation $evaluation): int
    {
        $answers = $evaluation->referencia_iii_answers ?? [];
        $conditionalAnswers = $evaluation->referencia_iii_conditional ?? [];
        $answerValues = config('answer_values');
        $totalScore = 0;

        $isManager = isset($conditionalAnswers['management']['condition'])
            && $conditionalAnswers['management']['condition'] === 'SI';

        $managementQuestions = [];
        if ($isManager && isset($conditionalAnswers['management']['questions'])) {
            $managementQuestions = $conditionalAnswers['management']['questions'];
        }

        foreach ($answers as $questionNumber => $answer) {
            if ($answer === null || is_array($answer)) {
                continue;
            }

            if (in_array($questionNumber, [69, 70, 71, 72])) {
                continue;
            }

            $questionKey = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
            $group = in_array($questionKey, $answerValues['group1']['questions'])
                ? 'group1'
                : 'group2';

            $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
        }

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
        if (isset($riskLevels['categories'][$categoryName]['levels'])) {
            $levels = $riskLevels['categories'][$categoryName]['levels'];
        } else {
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
     * Obtener nivel de riesgo para una dimensión
     */
    private function getDimensionRiskLevel(float $score, string $dimensionName, array $riskLevels): string
    {
        if (isset($riskLevels['dimensions'][$dimensionName]['levels'])) {
            $levels = $riskLevels['dimensions'][$dimensionName]['levels'];
        } else {
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

        return $totalQuestions * 4;
    }

    /**
     * Retornar estructura vacía cuando no hay evaluaciones
     */
    private function getEmptyStatistics(): array
    {
        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $domains = [];
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
     * Retornar estructura vacía de categorías cuando no hay evaluaciones
     */
    private function getEmptyCategoryStatistics(): array
    {
        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $categories = [];
        foreach ($domainConfig as $domainName => $categoriesInDomain) {
            foreach (array_keys($categoriesInDomain) as $categoryName) {
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
