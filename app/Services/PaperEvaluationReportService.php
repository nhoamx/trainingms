<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;

/**
 * Service for aggregating and reporting on Paper Evaluations
 * Replaces legacy dimension/domain/category report logic
 */
class PaperEvaluationReportService
{
    public function __construct(
        protected PaperEvaluationScoreService $scoreService
    ) {}

    /**
     * Get comprehensive report summary by organization
     * Includes categories, domains, dimensions, final scores, and participants
     */
    public function getReportSummaryByOrganization(?string $organizationId = null): array
    {
        if (! $organizationId) {
            return $this->getEmptyReportStructure();
        }

        // Get all completed paper evaluations for organization
        $evaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii') // Only Referencia III for risk scoring
            ->get();

        if ($evaluations->isEmpty()) {
            return $this->getEmptyReportStructure();
        }

        // Aggregate data by category, domain, dimension
        $categoryData = $this->aggregateByCategory($evaluations);
        $domainData = $this->aggregateByDomain($evaluations);
        $dimensionData = $this->aggregateByDimension($evaluations);
        $finalRiskData = $this->aggregateFinalRiskLevels($evaluations);
        $participantData = $this->aggregateParticipantScores($evaluations);
        $detailedResults = $this->getDetailedResultsForOrganization($evaluations);

        return [
            'grouped_by_category' => $categoryData,
            'grouped_by_domain' => $domainData,
            'grouped_by_dimension' => $dimensionData,
            'final_risk_levels' => $finalRiskData,
            'personalCalification' => $participantData,
            'detailed_results' => $detailedResults,
        ];
    }

    /**
     * Aggregate evaluations by category
     */
    protected function aggregateByCategory(Collection $evaluations): array
    {
        $categoryRiskData = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $personalFolio = $evaluation->personal_folio;

            foreach ($scores['categories'] as $categoryName => $categoryData) {
                $riskLevel = $this->getCategoryRiskLevel($categoryName, $categoryData['score']);

                if (! isset($categoryRiskData[$categoryName][$riskLevel])) {
                    $categoryRiskData[$categoryName][$riskLevel] = [];
                }

                $categoryRiskData[$categoryName][$riskLevel][] = $personalFolio;
            }
        }

        return $this->formatAggregatedData($categoryRiskData, 'categoria');
    }

    /**
     * Aggregate evaluations by domain
     */
    protected function aggregateByDomain(Collection $evaluations): array
    {
        $domainRiskData = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $personalFolio = $evaluation->personal_folio;

            foreach ($scores['domains'] as $domainKey => $domainData) {
                $domainName = $domainData['name'];
                $riskLevel = $this->getDomainRiskLevel($domainName, $domainData['score']);

                if (! isset($domainRiskData[$domainName][$riskLevel])) {
                    $domainRiskData[$domainName][$riskLevel] = [];
                }

                $domainRiskData[$domainName][$riskLevel][] = $personalFolio;
            }
        }

        return $this->formatAggregatedData($domainRiskData, 'dominio');
    }

    /**
     * Aggregate evaluations by dimension
     */
    protected function aggregateByDimension(Collection $evaluations): array
    {
        $dimensionRiskData = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $personalFolio = $evaluation->personal_folio;

            foreach ($scores['dimensions'] as $dimensionKey => $dimensionData) {
                $dimensionName = $dimensionData['name'];
                $riskLevel = $this->getDimensionRiskLevel($dimensionName, $dimensionData['score']);

                if (! isset($dimensionRiskData[$dimensionName][$riskLevel])) {
                    $dimensionRiskData[$dimensionName][$riskLevel] = [];
                }

                $dimensionRiskData[$dimensionName][$riskLevel][] = $personalFolio;
            }
        }

        return $this->formatAggregatedData($dimensionRiskData, 'dimension');
    }

    /**
     * Aggregate final risk levels across all evaluations
     */
    protected function aggregateFinalRiskLevels(Collection $evaluations): array
    {
        $finalRiskData = [
            'Nulo' => [],
            'Bajo' => [],
            'Medio' => [],
            'Alto' => [],
            'Muy Alto' => [],
        ];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);
            $finalRiskData[$riskLevel][] = $evaluation->personal_folio;
        }

        // Format for frontend
        $result = [];
        foreach ($finalRiskData as $level => $personal) {
            $result[] = [
                'nivel_riesgo' => $level,
                'conteo' => count($personal),
                'personal' => $personal,
            ];
        }

        return $result;
    }

    /**
     * Aggregate participant scores
     */
    protected function aggregateParticipantScores(Collection $evaluations): array
    {
        $participantScores = [];

        foreach ($evaluations as $evaluation) {
            $scores = $this->scoreService->calculateReferenciaIIIScores($evaluation);
            $totalScore = $scores['total_score'];
            $riskLevel = $this->scoreService->calculateRiskLevel($totalScore);

            $participantScores[] = [
                'personal_folio' => $evaluation->personal_folio,
                'folio' => $evaluation->folio,
                'calificacion' => $totalScore,
                'nivel_riesgo' => $riskLevel,
                'created_at' => $evaluation->created_at->format('Y-m-d H:i:s'),
            ];
        }

        // Sort by score descending
        usort($participantScores, fn ($a, $b) => $b['calificacion'] <=> $a['calificacion']);

        return $participantScores;
    }

    /**
     * Get detailed results for all evaluations in organization
     * Returns a unified table structure with AVERAGE scores across all evaluations
     */
    protected function getDetailedResultsForOrganization(Collection $evaluations): array
    {
        if ($evaluations->isEmpty()) {
            return [];
        }

        // Get the first evaluation to establish the structure
        $firstEvaluation = $evaluations->first();
        $detailedResults = $this->scoreService->getDetailedResults($firstEvaluation);

        // Group by categoria -> dominio -> dimension -> item to get unique structure
        // and accumulate scores from all evaluations
        $grouped = [];
        $itemScores = []; // Track scores for averaging: [catNombre][domNombre][dimNombre][itemNumero] = [scores]

        // Process all evaluations to accumulate scores
        foreach ($evaluations as $evaluation) {
            $evalResults = $this->scoreService->getDetailedResults($evaluation);

            foreach ($evalResults as $row) {
                $catNombre = $row['categoria']['nombre'];
                $domNombre = $row['dominio']['nombre'];
                $dimNombre = $row['dimension'];
                $itemNombre = $row['item'];
                $itemNumero = $row['item_numero'] ?? $itemNombre;

                // Initialize structure
                if (! isset($grouped[$catNombre])) {
                    $grouped[$catNombre] = [
                        'nombre' => $catNombre,
                        'dominios' => [],
                    ];
                }

                if (! isset($grouped[$catNombre]['dominios'][$domNombre])) {
                    $grouped[$catNombre]['dominios'][$domNombre] = [
                        'nombre' => $domNombre,
                        'dimensiones' => [],
                    ];
                }

                if (! isset($grouped[$catNombre]['dominios'][$domNombre]['dimensiones'][$dimNombre])) {
                    $grouped[$catNombre]['dominios'][$domNombre]['dimensiones'][$dimNombre] = [
                        'nombre' => $dimNombre,
                        'items' => [],
                    ];
                }

                // Accumulate scores for averaging
                if (! isset($itemScores[$catNombre][$domNombre][$dimNombre][$itemNumero])) {
                    $itemScores[$catNombre][$domNombre][$dimNombre][$itemNumero] = [
                        'scores' => [],
                        'nombre' => $itemNombre,
                    ];
                }

                $itemScores[$catNombre][$domNombre][$dimNombre][$itemNumero]['scores'][] = $row['puntaje'];
            }
        }

        // Calculate averages and build items structure
        foreach ($itemScores as $catNombre => $dominios) {
            foreach ($dominios as $domNombre => $dimensiones) {
                foreach ($dimensiones as $dimNombre => $items) {
                    foreach ($items as $itemNumero => $itemData) {
                        $scores = $itemData['scores'];
                        $promedio = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
                        // Round to nearest integer (frequency level)
                        $promedioRedondeado = round($promedio);

                        $grouped[$catNombre]['dominios'][$domNombre]['dimensiones'][$dimNombre]['items'][] = [
                            'nombre' => $itemData['nombre'],
                            'item_numero' => $itemNumero,
                            'puntaje' => $promedioRedondeado, // Rounded average for frequency
                            'promedio' => round($promedio, 2), // Precise average for display
                        ];
                    }
                }
            }
        }

        // Convert to array structure for frontend
        $result = [];
        foreach ($grouped as $catData) {
            $dominios = [];
            $catSumatoria = 0;

            foreach ($catData['dominios'] as $domData) {
                $dimensiones = [];
                $domSumatoria = 0;

                foreach ($domData['dimensiones'] as $dimData) {
                    // Calculate dimension sumatoria (sum of rounded item averages)
                    $dimSumatoria = 0;
                    foreach ($dimData['items'] as $item) {
                        $dimSumatoria += $item['puntaje']; // Already rounded
                    }

                    $dimData['sumatoria'] = $dimSumatoria;
                    // Dimensions don't have specific NOM-035 thresholds, so no nivel_riesgo
                    $domSumatoria += $dimSumatoria;

                    $dimensiones[] = $dimData;
                }

                $domData['dimensiones'] = $dimensiones;
                $domData['sumatoria'] = $domSumatoria;
                // Calculate risk level for domain based on its sumatoria
                $domData['nivel_riesgo'] = $this->getDomainRiskLevel($domData['nombre'], $domSumatoria);
                $catSumatoria += $domSumatoria;

                $dominios[] = $domData;
            }

            $catData['dominios'] = $dominios;
            $catData['sumatoria'] = $catSumatoria;
            // Calculate risk level for category based on its sumatoria
            $catData['nivel_riesgo'] = $this->getCategoryRiskLevel($catData['nombre'], $catSumatoria);

            // Calculate rowspans
            $catData['rowspan'] = 0;
            foreach ($catData['dominios'] as &$dom) {
                $dom['rowspan'] = 0;
                foreach ($dom['dimensiones'] as &$dim) {
                    $dim['rowspan'] = count($dim['items']);
                    $dom['rowspan'] += $dim['rowspan'];
                }
                $catData['rowspan'] += $dom['rowspan'];
            }

            $result[] = $catData;
        }

        return $result;
    }

    /**
     * Format aggregated data for frontend consumption
     */
    protected function formatAggregatedData(array $aggregatedData, string $type): array
    {
        $result = [];

        foreach ($aggregatedData as $name => $riskLevels) {
            foreach ($riskLevels as $level => $personal) {
                $result[] = [
                    $type => $name,
                    'nivel_riesgo' => $level,
                    'conteo' => count($personal),
                    'personal' => $personal,
                ];
            }
        }

        return $result;
    }

    /**
     * Get risk level for a category based on its score
     * Uses NOM-035 specific category thresholds
     */
    protected function getCategoryRiskLevel(string $categoryName, int $score): string
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
        return $this->scoreService->calculateRiskLevel($score);
    }

    /**
     * Get risk level for a domain based on its score
     * Uses NOM-035 specific domain thresholds
     */
    protected function getDomainRiskLevel(string $domainName, int $score): string
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
        return $this->scoreService->calculateRiskLevel($score);
    }

    /**
     * Get risk level for a dimension based on its score
     */
    protected function getDimensionRiskLevel(string $dimensionName, int $score): string
    {
        $dimensionThresholds = $this->getDimensionRiskThresholds();

        if (isset($dimensionThresholds[$dimensionName])) {
            foreach ($dimensionThresholds[$dimensionName] as $threshold) {
                $min = $threshold['min'] ?? 0;
                $max = $threshold['max'] ?? PHP_INT_MAX;

                if ($score >= $min && $score <= $max) {
                    return $threshold['level'];
                }
            }
        }

        // Fallback to general risk calculation
        return $this->scoreService->calculateRiskLevel($score);
    }

    /**
     * Get domain risk thresholds based on NOM-035
     */
    public function getDomainRiskThresholds(): array
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

    /**
     * Get category risk thresholds based on NOM-035
     */
    public function getCategoryRiskThresholds(): array
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
     * Get dimension risk thresholds based on NOM-035
     */
    protected function getDimensionRiskThresholds(): array
    {
        // Dimensions use proportional thresholds based on their parent domain
        // These are approximations based on the typical score ranges for each dimension
        return [
            // Categoría 1: Ambiente de trabajo -> Dominio: Condiciones en el ambiente de trabajo
            'Condiciones peligrosas e inseguras' => [ // 2 items, max ~8
                ['max' => 1, 'level' => 'Nulo'],
                ['min' => 2, 'max' => 3, 'level' => 'Bajo'],
                ['min' => 4, 'max' => 5, 'level' => 'Medio'],
                ['min' => 6, 'max' => 7, 'level' => 'Alto'],
                ['min' => 8, 'level' => 'Muy Alto'],
            ],
            'Condiciones deficientes e insalubres' => [ // 2 items, max ~8
                ['max' => 1, 'level' => 'Nulo'],
                ['min' => 2, 'max' => 3, 'level' => 'Bajo'],
                ['min' => 4, 'max' => 5, 'level' => 'Medio'],
                ['min' => 6, 'max' => 7, 'level' => 'Alto'],
                ['min' => 8, 'level' => 'Muy Alto'],
            ],
            'Trabajos peligrosos' => [ // 1 item, max ~4
                ['max' => 0, 'level' => 'Nulo'],
                ['min' => 1, 'max' => 1, 'level' => 'Bajo'],
                ['min' => 2, 'max' => 2, 'level' => 'Medio'],
                ['min' => 3, 'max' => 3, 'level' => 'Alto'],
                ['min' => 4, 'level' => 'Muy Alto'],
            ],

            // Categoría 2: Factores propios de la actividad -> Dominio: Carga de trabajo
            'Cargas cuantitativas' => [ // 3 items, max ~12
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 6, 'level' => 'Bajo'],
                ['min' => 7, 'max' => 8, 'level' => 'Medio'],
                ['min' => 9, 'max' => 11, 'level' => 'Alto'],
                ['min' => 12, 'level' => 'Muy Alto'],
            ],
            'Ritmos de trabajo acelerado' => [ // 2 items, max ~8
                ['max' => 2, 'level' => 'Nulo'],
                ['min' => 3, 'max' => 4, 'level' => 'Bajo'],
                ['min' => 5, 'max' => 5, 'level' => 'Medio'],
                ['min' => 6, 'max' => 7, 'level' => 'Alto'],
                ['min' => 8, 'level' => 'Muy Alto'],
            ],
            'Carga mental' => [ // 4 items, max ~16
                ['max' => 5, 'level' => 'Nulo'],
                ['min' => 6, 'max' => 8, 'level' => 'Bajo'],
                ['min' => 9, 'max' => 11, 'level' => 'Medio'],
                ['min' => 12, 'max' => 14, 'level' => 'Alto'],
                ['min' => 15, 'level' => 'Muy Alto'],
            ],
            'Cargas psicológicas emocionales' => [ // 1 item, max ~4
                ['max' => 1, 'level' => 'Nulo'],
                ['min' => 2, 'max' => 2, 'level' => 'Bajo'],
                ['min' => 3, 'max' => 3, 'level' => 'Medio'],
                ['min' => 4, 'max' => 4, 'level' => 'Alto'],
                ['min' => 5, 'level' => 'Muy Alto'],
            ],
            'Cargas de alta responsabilidad' => [ // 2 items, max ~8
                ['max' => 2, 'level' => 'Nulo'],
                ['min' => 3, 'max' => 4, 'level' => 'Bajo'],
                ['min' => 5, 'max' => 5, 'level' => 'Medio'],
                ['min' => 6, 'max' => 7, 'level' => 'Alto'],
                ['min' => 8, 'level' => 'Muy Alto'],
            ],
            'Cargas contradictorias o inconsistentes' => [ // 3 items, max ~12
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 6, 'level' => 'Bajo'],
                ['min' => 7, 'max' => 8, 'level' => 'Medio'],
                ['min' => 9, 'max' => 11, 'level' => 'Alto'],
                ['min' => 12, 'level' => 'Muy Alto'],
            ],

            // Dominio: Falta de control sobre el trabajo
            'Falta de control y autonomía sobre el trabajo' => [ // 5 items, max ~20
                ['max' => 5, 'level' => 'Nulo'],
                ['min' => 6, 'max' => 9, 'level' => 'Bajo'],
                ['min' => 10, 'max' => 13, 'level' => 'Medio'],
                ['min' => 14, 'max' => 17, 'level' => 'Alto'],
                ['min' => 18, 'level' => 'Muy Alto'],
            ],
            'Limitada o nula posibilidad de desarrollo' => [ // 1 item, max ~4
                ['max' => 1, 'level' => 'Nulo'],
                ['min' => 2, 'max' => 2, 'level' => 'Bajo'],
                ['min' => 3, 'max' => 3, 'level' => 'Medio'],
                ['min' => 4, 'max' => 4, 'level' => 'Alto'],
                ['min' => 5, 'level' => 'Muy Alto'],
            ],
            'Insuficiente participación y manejo del cambio' => [ // 3 items, max ~12
                ['max' => 3, 'level' => 'Nulo'],
                ['min' => 4, 'max' => 6, 'level' => 'Bajo'],
                ['min' => 7, 'max' => 8, 'level' => 'Medio'],
                ['min' => 9, 'max' => 11, 'level' => 'Alto'],
                ['min' => 12, 'level' => 'Muy Alto'],
            ],
            'Limitada o inexistente capacitación' => [ // 2 items, max ~8
                ['max' => 2, 'level' => 'Nulo'],
                ['min' => 3, 'max' => 4, 'level' => 'Bajo'],
                ['min' => 5, 'max' => 5, 'level' => 'Medio'],
                ['min' => 6, 'max' => 7, 'level' => 'Alto'],
                ['min' => 8, 'level' => 'Muy Alto'],
            ],

            // Categoría 3: Organización del tiempo de trabajo
            'Jornadas de trabajo extensas' => [ // 1 item, max ~4
                ['max' => 0, 'level' => 'Nulo'],
                ['min' => 1, 'max' => 1, 'level' => 'Bajo'],
                ['min' => 2, 'max' => 3, 'level' => 'Medio'],
                ['min' => 4, 'max' => 4, 'level' => 'Alto'],
                ['min' => 5, 'level' => 'Muy Alto'],
            ],
            'Influencia del trabajo fuera del centro de trabajo' => [ // 4 items, max ~16
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 7, 'level' => 'Bajo'],
                ['min' => 8, 'max' => 10, 'level' => 'Medio'],
                ['min' => 11, 'max' => 13, 'level' => 'Alto'],
                ['min' => 14, 'level' => 'Muy Alto'],
            ],
            'Influencia de las responsabilidades familiares' => [ // 1 item, max ~4
                ['max' => 1, 'level' => 'Nulo'],
                ['min' => 2, 'max' => 2, 'level' => 'Bajo'],
                ['min' => 3, 'max' => 3, 'level' => 'Medio'],
                ['min' => 4, 'max' => 4, 'level' => 'Alto'],
                ['min' => 5, 'level' => 'Muy Alto'],
            ],

            // Categoría 4: Liderazgo y relaciones en el trabajo
            'Escasa claridad de funciones' => [ // 4 items, max ~16
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 7, 'level' => 'Bajo'],
                ['min' => 8, 'max' => 10, 'level' => 'Medio'],
                ['min' => 11, 'max' => 13, 'level' => 'Alto'],
                ['min' => 14, 'level' => 'Muy Alto'],
            ],
            'Características del liderazgo' => [ // 8 items, max ~32
                ['max' => 9, 'level' => 'Nulo'],
                ['min' => 10, 'max' => 14, 'level' => 'Bajo'],
                ['min' => 15, 'max' => 20, 'level' => 'Medio'],
                ['min' => 21, 'max' => 27, 'level' => 'Alto'],
                ['min' => 28, 'level' => 'Muy Alto'],
            ],
            'Relaciones sociales en el trabajo' => [ // 4 items, max ~16
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 7, 'level' => 'Bajo'],
                ['min' => 8, 'max' => 10, 'level' => 'Medio'],
                ['min' => 11, 'max' => 13, 'level' => 'Alto'],
                ['min' => 14, 'level' => 'Muy Alto'],
            ],
            'Deficiente relación con los colaboradores' => [ // 3 items, max ~12
                ['max' => 3, 'level' => 'Nulo'],
                ['min' => 4, 'max' => 5, 'level' => 'Bajo'],
                ['min' => 6, 'max' => 8, 'level' => 'Medio'],
                ['min' => 9, 'max' => 10, 'level' => 'Alto'],
                ['min' => 11, 'level' => 'Muy Alto'],
            ],
            'Violencia laboral' => [ // 7 items, max ~28
                ['max' => 7, 'level' => 'Nulo'],
                ['min' => 8, 'max' => 12, 'level' => 'Bajo'],
                ['min' => 13, 'max' => 17, 'level' => 'Medio'],
                ['min' => 18, 'max' => 23, 'level' => 'Alto'],
                ['min' => 24, 'level' => 'Muy Alto'],
            ],

            // Categoría 5: Entorno organizacional
            'Escasa o nula retroalimentación del desempeño' => [ // 3 items, max ~12
                ['max' => 3, 'level' => 'Nulo'],
                ['min' => 4, 'max' => 5, 'level' => 'Bajo'],
                ['min' => 6, 'max' => 8, 'level' => 'Medio'],
                ['min' => 9, 'max' => 10, 'level' => 'Alto'],
                ['min' => 11, 'level' => 'Muy Alto'],
            ],
            'Escaso o nulo reconocimiento y compensación' => [ // 4 items, max ~16
                ['max' => 4, 'level' => 'Nulo'],
                ['min' => 5, 'max' => 7, 'level' => 'Bajo'],
                ['min' => 8, 'max' => 10, 'level' => 'Medio'],
                ['min' => 11, 'max' => 13, 'level' => 'Alto'],
                ['min' => 14, 'level' => 'Muy Alto'],
            ],
            'Limitado sentido de pertenencia' => [ // 2 items, max ~8
                ['max' => 2, 'level' => 'Nulo'],
                ['min' => 3, 'max' => 4, 'level' => 'Bajo'],
                ['min' => 5, 'max' => 5, 'level' => 'Medio'],
                ['min' => 6, 'max' => 7, 'level' => 'Alto'],
                ['min' => 8, 'level' => 'Muy Alto'],
            ],
            'Inestabilidad laboral' => [ // 2 items, max ~8
                ['max' => 2, 'level' => 'Nulo'],
                ['min' => 3, 'max' => 4, 'level' => 'Bajo'],
                ['min' => 5, 'max' => 5, 'level' => 'Medio'],
                ['min' => 6, 'max' => 7, 'level' => 'Alto'],
                ['min' => 8, 'level' => 'Muy Alto'],
            ],
        ];
    }

    /**
     * Get empty report structure when no data available
     */
    protected function getEmptyReportStructure(): array
    {
        return [
            'grouped_by_category' => [],
            'grouped_by_domain' => [],
            'grouped_by_dimension' => [],
            'final_risk_levels' => [],
            'personalCalification' => [],
            'detailed_results' => [],
        ];
    }

    /**
     * Get demographic distribution by organization
     */
    public function getDemographicDistribution(?string $organizationId = null): array
    {
        if (! $organizationId) {
            return [];
        }

        // Get all completed Referencia V evaluations
        $evaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_v')
            ->get();

        if ($evaluations->isEmpty()) {
            return [];
        }

        // Get corresponding Referencia III evaluations for risk levels
        $referenciaIIIEvaluations = PaperEvaluation::where('organization_id', $organizationId)
            ->where('source', 'paper')
            ->where('processing_status', 'completed')
            ->where('evaluation_type', 'referencia_iii')
            ->get()
            ->keyBy('personal_folio');

        return $this->aggregateDemographicData($evaluations, $referenciaIIIEvaluations);
    }

    /**
     * Aggregate demographic data with risk levels
     */
    protected function aggregateDemographicData(Collection $evaluations, Collection $referenciaIIIEvaluations): array
    {
        $demographicCategories = [
            'sexo' => 'Distribución Conforme a Género',
            'estado_civil' => 'Distribución Conforme a Estado Civil',
            'edad' => 'Distribución Conforme a Rango de Edad',
            'nivel_estudios' => 'Distribución Conforme a Nivel de Estudios',
            'ocupacion_puesto' => 'Distribución Conforme al Puesto',
            'tipo_contratacion' => 'Distribución Conforme a Tipo de Contratación',
            'tipo_personal' => 'Distribución Conforme a Tipo de Personal',
            'tipo_jornada' => 'Distribución Conforme al Tipo de Jornada Laboral',
            'rotacion_turnos' => 'Distribución Conforme a Rotación de Turnos',
            'departamento_seccion_area' => 'Distribución Conforme al Área',
            'tiempo_puesto_actual' => 'Distribución Conforme a Tiempo en el Puesto Actual (Antigüedad)',
        ];

        $result = [];

        foreach ($demographicCategories as $key => $title) {
            $categoryData = $this->aggregateDemographicCategory($key, $evaluations, $referenciaIIIEvaluations);

            if (! empty($categoryData)) {
                $result[] = [
                    'title' => $title,
                    'data' => $categoryData,
                ];
            }
        }

        return $result;
    }

    /**
     * Aggregate single demographic category
     */
    protected function aggregateDemographicCategory(
        string $categoryKey,
        Collection $evaluations,
        Collection $referenciaIIIEvaluations
    ): array {
        $categoryData = [];

        foreach ($evaluations as $evaluation) {
            $demographicData = $evaluation->demographic_data ?? [];
            $value = $demographicData[$categoryKey] ?? null;

            // Handle nested demographic data
            $valueLabel = is_array($value) ? ($value['label'] ?? $value['value'] ?? null) : $value;

            // For specific categories, handle missing/null values
            if (! $valueLabel || $valueLabel === 'NULL' || trim($valueLabel) === '') {
                // For gender category, explicitly mark as "Sin género"
                if ($categoryKey === 'sexo') {
                    $valueLabel = 'Sin género';
                } else {
                    // For other categories, skip if no value
                    continue;
                }
            }

            if (! isset($categoryData[$valueLabel])) {
                $categoryData[$valueLabel] = [
                    'Nulo' => [],
                    'Bajo' => [],
                    'Medio' => [],
                    'Alto' => [],
                    'Muy Alto' => [],
                ];
            }

            // Get risk level from Referencia III
            $personalFolio = $evaluation->personal_folio;
            $referenciaIII = $referenciaIIIEvaluations->get($personalFolio);

            if ($referenciaIII) {
                $scores = $this->scoreService->calculateReferenciaIIIScores($referenciaIII);
                $riskLevel = $this->scoreService->calculateRiskLevel($scores['total_score']);
                $categoryData[$valueLabel][$riskLevel][] = $personalFolio;
            }
        }

        // Format for frontend
        $result = [];
        foreach ($categoryData as $name => $riskLevels) {
            $total = 0;
            $riskLevelsFormatted = [];

            foreach ($riskLevels as $level => $personal) {
                $count = count($personal);
                $total += $count;
                $riskLevelsFormatted[$level] = $count;
            }

            $result[] = [
                'name' => $name,
                'total' => $total,
                'risk_levels' => $riskLevelsFormatted,
                'personal_by_risk' => $riskLevels,
            ];
        }

        return $result;
    }
}
