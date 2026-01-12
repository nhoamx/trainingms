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

        foreach ($domainConfig as $domainName => $categories) {
            $domainScores[$domainName] = [];
            $domainDistributions[$domainName] = [
                'nulo' => 0,
                'bajo' => 0,
                'medio' => 0,
                'alto' => 0,
                'muy_alto' => 0,
            ];
        }

        // Calcular puntajes por evaluación
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($domainConfig as $domainName => $categories) {
                $score = $this->calculateDomainScore($answers, $categories);
                $domainScores[$domainName][] = $score;

                // Clasificar en nivel de riesgo
                $level = $this->getRiskLevel($score, $domainName, $riskLevels);
                $domainDistributions[$domainName][$level]++;
            }
        }

        // Calcular promedios y preparar respuesta
        $result = [];
        foreach ($domainScores as $domainName => $scores) {
            $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
            $maxScore = $riskLevels['domains'][$domainName]['max_score'];
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
        $categoryDomains = [];

        // Preparar estructura para cada categoría
        foreach ($domainConfig as $domainName => $categories) {
            foreach ($categories as $categoryName => $subcategories) {
                $categoryScores[$categoryName] = [];
                $categoryDistributions[$categoryName] = [
                    'nulo' => 0,
                    'bajo' => 0,
                    'medio' => 0,
                    'alto' => 0,
                    'muy_alto' => 0,
                ];
                $categoryDomains[$categoryName] = $domainName;
            }
        }

        // Calcular puntajes por evaluación
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];

            foreach ($domainConfig as $domainName => $categories) {
                foreach ($categories as $categoryName => $subcategories) {
                    $score = $this->calculateCategoryScore($answers, $subcategories);
                    $categoryScores[$categoryName][] = $score;

                    // Clasificar en nivel de riesgo usando los niveles del dominio
                    $level = $this->getRiskLevel($score, $domainName, $riskLevels);
                    $categoryDistributions[$categoryName][$level]++;
                }
            }
        }

        // Calcular promedios y preparar respuesta
        $result = [];
        foreach ($categoryScores as $categoryName => $scores) {
            $domainName = $categoryDomains[$categoryName];
            $average = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
            $maxScore = $riskLevels['domains'][$domainName]['max_score'];
            $averageLevel = $this->getRiskLevel($average, $domainName, $riskLevels);

            $result[$categoryName] = [
                'average_score' => round($average, 2),
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($average / $maxScore) * 100, 2) : 0,
                'risk_level' => $averageLevel,
                'risk_level_label' => $riskLevels['labels'][$averageLevel],
                'distribution' => $categoryDistributions[$categoryName],
                'total_evaluations' => count($scores),
                'domain' => $domainName,
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
     * Retornar estructura vacía cuando no hay evaluaciones
     */
    private function getEmptyStatistics(): array
    {
        $domainConfig = config('question_dimensions');
        $riskLevels = config('nom035_risk_levels');

        $domains = [];
        foreach (array_keys($domainConfig) as $domainName) {
            $domains[$domainName] = [
                'average_score' => 0,
                'max_score' => $riskLevels['domains'][$domainName]['max_score'],
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
                $categories[$categoryName] = [
                    'average_score' => 0,
                    'max_score' => $riskLevels['domains'][$domainName]['max_score'],
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
}
