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

        return [
            'grouped_by_category' => $categoryData,
            'grouped_by_domain' => $domainData,
            'grouped_by_dimension' => $dimensionData,
            'final_risk_levels' => $finalRiskData,
            'personalCalification' => $participantData,
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
     */
    protected function getCategoryRiskLevel(string $categoryName, int $score): string
    {
        // Categories don't have specific thresholds in NOM-035
        // Use general thresholds or domain-based calculation
        return $this->scoreService->calculateRiskLevel($score);
    }

    /**
     * Get risk level for a domain based on its score
     * Uses NOM-035 specific domain thresholds from config
     */
    protected function getDomainRiskLevel(string $domainName, int $score): string
    {
        $domainThresholds = config('domain_risk_thresholds', []);

        if (isset($domainThresholds[$domainName])) {
            $thresholds = $domainThresholds[$domainName];

            foreach ($thresholds as $threshold) {
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
        $dimensionThresholds = config('dimension_risk_thresholds', []);

        if (isset($dimensionThresholds[$dimensionName])) {
            $thresholds = $dimensionThresholds[$dimensionName];

            foreach ($thresholds as $threshold) {
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
            'sexo' => 'Sexo',
            'estado_civil' => 'Estado Civil',
            'nivel_estudios' => 'Nivel de Estudios',
            'tipo_puesto' => 'Tipo de Puesto',
            'tipo_contratacion' => 'Tipo de Contratación',
            'tipo_jornada' => 'Tipo de Jornada',
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

            if (! $value) {
                continue;
            }

            // Handle nested demographic data
            $valueLabel = is_array($value) ? ($value['label'] ?? $value['value'] ?? 'Desconocido') : $value;

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
