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

            // Save demographic data if present (from Referencia V or Likert)
            if (isset($structuredData['demographic_data']) && ! empty($structuredData['demographic_data'])) {
                $this->saveDemographicData($paperEvaluation, $structuredData['demographic_data']);
            } elseif (isset($structuredData['likert_answers']) && ! empty($structuredData['likert_answers'])) {
                $this->saveDemographicData($paperEvaluation, $structuredData['likert_answers']);
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
                'marital_status' => $extractedData['marital_status'] ?? null,
                'education_level' => $extractedData['education_level'] ?? null,
                'position' => $extractedData['position'] ?? null,
                'department' => $extractedData['department'] ?? null,
                'position_type' => $extractedData['position_type'] ?? null,
                'contract_type' => $extractedData['contract_type'] ?? null,
                'personnel_type' => $extractedData['personnel_type'] ?? null,
                'work_schedule' => $extractedData['work_schedule'] ?? null,
                'shift_rotation' => $extractedData['shift_rotation'] ?? null,
                'time_in_current_position' => $extractedData['time_in_current_position'] ?? null,
                'work_experience' => $extractedData['work_experience'] ?? null,
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
     * Handles new nested structure (datos_laborales), old OCR structure, and Likert data
     */
    protected function extractDemographicInfo(array $demographicData): array
    {
        // Check if this is Likert data (has 'questions' key indicating it's from likert_answers)
        if (isset($demographicData['questions'])) {
            return $this->extractFromLikert($demographicData);
        }

        // Determine which structure we're dealing with for Referencia V
        if ($this->isNewStructure($demographicData)) {
            return $this->extractFromNewStructure($demographicData);
        } else {
            return $this->extractFromOldStructure($demographicData);
        }
    }

    /**
     * Extract from Likert scale data (workplace climate evaluation)
     */
    private function extractFromLikert(array $likertData): array
    {
        return [
            'gender' => $this->normalizeValue($likertData['genero'] ?? null, [
                'masculino' => 'Masculino',
                'femenino' => 'Femenino',
            ]),
            'age' => null, // Not provided in Likert data
            'marital_status' => null, // Not provided in Likert data
            'education_level' => null, // Not provided in Likert data
            'position' => $likertData['puestos'] ?? null,
            'department' => $likertData['areas'] ?? null,
            'position_type' => null, // Not provided in Likert data
            'contract_type' => $this->normalizeContractType($likertData['tipo_contrato'] ?? null),
            'personnel_type' => null, // Not provided in Likert data
            'work_schedule' => $this->normalizeWorkSchedule($likertData['turno'] ?? null),
            'shift_rotation' => null, // Not provided in Likert data
            'time_in_current_position' => null, // Not provided in Likert data
            'work_experience' => null, // Not provided in Likert data
            'extra_fields' => [
                'questions' => $likertData['questions'] ?? null,
            ],
        ];
    }

    /**
     * Check if using new nested structure (datos_laborales)
     */
    private function isNewStructure(array $data): bool
    {
        return isset($data['datos_laborales']) && is_array($data['datos_laborales']);
    }

    /**
     * Extract from new nested structure (datos_laborales)
     */
    private function extractFromNewStructure(array $demographicData): array
    {
        $laboralData = $demographicData['datos_laborales'] ?? [];
        $experiencia = $laboralData['experiencia'] ?? [];

        return [
            'gender' => $demographicData['sexo'] ?? null,
            'age' => $demographicData['edad'] ?? null,
            'marital_status' => $demographicData['estado_civil'] ?? null,
            'education_level' => $demographicData['nivel_estudios'] ?? null,
            'position' => $laboralData['ocupacion_puesto'] ?? null,
            'department' => $laboralData['departamento_seccion_area'] ?? null,
            'position_type' => $laboralData['tipo_puesto'] ?? null,
            'contract_type' => $laboralData['tipo_contratacion'] ?? null,
            'personnel_type' => $laboralData['tipo_personal'] ?? null,
            'work_schedule' => $laboralData['tipo_jornada'] ?? null,
            'shift_rotation' => $laboralData['rotacion_turnos'] ?? null,
            'time_in_current_position' => $experiencia['tiempo_puesto_actual'] ?? null,
            'work_experience' => $experiencia['tiempo_experiencia_laboral'] ?? null,
        ];
    }

    /**
     * Extract from old OCR structure
     */
    private function extractFromOldStructure(array $demographicData): array
    {
        // Build age from decenas/unidades if available
        $age = null;
        if (isset($demographicData['edad']) && is_array($demographicData['edad'])) {
            $decenas = $demographicData['edad']['decenas'] ?? 0;
            $unidades = $demographicData['edad']['unidades'] ?? 0;
            $ageValue = ($decenas * 10) + $unidades;
            // Convert numeric age to range format
            $age = $this->convertAgeToRange($ageValue);
        } elseif (is_string($demographicData['edad'] ?? null)) {
            $age = $demographicData['edad'];
        }

        // Extract from fila1 if value is array
        $position = $this->extractFromObject($demographicData['ocupacion_puesto'] ?? null);
        $department = $this->extractFromObject($demographicData['departamento_seccion_area'] ?? null);

        // Normalize field values (convert underscores to proper format)
        $sexo = $demographicData['sexo'] ?? null;
        $sexo = $this->normalizeValue($sexo, ['masculino' => 'Masculino', 'femenino' => 'Femenino']);

        $estadoCivil = $demographicData['estado_civil'] ?? null;
        $estadoCivil = $this->normalizeValue($estadoCivil, [
            'soltero' => 'Soltero',
            'casado' => 'Casado',
            'union_libre' => 'Unión libre',
            'divorciado' => 'Divorciado',
            'viudo' => 'Viudo',
        ]);

        $nivelEstudios = $this->extractEducationLevel($demographicData['nivel_estudios'] ?? null);

        return [
            'gender' => $sexo,
            'age' => $age,
            'marital_status' => $estadoCivil,
            'education_level' => $nivelEstudios,
            'position' => $position,
            'department' => $department,
            'position_type' => $this->normalizePosicionType($demographicData['tipo_puesto'] ?? null),
            'contract_type' => $this->normalizeContractType($demographicData['tipo_contratacion'] ?? null),
            'personnel_type' => $this->normalizePersonnelType($demographicData['tipo_personal'] ?? null),
            'work_schedule' => $this->normalizeWorkSchedule($demographicData['tipo_jornada'] ?? null),
            'shift_rotation' => $this->normalizeYesNo($demographicData['rotacion_turnos'] ?? null),
            'time_in_current_position' => $this->normalizeExperience($demographicData['tiempo_puesto_actual'] ?? null),
            'work_experience' => $this->normalizeExperience($demographicData['tiempo_experiencia_laboral'] ?? null),
        ];
    }

    /**
     * Extract value from object (fila1 or direct value)
     */
    private function extractFromObject($value): ?string
    {
        if (is_array($value) && isset($value['fila1'])) {
            return $value['fila1'] ?: null;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Convert numeric age to age range format
     */
    private function convertAgeToRange(int $age): string
    {
        if ($age < 15) {
            return '15 - 19';
        }
        if ($age <= 19) {
            return '15 - 19';
        }
        if ($age <= 24) {
            return '20 - 24';
        }
        if ($age <= 29) {
            return '25 - 29';
        }
        if ($age <= 34) {
            return '30 - 34';
        }
        if ($age <= 39) {
            return '35 - 39';
        }
        if ($age <= 44) {
            return '40 - 44';
        }
        if ($age <= 49) {
            return '45 - 49';
        }
        if ($age <= 54) {
            return '50 - 54';
        }
        if ($age <= 59) {
            return '55 - 59';
        }
        if ($age <= 64) {
            return '60 - 64';
        }
        if ($age <= 69) {
            return '65 - 69';
        }

        return '70 o más';
    }

    /**
     * Normalize position type
     */
    private function normalizePosicionType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'operativo' => 'Operativo',
            'profesional_o_tecnico' => 'Profesional o técnico',
            'supervisor' => 'Supervisor',
            'gerente' => 'Gerente',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Normalize contract type
     */
    private function normalizeContractType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'por_obra_o_proyecto' => 'Por obra o proyecto',
            'por_tiempo_determinado_temporal' => 'Por tiempo determinado (temporal)',
            'tiempo_indeterminado' => 'Tiempo indeterminado',
            'honorarios' => 'Honorarios',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Normalize personnel type
     */
    private function normalizePersonnelType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'sindicalizado' => 'Sindicalizado',
            'confianza' => 'Confianza',
            'ninguno' => 'Ninguno',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Normalize work schedule
     */
    private function normalizeWorkSchedule(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'fijo_nocturno_(entre_las_20:00_y_6:00_hrs)' => 'Fijo nocturno (entre las 20:00 y 6:00 hrs)',
            'fijo_diurno_(entre_las_6:00_y_20:00_hrs)' => 'Fijo diurno (entre las 6:00 y 20:00 hrs)',
            'fijo_mixto_(combinacion_de_nocturno_y_diurno)' => 'Fijo mixto (combinación de nocturno y diurno)',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Normalize yes/no values
     */
    private function normalizeYesNo(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return match (strtolower($value)) {
            'si', 'yes', 'true' => 'Sí',
            'no', 'false' => 'No',
            default => $value,
        };
    }

    /**
     * Normalize experience/time ranges
     */
    private function normalizeExperience(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $map = [
            'menos_de_6_meses' => 'Menos de 6 meses',
            'entre_6_meses_y_1_ano' => 'Entre 6 meses y 1 año',
            'entre_1_a_4_anos' => 'Entre 1 a 4 años',
            'entre_5_a_9_anos' => 'Entre 5 a 9 años',
            'entre_10_a_14_anos' => 'Entre 10 a 14 años',
            'entre_15_a_19_anos' => 'Entre 15 a 19 años',
            'entre_20_a_24_anos' => 'Entre 20 a 24 años',
            '25_anos_o_mas' => '25 años o más',
        ];

        return $map[strtolower($value)] ?? $value;
    }

    /**
     * Extract education level from nested structure
     */
    private function extractEducationLevel($value): ?string
    {
        if (is_array($value)) {
            // Old OCR structure with nested education
            foreach ($value as $key => $item) {
                if (is_array($item) && isset($item['seleccionado']) && $item['seleccionado']) {
                    $completado = $item['completado'] ?? 'Terminada';
                    $level = empty($key) ? 'Desconocido' : ucfirst(str_replace('_', ' ', $key));

                    return $level.' - '.ucfirst(str_replace('_', ' ', $completado));
                }
            }
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Normalize generic values
     */
    private function normalizeValue(?string $value, array $map): ?string
    {
        if (! $value) {
            return null;
        }

        return $map[strtolower($value)] ?? $value;
    }
}
