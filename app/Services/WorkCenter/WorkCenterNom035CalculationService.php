<?php

namespace App\Services\WorkCenter;

use App\Enums\EvaluationInstrument;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WorkCenterNom035CalculationService
{
    private function baseRefIIIQuery(WorkCenter $workCenter, ?string $source = null): Builder
    {
        $instrumentValue = EvaluationInstrument::ReferenciaIII->value;

        $query = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->whereNull('deleted_at')
            ->with(['evaluationAnswers' => function ($q) use ($instrumentValue): void {
                $q->where('instrument', $instrumentValue);
            }])
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('referencia_iii_answers')
                    ->orWhereNotNull('referencia_iii_conditional')
                    ->orWhereNotNull('raw_data');
            });

        if (in_array($source, ['online', 'paper'], true)) {
            $query->where('source', $source);
        } else {
            $query->whereIn('source', ['paper', 'online']);
        }

        return $query;
    }

    /**
     * Calcular estadísticas de dominios NOM-035 para un centro de trabajo
     */
    public function calculateDomainStatistics(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)->get();

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
            $answers = $this->getMergedReferenciaIIIAnswers($evaluation);

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
    public function calculateCategoryStatistics(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)->get();

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
            $answers = $this->getMergedReferenciaIIIAnswers($evaluation);

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
    public function calculateDimensionStatistics(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)->get();

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
            $answers = $this->getMergedReferenciaIIIAnswers($evaluation);

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
    public function calculateGlobalStatistics(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)->get();

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

        $dominantLevelKey = 'nulo';
        $dominantCount = -1;

        foreach ($globalDistribution as $levelKey => $count) {
            if ($count > $dominantCount) {
                $dominantCount = $count;
                $dominantLevelKey = $levelKey;
            }
        }

        return [
            'global' => [
                'average_score' => round($average, 2),
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($average / $maxScore) * 100, 2) : 0,
                'risk_level' => $dominantLevelKey,
                'risk_level_label' => $riskLevels['labels'][$dominantLevelKey],
                'levels' => $riskLevels['global']['levels'] ?? [],
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
    public function calculateQuestionStatistics(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyQuestionStatistics();
        }

        $questionsConfig = $this->getReferenciaIIIQuestionTexts();
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
            $answers = $this->getMergedReferenciaIIIAnswers($evaluation);

            foreach ($answers as $questionNumber => $answer) {
                if (is_array($answer)) {
                    continue;
                }

                $questionNumeric = (int) $questionNumber;

                if (isset($questionStats[$questionNumeric]) && $answer !== null && isset($responseLabels[$answer])) {
                    $responseLabel = $responseLabels[$answer];
                    $questionStats[$questionNumeric]['responses'][$responseLabel]++;

                    $questionKey = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
                    $group = in_array($questionKey, $answerValues['group1']['questions'])
                        ? 'group1'
                        : 'group2';

                    $score = $answerValues[$group]['values'][$answer] ?? 0;
                    $questionStats[$questionNumeric]['scores'][] = $score;
                }
            }

            for ($questionNumber = 65; $questionNumber <= 72; $questionNumber++) {
                $questionKey = $this->normalizeQuestionKey($questionNumber);

                if (array_key_exists($questionKey, $answers) || array_key_exists($questionNumber, $answers)) {
                    continue;
                }

                if (! isset($questionStats[$questionNumber])) {
                    continue;
                }

                $questionStats[$questionNumber]['responses']['nunca']++;

                $group = in_array($questionKey, $answerValues['group1']['questions'], true)
                    ? 'group1'
                    : 'group2';

                $score = $answerValues[$group]['values']['E'] ?? 0;
                $questionStats[$questionNumber]['scores'][] = $score;
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
    public function calculateBlockStatistics(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)->get();

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
                $answers = $this->getMergedReferenciaIIIAnswers($evaluation);

                foreach ($questions as $questionNumber) {
                    $answer = $this->getAnswerByQuestionNumber($answers, $questionNumber);

                    if ($answer === null || is_array($answer)) {
                        continue;
                    }

                    $label = $responseLabels[$answer] ?? null;
                    if ($label) {
                        $blockStats[$blockNumber]['responses'][$label]++;
                        $totalResponses++;

                        $answerValues = config('answer_values');
                        $questionKey = $this->normalizeQuestionKey($questionNumber);
                        $group = in_array($questionKey, $answerValues['group1']['questions'], true)
                            ? 'group1'
                            : 'group2';
                        $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
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
     * Calculate violence labor statistics for questions 57-64.
     */
    public function calculateViolenceLaborStatistics(WorkCenter $workCenter, ?string $source = null): array
    {
        $violenceQuestions = [57, 58, 59, 60, 61, 62, 63, 64];
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)
            ->with(['demographicData:id,paper_evaluation_id,gender,position,department,work_schedule'])
            ->select(['id', 'personal_folio', 'referencia_iii_answers', 'source'])
            ->get();

        $riskLevels = config('nom035_risk_levels');
        $answerValues = config('answer_values');
        $questionsConfig = $this->getReferenciaIIIQuestionTexts();

        $totalByLevel = [
            'nulo' => 0,
            'bajo' => 0,
            'medio' => 0,
            'alto' => 0,
            'muy_alto' => 0,
        ];

        $questionDistributions = [];
        foreach ($violenceQuestions as $questionNumber) {
            $questionDistributions[$questionNumber] = [
                'nulo' => 0,
                'bajo' => 0,
                'medio' => 0,
                'alto' => 0,
                'muy_alto' => 0,
            ];
        }

        $participants = [];

        foreach ($evaluations as $evaluation) {
            $answers = $this->getMergedReferenciaIIIAnswers($evaluation);
            $violenceScore = 0;
            $answeredQuestions = 0;
            $questionLevels = [];

            foreach ($violenceQuestions as $questionNumber) {
                $answer = $this->getAnswerByQuestionNumber($answers, $questionNumber);
                if ($answer === null || is_array($answer)) {
                    continue;
                }

                $questionKey = $this->normalizeQuestionKey($questionNumber);
                $group = in_array($questionKey, $answerValues['group1']['questions'], true)
                    ? 'group1'
                    : 'group2';

                $score = (int) ($answerValues[$group]['values'][$answer] ?? 0);
                $level = $this->mapScoreToRiskLevel($score);

                $violenceScore += $score;
                $answeredQuestions++;
                $questionLevels[$questionNumber] = $level;
                $questionDistributions[$questionNumber][$level]++;
            }

            if ($answeredQuestions === 0) {
                continue;
            }

            $overallLevel = $this->getRiskLevel($violenceScore, 'Violencia', $riskLevels);
            $totalByLevel[$overallLevel]++;

            $participants[] = [
                'personal_folio' => (string) ($evaluation->personal_folio ?? ''),
                'source' => in_array($evaluation->source, ['online', 'paper'], true) ? $evaluation->source : null,
                'demographics' => [
                    'genero' => $evaluation->demographicData->gender ?? 'No especificado',
                    'puesto' => $evaluation->demographicData->position ?? 'No especificado',
                    'area' => $evaluation->demographicData->department ?? 'No especificado',
                    'turno' => $evaluation->demographicData->work_schedule ?? 'No especificado',
                ],
                'violence_score' => $violenceScore,
                'risk_level' => $overallLevel,
                'question_levels' => $questionLevels,
            ];
        }

        $questions = [];
        foreach ($violenceQuestions as $questionNumber) {
            $distribution = $questionDistributions[$questionNumber];
            $questions[] = [
                'number' => $questionNumber,
                'text' => $questionsConfig[$questionNumber] ?? "Pregunta {$questionNumber}",
                'distribution' => $distribution,
                'total_responses' => array_sum($distribution),
                'high_risk_total' => ($distribution['alto'] ?? 0) + ($distribution['muy_alto'] ?? 0),
            ];
        }

        return [
            'question_numbers' => $violenceQuestions,
            'labels' => $riskLevels['labels'],
            'colors' => $riskLevels['colors'],
            'domain_levels' => $riskLevels['domains']['Violencia']['levels'] ?? [],
            'total_evaluated' => count($participants),
            'total_by_level' => $totalByLevel,
            'high_risk_total' => ($totalByLevel['alto'] ?? 0) + ($totalByLevel['muy_alto'] ?? 0),
            'questions' => $questions,
            'participants' => $participants,
        ];
    }

    /**
     * Get evaluations with demographics and scores for analysis
     */
    public function getEvaluationsWithDemographicsAndScores(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluations = $this->baseRefIIIQuery($workCenter, $source)
            ->with(['demographicData:id,paper_evaluation_id,gender,position,department,work_schedule,contract_type'])
            ->select(['id', 'folio', 'personal_folio', 'evaluee_name', 'referencia_iii_answers', 'referencia_iii_conditional', 'raw_data', 'organization_id', 'source'])
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
            $mergedAnswers = $this->getMergedReferenciaIIIAnswers($evaluation);

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
                        $mergedAnswers,
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
                    $mergedAnswers,
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
                'source' => in_array($evaluation->source, ['online', 'paper'], true) ? $evaluation->source : null,
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
     * Build aggregated report rows for category/domain/dimension/item detail table.
     *
     * Category, domain and dimension use summed scores (NOM-035 ranges are sum-based).
     * Item keeps average score (0-4) for UI display and coloring only.
     *
     * @return array{total_evaluations:int, average_total_score:float, total_score:int, max_score:int, percentage:float, final_average_score:float, final_max_score:int, final_percentage:float, final_risk_level:string, final_risk_label:string, rows:array<int, array{categoria:array{nombre:string,score:int,nivel_riesgo:string}, dominio:array{nombre:string,score:int,nivel_riesgo:string}, dimension:array{nombre:string,score:int,nivel_riesgo:string}, item:string, item_numero:int, puntaje:float}>}
     */
    /**
     * @param  array{genero?:string,puesto?:string,area?:string,turno?:string}|null  $demographicFilters
     */
    public function getGeneralDetailedReport(WorkCenter $workCenter, ?array $demographicFilters = null, ?string $source = null): array
    {
        $evaluationsQuery = $this->baseRefIIIQuery($workCenter, $source)
            ->select(['id', 'referencia_iii_answers', 'referencia_iii_conditional', 'raw_data']);

        if (is_array($demographicFilters) && $demographicFilters !== []) {
            $this->applyDemographicFilters($evaluationsQuery, $demographicFilters);
        }

        $evaluations = $evaluationsQuery->get();

        if ($evaluations->isEmpty()) {
            return [
                'total_evaluations' => 0,
                'average_total_score' => 0.0,
                'total_score' => 0,
                'max_score' => 0,
                'percentage' => 0.0,
                'final_average_score' => 0.0,
                'final_max_score' => 0,
                'final_percentage' => 0.0,
                'final_risk_level' => 'nulo',
                'final_risk_label' => 'Nulo',
                'rows' => [],
            ];
        }

        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');
        $questionsConfig = $this->getReferenciaIIIQuestionTexts();

        $questionTotals = [];
        $questionResponseCounts = [];
        $questionAverages = [];

        foreach ($domainConfig as $domains) {
            foreach ($domains as $dimensions) {
                foreach ($dimensions as $questions) {
                    foreach ($questions as $questionNumber) {
                        $questionKey = $this->normalizeQuestionKey($questionNumber);
                        $questionTotals[$questionKey] = 0.0;
                        $questionResponseCounts[$questionKey] = 0;
                    }
                }
            }
        }

        $totalScore = 0;

        foreach ($evaluations as $evaluation) {
            $answers = $this->getMergedReferenciaIIIAnswers($evaluation);
            $totalScore += $this->calculateTotalScore($evaluation);

            foreach ($questionTotals as $questionKey => $_) {
                $rawAnswer = $this->getAnswerByQuestionNumber($answers, $questionKey);

                if ($rawAnswer === null || is_array($rawAnswer)) {
                    continue;
                }

                $questionTotals[$questionKey] += $this->getQuestionScoreFromAnswer($answers, $questionKey);
                $questionResponseCounts[$questionKey]++;
            }
        }

        foreach ($questionTotals as $questionKey => $total) {
            $responses = $questionResponseCounts[$questionKey] ?? 0;
            $questionAverages[$questionKey] = $responses > 0
                ? $total / $responses
                : 0.0;
        }

        $questionNormalizedScores = array_map(
            static fn (float $average): int => (int) round($average),
            $questionAverages
        );

        $totalEvaluations = $evaluations->count();
        $rows = [];
        $overallScore = 0;

        foreach ($domainConfig as $categoryName => $domains) {
            $categoryScore = 0.0;
            $categoryDimensionMap = [];

            foreach ($domains as $domainName => $dimensions) {
                $domainScore = 0.0;
                $domainDimensionMap = [];

                foreach ($dimensions as $dimensionName => $questions) {
                    $dimensionScore = array_sum(array_map(
                        fn (int|string $questionNumber): int => $questionNormalizedScores[$this->normalizeQuestionKey($questionNumber)] ?? 0,
                        $questions
                    ));

                    $dimensionRiskLevel = $this->getDimensionRiskLevel($dimensionScore, $dimensionName, $riskLevels);

                    $domainDimensionMap[$dimensionName] = [
                        'score' => $dimensionScore,
                        'nivel_riesgo' => $dimensionRiskLevel,
                    ];

                    $domainScore += $dimensionScore;
                }

                $categoryDimensionMap[$domainName] = [
                    'score' => (int) $domainScore,
                    'nivel_riesgo' => $this->getRiskLevel(
                        $domainScore,
                        $domainName,
                        $riskLevels
                    ),
                    'dimensions' => $domainDimensionMap,
                ];

                $categoryScore += $domainScore;
            }

            $categoryScoreInt = (int) $categoryScore;
            $overallScore += $categoryScoreInt;

            $categoryRiskLevel = $this->getCategoryRiskLevel(
                $categoryScoreInt,
                $categoryName,
                $riskLevels
            );

            foreach ($domains as $domainName => $dimensions) {
                foreach ($dimensions as $dimensionName => $questions) {
                    foreach ($questions as $questionNumber) {
                        $rows[] = [
                            'categoria' => [
                                'nombre' => $categoryName,
                                'score' => $categoryScoreInt,
                                'nivel_riesgo' => $categoryRiskLevel,
                            ],
                            'dominio' => [
                                'nombre' => $domainName,
                                'score' => $categoryDimensionMap[$domainName]['score'] ?? 0,
                                'nivel_riesgo' => $categoryDimensionMap[$domainName]['nivel_riesgo'] ?? 'nulo',
                            ],
                            'dimension' => [
                                'nombre' => $dimensionName,
                                'score' => $categoryDimensionMap[$domainName]['dimensions'][$dimensionName]['score'] ?? 0,
                                'nivel_riesgo' => $categoryDimensionMap[$domainName]['dimensions'][$dimensionName]['nivel_riesgo'] ?? 'nulo',
                            ],
                            'item' => $questionsConfig[$questionNumber] ?? "Pregunta {$questionNumber}",
                            'item_numero' => (int) $questionNumber,
                            'puntaje' => round($questionAverages[$this->normalizeQuestionKey($questionNumber)] ?? 0.0, 2),
                        ];
                    }
                }
            }
        }

        $globalMaxPerEvaluation = (int) ($riskLevels['global']['max_score'] ?? 0);
        $globalMaxScore = $globalMaxPerEvaluation;
        $finalAverageScore = (float) $overallScore;
        $finalRiskLevel = $this->getGlobalRiskLevel($finalAverageScore, $riskLevels);

        return [
            'total_evaluations' => $totalEvaluations,
            'average_total_score' => round($totalScore / $totalEvaluations, 2),
            'total_score' => $overallScore,
            'max_score' => $globalMaxScore,
            'percentage' => $globalMaxScore > 0 ? round(($overallScore / $globalMaxScore) * 100, 2) : 0.0,
            'final_average_score' => round($finalAverageScore, 2),
            'final_max_score' => $globalMaxPerEvaluation,
            'final_percentage' => $globalMaxPerEvaluation > 0 ? round(($finalAverageScore / $globalMaxPerEvaluation) * 100, 2) : 0.0,
            'final_risk_level' => $finalRiskLevel,
            'final_risk_label' => $riskLevels['labels'][$finalRiskLevel] ?? 'Nulo',
            'rows' => $rows,
        ];
    }

    /**
     * Merge base Referencia III answers with conditional management answers (65-72) when applicable.
     *
     * Prefers the normalized `evaluation_answers` table when rows exist for this evaluation,
     * ensuring the same conditional-question logic used by the executive report is applied.
     * Falls back to the legacy JSON columns for evaluations not yet in that table.
     *
     * @return array<int|string, mixed>
     */
    private function getMergedReferenciaIIIAnswers(PaperEvaluation $evaluation): array
    {
        $dbAnswers = $this->getRefIIIAnswersFromTable($evaluation);

        if ($dbAnswers->isNotEmpty()) {
            $answers = [];

            foreach ($dbAnswers as $row) {
                if (! is_numeric($row->question_key) || $row->answer_value === null) {
                    continue;
                }

                $normalizedValue = strtoupper(trim($row->answer_value));

                if (! in_array($normalizedValue, ['A', 'B', 'C', 'D', 'E'], true)) {
                    continue;
                }

                $answers[$this->normalizeQuestionKey((int) $row->question_key)] = $normalizedValue;
            }

            return $answers;
        }

        // Fallback: read from legacy JSON columns.
        $answers = is_array($evaluation->referencia_iii_answers) ? $evaluation->referencia_iii_answers : [];
        $rawAnswers = $this->getRawReferenciaIIIAnswers($evaluation);
        $conditionalAnswers = is_array($evaluation->referencia_iii_conditional) ? $evaluation->referencia_iii_conditional : [];

        if ($answers === [] && $rawAnswers !== []) {
            $answers = $rawAnswers;
        } elseif ($rawAnswers !== []) {
            foreach ($rawAnswers as $questionNumber => $answer) {
                $key = $this->normalizeQuestionKey($questionNumber);
                $current = $answers[$key]
                    ?? $answers[(string) ((int) $questionNumber)]
                    ?? $answers[(int) $questionNumber]
                    ?? null;

                if ($current === null || $current === '') {
                    $answers[$key] = $answer;
                }
            }
        }

        foreach ($this->getEnabledConditionalQuestionAnswers($conditionalAnswers) as $questionNumber => $answer) {
            if ($answer === null || is_array($answer)) {
                continue;
            }

            $answers[$this->normalizeQuestionKey($questionNumber)] = $answer;
        }

        return $answers;
    }

    /**
     * Read Referencia III rows from the `evaluation_answers` table for a single evaluation.
     *
     * Uses the already-loaded relation when available (eager-loaded via baseRefIIIQuery),
     * otherwise falls back to a lazy query to avoid N+1 for callers that skipped eager loading.
     *
     * @return Collection<int, \App\Models\EvaluationAnswer>
     */
    private function getRefIIIAnswersFromTable(PaperEvaluation $evaluation): Collection
    {
        $instrumentValue = EvaluationInstrument::ReferenciaIII->value;

        if ($evaluation->relationLoaded('evaluationAnswers')) {
            return $evaluation->evaluationAnswers->filter(
                fn ($a): bool => ($a->instrument instanceof EvaluationInstrument
                    ? $a->instrument->value
                    : (string) $a->instrument) === $instrumentValue
            )->values();
        }

        return $evaluation->evaluationAnswers()
            ->where('instrument', $instrumentValue)
            ->get();
    }

    /**
     * @param  array<int|string, mixed>  $answers
     * @param  array<string, mixed>  $conditionalAnswers
     */
    private function removeDisabledConditionalQuestions(array &$answers, array $conditionalAnswers): void
    {
        $conditionalRanges = [
            'customer_service' => [65, 68],
            'management' => [69, 72],
        ];

        foreach ($conditionalRanges as $section => [$start, $end]) {
            $condition = $conditionalAnswers[$section]['condition'] ?? null;

            if (! is_string($condition) || strtoupper(trim($condition)) !== 'NO') {
                continue;
            }

            for ($questionNumber = $start; $questionNumber <= $end; $questionNumber++) {
                unset($answers[$this->normalizeQuestionKey($questionNumber)]);
            }
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getRawReferenciaIIIAnswers(PaperEvaluation $evaluation): array
    {
        if (! is_array($evaluation->raw_data)) {
            return [];
        }

        $rawData = $evaluation->raw_data;
        $sources = [$rawData];

        if (is_array($rawData['referencia_iii'] ?? null)) {
            $sources[] = $rawData['referencia_iii'];
        }

        $answers = [];

        foreach ($sources as $source) {
            foreach ($source as $questionNumber => $answer) {
                if (! is_numeric((string) $questionNumber)) {
                    continue;
                }

                $numericQuestion = (int) $questionNumber;
                if ($numericQuestion < 1 || $numericQuestion > 72) {
                    continue;
                }

                $answerValue = null;

                if (is_string($answer)) {
                    $answerValue = $answer;
                } elseif (is_array($answer) && is_string($answer['value'] ?? null)) {
                    $answerValue = $answer['value'];
                }

                if (! is_string($answerValue)) {
                    continue;
                }

                $normalizedAnswer = strtoupper(trim($answerValue));
                if (! in_array($normalizedAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
                    continue;
                }

                $answers[$this->normalizeQuestionKey($numericQuestion)] = $normalizedAnswer;
            }
        }

        return $answers;
    }

    /**
     * @param  array{genero?:string,puesto?:string,area?:string,turno?:string}  $filters
     */
    private function applyDemographicFilters(Builder $evaluationsQuery, array $filters): void
    {
        $filterMap = [
            'genero' => 'gender',
            'puesto' => 'position',
            'area' => 'department',
            'turno' => 'work_schedule',
        ];

        foreach ($filterMap as $filterKey => $column) {
            $value = $filters[$filterKey] ?? null;
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $normalizedValue = trim($value);

            $evaluationsQuery->whereHas('demographicData', function (Builder $query) use ($column, $normalizedValue): void {
                if ($normalizedValue === 'No especificado') {
                    $query->where(function (Builder $nestedQuery) use ($column): void {
                        $nestedQuery
                            ->whereNull($column)
                            ->orWhere($column, '');
                    });

                    return;
                }

                $query->where($column, $normalizedValue);
            });
        }
    }

    private function getQuestionScoreFromAnswer(array $answers, int|string $questionNumber): int
    {
        $answer = $this->getAnswerByQuestionNumber($answers, $questionNumber);

        if ($answer === null || is_array($answer)) {
            return 0;
        }

        $answerValues = config('answer_values');
        $questionKey = $this->normalizeQuestionKey($questionNumber);
        $group = in_array($questionKey, $answerValues['group1']['questions'], true)
            ? 'group1'
            : 'group2';

        return $answerValues[$group]['values'][$answer] ?? 0;
    }

    /**
     * @param  array<int, int|string>  $questions
     * @param  array<string, float|int|string|null>  $questionTotals
     * @return array<int, float>
     */
    private function getValidQuestionTotals(array $questions, array $questionTotals): array
    {
        $values = array_map(
            fn (int|string $questionNumber): mixed => $questionTotals[$this->normalizeQuestionKey($questionNumber)] ?? null,
            $questions
        );

        $values = array_values(array_filter(
            $values,
            static fn (mixed $value): bool => is_numeric($value) && is_finite((float) $value)
        ));

        return array_map(static fn (mixed $value): float => (float) $value, $values);
    }

    private function normalizeQuestionKey(int|string $questionNumber): string
    {
        return str_pad((string) ((int) $questionNumber), 2, '0', STR_PAD_LEFT);
    }

    private function getAnswerByQuestionNumber(array $answers, int|string $questionNumber): mixed
    {
        $questionKey = $this->normalizeQuestionKey($questionNumber);

        return $answers[$questionKey]
            ?? $answers[(string) ((int) $questionNumber)]
            ?? $answers[(int) $questionNumber]
            ?? null;
    }

    private function normalizeOrganizationalScore(int|float $score, int $totalEmployees): float
    {
        if ($totalEmployees <= 0) {
            return 0.0;
        }

        return (float) $score / $totalEmployees;
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
                    $answer = $this->getAnswerByQuestionNumber($answers, $questionNumber);

                    if ($answer === null) {
                        continue;
                    }

                    $questionKey = $this->normalizeQuestionKey($questionNumber);
                    $group = in_array($questionKey, $answerValues['group1']['questions'], true)
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
                $answer = $this->getAnswerByQuestionNumber($answers, $questionNumber);

                if ($answer === null) {
                    continue;
                }

                $questionKey = $this->normalizeQuestionKey($questionNumber);
                $group = in_array($questionKey, $answerValues['group1']['questions'], true)
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
            $answer = $this->getAnswerByQuestionNumber($answers, $questionNumber);

            if ($answer === null) {
                continue;
            }

            $questionKey = $this->normalizeQuestionKey($questionNumber);
            $group = in_array($questionKey, $answerValues['group1']['questions'], true)
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
        $answers = $this->getMergedReferenciaIIIAnswers($evaluation);
        $answerValues = config('answer_values');
        $totalScore = 0;

        foreach ($answers as $questionNumber => $answer) {
            if ($answer === null || is_array($answer)) {
                continue;
            }

            $questionKey = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
            $group = in_array($questionKey, $answerValues['group1']['questions'], true)
                ? 'group1'
                : 'group2';

            $totalScore += $answerValues[$group]['values'][$answer] ?? 0;
        }

        return $totalScore;
    }

    /**
     * @param  array<string, mixed>  $conditionalAnswers
     * @return array<int|string, mixed>
     */
    private function getEnabledConditionalQuestionAnswers(array $conditionalAnswers): array
    {
        $enabledQuestions = [];

        foreach ($conditionalAnswers as $section) {
            if (! is_array($section)) {
                continue;
            }

            $isEnabled = isset($section['condition']) && $section['condition'] === 'SI';
            $questions = $section['questions'] ?? null;

            if (! $isEnabled || ! is_array($questions)) {
                continue;
            }

            foreach ($questions as $questionNumber => $answer) {
                $enabledQuestions[$questionNumber] = $answer;
            }
        }

        return $enabledQuestions;
    }

    /**
     * @return array<int|string, string>
     */
    private function getReferenciaIIIQuestionTexts(): array
    {
        $generalQuestions = config('referencia_iii.general', []);
        $conditionalSections = config('referencia_iii.conditional_sections', []);

        if (! is_array($generalQuestions)) {
            $generalQuestions = [];
        }

        if (! is_array($conditionalSections)) {
            return $generalQuestions;
        }

        $questionTexts = $generalQuestions;

        foreach ($conditionalSections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $questions = $section['questions'] ?? null;
            if (! is_array($questions)) {
                continue;
            }

            foreach ($questions as $questionNumber => $questionText) {
                if (is_string($questionText) && trim($questionText) !== '') {
                    $questionTexts[(int) $questionNumber] = $questionText;
                }
            }
        }

        return $questionTexts;
    }

    private function mapScoreToRiskLevel(int $score): string
    {
        return match ($score) {
            0 => 'nulo',
            1 => 'bajo',
            2 => 'medio',
            3 => 'alto',
            default => 'muy_alto',
        };
    }

    /**
     * Determinar nivel de riesgo según puntaje
     */
    private function getRiskLevel(float $score, string $domainName, array $riskLevels): string
    {
        $levels = $riskLevels['domains'][$domainName]['levels'];
        $score = round($score);

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

        $score = round($score);

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
        $normalizedName = mb_strtolower(trim($dimensionName));

        $dimensions = array_change_key_case(
            array_map(fn ($v) => $v, $riskLevels['dimensions']),
            CASE_LOWER
        );

        if (! isset($dimensions[$normalizedName]['levels'])) {
            return 'nulo';
        }

        $levels = $dimensions[$normalizedName]['levels'];

        $score = round($score); // 👈 importante

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
                'levels' => $riskLevels['global']['levels'] ?? [],
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
