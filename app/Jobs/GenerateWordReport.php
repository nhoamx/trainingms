<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\ReportGeneration;
use App\Services\ReportPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Spatie\Browsershot\Browsershot;

class GenerateWordReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ReportGeneration $reportGeneration
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->reportGeneration->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info('Starting Word report generation', [
                'report_generation_id' => $this->reportGeneration->id,
                'report_type' => $this->reportGeneration->report_type,
                'organization_id' => $this->reportGeneration->organization_id,
            ]);

            $organization = Organization::findOrFail($this->reportGeneration->organization_id);
            $reportPdfService = app(ReportPdfService::class);

            // Generate HTML based on report type
            $html = $this->generateReportHtml($organization, $reportPdfService);

            // Generate PDF first
            $pdfFilename = $this->generatePdfFilename($organization);
            $pdfPath = storage_path('app/temp/'.$pdfFilename);

            // Ensure temp directory exists
            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            Log::info('Generating PDF', ['path' => $pdfPath]);
            $this->configureBrowsershot($html)->save($pdfPath);

            // Convert PDF to Word
            $docxFilename = str_replace('.pdf', '.docx', $pdfFilename);
            $docxPath = storage_path('app/temp/'.$docxFilename);

            Log::info('Converting PDF to DOCX', [
                'pdf_path' => $pdfPath,
                'docx_path' => $docxPath,
            ]);

            if (! $this->convertPdfToDocx($pdfPath, $docxPath)) {
                throw new \Exception('Failed to convert PDF to DOCX');
            }

            // Clean up PDF file
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            // Verify DOCX file actually exists before marking as completed
            if (! file_exists($docxPath)) {
                throw new \Exception('DOCX file was not created: '.$docxPath);
            }

            $fileSize = filesize($docxPath);
            if ($fileSize === 0) {
                throw new \Exception('DOCX file is empty (0 bytes)');
            }

            Log::info('DOCX file created successfully', [
                'path' => $docxPath,
                'size' => $fileSize,
                'exists' => file_exists($docxPath),
            ]);

            // Update report generation record
            $this->reportGeneration->update([
                'status' => 'completed',
                'file_path' => $docxPath,
                'original_filename' => $docxFilename,
                'completed_at' => now(),
            ]);

            Log::info('Word report generation completed successfully', [
                'report_generation_id' => $this->reportGeneration->id,
                'file_path' => $docxPath,
                'file_size' => $fileSize,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating Word report', [
                'report_generation_id' => $this->reportGeneration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->reportGeneration->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function generateReportHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        return match ($this->reportGeneration->report_type) {
            'demographic' => $this->generateDemographicHtml($organization, $reportPdfService),
            'diagnostic' => $this->generateDiagnosticHtml($organization, $reportPdfService),
            'executive' => $this->generateExecutiveHtml($organization, $reportPdfService),
            default => throw new \Exception('Invalid report type'),
        };
    }

    protected function generateDemographicHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        $demographicData = $reportPdfService->getDemographicDistributionData($organization->id);

        if (empty($demographicData)) {
            throw new \Exception('No demographic data available');
        }

        return view('pdfs.demographic-report-browsershot', [
            'organization' => $organization,
            'demographicData' => $demographicData,
            'generatedDate' => now()->format('d/m/Y'),
        ])->render();
    }

    protected function generateDiagnosticHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        $diagnosticData = $reportPdfService->getDiagnosticResultsData($organization->id);
        $demographicData = $reportPdfService->getDemographicDistributionData($organization->id);
        $traumaticEventsData = $reportPdfService->getTraumaticEventsData($organization->id);

        if (empty($diagnosticData['final_risk'])) {
            throw new \Exception('No diagnostic data available');
        }

        return view('pdfs.diagnostic-report-browsershot', [
            'organization' => $organization,
            'diagnosticData' => $diagnosticData,
            'demographicData' => $demographicData,
            'traumaticEventsData' => $traumaticEventsData,
            'generatedDate' => now()->format('d/m/Y'),
        ])->render();
    }

    protected function generateExecutiveHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        $executiveData = $reportPdfService->getExecutiveReportData($organization->id);
        $demographicData = $reportPdfService->getDemographicDistributionData($organization->id);

        if (empty($executiveData['analisis_cuantitativo_final']['total'])) {
            throw new \Exception('No executive data available');
        }

        return view('pdfs.executive-report', [
            'organization' => $organization,
            'executiveData' => $executiveData,
            'demographicData' => $demographicData,
            'generatedDate' => now()->format('d/m/Y'),
        ])->render();
    }

    protected function generatePdfFilename(Organization $organization): string
    {
        $typeMap = [
            'demographic' => 'demografico',
            'diagnostic' => 'diagnostico',
            'executive' => 'ejecutivo',
        ];

        $type = $typeMap[$this->reportGeneration->report_type] ?? $this->reportGeneration->report_type;

        return 'informe-'.$type.'-'.$organization->name.'-'.now()->format('Y-m-d-His').'.pdf';
    }

    protected function configureBrowsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->paperSize(8.5, 11, 'in')
            ->margins(0, 0, 0, 0)
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->showBackground();

        if (PHP_OS_FAMILY === 'Linux' && app()->isProduction()) {
            $browsershot->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
            ]);
        }

        return $browsershot;
    }

    protected function convertPdfToDocx(string $pdfPath, string $docxPath): bool
    {
        try {
            $containerName = config('services.docker.omr_container', 'training-and-ms');
            $dockerPdfPath = '/app/temp_pdf_input.pdf';
            $dockerDocxPath = '/app/temp_docx_output.docx';

            Log::info('Starting PDF to DOCX conversion', [
                'pdf_path' => $pdfPath,
                'docx_path' => $docxPath,
                'container' => $containerName,
            ]);

            // Copy PDF to Docker
            $copyToDocketCommand = "docker cp \"{$pdfPath}\" {$containerName}:{$dockerPdfPath}";
            $copyResult = Process::timeout(30)->run($copyToDocketCommand);

            if (! $copyResult->successful()) {
                Log::error('Failed to copy PDF to Docker', [
                    'output' => $copyResult->output(),
                    'error' => $copyResult->errorOutput(),
                ]);

                return false;
            }

            // Convert
            $convertCommand = "docker exec {$containerName} python /app/pdf_converter/convert_pdf_to_word.py {$dockerPdfPath} {$dockerDocxPath}";
            $convertResult = Process::timeout(300)->run($convertCommand);

            if (! $convertResult->successful()) {
                Log::error('Failed to convert PDF to DOCX', [
                    'output' => $convertResult->output(),
                    'error' => $convertResult->errorOutput(),
                ]);

                return false;
            }

            // Copy back
            $copyFromDockerCommand = "docker cp {$containerName}:{$dockerDocxPath} \"{$docxPath}\"";
            $copyBackResult = Process::timeout(30)->run($copyFromDockerCommand);

            if (! $copyBackResult->successful()) {
                Log::error('Failed to copy DOCX from Docker', [
                    'output' => $copyBackResult->output(),
                    'error' => $copyBackResult->errorOutput(),
                ]);

                return false;
            }

            // Cleanup
            Process::timeout(10)->run("docker exec {$containerName} rm -f {$dockerPdfPath} {$dockerDocxPath}");

            return file_exists($docxPath);
        } catch (\Exception $e) {
            Log::error('Error in PDF to DOCX conversion', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
