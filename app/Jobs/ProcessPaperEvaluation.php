<?php

namespace App\Jobs;

use App\Events\EvaluationProcessingStatusChanged;
use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\DemographicDataNormalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessPaperEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $fullPath;

    protected ?string $initiatorUserId;

    protected ?string $batchId;

    protected int $currentIndex;

    protected int $totalFiles;

    protected string $fileName;

    protected ?string $instrument;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $fullPath,
        ?string $initiatorUserId = null,
        ?string $batchId = null,
        int $currentIndex = 0,
        int $totalFiles = 1,
        string $fileName = '',
        ?string $instrument = null
    ) {
        $this->fullPath = $fullPath;
        $this->initiatorUserId = $initiatorUserId;
        $this->batchId = $batchId;
        $this->currentIndex = $currentIndex;
        $this->totalFiles = $totalFiles;
        $this->fileName = $fileName;
        $this->instrument = $instrument;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->broadcastStatus('running', 'Enviando PDF al servicio OCR...');

        try {
            // 1. Call OCR HTTP service
            $this->broadcastStatus('running', 'Ejecutando análisis OCR...');
            $results = $this->callOcrService();

            $this->broadcastStatus('running', 'Guardando resultados en la base de datos...');

            // 2. Process each result returned by the service
            foreach ($results as $result) {
                $this->processOcrResult($result);
            }

            // 3. Cleanup uploaded file
            $this->cleanupFiles();

            $this->broadcastStatus('finished', 'El procesamiento ha finalizado exitosamente', true);

        } catch (\Exception $e) {
            Log::error('ProcessPaperEvaluation - Error: '.$e->getMessage(), [
                'file' => $this->fullPath,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->broadcastStatus('error', 'Error durante el procesamiento: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Broadcast status update with batch information.
     * Wrapped in try/catch to isolate Reverb failures from job processing.
     */
    protected function broadcastStatus(string $status, string $message, bool $finished = false): void
    {
        try {
            broadcast(new EvaluationProcessingStatusChanged(
                $status,
                $message,
                $finished,
                $this->initiatorUserId,
                $this->batchId,
                $this->currentIndex,
                $this->totalFiles,
                $this->fileName
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast evaluation status: '.$e->getMessage());
        }
    }

    /**
     * Send PDF to OCR HTTP service and return parsed results.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function callOcrService(): array
    {
        $serviceUrl = config('services.ocr.url');
        $timeout = (int) config('services.ocr.timeout', 300);

        Log::info('Sending PDF to OCR service', ['file' => $this->fileName, 'url' => $serviceUrl]);

        $response = Http::timeout($timeout)
            ->attach('file', file_get_contents($this->fullPath), basename($this->fullPath))
            ->post($serviceUrl.'/process');

        if ($response->failed()) {
            $error = $response->json('error', 'Unknown error');
            $detail = $response->json('detail', '');
            throw new \RuntimeException("OCR service returned error ({$response->status()}): {$error}. {$detail}");
        }

        $results = $response->json('results');

        if (empty($results)) {
            throw new \RuntimeException('OCR service returned no results for file: '.$this->fileName);
        }

        Log::info('OCR service processed successfully', ['results' => count($results), 'file' => $this->fileName]);

        return $results;
    }

    /**
     * Process a single OCR result from the HTTP service response.
     *
     * @param  array<string, mixed>  $result
     */
    protected function processOcrResult(array $result): void
    {
        $folio = $result['folio'];
        $rawData = $result['answers'];
        $markedImageBase64 = $result['marked_image_base64'] ?? null;

        try {
            // Parse folio
            $folioData = PaperEvaluation::parseFolio($folio);

            // Find or create organization
            $organization = $this->findOrCreateOrganization($folioData['organization_code']);
            $workCenter = $this->resolveWorkCenterByCode($organization, $folioData['work_center_code'] ?? null);

            // Extract structured data based on evaluation type
            $structuredData = $this->extractStructuredData($rawData, $folioData['evaluation_type'], $folioData['evaluation_type_code']);

            // Check if evaluation already exists to preserve evaluee_name
            $existingEvaluation = PaperEvaluation::query()
                ->where('folio', $folio)
                ->where('source', 'paper')
                ->first();
            $preservedName = $existingEvaluation?->evaluee_name;

            // Create or update PaperEvaluation
            $paperEvaluation = PaperEvaluation::updateOrCreate(
                ['folio' => $folio, 'source' => 'paper'],
                [
                    'evaluation_type_code' => $folioData['evaluation_type_code'],
                    'organization_code' => $folioData['organization_code'],
                    'work_center_code' => $folioData['work_center_code'] ?? null,
                    'personal_folio' => $folioData['personal_folio'],
                    'organization_id' => $organization?->id,
                    'work_center_id' => $workCenter?->id,
                    'evaluation_type' => $folioData['evaluation_type'],
                    'source' => 'paper',
                    'processing_status' => 'completed',
                    'processed_at' => now(),
                    'pdf_file_path' => $this->fullPath,
                    'evaluee_name' => $preservedName,
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
                $this->propagateDemographicDataToRelatedEvaluations($paperEvaluation, $structuredData['demographic_data']);
            } elseif (isset($structuredData['likert_answers']) && ! empty($structuredData['likert_answers'])) {
                $this->saveDemographicData($paperEvaluation, $structuredData['likert_answers']);
            } elseif (in_array($paperEvaluation->evaluation_type, ['referencia_i', 'referencia_iii'], true)) {
                $this->syncDemographicDataFromRelatedReferenciaV($paperEvaluation);
            }

            Log::info("Paper evaluation processed successfully: {$folio}");

            // Save marked image if provided by the service
            if ($markedImageBase64) {
                $this->saveMarkedImageFromBase64($folio, $markedImageBase64);
            }

        } catch (\Exception $e) {
            Log::error("Error processing OCR result for folio {$folio}: ".$e->getMessage());

            // Create failed record
            try {
                $folioData = PaperEvaluation::parseFolio($folio);
                $organization = $this->findOrCreateOrganization($folioData['organization_code']);
                $workCenter = $this->resolveWorkCenterByCode($organization, $folioData['work_center_code'] ?? null);

                PaperEvaluation::updateOrCreate(
                    ['folio' => $folio, 'source' => 'paper'],
                    [
                        'evaluation_type_code' => $folioData['evaluation_type_code'],
                        'organization_code' => $folioData['organization_code'],
                        'work_center_code' => $folioData['work_center_code'] ?? null,
                        'personal_folio' => $folioData['personal_folio'],
                        'organization_id' => $organization?->id,
                        'work_center_id' => $workCenter?->id,
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
    protected function extractStructuredData(array $rawData, string $evaluationType, string $evaluationTypeCode = ''): array
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
                $structuredData['citsats_s1'] = $rawData['citsats_s1'] ?? $this->extractCitsatsFromFlatRawData($rawData);
                break;

            case 'referencia_v':
                // Reference V - Demographic data
                $structuredData['demographic_data'] = $rawData;
                break;

            case 'cisneros':
                // Cisneros scale - mobbing questions
                $cisnerosRawAnswers = $rawData['cisneros'] ?? $rawData;
                $structuredData['cisneros_answers'] = is_array($cisnerosRawAnswers)
                    ? $this->normalizeCisnerosAnswers($cisnerosRawAnswers)
                    : null;
                break;

            case 'likert':
                // Likert - Workplace climate evaluation (23 questions + demographics)
                // Code 05 = standard Likert, Code 06 = Likert Planta 3
                $questionsKey = $evaluationTypeCode === '06' ? 'likert_planta_3' : 'likert';
                $structuredData['likert_answers'] = [
                    'questions' => $rawData[$questionsKey] ?? null,
                    'genero' => $rawData['genero'] ?? null,
                    'turno' => $rawData['turno'] ?? null,
                    'tipo_contrato' => $rawData['tipo_contrato'] ?? null,
                    'puestos' => $rawData['puestos'] ?? null,
                    'areas' => $rawData['areas'] ?? null,
                ];
                break;

            case 'likert_planta_3':
                // Likert Planta 3 - Workplace climate evaluation (23 questions + demographics)
                $structuredData['likert_answers'] = [
                    'questions' => $rawData['likert_planta_3'] ?? null,
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
     * Extract ATS answers (6 items) from flat OMR payloads where answers are keyed numerically.
     *
     * @param  array<string, mixed>  $rawData
     * @return array<string, mixed>|null
     */
    private function extractCitsatsFromFlatRawData(array $rawData): ?array
    {
        $atsEntries = [];

        foreach ($rawData as $rawKey => $rawAnswer) {
            if (! is_array($rawAnswer)) {
                continue;
            }

            if (($rawAnswer['mapping_section'] ?? null) !== 'ats') {
                continue;
            }

            if (! is_numeric((string) $rawKey)) {
                continue;
            }

            $atsEntries[(int) $rawKey] = $rawAnswer;
        }

        if ($atsEntries === []) {
            return null;
        }

        ksort($atsEntries);

        $citsats = [];
        $index = 1;

        foreach ($atsEntries as $entry) {
            if ($index > 6) {
                break;
            }

            $citsats[(string) $index] = $entry['value'] ?? null;
            $index++;
        }

        return $citsats === [] ? null : $citsats;
    }

    /**
     * Normalize Cisneros answers to canonical JSON format:
     * 1-43 => ['persona' => 'A|B|C|null', 'frecuencia' => 0..6|null]
     * 44 => bool
     */
    private function normalizeCisnerosAnswers(array $answers): ?array
    {
        $normalized = [];

        for ($question = 1; $question <= 43; $question++) {
            $persona = $this->extractCisnerosPersona($answers, $question);
            $frecuencia = $this->extractCisnerosFrecuencia($answers, $question);

            if ($persona !== null || $frecuencia !== null) {
                $normalized[(string) $question] = [
                    'persona' => $persona,
                    'frecuencia' => $frecuencia,
                ];
            }
        }

        $question44 = $this->extractCisnerosQuestion44($answers);
        if ($question44 !== null) {
            $normalized['44'] = $question44;
        }

        return ! empty($normalized) ? $normalized : null;
    }

    private function extractCisnerosPersona(array $answers, int $question): ?string
    {
        $questionKey = (string) $question;
        $questionData = $answers[$questionKey] ?? null;

        if (is_array($questionData) && array_key_exists('persona', $questionData)) {
            return $this->normalizeCisnerosPersonaValue($questionData['persona']);
        }

        $flatKey = 'persona'.$question;

        return $this->normalizeCisnerosPersonaValue($answers[$flatKey] ?? null);
    }

    private function extractCisnerosFrecuencia(array $answers, int $question): ?int
    {
        $questionKey = (string) $question;
        $questionData = $answers[$questionKey] ?? null;

        if (is_array($questionData) && array_key_exists('frecuencia', $questionData)) {
            return $this->normalizeCisnerosFrecuenciaValue($questionData['frecuencia']);
        }

        $flatKey = 'frecuencia'.$question;

        return $this->normalizeCisnerosFrecuenciaValue($answers[$flatKey] ?? null);
    }

    private function extractCisnerosQuestion44(array $answers): ?bool
    {
        $candidates = [
            $answers['44'] ?? null,
            $answers['q44'] ?? null,
            $answers['question44'] ?? null,
            $answers['pregunta44'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeYesNoValue($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeCisnerosPersonaValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return in_array($normalized, ['A', 'B', 'C'], true) ? $normalized : null;
    }

    private function normalizeCisnerosFrecuenciaValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return ($value >= 0 && $value <= 6) ? $value : null;
        }

        if (is_string($value) && is_numeric($value)) {
            $parsed = (int) $value;

            return ($parsed >= 0 && $parsed <= 6) ? $parsed : null;
        }

        return null;
    }

    private function normalizeYesNoValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : null);
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
        ]);
        $normalized = strtoupper($normalized);

        if (in_array($normalized, ['SI', 'S', 'YES', 'Y', 'TRUE', '1'], true)) {
            return true;
        }

        if (in_array($normalized, ['NO', 'N', 'FALSE', '0'], true)) {
            return false;
        }

        return null;
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

    protected function resolveWorkCenterByCode(?Organization $organization, ?string $workCenterCode): ?WorkCenter
    {
        if (! $organization || ! is_string($workCenterCode) || trim($workCenterCode) === '') {
            return null;
        }

        $normalizedCode = str_pad(trim($workCenterCode), 2, '0', STR_PAD_LEFT);
        $fourDigitCode = str_pad($normalizedCode, 4, '0', STR_PAD_LEFT);

        return $organization->workCenters()
            ->where(function ($query) use ($normalizedCode, $fourDigitCode) {
                $query->where('code', $normalizedCode)
                    ->orWhere('code', $fourDigitCode)
                    ->orWhereRaw("LPAD(RIGHT(code, 2), 2, '0') = ?", [$normalizedCode]);
            })
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->first();
    }

    /**
     * Save the base64-encoded marked image returned by the OCR service to public storage.
     */
    protected function saveMarkedImageFromBase64(string $folio, string $base64Image): void
    {
        try {
            $imageData = base64_decode($base64Image);
            $publicFoliosPath = storage_path("app/public/folios/{$folio}.png");
            $foliosDir = dirname($publicFoliosPath);

            if (! File::exists($foliosDir)) {
                File::makeDirectory($foliosDir, 0755, true);
            }

            File::put($publicFoliosPath, $imageData);
            Log::info("Marked image saved for folio: {$folio}");
        } catch (\Exception $e) {
            Log::error("Error saving marked image for folio {$folio}: ".$e->getMessage());
        }
    }

    /**
     * Cleanup uploaded files - only delete the specific file processed
     */
    protected function cleanupFiles(): void
    {
        try {
            if (File::exists($this->fullPath)) {
                File::delete($this->fullPath);
                Log::info('Cleanup completed successfully for file: '.$this->fullPath);
            }
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
            Log::info('Demographic Data: '.json_encode($demographicData));
            $normalizationService = app(DemographicDataNormalizationService::class);
            $extractedData = $normalizationService->extractDemographicInfo($demographicData);
            Log::info('extractedData: '.json_encode($extractedData));

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
     * Propagate demographic data captured in Referencia V to related paper evaluations
     * for the same person in the same organization/work center.
     */
    protected function propagateDemographicDataToRelatedEvaluations(PaperEvaluation $sourceEvaluation, array $demographicData): void
    {
        if ($sourceEvaluation->evaluation_type !== 'referencia_v') {
            return;
        }

        $relatedEvaluations = PaperEvaluation::query()
            ->where('source', 'paper')
            ->where('organization_id', $sourceEvaluation->organization_id)
            ->where('work_center_id', $sourceEvaluation->work_center_id)
            ->where('personal_folio', $sourceEvaluation->personal_folio)
            ->whereIn('evaluation_type', ['referencia_i', 'referencia_iii'])
            ->where('id', '!=', $sourceEvaluation->id)
            ->get();

        foreach ($relatedEvaluations as $relatedEvaluation) {
            $relatedEvaluation->update([
                'demographic_data' => $demographicData,
            ]);

            $this->saveDemographicData($relatedEvaluation, $demographicData);
        }
    }

    /**
     * Ensure Ref I/III records get demographic data when Ref V already exists.
     */
    protected function syncDemographicDataFromRelatedReferenciaV(PaperEvaluation $paperEvaluation): void
    {
        $relatedReferenciaV = PaperEvaluation::query()
            ->where('source', 'paper')
            ->where('organization_id', $paperEvaluation->organization_id)
            ->where('work_center_id', $paperEvaluation->work_center_id)
            ->where('personal_folio', $paperEvaluation->personal_folio)
            ->where('evaluation_type', 'referencia_v')
            ->whereNotNull('demographic_data')
            ->first();

        if (! $relatedReferenciaV || ! is_array($relatedReferenciaV->demographic_data) || empty($relatedReferenciaV->demographic_data)) {
            return;
        }

        $paperEvaluation->update([
            'demographic_data' => $relatedReferenciaV->demographic_data,
        ]);

        $this->saveDemographicData($paperEvaluation, $relatedReferenciaV->demographic_data);
    }

    /**
     * Extract demographic information from raw data
     * Handles new nested structure (datos_laborales), old OCR structure, and Likert data
     */
    protected function extractDemographicInfo(array $demographicData): array
    {
        // Check if this is Likert data (has 'questions' key indicating it's from likert_answers)
        // Use array_key_exists instead of isset because isset returns false for null values
        if (array_key_exists('questions', $demographicData)) {
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
            'position' => $this->normalizePositionType($likertData['puestos'] ?? null),
            'department' => $this->normalizeDepartmentType($likertData['areas'] ?? null),
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

        $lowerValue = strtolower($value);

        // Map for standard Likert (05) - Spanish descriptions
        $mapLikert = [
            'por_obra_o_proyecto' => 'Por obra o proyecto',
            'por_tiempo_determinado_temporal' => 'Por tiempo determinado (temporal)',
            'tiempo_indeterminado' => 'Tiempo indeterminado',
            'tiempo_determinado' => 'Tiempo determinado',
            'honorarios' => 'Honorarios',
            'confianza' => 'Confianza',
            'sindicalizado' => 'Sindicalizado',
        ];

        // Map for Likert Planta 3 (06) - opcion_N format
        $mapPlanta3 = [
            'opcion_1' => 'Opción 1',
            'opcion_2' => 'Opción 2',
        ];

        // Try Planta 3 format first (opcion_N)
        if (isset($mapPlanta3[$lowerValue])) {
            return $mapPlanta3[$lowerValue];
        }

        // Then try standard Likert format
        if (isset($mapLikert[$lowerValue])) {
            return $mapLikert[$lowerValue];
        }

        // Return original value as fallback
        return $value;
    }

    /**
     * Normalize position
     */
    private function normalizePositionType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        // Get Likert standard position map
        $mapLikert = config('likert-value.puestos', []);

        // Try direct lookup first (for text values)
        if (isset($mapLikert[strtolower($value)])) {
            return $mapLikert[strtolower($value)];
        }

        // For numeric indices (1-19 from Planta 3), map to config values
        // Get Planta 3 position config
        $mapPlanta3 = config('likert-value.puestos_planta_3', []);

        if (is_numeric($value) && isset($mapPlanta3[$value])) {
            return $mapPlanta3[$value];
        }

        // Also check old likert_puestos config by numeric index
        if (is_numeric($value)) {
            $puestoNumber = (int) $value;
            $puestos = config('likert-value.puestos', []);

            // Try to find by position in array
            $puestosArray = array_values($puestos);
            if (isset($puestosArray[$puestoNumber - 1])) {
                return $puestosArray[$puestoNumber - 1];
            }
        }

        // Return original value as fallback
        return $value;
    }

    /**
     * Normalize department
     */
    private function normalizeDepartmentType(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        // Get Likert standard areas map
        $mapLikert = config('likert-value.areas', []);

        // Try direct lookup first (for text values)
        if (isset($mapLikert[strtolower($value)])) {
            return $mapLikert[strtolower($value)];
        }

        // For numeric indices (1-10 from Planta 3), map to config values
        // Get Planta 3 areas config
        $mapPlanta3 = config('likert-value.areas_planta_3', []);

        if (is_numeric($value) && isset($mapPlanta3[$value])) {
            return $mapPlanta3[$value];
        }

        // Also check old likert_areas config by numeric index
        if (is_numeric($value)) {
            $areaNumber = (int) $value;
            $areas = config('likert-value.areas', []);

            // Try to find by position in array
            $areasArray = array_values($areas);
            if (isset($areasArray[$areaNumber - 1])) {
                return $areasArray[$areaNumber - 1];
            }
        }

        // Return original value as fallback
        return $value;
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
            'confianza' => 'Salary',
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

        $lowerValue = strtolower($value);

        // Map for standard Likert (05) - Detailed shift names
        $mapLikert = [
            'fijo_nocturno_(entre_las_20:00_y_6:00_hrs)' => 'Fijo nocturno (entre las 20:00 y 6:00 hrs)',
            'fijo_diurno_(entre_las_6:00_y_20:00_hrs)' => 'Fijo diurno (entre las 6:00 y 20:00 hrs)',
            'fijo_mixto_(combinacion_de_nocturno_y_diurno)' => 'Fijo mixto (combinación de nocturno y diurno)',
            'rotativo' => 'Rotativo',
            'nocturno' => 'Nocturno',
            'diurno' => 'Diurno',
            'mixto' => 'Mixto',
        ];

        // Map for Likert Planta 3 (06) - turno_N format
        $mapPlanta3 = [
            'turno_1' => 'Turno 1',
            'turno_2' => 'Turno 2',
            'turno_3' => 'Turno 3',
            'turno_4' => 'Turno 4',
            'turno_5' => 'Turno 5',
        ];

        // Try Planta 3 format first (turno_N)
        if (isset($mapPlanta3[$lowerValue])) {
            return $mapPlanta3[$lowerValue];
        }

        // Then try standard Likert format
        if (isset($mapLikert[$lowerValue])) {
            return $mapLikert[$lowerValue];
        }

        // Return original value as fallback
        return $value;
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
