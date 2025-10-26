<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\ReportPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class ReportPdfController extends Controller
{
    public function __construct(
        protected ReportPdfService $reportPdfService
    ) {}

    /**
     * Generate and download demographic report PDF
     */
    public function downloadDemographicReport(Request $request, string $organizationId)
    {
        try {
            // Authorization check
            $user = $request->user();
            if (! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json([
                    'error' => 'No autorizado para generar reportes',
                ], 403);
            }

            $organization = Organization::findOrFail($organizationId);
            $demographicData = $this->reportPdfService->getDemographicDistributionData($organizationId);

            if (empty($demographicData)) {
                return response()->json([
                    'error' => 'No hay datos demográficos disponibles para generar el reporte',
                ], 404);
            }

            // Render HTML view with Vue + Chart.js
            $html = view('pdfs.demographic-report-browsershot', [
                'organization' => $organization,
                'demographicData' => $demographicData,
                'generatedDate' => now()->format('d/m/Y'),
            ])->render();

            // Generate PDF using Browsershot
            $filename = 'informe-demografico-'.$organization->name.'-'.now()->format('Y-m-d').'.pdf';
            $tempPath = storage_path('app/temp/'.$filename);

            // Ensure temp directory exists
            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Configure Browsershot for PDF generation
            Browsershot::html($html)
                ->paperSize(8.5, 11, 'in') // Letter size
                ->margins(0, 0, 0, 0) // Margins handled by CSS @page
                ->waitUntilNetworkIdle()
                ->timeout(120)
                ->showBackground()
                ->save($tempPath);

            // Return PDF for download
            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error generating demographic report PDF: '.$e->getMessage(), [
                'organization_id' => $organizationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al generar el reporte demográfico: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and download diagnostic results report PDF
     */
    public function downloadDiagnosticReport(Request $request, string $organizationId)
    {
        try {
            // Authorization check
            $user = $request->user();
            if (! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json([
                    'error' => 'No autorizado para generar reportes',
                ], 403);
            }

            $organization = Organization::findOrFail($organizationId);
            $diagnosticData = $this->reportPdfService->getDiagnosticResultsData($organizationId);

            if (empty($diagnosticData['final_risk'])) {
                return response()->json([
                    'error' => 'No hay datos de diagnóstico disponibles para generar el reporte',
                ], 404);
            }

            // Render HTML view with Vue + Chart.js
            $html = view('pdfs.diagnostic-report-browsershot', [
                'organization' => $organization,
                'diagnosticData' => $diagnosticData,
                'generatedDate' => now()->format('d/m/Y'),
            ])->render();

            // Generate PDF using Browsershot
            $filename = 'informe-diagnostico-'.$organization->name.'-'.now()->format('Y-m-d').'.pdf';
            $tempPath = storage_path('app/temp/'.$filename);

            // Ensure temp directory exists
            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Configure Browsershot for PDF generation
            Browsershot::html($html)
                ->paperSize(8.5, 11, 'in') // Letter size
                ->margins(0, 0, 0, 0) // Margins handled by CSS @page
                ->waitUntilNetworkIdle()
                ->timeout(120)
                ->showBackground()
                ->save($tempPath);

            // Return PDF for download
            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error generating diagnostic report PDF: '.$e->getMessage(), [
                'organization_id' => $organizationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al generar el reporte de diagnóstico: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and download executive report PDF (placeholder)
     */
    public function downloadExecutiveReport(Request $request, string $organizationId)
    {
        try {
            // Authorization check
            $user = $request->user();
            if (! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json([
                    'error' => 'No autorizado para generar reportes',
                ], 403);
            }

            return response()->json([
                'message' => 'El informe ejecutivo estará disponible próximamente',
            ], 501);
        } catch (\Exception $e) {
            Log::error('Error generating executive report PDF: '.$e->getMessage(), [
                'organization_id' => $organizationId,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al generar el reporte ejecutivo: '.$e->getMessage(),
            ], 500);
        }
    }
}
