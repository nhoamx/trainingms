<?php

namespace App\Services;

class ReportPdfService
{
    public function __construct(
        protected PaperEvaluationReportService $paperReportService,
        protected PaperEvaluationScoreService $scoreService,
        protected ExecutiveReportService $executiveReportService
    ) {}

    /**
     * Get demographic distribution data for PDF report
     */
    public function getDemographicDistributionData(string $organizationId): array
    {
        return $this->paperReportService->getDemographicDistribution($organizationId);
    }

    /**
     * Get traumatic events data for PDF report
     */
    public function getTraumaticEventsData(string $organizationId): array
    {
        // Get CITSATS data from Referencia III evaluations (not Referencia I)
        $citsatsEvaluations = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->whereNotNull('citsats_s1')
            ->get();

        // Also get Referencia V for demographic data
        $referenciaVEvaluations = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_v')
            ->get()
            ->keyBy('personal_folio');

        if ($citsatsEvaluations->isEmpty()) {
            return [
                'total_affected' => 0,
                'by_gender' => ['Hombres' => 0, 'Mujeres' => 0, 'GNE' => 0],
                'by_event_type' => [],
            ];
        }

        // Define CITSATS questions mapping
        $eventTypes = [
            '1' => 'Accidente que tenga como consecuencia la muerte, la pérdida de un miembro o una lesión grave?',
            '2' => 'Asaltos?',
            '3' => 'Actos violentos que derivaron en lesiones graves?',
            '4' => 'Secuestro?',
            '5' => 'Amenazas?, o',
            '6' => 'Cualquier otro que ponga en riesgo su vida o salud, y/o la de otras personas?',
        ];

        $affectedWorkers = [];
        $eventCounts = [];
        $genderCounts = ['Hombres' => 0, 'Mujeres' => 0, 'GNE' => 0];

        foreach ($citsatsEvaluations as $evaluation) {
            $citsatsData = $evaluation->citsats_s1;
            $hasTrauma = false;

            if (is_array($citsatsData)) {
                foreach ($citsatsData as $questionId => $answer) {
                    if (strtoupper($answer) === 'SI') {
                        $hasTrauma = true;

                        if (! isset($eventCounts[$questionId])) {
                            $eventCounts[$questionId] = [
                                'description' => $eventTypes[$questionId] ?? 'Desconocido',
                                'hombres' => 0,
                                'mujeres' => 0,
                                'gne' => 0,
                                'total' => 0,
                            ];
                        }

                        $eventCounts[$questionId]['total']++;

                        // Get gender from Referencia V demographic data
                        $referenciaV = $referenciaVEvaluations->get($evaluation->personal_folio);
                        $gender = null;

                        if ($referenciaV) {
                            $demographicData = $referenciaV->demographic_data ?? [];
                            $gender = $demographicData['sexo'] ?? null;

                            if (is_array($gender)) {
                                $gender = $gender['value'] ?? $gender['label'] ?? null;
                            }
                        }

                        if ($gender && (stripos($gender, 'Hombre') !== false || stripos($gender, 'Masculino') !== false)) {
                            $eventCounts[$questionId]['hombres']++;
                        } elseif ($gender && (stripos($gender, 'Mujer') !== false || stripos($gender, 'Femenino') !== false)) {
                            $eventCounts[$questionId]['mujeres']++;
                        } else {
                            $eventCounts[$questionId]['gne']++;
                        }
                    }
                }
            }

            if ($hasTrauma && ! in_array($evaluation->personal_folio, $affectedWorkers)) {
                $affectedWorkers[] = $evaluation->personal_folio;

                // Count gender for total affected
                $referenciaV = $referenciaVEvaluations->get($evaluation->personal_folio);
                $gender = null;

                if ($referenciaV) {
                    $demographicData = $referenciaV->demographic_data ?? [];
                    $gender = $demographicData['sexo'] ?? null;

                    if (is_array($gender)) {
                        $gender = $gender['value'] ?? $gender['label'] ?? null;
                    }
                }

                if ($gender && (stripos($gender, 'Hombre') !== false || stripos($gender, 'Masculino') !== false)) {
                    $genderCounts['Hombres']++;
                } elseif ($gender && (stripos($gender, 'Mujer') !== false || stripos($gender, 'Femenino') !== false)) {
                    $genderCounts['Mujeres']++;
                } else {
                    $genderCounts['GNE']++;
                }
            }
        }

        return [
            'total_affected' => count($affectedWorkers),
            'by_gender' => $genderCounts,
            'by_event_type' => $eventCounts,
        ];
    }

    /**
     * Get diagnostic results data for PDF report
     */
    public function getDiagnosticResultsData(string $organizationId): array
    {
        $reportData = $this->paperReportService->getReportSummaryByOrganization($organizationId);

        if (empty($reportData)) {
            return [];
        }

        // Format final risk distribution
        $finalRiskDistribution = $this->formatFinalRiskDistribution($reportData['final_risk_levels'] ?? []);

        // Format category distribution
        $categoryDistribution = $this->formatDistribution($reportData['grouped_by_category'] ?? [], 'categoria');

        // Format domain distribution
        $domainDistribution = $this->formatDistribution($reportData['grouped_by_domain'] ?? [], 'dominio');

        // Format dimension distribution
        $dimensionDistribution = $this->formatDistribution($reportData['grouped_by_dimension'] ?? [], 'dimension');

        // Count total participants
        $totalParticipants = $this->calculateTotalParticipants($reportData['final_risk_levels'] ?? []);

        // Get additional data for new sections
        $finalRiskByArea = $this->getFinalRiskByArea($organizationId);
        $finalRiskByPuesto = $this->getFinalRiskByPuesto($organizationId);
        $responseFrequencies = $this->getResponseFrequencies($organizationId);
        $questionTexts = $this->getQuestionTexts();
        $blockScores = $this->getBlockScores($organizationId);
        $generalScore = $this->getGeneralScore($organizationId);

        return [
            'final_risk' => $finalRiskDistribution,
            'categories' => $categoryDistribution,
            'domains' => $domainDistribution,
            'dimensions' => $dimensionDistribution,
            'total_participants' => $totalParticipants,
            'final_risk_by_area' => $finalRiskByArea,
            'final_risk_by_puesto' => $finalRiskByPuesto,
            'response_frequencies' => $responseFrequencies,
            'question_texts' => $questionTexts,
            'blocks' => $blockScores,
            'general_score' => $generalScore,
        ];
    }

    /**
     * Compute obtained and maximum scores per block of questions (01-12)
     * Blocks mapping based on NOM-035 Referencia III guidance.
     * Returns array keyed by two-digit block number with: name, obtained, max, questions.
     */
    private function getBlockScores(string $organizationId): array
    {
        // Define blocks and their question numbers
        $blocks = [
            '01' => ['name' => 'Condiciones ambientales del centro de trabajo', 'questions' => [1, 2, 3, 4, 5]],
            '02' => ['name' => 'Cantidad y ritmo de trabajo', 'questions' => [6, 7, 8]],
            '03' => ['name' => 'Esfuerzo mental requerido', 'questions' => [9, 10, 11, 12]],
            '04' => ['name' => 'Actividades y responsabilidades del trabajo', 'questions' => [13, 14, 15, 16]],
            '05' => ['name' => 'Jornada de trabajo', 'questions' => [17, 18, 19, 20, 21, 22]],
            '06' => ['name' => 'Decisiones en el trabajo', 'questions' => [23, 24, 25, 26, 27, 28]],
            '07' => ['name' => 'Cambios en el trabajo', 'questions' => [29, 30]],
            '08' => ['name' => 'Capacitación e información', 'questions' => [31, 32, 33, 34, 35, 36]],
            '09' => ['name' => 'Relación con jefes', 'questions' => [37, 38, 39, 40, 41]],
            '10' => ['name' => 'Relación con compañeros', 'questions' => [42, 43, 44, 45, 46]],
            '11' => ['name' => 'Información, reconocimiento, pertenencia y estabilidad', 'questions' => [47, 48, 49, 50, 51, 52, 53, 54, 55, 56]],
            '12' => ['name' => 'Violencia laboral', 'questions' => [57, 58, 59, 60, 61, 62, 63, 64]],
        ];

        // Fetch evaluations
        $evaluations = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->get();

        $participantCount = $evaluations->count();

        // Initialize results
        $results = [];
        foreach ($blocks as $blockNo => $info) {
            $results[$blockNo] = [
                'name' => $info['name'],
                'obtained' => 0,
                'max' => ($participantCount * count($info['questions']) * 4),
                'questions' => $info['questions'],
            ];
        }

        if ($participantCount === 0) {
            return $results;
        }

        // Load answer value config and prepare quick lookup for groups
        $answerConfig = config('answer_values');
        $groupLookups = [];
        foreach ($answerConfig as $groupKey => $group) {
            foreach ($group['questions'] as $q) {
                $groupLookups[$q] = $groupKey;
            }
        }

        // Helper to get score for answer
        $getScore = function (int $questionNumber, ?string $answer) use ($answerConfig, $groupLookups): int {
            if (! $answer) {
                return 0;
            }
            $qKey = str_pad((string) $questionNumber, 2, '0', STR_PAD_LEFT);
            $groupKey = $groupLookups[$qKey] ?? null;
            if (! $groupKey) {
                return 0;
            }
            $answer = strtoupper(trim($answer));

            return $answerConfig[$groupKey]['values'][$answer] ?? 0;
        };

        // Sum obtained points per block
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];
            // Normalize keys to both numeric and zero-padded strings
            $normalized = [];
            foreach ($answers as $key => $val) {
                $num = is_numeric($key) ? (int) $key : (int) ltrim((string) $key, '0');
                if ($num >= 1 && $num <= 64) {
                    $normalized[$num] = $val;
                }
            }

            foreach ($blocks as $blockNo => $info) {
                $obtainedThisEval = 0;
                foreach ($info['questions'] as $q) {
                    $ans = $normalized[$q] ?? null;
                    $obtainedThisEval += $getScore($q, is_string($ans) ? $ans : null);
                }
                $results[$blockNo]['obtained'] += $obtainedThisEval;
            }
        }

        return $results;
    }

    /**
     * Calculate general score breakdown (A, B, C)
     * A) 64 questions for all participants
     * B) 4 questions for client/user attention (conditional questions 65-68)
     * C) 4 questions for management (conditional questions 69-72)
     */
    private function getGeneralScore(string $organizationId): array
    {
        $evaluations = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->get();

        $totalParticipants = $evaluations->count();

        // Count participants with conditional sections
        $clientAttentionCount = 0;
        $managementCount = 0;

        $obtainedA = 0;
        $obtainedB = 0;
        $obtainedC = 0;

        // Load answer value config
        $answerConfig = config('answer_values');
        $groupLookups = [];
        foreach ($answerConfig as $groupKey => $group) {
            foreach ($group['questions'] as $q) {
                $groupLookups[$q] = $groupKey;
            }
        }

        $getScore = function (int $questionNumber, ?string $answer) use ($answerConfig, $groupLookups): int {
            if (! $answer) {
                return 0;
            }
            $qKey = str_pad((string) $questionNumber, 2, '0', STR_PAD_LEFT);
            $groupKey = $groupLookups[$qKey] ?? null;
            if (! $groupKey) {
                return 0;
            }
            $answer = strtoupper(trim($answer));

            return $answerConfig[$groupKey]['values'][$answer] ?? 0;
        };

        foreach ($evaluations as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];
            $conditionalAnswers = $evaluation->referencia_iii_conditional ?? [];

            // Normalize answers keys for questions 1-64
            $normalized = [];
            foreach ($answers as $key => $val) {
                $num = is_numeric($key) ? (int) $key : (int) ltrim((string) $key, '0');
                if ($num >= 1 && $num <= 64) {
                    $normalized[$num] = $val;
                }
            }

            // A) Calculate score for questions 1-64
            for ($q = 1; $q <= 64; $q++) {
                $ans = $normalized[$q] ?? null;
                $obtainedA += $getScore($q, is_string($ans) ? $ans : null);
            }

            // B) Client attention conditional (questions 65-68)
            if (isset($conditionalAnswers['client_attention']['condition'])
                && $conditionalAnswers['client_attention']['condition'] === 'SI') {
                $clientAttentionCount++;
                $clientQuestions = $conditionalAnswers['client_attention']['questions'] ?? [];
                foreach ([65, 66, 67, 68] as $q) {
                    $ans = $clientQuestions[$q] ?? null;
                    $obtainedB += $getScore($q, $ans);
                }
            }

            // C) Management conditional (questions 69-72)
            if (isset($conditionalAnswers['management']['condition'])
                && $conditionalAnswers['management']['condition'] === 'SI') {
                $managementCount++;
                $managementQuestions = $conditionalAnswers['management']['questions'] ?? [];
                foreach ([69, 70, 71, 72] as $q) {
                    $ans = $managementQuestions[$q] ?? null;
                    $obtainedC += $getScore($q, $ans);
                }
            }
        }

        $maxA = $totalParticipants * 64 * 4;
        $maxB = $clientAttentionCount * 4 * 4;
        $maxC = $managementCount * 4 * 4;
        $maxGeneral = $maxA + $maxB + $maxC;
        $obtainedGeneral = $obtainedA + $obtainedB + $obtainedC;

        return [
            'general' => [
                'obtained' => $obtainedGeneral,
                'max' => $maxGeneral,
                'participants' => $totalParticipants,
            ],
            'a' => [
                'name' => 'Preguntas generales (1-64)',
                'obtained' => $obtainedA,
                'max' => $maxA,
                'participants' => $totalParticipants,
            ],
            'b' => [
                'name' => 'Atención a clientes y usuarios (65-68)',
                'obtained' => $obtainedB,
                'max' => $maxB,
                'participants' => $clientAttentionCount,
            ],
            'c' => [
                'name' => 'Relación con colaboradores que supervisa (69-72)',
                'obtained' => $obtainedC,
                'max' => $maxC,
                'participants' => $managementCount,
            ],
        ];
    }

    /**
     * Format final risk distribution from report data
     */
    private function formatFinalRiskDistribution(array $finalRiskLevels): array
    {
        $distribution = [
            'Nulo' => 0,
            'Bajo' => 0,
            'Medio' => 0,
            'Alto' => 0,
            'Muy Alto' => 0,
        ];

        foreach ($finalRiskLevels as $item) {
            $riskLevel = $item['nivel_riesgo'] ?? null;
            $count = $item['conteo'] ?? 0;

            if ($riskLevel && isset($distribution[$riskLevel])) {
                $distribution[$riskLevel] = $count;
            }
        }

        return $distribution;
    }

    /**
     * Format distribution data for categories, domains, or dimensions
     */
    private function formatDistribution(array $items, string $typeKey): array
    {
        $distribution = [];

        foreach ($items as $item) {
            $name = $item[$typeKey] ?? null;
            $riskLevel = $item['nivel_riesgo'] ?? null;
            $count = $item['conteo'] ?? 0;

            if (! $name || ! $riskLevel) {
                continue;
            }

            if (! isset($distribution[$name])) {
                $distribution[$name] = [
                    'Nulo' => 0,
                    'Bajo' => 0,
                    'Medio' => 0,
                    'Alto' => 0,
                    'Muy Alto' => 0,
                ];
            }

            $distribution[$name][$riskLevel] = $count;
        }

        return $distribution;
    }

    /**
     * Calculate total participants from final risk levels
     */
    private function calculateTotalParticipants(array $finalRiskLevels): int
    {
        $total = 0;

        foreach ($finalRiskLevels as $item) {
            $total += $item['conteo'] ?? 0;
        }

        return $total;
    }

    /**
     * Get final risk distribution by area
     */
    private function getFinalRiskByArea(string $organizationId): array
    {
        $evaluations = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->get();

        $distribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $totalScore = $scores['total_score'] ?? 0;
            $finalRiskLevel = $this->scoreService->calculateRiskLevel($totalScore);

            // Extract area from demographic data
            $area = $this->extractAreaFromEvaluation($evaluation);

            if (! isset($distribution[$area])) {
                $distribution[$area] = [
                    'Nulo' => 0,
                    'Bajo' => 0,
                    'Medio' => 0,
                    'Alto' => 0,
                    'Muy Alto' => 0,
                ];
            }

            $distribution[$area][$finalRiskLevel]++;
        }

        return $distribution;
    }

    /**
     * Get final risk distribution by puesto
     */
    private function getFinalRiskByPuesto(string $organizationId): array
    {
        $evaluations = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->get();

        $distribution = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $totalScore = $scores['total_score'] ?? 0;
            $finalRiskLevel = $this->scoreService->calculateRiskLevel($totalScore);

            // Extract puesto from demographic data
            $puesto = $this->extractPuestoFromEvaluation($evaluation);

            if (! isset($distribution[$puesto])) {
                $distribution[$puesto] = [
                    'Nulo' => 0,
                    'Bajo' => 0,
                    'Medio' => 0,
                    'Alto' => 0,
                    'Muy Alto' => 0,
                ];
            }

            $distribution[$puesto][$finalRiskLevel]++;
        }

        return $distribution;
    }

    /**
     * Get response frequencies for all 72 questions
     */
    private function getResponseFrequencies(string $organizationId): array
    {
        $evaluations = \App\Models\PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->get();

        $frequencies = [];

        // Initialize frequencies for questions 1-72
        for ($i = 1; $i <= 72; $i++) {
            $frequencies[$i] = [
                'SI' => 0,
                'CS' => 0,
                'AV' => 0,
                'CN' => 0,
                'NU' => 0,
            ];
        }

        // Load answer value mappings
        $answerConfig = config('answer_values');
        $group1Questions = array_flip($answerConfig['group1']['questions']);
        $group2Questions = array_flip($answerConfig['group2']['questions']);

        // Mapping from bubble answers (A,B,C,D,E) to text labels
        $bubbleToText = [
            'group1' => [
                'A' => 'SI',  // Siempre
                'B' => 'CS',  // Casi Siempre
                'C' => 'AV',  // Algunas Veces
                'D' => 'CN',  // Casi Nunca
                'E' => 'NU',  // Nunca
            ],
            'group2' => [
                'A' => 'SI',  // Siempre (reversed scoring but same label)
                'B' => 'CS',  // Casi Siempre
                'C' => 'AV',  // Algunas Veces
                'D' => 'CN',  // Casi Nunca
                'E' => 'NU',  // Nunca
            ],
        ];

        foreach ($evaluations as $evaluation) {
            $responses = $evaluation->referencia_iii_answers ?? [];

            foreach ($responses as $questionKey => $response) {
                $questionNumber = is_numeric($questionKey) ? (int) $questionKey : null;

                if (! $questionNumber || $questionNumber < 1 || $questionNumber > 72) {
                    continue;
                }

                // Determine which group this question belongs to
                $paddedQuestion = str_pad($questionNumber, 2, '0', STR_PAD_LEFT);
                $group = isset($group1Questions[$paddedQuestion]) ? 'group1' : 'group2';

                // Convert bubble answer to text label
                $response = strtoupper(trim($response));
                if (isset($bubbleToText[$group][$response])) {
                    $textLabel = $bubbleToText[$group][$response];
                    $frequencies[$questionNumber][$textLabel]++;
                }
            }
        }

        return $frequencies;
    }

    /**
     * Get question texts from config
     */
    private function getQuestionTexts(): array
    {
        $config = config('referencia_iii');
        $texts = [];

        // Get general questions (1-64)
        if (isset($config['general'])) {
            foreach ($config['general'] as $questionNumber => $text) {
                $texts[$questionNumber] = $text;
            }
        }

        // Get conditional questions (65-72)
        if (isset($config['conditional_sections'])) {
            foreach ($config['conditional_sections'] as $section) {
                if (isset($section['questions'])) {
                    foreach ($section['questions'] as $questionNumber => $text) {
                        $texts[$questionNumber] = $text;
                    }
                }
            }
        }

        return $texts;
    }

    /**
     * Extract area from evaluation demographic data
     */
    private function extractAreaFromEvaluation(\App\Models\PaperEvaluation $evaluation): string
    {
        // First try to get from Referencia V demographic data
        $referenciaV = \App\Models\PaperEvaluation::where('personal_folio', $evaluation->personal_folio)
            ->where('organization_id', $evaluation->organization_id)
            ->where('evaluation_type', 'referencia_v')
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->first();

        if ($referenciaV && isset($referenciaV->demographic_data['departamento'])) {
            $departamento = $referenciaV->demographic_data['departamento'];

            // Handle both string and array formats
            if (is_array($departamento)) {
                $area = trim(($departamento['fila1'] ?? '').' '.($departamento['fila2'] ?? ''));

                return $area ?: 'Sin área';
            }

            return $departamento ?: 'Sin área';
        }

        // Fallback to evaluation's own demographic data (aunque no debería tener)
        if (isset($evaluation->demographic_data['departamento'])) {
            $departamento = $evaluation->demographic_data['departamento'];

            if (is_array($departamento)) {
                $area = trim(($departamento['fila1'] ?? '').' '.($departamento['fila2'] ?? ''));

                return $area ?: 'Sin área';
            }

            return $departamento ?: 'Sin área';
        }

        return 'Sin área';
    }

    /**
     * Extract puesto from evaluation demographic data
     */
    private function extractPuestoFromEvaluation(\App\Models\PaperEvaluation $evaluation): string
    {
        // First try to get from Referencia V demographic data
        $referenciaV = \App\Models\PaperEvaluation::where('personal_folio', $evaluation->personal_folio)
            ->where('organization_id', $evaluation->organization_id)
            ->where('evaluation_type', 'referencia_v')
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->first();

        if ($referenciaV && isset($referenciaV->demographic_data['ocupacion'])) {
            $ocupacion = $referenciaV->demographic_data['ocupacion'];

            // Handle both string and array formats
            if (is_array($ocupacion)) {
                $puesto = trim(($ocupacion['fila1'] ?? '').' '.($ocupacion['fila2'] ?? ''));

                return $puesto ?: 'Sin puesto';
            }

            return $ocupacion ?: 'Sin puesto';
        }

        // Fallback to evaluation's own demographic data (aunque no debería tener)
        if (isset($evaluation->demographic_data['ocupacion'])) {
            $ocupacion = $evaluation->demographic_data['ocupacion'];

            if (is_array($ocupacion)) {
                $puesto = trim(($ocupacion['fila1'] ?? '').' '.($ocupacion['fila2'] ?? ''));

                return $puesto ?: 'Sin puesto';
            }

            return $ocupacion ?: 'Sin puesto';
        }

        return 'Sin puesto';
    }

    /**
     * Get executive report data for PDF report
     */
    public function getExecutiveReportData(string $organizationId): array
    {
        return $this->executiveReportService->getExecutiveReportData($organizationId);
    }
}
