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
            $finalRiskLevel = $scores['final_risk']['level'] ?? 'Nulo';

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
            $finalRiskLevel = $scores['final_risk']['level'] ?? 'Nulo';

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
