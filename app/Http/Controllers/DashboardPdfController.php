<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\CategoryReportService;
use App\Services\DomainReportService;
use App\Services\DimensionReportService;
use App\Services\DemographicReportService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class DashboardPdfController extends Controller
{
    protected $reportService;
    protected $categoryReportService;
    protected $domainReportService;
    protected $dimensionReportService;
    protected $demographicReportService;

    public function __construct(
        ReportService $reportService,
        CategoryReportService $categoryReportService,
        DomainReportService $domainReportService,
        DimensionReportService $dimensionReportService,
        DemographicReportService $demographicReportService
    ) {
        $this->reportService = $reportService;
        $this->categoryReportService = $categoryReportService;
        $this->domainReportService = $domainReportService;
        $this->dimensionReportService = $dimensionReportService;
        $this->demographicReportService = $demographicReportService;
    }

    /**
     * Generate PDF report for category qualifications
     */
    public function generateCategoryReport(Request $request)
    {
        try {
            $user = $request->user();
            
            // Authorization check
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get category qualifications data
            $categoryQualifications = $this->reportService->calculateCategoryQualifications();
            
            // Get category risk level distribution
            $categoryDistribution = $this->categoryReportService->getCategoryRiskLevelDistribution();

            $data = [
                'title' => 'Reporte de Categorías',
                'date' => now()->format('d/m/Y H:i'),
                'organization' => $user->organization ? $user->organization->name : 'Sistema Global',
                'qualifications' => $categoryQualifications,
                'distribution' => $categoryDistribution,
                'user' => $user
            ];

            $pdf = Pdf::loadView('reports.pdf.category-report', $data);
            
            return $pdf->download('reporte-categorias-' . now()->format('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            Log::error("Error generating category PDF report: " . $e->getMessage());
            return response()->json(['error' => 'Error al generar el reporte PDF'], 500);
        }
    }

    /**
     * Generate PDF report for domain qualifications
     */
    public function generateDomainReport(Request $request)
    {
        try {
            $user = $request->user();
            
            // Authorization check
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get domain qualifications data
            $domainQualifications = $this->reportService->calculateDomainQualifications();
            
            // Get domain risk level distribution
            $domainDistribution = $this->domainReportService->getDomainRiskLevelDistribution();

            $data = [
                'title' => 'Reporte de Dominios',
                'date' => now()->format('d/m/Y H:i'),
                'organization' => $user->organization ? $user->organization->name : 'Sistema Global',
                'qualifications' => $domainQualifications,
                'distribution' => $domainDistribution,
                'user' => $user
            ];

            $pdf = Pdf::loadView('reports.pdf.domain-report', $data);
            
            return $pdf->download('reporte-dominios-' . now()->format('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            Log::error("Error generating domain PDF report: " . $e->getMessage());
            return response()->json(['error' => 'Error al generar el reporte PDF'], 500);
        }
    }

    /**
     * Generate PDF report for dimension qualifications
     */
    public function generateDimensionReport(Request $request)
    {
        try {
            $user = $request->user();
            
            // Authorization check
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get dimension risk level distribution
            $dimensionDistribution = $this->dimensionReportService->getDimensionRiskLevelDistribution();

            $data = [
                'title' => 'Reporte de Dimensiones',
                'date' => now()->format('d/m/Y H:i'),
                'organization' => $user->organization ? $user->organization->name : 'Sistema Global',
                'distribution' => $dimensionDistribution,
                'user' => $user
            ];

            $pdf = Pdf::loadView('reports.pdf.dimension-report', $data);
            
            return $pdf->download('reporte-dimensiones-' . now()->format('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            Log::error("Error generating dimension PDF report: " . $e->getMessage());
            return response()->json(['error' => 'Error al generar el reporte PDF'], 500);
        }
    }

    /**
     * Generate PDF report for demographic distributions
     */
    public function generateDemographicReport(Request $request)
    {
        try {
            $user = $request->user();
            
            // Authorization check
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get demographic distributions
            $demographicDistributions = $this->reportService->getDemographicDistributions();

            $data = [
                'title' => 'Reporte Demográfico',
                'date' => now()->format('d/m/Y H:i'),
                'organization' => $user->organization ? $user->organization->name : 'Sistema Global',
                'distributions' => $demographicDistributions,
                'user' => $user
            ];

            $pdf = Pdf::loadView('reports.pdf.demographic-report', $data);
            
            return $pdf->download('reporte-demografico-' . now()->format('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            Log::error("Error generating demographic PDF report: " . $e->getMessage());
            return response()->json(['error' => 'Error al generar el reporte PDF'], 500);
        }
    }

    /**
     * Generate complete dashboard PDF report with all charts
     */
    public function generateCompleteReport(Request $request)
    {
        try {
            $user = $request->user();
            
            // Authorization check
            if (!$user->hasRole('organization') && !$user->hasRole('admin') && !$user->hasRole('super-admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Get all dashboard data
            $categoryQualifications = $this->reportService->calculateCategoryQualifications();
            $domainQualifications = $this->reportService->calculateDomainQualifications();
            $demographicDistributions = $this->reportService->getDemographicDistributions();
            
            // Get distributions
            $categoryDistribution = $this->categoryReportService->getCategoryRiskLevelDistribution();
            $domainDistribution = $this->domainReportService->getDomainRiskLevelDistribution();
            $dimensionDistribution = $this->dimensionReportService->getDimensionRiskLevelDistribution();

            $data = [
                'title' => 'Reporte Completo del Dashboard',
                'date' => now()->format('d/m/Y H:i'),
                'organization' => $user->organization ? $user->organization->name : 'Sistema Global',
                'categoryQualifications' => $categoryQualifications,
                'domainQualifications' => $domainQualifications,
                'demographicDistributions' => $demographicDistributions,
                'categoryDistribution' => $categoryDistribution,
                'domainDistribution' => $domainDistribution,
                'dimensionDistribution' => $dimensionDistribution,
                'user' => $user
            ];

            $pdf = Pdf::loadView('reports.pdf.complete-dashboard-report', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'defaultFont' => 'Arial'
                ]);
            
            return $pdf->download('reporte-dashboard-completo-' . now()->format('Y-m-d') . '.pdf');
            
        } catch (\Exception $e) {
            Log::error("Error generating complete dashboard PDF report: " . $e->getMessage());
            return response()->json(['error' => 'Error al generar el reporte PDF completo'], 500);
        }
    }
}