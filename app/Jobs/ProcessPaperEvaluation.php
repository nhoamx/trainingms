<?php

namespace App\Jobs;

use App\Events\EvaluationProcessingStatusChanged;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProcessPaperEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $fullPath;

    protected string $containerName;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fullPath, string $containerName)
    {
        $this->fullPath = $fullPath;
        $this->containerName = $containerName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        broadcast(new EvaluationProcessingStatusChanged(
            'running',
            'El procesamiento ha iniciado',
            false
        ));

        try {
            // 1. Copy PDF to Docker container
            $this->copyPdfToContainer();

            // 2. Execute OCR processing
            $this->executeOcrProcessing();

            // 3. Process JSON results and store in new structure
            $this->processJsonResults();

            // 4. Cleanup uploaded files
            $this->cleanupFiles();

            broadcast(new EvaluationProcessingStatusChanged(
                'finished',
                'El procesamiento ha finalizado exitosamente',
                true
            ));

        } catch (\Exception $e) {
            Log::error('ProcessPaperEvaluation - Error: '.$e->getMessage(), [
                'file' => $this->fullPath,
                'trace' => $e->getTraceAsString(),
            ]);

            broadcast(new EvaluationProcessingStatusChanged(
                'error',
                'Error durante el procesamiento: '.$e->getMessage(),
                false
            ));

            throw $e;
        }
    }

    /**
     * Copy PDF file to Docker container
     */
    protected function copyPdfToContainer(): void
    {
        $destinationPath = '/app/input/evaluation.pdf';
        $copyCommand = 'docker cp '.escapeshellarg($this->fullPath)." {$this->containerName}:".escapeshellarg($destinationPath);

        exec($copyCommand, $copyOutput, $copyReturn);

        if ($copyReturn !== 0) {
            throw new \RuntimeException('Error al copiar el archivo al contenedor. Código: '.$copyReturn);
        }

        Log::info('PDF copied to container successfully');
    }

    /**
     * Execute OCR processing in Docker container
     */
    protected function executeOcrProcessing(): void
    {
        $execCommand = "docker exec {$this->containerName} python /app/main.py";

        exec($execCommand, $execOutput, $execReturn);

        if ($execReturn !== 0) {
            throw new \RuntimeException('Error al ejecutar el comando en el contenedor. Código: '.$execReturn);
        }

        Log::info('OCR processing completed successfully');
    }

    /**
     * Process JSON results from OCR and store in structured format
     */
    protected function processJsonResults(): void
    {
        $outputFolder = base_path('docker/output');
        $jsonFiles = glob($outputFolder.'/*.json');

        if (! $jsonFiles) {
            throw new \RuntimeException('No se encontraron archivos JSON para procesar');
        }

        Log::info('Processing '.count($jsonFiles).' JSON files');

        foreach ($jsonFiles as $jsonFile) {
            $this->processJsonFile($jsonFile);
        }
    }

    /**
     * Process individual JSON file
     */
    protected function processJsonFile(string $jsonFile): void
    {
        $folio = basename($jsonFile, '.json');

        try {
            // Parse folio
            $folioData = PaperEvaluation::parseFolio($folio);

            // Read JSON content
            $jsonContent = file_get_contents($jsonFile);
            $rawData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Error al decodificar JSON: '.json_last_error_msg());
            }

            // Find or create organization
            $organization = $this->findOrCreateOrganization($folioData['organization_code']);

            // Extract structured data based on evaluation type
            $structuredData = $this->extractStructuredData($rawData, $folioData['evaluation_type']);

            // Create or update PaperEvaluation
            PaperEvaluation::updateOrCreate(
                ['folio' => $folio],
                [
                    'evaluation_type_code' => $folioData['evaluation_type_code'],
                    'organization_code' => $folioData['organization_code'],
                    'personal_folio' => $folioData['personal_folio'],
                    'organization_id' => $organization?->id,
                    'evaluation_type' => $folioData['evaluation_type'],
                    'source' => 'paper',
                    'processing_status' => 'completed',
                    'processed_at' => now(),
                    'pdf_file_path' => $this->fullPath,
                    'demographic_data' => $structuredData['demographic_data'] ?? null,
                    'referencia_i_answers' => $structuredData['referencia_i_answers'] ?? null,
                    'referencia_iii_answers' => $structuredData['referencia_iii_answers'] ?? null,
                    'referencia_iii_conditional' => $structuredData['referencia_iii_conditional'] ?? null,
                    'cisneros_answers' => $structuredData['cisneros_answers'] ?? null,
                    'raw_data' => $rawData,
                ]
            );

            Log::info("Paper evaluation processed successfully: {$folio}");

        } catch (\Exception $e) {
            Log::error("Error processing JSON file {$jsonFile}: ".$e->getMessage());

            // Create failed record
            try {
                $folioData = PaperEvaluation::parseFolio($folio);
                PaperEvaluation::updateOrCreate(
                    ['folio' => $folio],
                    [
                        'evaluation_type_code' => $folioData['evaluation_type_code'],
                        'organization_code' => $folioData['organization_code'],
                        'personal_folio' => $folioData['personal_folio'],
                        'evaluation_type' => $folioData['evaluation_type'],
                        'source' => 'paper',
                        'processing_status' => 'failed',
                        'processing_error' => $e->getMessage(),
                        'retry_count' => 1,
                    ]
                );
            } catch (\Exception $createError) {
                Log::error("Failed to create error record for {$folio}: ".$createError->getMessage());
            }
        }
    }

    /**
     * Extract structured data based on evaluation type
     */
    protected function extractStructuredData(array $rawData, string $evaluationType): array
    {
        $structuredData = [];

        switch ($evaluationType) {
            case 'referencia_i':
                // Guide I - PTSD questions (simple key-value pairs)
                $structuredData['referencia_i_answers'] = $rawData;
                break;

            case 'referencia_iii':
                // Reference III - Workplace questions with conditionals
                $structuredData['referencia_iii_answers'] = $rawData['referencia_iii'] ?? null;
                $structuredData['referencia_iii_conditional'] = [
                    'customer_service' => [
                        'condition' => $rawData['customer_service_conditional']['condition'] ?? null,
                        'questions' => $rawData['customer_service_questions'] ?? null,
                    ],
                    'management' => [
                        'condition' => $rawData['conditional_management']['condition'] ?? null,
                        'questions' => $rawData['management_questions'] ?? null,
                    ],
                ];
                $structuredData['cisneros_answers'] = $rawData['citsats_s1'] ?? null;
                break;

            case 'referencia_v':
                // Reference V - Demographic data
                $structuredData['demographic_data'] = $rawData;
                break;

            case 'cisneros':
                // Cisneros scale - mobbing questions
                $structuredData['cisneros_answers'] = $rawData;
                break;
        }

        return $structuredData;
    }

    /**
     * Find or create organization by code
     */
    protected function findOrCreateOrganization(string $organizationCode): ?Organization
    {
        $organization = Organization::where('folio_organization', $organizationCode)->first();

        if (! $organization) {
            Log::warning("Organization not found for code: {$organizationCode}. Creating new organization.");

            try {
                $organization = Organization::create([
                    'name' => 'Organización '.$organizationCode,
                    'folio_organization' => $organizationCode,
                ]);
            } catch (\Exception $e) {
                Log::error("Error creating organization for code {$organizationCode}: ".$e->getMessage());

                return null;
            }
        }

        return $organization;
    }

    /**
     * Cleanup uploaded files
     */
    protected function cleanupFiles(): void
    {
        $evaluationsPath = storage_path('app/public/evaluations');

        try {
            $files = File::files($evaluationsPath);
            foreach ($files as $file) {
                File::delete($file);
            }
            Log::info('Cleanup completed successfully');
        } catch (\Exception $e) {
            Log::error('Error during cleanup: '.$e->getMessage());
        }
    }
}
