<?php

namespace App\Services;

use App\Models\PaperEvaluation;

class PaperEvaluationScoreService
{
    /**
     * Calculate scores for Referencia III evaluation
     */
    public function calculateReferenciaIIIScores(PaperEvaluation $evaluation): array
    {
        if ($evaluation->evaluation_type !== 'referencia_iii') {
            return [
                'total_score' => 0,
                'categories' => [],
                'domains' => [],
                'dimensions' => [],
            ];
        }

        $answers = $this->normalizeAnswers($evaluation->referencia_iii_answers ?? []);
        $conditionalAnswers = is_array($evaluation->referencia_iii_conditional) ? $evaluation->referencia_iii_conditional : [];
        $rawReferenciaIII = $this->normalizeAnswers($evaluation->raw_data['referencia_iii'] ?? []);
        $answerValues = config('answer_values');
        $questionDimensions = config('question_dimensions');

        $customerServiceSection = $this->extractConditionalSection($conditionalAnswers, 'customer_service');
        $managementSection = $this->extractConditionalSection($conditionalAnswers, 'management');

        $customerServiceCondition = $this->normalizeYesNo($customerServiceSection['condition'] ?? null);
        $managementCondition = $this->normalizeYesNo($managementSection['condition'] ?? null);

        $shouldIncludeCustomerService = $customerServiceCondition === true
            || ($customerServiceCondition === null && $this->hasAnyDeclaredQuestionsInRange($rawReferenciaIII, 65, 68));

        $shouldIncludeManagement = $managementCondition === true
            || ($managementCondition === null && $this->hasAnyDeclaredQuestionsInRange($rawReferenciaIII, 69, 72));

        $customerServiceQuestions = $this->normalizeAnswers($customerServiceSection['questions'] ?? []);
        $managementQuestions = $this->normalizeAnswers($managementSection['questions'] ?? []);

        $categoryScores = [];
        $domainScores = [];
        $dimensionScores = [];
        $totalScore = 0;

        foreach ($questionDimensions as $categoryName => $domains) {
            $categoryScore = 0;
            $categoryScores[$categoryName] = [
                'name' => $categoryName,
                'score' => 0,
                'domains' => [],
            ];

            foreach ($domains as $domainName => $dimensions) {
                $domainScore = 0;
                $domainKey = $categoryName.'|'.$domainName;

                foreach ($dimensions as $dimensionName => $questions) {
                    $dimensionScore = 0;
                    $dimensionKey = $domainKey.'|'.$dimensionName;
                    $dimensionItems = [];

                    foreach ($questions as $questionNumber) {
                        $resolvedAnswer = null;

                        if (in_array($questionNumber, [65, 66, 67, 68], true)) {
                            if (! $shouldIncludeCustomerService) {
                                continue;
                            }

                            $resolvedAnswer = $this->resolveAnswerWithPresence(
                                $questionNumber,
                                $customerServiceQuestions,
                                $answers,
                                $rawReferenciaIII
                            );
                        } elseif (in_array($questionNumber, [69, 70, 71, 72], true)) {
                            if (! $shouldIncludeManagement) {
                                continue;
                            }

                            $resolvedAnswer = $this->resolveAnswerWithPresence(
                                $questionNumber,
                                $managementQuestions,
                                $answers,
                                $rawReferenciaIII
                            );
                        } else {
                            $resolvedAnswer = $this->resolveAnswerWithPresence($questionNumber, $answers, $rawReferenciaIII);
                        }

                        if ($resolvedAnswer['found']) {
                            $answer = $resolvedAnswer['answer'];
                            $score = is_string($answer)
                                ? $this->getAnswerValue($questionNumber, $answer, $answerValues)
                                : 0;
                            $dimensionScore += $score;
                            $totalScore += $score;

                            $dimensionItems[] = [
                                'question_number' => $questionNumber,
                                'question_key' => sprintf('%02d', $questionNumber),
                                'answer' => $answer ?? '0',
                                'score' => $score,
                            ];
                        }
                    }

                    $domainScore += $dimensionScore;
                    $dimensionScores[$dimensionKey] = [
                        'name' => $dimensionName,
                        'score' => $dimensionScore,
                        'items' => $dimensionItems,
                    ];
                }

                $categoryScore += $domainScore;
                $domainScores[$domainKey] = [
                    'name' => $domainName,
                    'score' => $domainScore,
                ];

                $categoryScores[$categoryName]['domains'][] = $domainKey;
            }

            $categoryScores[$categoryName]['score'] = $categoryScore;
        }

        return [
            'total_score' => $totalScore,
            'categories' => $categoryScores,
            'domains' => $domainScores,
            'dimensions' => $dimensionScores,
        ];
    }

    /**
     * Get the value for a specific answer
     */
    protected function getAnswerValue(int $questionNumber, string $answer, array $answerValues): int
    {
        $questionKey = sprintf('%02d', $questionNumber);

        // Check which group this question belongs to
        foreach ($answerValues as $group) {
            if (in_array($questionKey, $group['questions'])) {
                return $group['values'][$answer] ?? 0;
            }
        }

        return 0;
    }

    /**
     * Get structured data for detailed results view
     */
    public function getDetailedResults(PaperEvaluation $evaluation): array
    {
        $scores = $this->calculateReferenciaIIIScores($evaluation);
        $questionDimensions = config('question_dimensions');
        $referenciaIIIQuestions = $this->getReferenciaIIIQuestionTexts();

        $detailedResults = [];

        foreach ($questionDimensions as $categoryName => $domains) {
            $categoryScore = $scores['categories'][$categoryName]['score'];
            $categoryRiskLevel = $this->calculateCategoryRiskLevel($categoryName, $categoryScore);

            foreach ($domains as $domainName => $dimensions) {
                $domainKey = $categoryName.'|'.$domainName;
                $domainScore = $scores['domains'][$domainKey]['score'];
                $domainRiskLevel = $this->calculateDomainRiskLevel($domainName, $domainScore);

                foreach ($dimensions as $dimensionName => $questions) {
                    $dimensionKey = $domainKey.'|'.$dimensionName;
                    $dimensionData = $scores['dimensions'][$dimensionKey] ?? ['score' => 0, 'items' => []];

                    foreach ($dimensionData['items'] as $item) {
                        $questionNumber = $item['question_number'];
                        $questionText = $referenciaIIIQuestions[$questionNumber] ?? "Pregunta {$questionNumber}";

                        $detailedResults[] = [
                            'categoria' => [
                                'nombre' => $categoryName,
                                'puntaje' => $categoryScore,
                                'nivel_riesgo' => $categoryRiskLevel,
                            ],
                            'dominio' => [
                                'nombre' => $domainName,
                                'puntaje' => $domainScore,
                                'nivel_riesgo' => $domainRiskLevel,
                            ],
                            'dimension' => $dimensionName,
                            'item' => $questionText,
                            'item_numero' => $questionNumber,
                            'respuesta' => $item['answer'],
                            'puntaje' => $item['score'],
                        ];
                    }
                }
            }
        }

        return $detailedResults;
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function normalizeAnswers(array $answers): array
    {
        if (isset($answers['referencia_iii']) && is_array($answers['referencia_iii'])) {
            $answers = $answers['referencia_iii'];
        }

        $normalized = [];

        foreach ($answers as $key => $value) {
            if (is_array($value) && array_key_exists('value', $value)) {
                $value = $value['value'];
            }

            if (is_numeric((string) $key)) {
                $normalized[(string) (int) $key] = $value;

                continue;
            }

            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $conditionalAnswers
     * @return array{condition: mixed, questions: array<string, mixed>}
     */
    private function extractConditionalSection(array $conditionalAnswers, string $section): array
    {
        $sectionData = is_array($conditionalAnswers[$section] ?? null) ? $conditionalAnswers[$section] : [];

        return [
            'condition' => $sectionData['condition'] ?? null,
            'questions' => is_array($sectionData['questions'] ?? null) ? $sectionData['questions'] : [],
        ];
    }

    private function normalizeYesNo(mixed $value): ?bool
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        if (in_array($normalized, ['SI', 'S', 'YES', 'Y', 'TRUE', '1'], true)) {
            return true;
        }

        if (in_array($normalized, ['NO', 'N', 'FALSE', '0'], true)) {
            return false;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private function hasAnyDeclaredQuestionsInRange(array $answers, int $start, int $end): bool
    {
        for ($number = $start; $number <= $end; $number++) {
            if (array_key_exists((string) $number, $answers)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  ...$sources
     */
    private function resolveAnswerWithPresence(int $questionNumber, array ...$sources): array
    {
        $key = (string) $questionNumber;

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            if (! array_key_exists($key, $source)) {
                continue;
            }

            $value = $source[$key];

            if ($value === null || $value === '') {
                return ['found' => true, 'answer' => null];
            }

            if (! is_string($value)) {
                return ['found' => true, 'answer' => null];
            }

            return ['found' => true, 'answer' => $value];
        }

        return ['found' => false, 'answer' => null];
    }

    /**
     * @return array<int, string>
     */
    private function getReferenciaIIIQuestionTexts(): array
    {
        $questions = config('referencia_iii.general', []);
        $conditionalSections = config('referencia_iii.conditional_sections', []);

        foreach ($conditionalSections as $section) {
            if (! is_array($section) || ! is_array($section['questions'] ?? null)) {
                continue;
            }

            foreach ($section['questions'] as $number => $text) {
                if (is_numeric((string) $number) && is_string($text)) {
                    $questions[(int) $number] = $text;
                }
            }
        }

        return $questions;
    }

    /**
     * Calculate risk level based on total score
     */
    public function calculateRiskLevel(int $totalScore): string
    {
        // NOM-035 risk levels for Referencia III
        if ($totalScore < 50) {
            return 'Nulo';
        } elseif ($totalScore < 75) {
            return 'Bajo';
        } elseif ($totalScore < 99) {
            return 'Medio';
        } elseif ($totalScore < 140) {
            return 'Alto';
        } else {
            return 'Muy Alto';
        }
    }

    /**
     * Calculate risk level for a category based on its score
     */
    public function calculateCategoryRiskLevel(string $categoryName, int $score): string
    {
        $categoryThresholds = $this->getCategoryRiskThresholds();

        if (isset($categoryThresholds[$categoryName])) {
            foreach ($categoryThresholds[$categoryName] as $threshold) {
                $min = $threshold['min'] ?? 0;
                $max = $threshold['max'] ?? PHP_INT_MAX;

                if ($score >= $min && $score <= $max) {
                    return $threshold['level'];
                }
            }
        }

        // Fallback to general risk calculation
        return $this->calculateRiskLevel($score);
    }

    /**
     * Calculate risk level for a domain based on its score
     */
    public function calculateDomainRiskLevel(string $domainName, int $score): string
    {
        $domainThresholds = $this->getDomainRiskThresholds();

        if (isset($domainThresholds[$domainName])) {
            foreach ($domainThresholds[$domainName] as $threshold) {
                $min = $threshold['min'] ?? 0;
                $max = $threshold['max'] ?? PHP_INT_MAX;

                if ($score >= $min && $score <= $max) {
                    return $threshold['level'];
                }
            }
        }

        // Fallback to general risk calculation
        return $this->calculateRiskLevel($score);
    }

    /**
     * Get category risk thresholds based on NOM-035
     */
    protected function getCategoryRiskThresholds(): array
    {
        return [
            'Ambiente de trabajo' => [
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 8, 'level' => 'Bajo'],
                ['min' => 9, 'max' => 10, 'level' => 'Medio'],
                ['min' => 11, 'max' => 13, 'level' => 'Alto'],
                ['min' => 14, 'level' => 'Muy Alto'],
            ],
            'Factores propios de la actividad' => [
                ['max' => 14, 'level' => 'Nulo'],
                ['min' => 15, 'max' => 29, 'level' => 'Bajo'],
                ['min' => 30, 'max' => 44, 'level' => 'Medio'],
                ['min' => 45, 'max' => 59, 'level' => 'Alto'],
                ['min' => 60, 'level' => 'Muy Alto'],
            ],
            'Organización del tiempo de trabajo' => [
                ['max' => 3, 'level' => 'Nulo'],
                ['min' => 4, 'max' => 5, 'level' => 'Bajo'],
                ['min' => 6, 'max' => 8, 'level' => 'Medio'],
                ['min' => 9, 'max' => 11, 'level' => 'Alto'],
                ['min' => 12, 'level' => 'Muy Alto'],
            ],
            'Liderazgo y relaciones en el trabajo' => [
                ['max' => 17, 'level' => 'Nulo'],
                ['min' => 18, 'max' => 31, 'level' => 'Bajo'],
                ['min' => 32, 'max' => 45, 'level' => 'Medio'],
                ['min' => 46, 'max' => 58, 'level' => 'Alto'],
                ['min' => 59, 'level' => 'Muy Alto'],
            ],
            'Entorno organizacional' => [
                ['max' => 8, 'level' => 'Nulo'],
                ['min' => 9, 'max' => 13, 'level' => 'Bajo'],
                ['min' => 14, 'max' => 19, 'level' => 'Medio'],
                ['min' => 20, 'max' => 25, 'level' => 'Alto'],
                ['min' => 26, 'level' => 'Muy Alto'],
            ],
        ];
    }

    /**
     * Get domain risk thresholds based on NOM-035
     */
    protected function getDomainRiskThresholds(): array
    {
        return [
            'Condiciones en el ambiente de trabajo' => [
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 8, 'level' => 'Bajo'],
                ['min' => 9, 'max' => 10, 'level' => 'Medio'],
                ['min' => 11, 'max' => 13, 'level' => 'Alto'],
                ['min' => 14, 'level' => 'Muy Alto'],
            ],
            'Carga de trabajo' => [
                ['max' => 14, 'level' => 'Nulo'],
                ['min' => 15, 'max' => 20, 'level' => 'Bajo'],
                ['min' => 21, 'max' => 26, 'level' => 'Medio'],
                ['min' => 27, 'max' => 36, 'level' => 'Alto'],
                ['min' => 37, 'level' => 'Muy Alto'],
            ],
            'Falta de control sobre el trabajo' => [
                ['max' => 10, 'level' => 'Nulo'],
                ['min' => 11, 'max' => 15, 'level' => 'Bajo'],
                ['min' => 16, 'max' => 20, 'level' => 'Medio'],
                ['min' => 21, 'max' => 24, 'level' => 'Alto'],
                ['min' => 25, 'level' => 'Muy Alto'],
            ],
            'Jornada de trabajo' => [
                ['max' => 0, 'level' => 'Nulo'],
                ['min' => 1, 'max' => 1, 'level' => 'Bajo'],
                ['min' => 2, 'max' => 3, 'level' => 'Medio'],
                ['min' => 4, 'max' => 5, 'level' => 'Alto'],
                ['min' => 6, 'level' => 'Muy Alto'],
            ],
            'Interferencia en la relación trabajo-familia' => [
                ['max' => 3, 'level' => 'Nulo'],
                ['min' => 4, 'max' => 5, 'level' => 'Bajo'],
                ['min' => 6, 'max' => 7, 'level' => 'Medio'],
                ['min' => 8, 'max' => 9, 'level' => 'Alto'],
                ['min' => 10, 'level' => 'Muy Alto'],
            ],
            'Liderazgo' => [
                ['max' => 8, 'level' => 'Nulo'],
                ['min' => 9, 'max' => 11, 'level' => 'Bajo'],
                ['min' => 12, 'max' => 15, 'level' => 'Medio'],
                ['min' => 16, 'max' => 19, 'level' => 'Alto'],
                ['min' => 20, 'level' => 'Muy Alto'],
            ],
            'Relaciones en el trabajo' => [
                ['max' => 9, 'level' => 'Nulo'],
                ['min' => 10, 'max' => 12, 'level' => 'Bajo'],
                ['min' => 13, 'max' => 16, 'level' => 'Medio'],
                ['min' => 17, 'max' => 20, 'level' => 'Alto'],
                ['min' => 21, 'level' => 'Muy Alto'],
            ],
            'Violencia' => [
                ['max' => 6, 'level' => 'Nulo'],
                ['min' => 7, 'max' => 9, 'level' => 'Bajo'],
                ['min' => 10, 'max' => 12, 'level' => 'Medio'],
                ['min' => 13, 'max' => 15, 'level' => 'Alto'],
                ['min' => 16, 'level' => 'Muy Alto'],
            ],
            'Reconocimiento del desempeño' => [
                ['max' => 5, 'level' => 'Nulo'],
                ['min' => 6, 'max' => 9, 'level' => 'Bajo'],
                ['min' => 10, 'max' => 13, 'level' => 'Medio'],
                ['min' => 14, 'max' => 17, 'level' => 'Alto'],
                ['min' => 18, 'level' => 'Muy Alto'],
            ],
            'Insuficiente sentido de pertenencia e inestabilidad' => [
                ['max' => 3, 'level' => 'Nulo'],
                ['min' => 4, 'max' => 5, 'level' => 'Bajo'],
                ['min' => 6, 'max' => 7, 'level' => 'Medio'],
                ['min' => 8, 'max' => 9, 'level' => 'Alto'],
                ['min' => 10, 'level' => 'Muy Alto'],
            ],
        ];
    }
}
