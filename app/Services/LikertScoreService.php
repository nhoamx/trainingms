<?php

namespace App\Services;

use App\Models\PaperEvaluation;

class LikertScoreService
{
    /**
     * Calculate Likert scores for all dimensions and total climate score
     */
    public function calculateLikertScores(PaperEvaluation $evaluation): array
    {
        $config = config('likert-value');
        $questions = $evaluation->likert_answers['questions'] ?? [];

        if (empty($questions)) {
            return [
                'dimensions' => [],
                'total_score' => 0,
                'interpretation' => null,
            ];
        }

        $scores = [];
        $totalScore = 0;

        // Calculate score for each dimension
        foreach ($config['niveles'] as $dimension => $data) {
            $dimensionScore = 0;

            foreach ($data['preguntas'] as $questionNum) {
                $answer = $questions[(string) $questionNum] ?? null;
                $dimensionScore += $config['valorOpciones'][$answer] ?? 0;
            }

            // Get interpretation for this dimension
            $interpretation = $this->getInterpretation($dimensionScore, $config['valorNiveles'][$dimension]);

            $scores[$dimension] = [
                'score' => $dimensionScore,
                'interpretation' => $interpretation,
                'questions' => $data['preguntas'],
            ];

            $totalScore += $dimensionScore;
        }

        // Get interpretation for total climate score
        $generalInterpretation = $this->getInterpretation($totalScore, $config['valorNiveles']['Clima Laboral']);

        return [
            'dimensions' => $scores,
            'total_score' => $totalScore,
            'interpretation' => $generalInterpretation,
        ];
    }

    /**
     * Get detailed results with question breakdown
     */
    public function getDetailedResults(PaperEvaluation $evaluation): array
    {
        $scores = $this->calculateLikertScores($evaluation);
        $questions = $evaluation->likert_answers['questions'] ?? [];
        $config = config('likert-value');

        // Add question-level details to each dimension
        foreach ($scores['dimensions'] as $dimension => &$dimensionData) {
            $questionDetails = [];

            foreach ($dimensionData['questions'] as $questionNum) {
                $answer = $questions[(string) $questionNum] ?? null;
                $questionDetails[] = [
                    'number' => $questionNum,
                    'answer' => $answer,
                    'value' => $config['valorOpciones'][$answer] ?? 0,
                ];
            }

            $dimensionData['question_details'] = $questionDetails;
        }

        return $scores;
    }

    /**
     * Get interpretation based on score and ranges
     */
    protected function getInterpretation(float $score, array $ranges): ?string
    {
        foreach ($ranges as $interpretation => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $interpretation;
            }
        }

        return null;
    }

    /**
     * Get demographic data from Likert evaluation
     * Tries to fetch from DemographicData model first, falls back to likert_answers JSON
     */
    public function getDemographicData(PaperEvaluation $evaluation): array
    {
        // Try to load demographic data from DemographicData model
        $demographicData = $evaluation->demographicData;

        if ($demographicData) {
            return [
                'genero' => $this->capitalizeGender($demographicData->gender),
                'turno' => $this->capitalizeShift($demographicData->work_schedule),
                'tipo_contrato' => $this->capitalizeContractType($demographicData->contract_type),
                'puesto' => $demographicData->position,
                'area' => $demographicData->department,
            ];
        }

        // Fallback to likert_answers JSON if DemographicData not found
        $likertAnswers = $evaluation->likert_answers ?? [];

        return [
            'genero' => $this->formatGender($likertAnswers['genero'] ?? null),
            'turno' => $this->formatShift($likertAnswers['turno'] ?? null),
            'tipo_contrato' => $this->formatContractType($likertAnswers['tipo_contrato'] ?? null),
            'puesto' => $likertAnswers['puestos'] ?? null,
            'area' => $likertAnswers['areas'] ?? null,
        ];
    }

    /**
     * Get demographic data from already eager-loaded relationship (optimized for bulk processing)
     * Avoids N+1 queries by using the pre-loaded relationshipLoaded
     */
    public function getDemographicDataFromLoaded(PaperEvaluation $evaluation): array
    {
        // Check if demographicData relationship is already loaded
        if ($evaluation->relationLoaded('demographicData') && $evaluation->demographicData) {
            $demographicData = $evaluation->demographicData;

            return [
                'genero' => $this->capitalizeGender($demographicData->gender),
                'turno' => $this->capitalizeShift($demographicData->work_schedule),
                'tipo_contrato' => $this->capitalizeContractType($demographicData->contract_type),
                'puesto' => $demographicData->position,
                'area' => $demographicData->department,
            ];
        }

        // Fallback to likert_answers JSON if DemographicData not loaded
        $likertAnswers = $evaluation->likert_answers ?? [];

        return [
            'genero' => $this->formatGender($likertAnswers['genero'] ?? null),
            'turno' => $this->formatShift($likertAnswers['turno'] ?? null),
            'tipo_contrato' => $this->formatContractType($likertAnswers['tipo_contrato'] ?? null),
            'puesto' => $likertAnswers['puestos'] ?? null,
            'area' => $likertAnswers['areas'] ?? null,
        ];
    }

    /**
     * Calculate Likert scores from raw data (optimized for bulk processing)
     * Avoids loading config multiple times by accepting it as parameter
     */
    public function calculateLikertScoresFromData(array $questions, array $config): array
    {
        if (empty($questions)) {
            return [
                'dimensions' => [],
                'total_score' => 0,
                'interpretation' => null,
            ];
        }

        $scores = [];
        $totalScore = 0;

        // Calculate score for each dimension
        foreach ($config['niveles'] as $dimension => $data) {
            $dimensionScore = 0;

            foreach ($data['preguntas'] as $questionNum) {
                $answer = $questions[(string) $questionNum] ?? null;
                $dimensionScore += $config['valorOpciones'][$answer] ?? 0;
            }

            // Get interpretation for this dimension
            $interpretation = $this->getInterpretation($dimensionScore, $config['valorNiveles'][$dimension]);

            $scores[$dimension] = [
                'score' => $dimensionScore,
                'interpretation' => $interpretation,
                'questions' => $data['preguntas'],
            ];

            $totalScore += $dimensionScore;
        }

        // Get interpretation for total climate score
        $generalInterpretation = $this->getInterpretation($totalScore, $config['valorNiveles']['Clima Laboral']);

        return [
            'dimensions' => $scores,
            'total_score' => $totalScore,
            'interpretation' => $generalInterpretation,
        ];
    }

    /**
     * Format gender value
     */
    protected function formatGender(?string $gender): ?string
    {
        if (! $gender) {
            return null;
        }

        $mapping = [
            'masculino' => 'Masculino',
            'femenino' => 'Femenino',
        ];

        return $mapping[strtolower($gender)] ?? ucfirst($gender);
    }

    /**
     * Format shift value
     */
    protected function formatShift(?string $shift): ?string
    {
        if (! $shift) {
            return null;
        }

        $mapping = [
            'matutino' => 'Matutino',
            'vespertino' => 'Vespertino',
            'nocturno' => 'Nocturno',
        ];

        return $mapping[strtolower($shift)] ?? ucfirst($shift);
    }

    /**
     * Format contract type value
     */
    protected function formatContractType(?string $contractType): ?string
    {
        if (! $contractType) {
            return null;
        }

        $mapping = [
            'por_obra_o_proyecto' => 'Por obra o proyecto',
            'por_tiempo_determinado' => 'Por tiempo determinado',
            'tiempo_indeterminado' => 'Tiempo indeterminado',
            'honorarios' => 'Honorarios',
            'confianza' => 'Confianza',
        ];

        return $mapping[strtolower($contractType)] ?? ucfirst(str_replace('_', ' ', $contractType));
    }

    /**
     * Capitalize gender value from DemographicData model (in English)
     */
    protected function capitalizeGender(?string $gender): ?string
    {
        if (! $gender) {
            return null;
        }

        $mapping = [
            'male' => 'Masculino',
            'female' => 'Femenino',
        ];

        return $mapping[strtolower($gender)] ?? ucfirst($gender);
    }

    /**
     * Capitalize shift value from DemographicData model (in English)
     */
    protected function capitalizeShift(?string $shift): ?string
    {
        if (! $shift) {
            return null;
        }

        $mapping = [
            'morning' => 'Matutino',
            'afternoon' => 'Vespertino',
            'night' => 'Nocturno',
            'morning_afternoon' => 'Matutino-Vespertino',
            'afternoon_night' => 'Vespertino-Nocturno',
            'rotating' => 'Rotativo',
        ];

        return $mapping[strtolower($shift)] ?? ucfirst(str_replace('_', ' ', $shift));
    }

    /**
     * Capitalize contract type from DemographicData model (in English)
     */
    protected function capitalizeContractType(?string $contractType): ?string
    {
        if (! $contractType) {
            return null;
        }

        $mapping = [
            'permanent' => 'Tiempo indeterminado',
            'fixed_term' => 'Por tiempo determinado',
            'project_based' => 'Por obra o proyecto',
            'honorarios' => 'Honorarios',
            'confidence' => 'Confianza',
            'unionized' => 'Sindicalizado',
        ];

        return $mapping[strtolower($contractType)] ?? ucfirst(str_replace('_', ' ', $contractType));
    }

    /**
     * Get summary statistics for multiple evaluations
     */
    public function calculateOrganizationStatistics(array $evaluations): array
    {
        if (empty($evaluations)) {
            return [
                'total_evaluations' => 0,
                'average_score' => 0,
                'dimension_averages' => [],
                'interpretation_distribution' => [],
            ];
        }

        $totalEvaluations = count($evaluations);
        $dimensionScores = [];
        $totalScores = [];
        $interpretations = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->calculateLikertScores($evaluation);

            $totalScores[] = $scores['total_score'];

            if (isset($scores['interpretation'])) {
                $interpretations[$scores['interpretation']] = ($interpretations[$scores['interpretation']] ?? 0) + 1;
            }

            foreach ($scores['dimensions'] as $dimension => $data) {
                if (! isset($dimensionScores[$dimension])) {
                    $dimensionScores[$dimension] = [];
                }
                $dimensionScores[$dimension][] = $data['score'];
            }
        }

        // Calculate averages
        $averageScore = ! empty($totalScores) ? array_sum($totalScores) / count($totalScores) : 0;

        $dimensionAverages = [];
        foreach ($dimensionScores as $dimension => $scores) {
            $dimensionAverages[$dimension] = ! empty($scores) ? array_sum($scores) / count($scores) : 0;
        }

        return [
            'total_evaluations' => $totalEvaluations,
            'average_score' => round($averageScore, 2),
            'dimension_averages' => $dimensionAverages,
            'interpretation_distribution' => $interpretations,
        ];
    }
}
