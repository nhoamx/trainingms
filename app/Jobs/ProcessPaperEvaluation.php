<?php

namespace App\Jobs;

use App\Events\EvaluationProcessingStatusChanged;
use App\Models\DemographicData;
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

    protected ?string $initiatorUserId;

    public int $timeout = 1200;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fullPath, string $containerName, ?string $initiatorUserId = null)
    {
        $this->fullPath = $fullPath;
        $this->containerName = $containerName;
        $this->initiatorUserId = $initiatorUserId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        broadcast(new EvaluationProcessingStatusChanged(
            'running',
            'Copiando PDF al contenedor...',
            false,
            $this->initiatorUserId
        ));

        try {
            // 1. Copy PDF to Docker container
            $this->copyPdfToContainer();

            broadcast(new EvaluationProcessingStatusChanged(
                'running',
                'Ejecutando análisis OCR...',
                false,
                $this->initiatorUserId
            ));

            // 2. Execute OCR processing
            $this->executeOcrProcessing();

            broadcast(new EvaluationProcessingStatusChanged(
                'running',
                'Esperando resultados del análisis...',
                false,
                $this->initiatorUserId
            ));

            // 3. Process JSON results and store in new structure
            $this->processJsonResults();

            broadcast(new EvaluationProcessingStatusChanged(
                'running',
                'Guardando resultados en la base de datos...',
                false,
                $this->initiatorUserId
            ));

            // 4. Cleanup uploaded files
            $this->cleanupFiles();

            broadcast(new EvaluationProcessingStatusChanged(
                'finished',
                'El procesamiento ha finalizado exitosamente',
                true,
                $this->initiatorUserId
            ));

        } catch (\Exception $e) {
            Log::error('ProcessPaperEvaluation - Error: '.$e->getMessage(), [
                'file' => $this->fullPath,
                'trace' => $e->getTraceAsString(),
            ]);

            broadcast(new EvaluationProcessingStatusChanged(
                'error',
                'Error durante el procesamiento: '.$e->getMessage(),
                false,
                $this->initiatorUserId
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
        $execCommand = "docker exec {$this->containerName} python /app/main.py 2>&1";

        Log::info('Starting OCR processing in container');

        exec($execCommand, $execOutput, $execReturn);

        // Log the output for debugging
        if (! empty($execOutput)) {
            Log::info('Python script output:', ['output' => implode("\n", $execOutput)]);
        }

        if ($execReturn !== 0) {
            Log::error('Docker exec failed', [
                'return_code' => $execReturn,
                'output' => $execOutput,
            ]);
            throw new \RuntimeException("Error al ejecutar el comando en el contenedor. Código: {$execReturn}");
        }

        Log::info('OCR processing command completed successfully');
    }

    /**
     * Process JSON results from OCR and store in structured format
     * Implements polling mechanism to wait for JSON files and process them incrementally
     */
    protected function processJsonResults(): void
    {
        $outputFolder = base_path('docker/output');
        $maxAttempts = 120; // Wait up to 20 minutes (120 attempts * 10 seconds)
        $attemptDelay = 10; // Wait 10 seconds between attempts
        $attempt = 0;
        $processedFiles = []; // Track already processed files
        $noNewFilesCount = 0; // Track attempts with no new files
        $maxNoNewFilesAttempts = 12; // Exit if no new files for 2 minutes (12 * 10s)

        Log::info('Waiting for JSON files to be created...', [
            'output_folder' => $outputFolder,
            'max_wait_time' => ($maxAttempts * $attemptDelay).' seconds',
        ]);

        // Polling loop to wait for and process JSON files incrementally
        while ($attempt < $maxAttempts) {
            $jsonFiles = glob($outputFolder.'/*.json');

            // Filter out already processed files
            $newFiles = array_diff($jsonFiles ?: [], $processedFiles);

            if (! empty($newFiles)) {
                Log::info('New JSON files found', [
                    'new_count' => count($newFiles),
                    'total_processed' => count($processedFiles),
                    'attempt' => $attempt + 1,
                    'wait_time' => ($attempt * $attemptDelay).' seconds',
                ]);

                // Process new files immediately
                foreach ($newFiles as $jsonFile) {
                    $this->processJsonFile($jsonFile);
                    $processedFiles[] = $jsonFile;

                    Log::info('Processed file', [
                        'file' => basename($jsonFile),
                        'total_processed' => count($processedFiles),
                    ]);
                }

                // Reset no-new-files counter since we found new files
                $noNewFilesCount = 0;

                // Broadcast progress
                broadcast(new EvaluationProcessingStatusChanged(
                    'running',
                    'Procesados '.count($processedFiles).' formularios...',
                    false,
                    $this->initiatorUserId
                ));
            } else {
                $noNewFilesCount++;

                // If we've processed at least one file and no new files for 2 minutes, consider done
                if (count($processedFiles) > 0 && $noNewFilesCount >= $maxNoNewFilesAttempts) {
                    Log::info('No new files detected for 2 minutes. Assuming processing complete.', [
                        'total_processed' => count($processedFiles),
                        'idle_time' => ($noNewFilesCount * $attemptDelay).' seconds',
                    ]);
                    break;
                }
            }

            $attempt++;

            if ($attempt < $maxAttempts) {
                if (count($processedFiles) === 0) {
                    Log::info("No JSON files found yet. Waiting... (Attempt {$attempt}/{$maxAttempts})");
                } else {
                    Log::info('Waiting for more files... (Processed: '.count($processedFiles).", No new files: {$noNewFilesCount}/{$maxNoNewFilesAttempts})");
                }

                // Send progress update every 3 attempts (30 seconds)
                if ($attempt % 3 === 0) {
                    $waitedTime = $attempt * $attemptDelay;
                    $message = count($processedFiles) > 0
                        ? 'Analizando documento... ('.count($processedFiles)." procesados, {$waitedTime}s transcurridos)"
                        : "Analizando documento... ({$waitedTime}s transcurridos)";

                    broadcast(new EvaluationProcessingStatusChanged(
                        'running',
                        $message,
                        false,
                        $this->initiatorUserId
                    ));
                }

                sleep($attemptDelay);
            }
        }

        // Final validation
        if (count($processedFiles) === 0) {
            Log::error('No JSON files found after polling', [
                'output_folder' => $outputFolder,
                'total_wait_time' => ($attempt * $attemptDelay).' seconds',
                'attempts' => $attempt,
            ]);
            throw new \RuntimeException('No se encontraron archivos JSON para procesar después de esperar '.($attempt * $attemptDelay).' segundos');
        }

        Log::info('JSON processing completed', [
            'total_processed' => count($processedFiles),
            'total_wait_time' => ($attempt * $attemptDelay).' seconds',
        ]);
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
            $paperEvaluation = PaperEvaluation::updateOrCreate(
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
                    'citsats_s1' => $structuredData['citsats_s1'] ?? null,
                    'cisneros_answers' => $structuredData['cisneros_answers'] ?? null,
                    'likert_answers' => $structuredData['likert_answers'] ?? null,
                    'raw_data' => $rawData,
                ]
            );

            // Save demographic data if present
            if (isset($structuredData['demographic_data']) && ! empty($structuredData['demographic_data'])) {
                $this->saveDemographicData($paperEvaluation, $structuredData['demographic_data']);
            }

            Log::info("Paper evaluation processed successfully: {$folio}");

            // Copy marked image to public storage
            $this->copyMarkedImageToStorage($folio);

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
                $structuredData['citsats_s1'] = $rawData['citsats_s1'] ?? null;
                break;

            case 'referencia_v':
                // Reference V - Demographic data
                $structuredData['demographic_data'] = $rawData;
                break;

            case 'cisneros':
                // Cisneros scale - mobbing questions
                $structuredData['cisneros_answers'] = $rawData['cisneros'] ?? null;
                break;

            case 'likert':
                // Likert - Workplace climate evaluation (23 questions + demographics)
                $structuredData['likert_answers'] = [
                    'questions' => $rawData['likert'] ?? null,
                    'genero' => $rawData['genero'] ?? null,
                    'turno' => $rawData['turno'] ?? null,
                    'tipo_contrato' => $rawData['tipo_contrato'] ?? null,
                    'puestos' => $rawData['puestos'] ?? null,
                    'areas' => $rawData['areas'] ?? null,
                ];
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
     * Copy marked image from Docker to public storage
     */
    protected function copyMarkedImageToStorage(string $folio): void
    {
        try {
            $containerOutputPath = base_path("docker/output_with_markers/{$folio}.png");
            $publicFoliosPath = storage_path("app/public/folios/{$folio}.png");

            // Ensure the folios directory exists
            $foliosDir = dirname($publicFoliosPath);
            if (! File::exists($foliosDir)) {
                File::makeDirectory($foliosDir, 0755, true);
            }

            // Copy file from Docker volume to public storage
            if (File::exists($containerOutputPath)) {
                File::copy($containerOutputPath, $publicFoliosPath);
                Log::info("Marked image copied for folio: {$folio}");
            } else {
                Log::warning("Marked image not found in Docker output: {$containerOutputPath}");
            }
        } catch (\Exception $e) {
            Log::error("Error copying marked image for folio {$folio}: ".$e->getMessage());
        }
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

    /**
     * Save demographic data to DemographicData table
     */
    protected function saveDemographicData(PaperEvaluation $paperEvaluation, array $demographicData): void
    {
        try {
            $extractedData = $this->extractDemographicInfo($demographicData);

            // Delete existing demographic data to avoid duplicates
            $paperEvaluation->demographicData?->delete();

            // Create new demographic data record
            DemographicData::create([
                'paper_evaluation_id' => $paperEvaluation->id,
                'gender' => $extractedData['gender'] ?? null,
                'age' => $extractedData['age'] ?? null,
                'estado_civil' => $extractedData['estado_civil'] ?? null,
                'nivel_estudios' => $extractedData['nivel_estudios'] ?? null,
                'puesto' => $extractedData['puesto'] ?? null,
                'area' => $extractedData['area'] ?? null,
                'tipo_puesto' => $extractedData['tipo_puesto'] ?? null,
                'tipo_contratacion' => $extractedData['tipo_contratacion'] ?? null,
                'tipo_personal' => $extractedData['tipo_personal'] ?? null,
                'tipo_jornada' => $extractedData['tipo_jornada'] ?? null,
                'rotacion_turnos' => $extractedData['rotacion_turnos'] ?? null,
                'tiempo_puesto_actual' => $extractedData['tiempo_puesto_actual'] ?? null,
                'tiempo_experiencia_laboral' => $extractedData['tiempo_experiencia_laboral'] ?? null,
                'extra_fields' => $extractedData['extra_fields'] ?? null,
            ]);

            Log::info("Demographic data saved successfully for evaluation: {$paperEvaluation->folio}");
        } catch (\Exception $e) {
            Log::error("Error saving demographic data for evaluation {$paperEvaluation->folio}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract demographic information from raw data
     */
    protected function extractDemographicInfo(array $demographicData): array
    {
        $info = [
            'gender' => $demographicData['genero'] ?? null,
            'age' => $demographicData['edad'] ?? null,
            'estado_civil' => $demographicData['estado_civil'] ?? null,
            'nivel_estudios' => $demographicData['nivel_estudios'] ?? null,
            'puesto' => $demographicData['ocupacion_puesto'] ?? null,
            'area' => $demographicData['departamento_seccion_area'] ?? null,
            'tipo_puesto' => $demographicData['tipo_puesto'] ?? null,
            'tipo_contratacion' => $demographicData['tipo_contratacion'] ?? null,
            'tipo_personal' => $demographicData['tipo_personal'] ?? null,
            'tipo_jornada' => $demographicData['tipo_jornada'] ?? null,
            'rotacion_turnos' => $demographicData['rotacion_turnos'] ?? null,
            'tiempo_puesto_actual' => $demographicData['tiempo_puesto_actual'] ?? null,
            'tiempo_experiencia_laboral' => $demographicData['tiempo_experiencia_laboral'] ?? null,
        ];

        // Store any extra fields in the extra_fields JSON column
        $knownFields = [
            'genero', 'edad', 'estado_civil', 'nivel_estudios', 'ocupacion_puesto',
            'departamento_seccion_area', 'tipo_puesto', 'tipo_contratacion', 'tipo_personal',
            'tipo_jornada', 'rotacion_turnos', 'tiempo_puesto_actual', 'tiempo_experiencia_laboral',
        ];

        $extraFields = [];
        foreach ($demographicData as $key => $value) {
            if (! in_array($key, $knownFields)) {
                $extraFields[$key] = $value;
            }
        }

        if (! empty($extraFields)) {
            $info['extra_fields'] = $extraFields;
        }

        return $info;
    }
}
