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

        $answers = $evaluation->referencia_iii_answers ?? [];
        $answerValues = config('answer_values');
        $questionDimensions = config('question_dimensions');

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
                        // Intentar con diferentes formatos de clave
                        $answer = $answers[$questionNumber] 
                            ?? $answers[sprintf('%02d', $questionNumber)] 
                            ?? $answers[(string)$questionNumber] 
                            ?? null;

                        if ($answer) {
                            $score = $this->getAnswerValue($questionNumber, $answer, $answerValues);
                            $dimensionScore += $score;
                            $totalScore += $score;

                            $dimensionItems[] = [
                                'question_number' => $questionNumber,
                                'question_key' => sprintf('%02d', $questionNumber),
                                'answer' => $answer,
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
        $referenciaIIIQuestions = config('referencia_iii.general');

        $detailedResults = [];

        foreach ($questionDimensions as $categoryName => $domains) {
            $categoryScore = $scores['categories'][$categoryName]['score'];

            foreach ($domains as $domainName => $dimensions) {
                $domainKey = $categoryName.'|'.$domainName;
                $domainScore = $scores['domains'][$domainKey]['score'];

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
                            ],
                            'dominio' => [
                                'nombre' => $domainName,
                                'puntaje' => $domainScore,
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
}
