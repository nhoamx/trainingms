<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWordReport;
use App\Models\Organization;
use App\Models\ReportGeneration;
use App\Services\ReportPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Spatie\Browsershot\Browsershot;

class ReportPdfController extends Controller
{
    public function __construct(
        protected ReportPdfService $reportPdfService
    ) {}

    /**
     * Configure Browsershot instance with common settings
     */
    protected function configureBrowsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->paperSize(8.5, 11, 'in') // Letter size
            ->margins(0, 0, 0, 0) // Margins handled by CSS @page
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->showBackground();

        // Add --no-sandbox flag for production Linux servers
        // This is required for Ubuntu 23.10+ and other Linux distros with AppArmor restrictions
        if (PHP_OS_FAMILY === 'Linux' && app()->isProduction()) {
            $browsershot->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
            ]);
        }

        return $browsershot;
    }

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

            // If preview parameter is present, return HTML view instead of PDF
            if ($request->has('preview')) {
                return response($html)->header('Content-Type', 'text/html');
            }

            // Generate PDF using Browsershot
            $filename = 'informe-demografico-'.$organization->name.'-'.now()->format('Y-m-d').'.pdf';
            $tempPath = storage_path('app/temp/'.$filename);

            // Ensure temp directory exists
            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Configure and generate PDF
            $this->configureBrowsershot($html)->save($tempPath);

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
            $demographicData = $this->reportPdfService->getDemographicDistributionData($organizationId);
            $traumaticEventsData = $this->reportPdfService->getTraumaticEventsData($organizationId);

            if (empty($diagnosticData['final_risk'])) {
                return response()->json([
                    'error' => 'No hay datos de diagnóstico disponibles para generar el reporte',
                ], 404);
            }

            // Render HTML view with Vue + Chart.js
            $html = view('pdfs.diagnostic-report-browsershot', [
                'organization' => $organization,
                'diagnosticData' => $diagnosticData,
                'demographicData' => $demographicData,
                'traumaticEventsData' => $traumaticEventsData,
                'generatedDate' => now()->format('d/m/Y'),
            ])->render();

            // If preview parameter is present, return HTML view instead of PDF
            if ($request->has('preview')) {
                return response($html)->header('Content-Type', 'text/html');
            }

            // Generate PDF using Browsershot
            $filename = 'informe-diagnostico-'.$organization->name.'-'.now()->format('Y-m-d').'.pdf';
            $tempPath = storage_path('app/temp/'.$filename);

            // Ensure temp directory exists
            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Configure and generate PDF
            $this->configureBrowsershot($html)->save($tempPath);

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
     * Generate and download executive report PDF
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

            $organization = Organization::findOrFail($organizationId);
            $executiveData = $this->reportPdfService->getExecutiveReportData($organizationId);
            $demographicData = $this->reportPdfService->getDemographicDistributionData($organizationId);

            if (empty($executiveData['analisis_cuantitativo_final']['total'])) {
                return response()->json([
                    'error' => 'No hay datos disponibles para generar el reporte ejecutivo',
                ], 404);
            }

            // Render HTML view
            $html = view('pdfs.executive-report', [
                'organization' => $organization,
                'executiveData' => $executiveData,
                'demographicData' => $demographicData,
                'generatedDate' => now()->format('d/m/Y'),
            ])->render();

            // If preview parameter is present, return HTML view instead of PDF
            if ($request->has('preview')) {
                return response($html)->header('Content-Type', 'text/html');
            }

            // Generate PDF using Browsershot
            $filename = 'informe-ejecutivo-'.$organization->name.'-'.now()->format('Y-m-d').'.pdf';
            $tempPath = storage_path('app/temp/'.$filename);

            // Ensure temp directory exists
            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Configure and generate PDF
            $this->configureBrowsershot($html)->save($tempPath);

            // Return PDF for download
            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
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

    /**
     * Convert PDF to DOCX using Python script in Docker
     */
    protected function convertPdfToDocx(string $pdfPath, string $docxPath): bool
    {
        try {
            // Get Docker container name from environment or use default
            $containerName = config('services.docker.omr_container', 'training-and-ms');

            // Paths inside the Docker container
            $dockerPdfPath = '/app/temp_pdf_input.pdf';
            $dockerDocxPath = '/app/temp_docx_output.docx';

            Log::info('Starting PDF to DOCX conversion', [
                'pdf_path' => $pdfPath,
                'docx_path' => $docxPath,
                'container' => $containerName,
            ]);

            // Copy PDF file into Docker container
            $copyToDocketCommand = "docker cp \"{$pdfPath}\" {$containerName}:{$dockerPdfPath}";
            $copyResult = Process::timeout(30)->run($copyToDocketCommand);

            if (! $copyResult->successful()) {
                Log::error('Failed to copy PDF to Docker container', [
                    'command' => $copyToDocketCommand,
                    'output' => $copyResult->output(),
                    'error' => $copyResult->errorOutput(),
                ]);

                return false;
            }

            Log::info('PDF copied to Docker container successfully');

            // Run Python conversion script inside Docker container with increased timeout
            $convertCommand = "docker exec {$containerName} python /app/pdf_converter/convert_pdf_to_word.py {$dockerPdfPath} {$dockerDocxPath}";
            $convertResult = Process::timeout(300)->run($convertCommand); // 5 minutes timeout

            if (! $convertResult->successful()) {
                Log::error('Failed to convert PDF to DOCX in Docker', [
                    'command' => $convertCommand,
                    'output' => $convertResult->output(),
                    'error' => $convertResult->errorOutput(),
                ]);

                return false;
            }

            Log::info('PDF converted to DOCX successfully', [
                'output' => $convertResult->output(),
            ]);

            // Copy DOCX file from Docker container
            $copyFromDockerCommand = "docker cp {$containerName}:{$dockerDocxPath} \"{$docxPath}\"";
            $copyBackResult = Process::timeout(30)->run($copyFromDockerCommand);

            if (! $copyBackResult->successful()) {
                Log::error('Failed to copy DOCX from Docker container', [
                    'command' => $copyFromDockerCommand,
                    'output' => $copyBackResult->output(),
                    'error' => $copyBackResult->errorOutput(),
                ]);

                return false;
            }

            Log::info('DOCX copied from Docker container successfully');

            // Clean up temporary files in Docker container
            Process::timeout(10)->run("docker exec {$containerName} rm -f {$dockerPdfPath} {$dockerDocxPath}");

            $success = file_exists($docxPath);

            if ($success) {
                Log::info('PDF to DOCX conversion completed successfully', [
                    'docx_size' => filesize($docxPath),
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('Error in PDF to DOCX conversion: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Initiate Word report generation (queued)
     */
    public function downloadDemographicReportWord(Request $request, string $organizationId)
    {
        return $this->initiateWordReportGeneration($request, $organizationId, 'demographic');
    }

    /**
     * Initiate Word report generation (queued)
     */
    public function downloadDiagnosticReportWord(Request $request, string $organizationId)
    {
        return $this->initiateWordReportGeneration($request, $organizationId, 'diagnostic');
    }

    /**
     * Initiate Word report generation (queued)
     */
    public function downloadExecutiveReportWord(Request $request, string $organizationId)
    {
        return $this->initiateWordReportGeneration($request, $organizationId, 'executive');
    }

    /**
     * Initiate Likert Word report generation (queued) - uses native PHPWord
     */
    public function downloadLikertReportWord(Request $request, string $organizationId)
    {
        return $this->initiateWordReportGeneration($request, $organizationId, 'likert');
    }

    /**
     * Common method to initiate Word report generation
     */
    protected function initiateWordReportGeneration(Request $request, string $organizationId, string $reportType)
    {
        try {
            // Authorization check
            $user = $request->user();
            if (! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json([
                    'error' => 'No autorizado para generar reportes',
                ], 403);
            }

            // Verify organization exists
            $organization = Organization::find($organizationId);
            if (! $organization) {
                return response()->json([
                    'error' => 'Organización no encontrada',
                ], 404);
            }

            // Create report generation record
            $reportGeneration = ReportGeneration::create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'report_type' => $reportType,
                'format' => 'docx',
                'status' => 'pending',
            ]);

            // Dispatch job
            GenerateWordReport::dispatch($reportGeneration);

            return response()->json([
                'success' => true,
                'report_id' => $reportGeneration->id,
                'message' => 'El reporte se está generando. Por favor espere...',
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating Word report generation', [
                'organization_id' => $organizationId,
                'report_type' => $reportType,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al iniciar la generación del reporte: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check report generation status
     */
    public function checkReportStatus(Request $request, string $reportId)
    {
        try {
            $user = $request->user();
            $reportGeneration = ReportGeneration::findOrFail($reportId);

            // Verify user owns this report
            if ($reportGeneration->user_id !== $user->id) {
                return response()->json([
                    'error' => 'No autorizado',
                ], 403);
            }

            // Additional check: ensure file actually exists when status is completed
            $fileExists = false;
            if ($reportGeneration->isCompleted() && $reportGeneration->file_path) {
                $fileExists = file_exists($reportGeneration->file_path);

                if (! $fileExists) {
                    Log::warning('Report marked as completed but file does not exist', [
                        'report_id' => $reportId,
                        'file_path' => $reportGeneration->file_path,
                        'status' => $reportGeneration->status,
                    ]);
                }
            }

            return response()->json([
                'status' => $reportGeneration->status,
                'completed' => $reportGeneration->isCompleted() && $fileExists,
                'failed' => $reportGeneration->isFailed(),
                'error_message' => $reportGeneration->error_message,
                'completed_at' => $reportGeneration->completed_at?->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking report status', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al verificar el estado del reporte',
            ], 500);
        }
    }

    /**
     * Download completed report
     */
    public function downloadCompletedReport(Request $request, string $reportId)
    {
        try {
            $user = $request->user();
            $reportGeneration = ReportGeneration::findOrFail($reportId);

            Log::info('Download request received', [
                'report_id' => $reportId,
                'user_id' => $user->id,
                'status' => $reportGeneration->status,
                'file_path' => $reportGeneration->file_path,
            ]);

            // Verify user owns this report
            if ($reportGeneration->user_id !== $user->id) {
                Log::warning('Unauthorized download attempt', [
                    'report_id' => $reportId,
                    'user_id' => $user->id,
                    'owner_id' => $reportGeneration->user_id,
                ]);

                return response()->json([
                    'error' => 'No autorizado',
                ], 403);
            }

            // Verify report is completed
            if (! $reportGeneration->isCompleted()) {
                Log::warning('Download attempted on incomplete report', [
                    'report_id' => $reportId,
                    'status' => $reportGeneration->status,
                ]);

                return response()->json([
                    'error' => 'El reporte aún no está listo',
                    'status' => $reportGeneration->status,
                ], 400);
            }

            // Verify file path exists
            if (! $reportGeneration->file_path) {
                Log::error('Report marked as completed but has no file path', [
                    'report_id' => $reportId,
                ]);

                return response()->json([
                    'error' => 'El archivo del reporte no tiene una ruta válida',
                ], 500);
            }

            // Verify file exists
            if (! file_exists($reportGeneration->file_path)) {
                Log::error('Report file does not exist', [
                    'report_id' => $reportId,
                    'file_path' => $reportGeneration->file_path,
                ]);

                return response()->json([
                    'error' => 'El archivo del reporte no se encuentra disponible',
                ], 404);
            }

            Log::info('Starting file download', [
                'report_id' => $reportId,
                'file_path' => $reportGeneration->file_path,
                'filename' => $reportGeneration->original_filename,
            ]);

            // Return file for download
            return response()->download(
                $reportGeneration->file_path,
                $reportGeneration->original_filename,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]
            )->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error downloading completed report', [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al descargar el reporte',
            ], 500);
        }
    }

    /**
     * Download Excel report with paper evaluations data
     */
    public function downloadExcelReport(Request $request, string $organizationId)
    {
        try {
            // Authorization check
            $user = $request->user();
            if (! $user->hasRole('admin') && ! $user->hasRole('super-admin')) {
                return response()->json([
                    'error' => 'No autorizado para generar reportes',
                ], 403);
            }

            // Verify organization exists
            $organization = Organization::findOrFail($organizationId);

            // Generate filename
            $filename = 'evaluaciones_'.$organization->name.'_'.now()->format('Y-m-d_His').'.xlsx';

            // Return Excel file
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PaperEvaluationsExport($organizationId),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Error downloading Excel report', [
                'organization_id' => $organizationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al generar el reporte Excel: '.$e->getMessage(),
            ], 500);
        }
    }
}
