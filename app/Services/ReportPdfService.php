<?php

namespace App\Services;

class ReportPdfService
{
    public function __construct(
        protected PaperEvaluationReportService $paperReportService
    ) {}

    /**
     * Get demographic distribution data for PDF report
     */
    public function getDemographicDistributionData(string $organizationId): array
    {
        return $this->paperReportService->getDemographicDistribution($organizationId);
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

        return [
            'final_risk' => $finalRiskDistribution,
            'categories' => $categoryDistribution,
            'domains' => $domainDistribution,
            'dimensions' => $dimensionDistribution,
            'total_participants' => $totalParticipants,
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
}
