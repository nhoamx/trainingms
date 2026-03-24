<?php

namespace App\Jobs;

use App\Models\PaperEvaluation;
use App\Models\SubmissionStatus;
use App\Models\User;
use App\Notifications\EvaluationCompletedNotification;
use App\Services\DemographicDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessOnlineEvaluation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes

    public int $tries = 3;

    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $submissionStatusId
    ) {
        // Uses default queue - ensures it's processed by standard queue:work command
    }

    /**
     * Execute the job.
     */
    public function handle(DemographicDataService $demographicService): void
    {
        $submissionStatus = null;

        try {
            // 1. Find SubmissionStatus
            $submissionStatus = SubmissionStatus::findOrFail($this->submissionStatusId);

            // 2. Mark as processing
            $submissionStatus->markAsProcessing();

            // 3. Create PaperEvaluation within a transaction
            DB::beginTransaction();

            $paperEvaluation = $this->createPaperEvaluation($submissionStatus);

            // 4. Create DemographicData using service if referencia_v exists
            // For dual-instrument records, create demographic data for BOTH records
            if (isset($submissionStatus->data_snapshot['referencia_v']) &&
                ! empty($submissionStatus->data_snapshot['referencia_v'])) {

                // Create for primary record
                $this->createDemographicData(
                    $paperEvaluation,
                    $demographicService,
                    $submissionStatus->data_snapshot
                );

                // If there's a related evaluation (dual-instrument), create for it too
                if ($paperEvaluation->related_evaluation_folio) {
                    $relatedEvaluation = PaperEvaluation::where('folio', $paperEvaluation->related_evaluation_folio)->first();
                    if ($relatedEvaluation) {
                        $this->createDemographicData(
                            $relatedEvaluation,
                            $demographicService,
                            $submissionStatus->data_snapshot
                        );
                    }
                }
            }

            DB::commit();

            // 5. Mark as completed
            $submissionStatus->markAsCompleted();

            // 6. Send notification to organization users
            $this->sendCompletionNotification($submissionStatus, $paperEvaluation);

        } catch (\Exception $e) {
            DB::rollBack();

            $errorMessage = $e->getMessage();

            Log::error('Error processing online evaluation', [
                'submission_id' => $this->submissionStatusId,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            // Handle errors with retry logic
            if ($submissionStatus && $submissionStatus->canRetry()) {
                // Calculate progressive delay (retry_count * 60 seconds)
                $delay = ($submissionStatus->retry_count + 1) * 60;

                $submissionStatus->update([
                    'status' => SubmissionStatus::STATUS_RETRYING,
                    'error_message' => $errorMessage,
                    'retry_count' => $submissionStatus->retry_count + 1,
                ]);

                // Re-dispatch with delay
                static::dispatch($this->submissionStatusId)
                    ->delay(now()->addSeconds($delay));
            } else {
                // Mark as failed if max retries reached or cannot retry
                if ($submissionStatus) {
                    $submissionStatus->markAsFailed($errorMessage);
                }

                Log::error('Online evaluation failed - max retries reached', [
                    'submission_id' => $this->submissionStatusId,
                    'retry_count' => $submissionStatus?->retry_count ?? 0,
                ]);
            }

            throw $e;
        }
    }

    /**
     * Create PaperEvaluation from SubmissionStatus
     * Replicates OCR pattern: creates separate records for each instrument type
     */
    protected function createPaperEvaluation(SubmissionStatus $submissionStatus): PaperEvaluation
    {
        $dataSnapshot = $submissionStatus->data_snapshot;

        // Determine which instruments are present in the submission
        $hasReferenciaIII = $this->hasReferenciaIII($dataSnapshot);
        $hasReferenciaI = $this->hasReferenciaI($dataSnapshot);
        $hasCisneros = $this->hasCisneros($dataSnapshot);

        if ($hasCisneros) {
            // CASE: Cisneros evaluation (separate instrument)
            return $this->createSingleInstrumentRecord($submissionStatus, 'cisneros');
        }

        if ($hasReferenciaIII && $hasReferenciaI) {
            // CASE: Complete quiz with both instruments (Take.vue)
            // Create 2 separate records like OCR paper forms
            return $this->createDualInstrumentRecords($submissionStatus);
        } elseif ($hasReferenciaI) {
            // CASE: Only Referencia I (TakeReduced.vue)
            return $this->createSingleInstrumentRecord($submissionStatus, 'referencia_i');
        } elseif ($hasReferenciaIII) {
            // CASE: Only Referencia III (rare - incomplete submission)
            return $this->createSingleInstrumentRecord($submissionStatus, 'referencia_iii');
        } else {
            throw new \Exception('No valid evaluation data found in submission');
        }
    }

    /**
     * Create DemographicData using DemographicDataService
     */
    protected function createDemographicData(
        PaperEvaluation $paperEvaluation,
        DemographicDataService $demographicService,
        array $dataSnapshot
    ): void {
        try {
            $referenciaV = $dataSnapshot['referencia_v'] ?? [];

            if (empty($referenciaV)) {
                return;
            }

            $demographicData = $demographicService->updateOrCreate($paperEvaluation, $referenciaV);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Build standardized raw_data structure for online submissions
     */
    protected function buildStandardizedRawData(array $dataSnapshot, SubmissionStatus $submissionStatus): array
    {
        $quiz = $submissionStatus->quiz;
        $organization = $submissionStatus->organization;

        // Usar datos de organization_info del usuario si están disponibles,
        // sino usar los datos del modelo Organization como fallback
        $userOrgInfo = $dataSnapshot['organization_info'] ?? [];

        $rawData = [
            'source' => 'online',
            'source_metadata' => [
                'quiz_id' => $quiz?->id,
                'quiz_name' => $quiz?->name,
                'quiz_type' => $this->determineQuizType($dataSnapshot),
                'submitted_at' => $submissionStatus->created_at->toIso8601String(),
                'submission_ip' => $dataSnapshot['submission_ip'] ?? null,
                'user_agent' => $dataSnapshot['user_agent'] ?? null,
                'organization_info' => [
                    'nombre_comercial' => $userOrgInfo['nombre_comercial'] ?? $organization?->nombre_comercial,
                    'division_sucursal' => $userOrgInfo['division_sucursal'] ?? $organization?->division_sucursal,
                    'estado' => $userOrgInfo['estado'] ?? $organization?->estado,
                    'ciudad' => $userOrgInfo['ciudad'] ?? $organization?->ciudad,
                ],
            ],
            'custom_fields' => $dataSnapshot['custom_fields'] ?? [],
            'file_uploads' => $this->extractFileUploads($dataSnapshot),
        ];

        // Include quiz responses sections
        if (isset($dataSnapshot['referencia_i'])) {
            $rawData['referencia_i'] = $dataSnapshot['referencia_i'];
        }

        if (isset($dataSnapshot['referencia_iii'])) {
            $rawData['referencia_iii'] = $dataSnapshot['referencia_iii'];
        }

        if (isset($dataSnapshot['escala_cisneros'])) {
            $rawData['escala_cisneros'] = $dataSnapshot['escala_cisneros'];
        }

        if (isset($dataSnapshot['referencia_v'])) {
            $rawData['referencia_v'] = $dataSnapshot['referencia_v'];
        }

        return $rawData;
    }

    /**
     * Determine quiz type from data_snapshot
     */
    protected function determineQuizType(array $dataSnapshot): string
    {
        // Check what type of evaluation data exists
        $hasCisneros = isset($dataSnapshot['escala_cisneros']) && ! empty($dataSnapshot['escala_cisneros']);
        $hasReferenciaIII = isset($dataSnapshot['referencia_iii']) && ! empty($dataSnapshot['referencia_iii']);
        $hasAcontecimientos = isset($dataSnapshot['referencia_i']['acontecimientos_traumaticos']) &&
            ! empty($dataSnapshot['referencia_i']['acontecimientos_traumaticos']);

        if ($hasCisneros) {
            return 'cisneros';
        }

        if ($hasAcontecimientos && ! $hasReferenciaIII) {
            return 'reducido';
        }

        return 'normal';
    }

    /**
     * Extract file upload paths from referencia_v
     */
    protected function extractFileUploads(array $dataSnapshot): array
    {
        $referenciaV = $dataSnapshot['referencia_v'] ?? [];

        return [
            'ine_frente' => $referenciaV['ine_frente'] ?? null,
            'ine_reverso' => $referenciaV['ine_reverso'] ?? null,
        ];
    }

    /**
     * Extract Referencia I answers (Guide I - PTSD)
     * Now expects direct indices 1-13
     */
    protected function extractReferenciaI(array $dataSnapshot): ?array
    {
        if (! isset($dataSnapshot['referencia_i']) || empty($dataSnapshot['referencia_i'])) {
            return null;
        }

        $referenciaI = $dataSnapshot['referencia_i'];

        // Filter only numeric keys (1-14)
        $answers = [];
        for ($i = 1; $i <= 14; $i++) {
            if (isset($referenciaI[$i])) {
                $answers[$i] = $referenciaI[$i];
            }
        }

        return ! empty($answers) ? $answers : null;
    }

    /**
     * Extract Referencia III answers (Workplace factors)
     * Now expects direct indices 1-64
     */
    protected function extractReferenciaIII(array $dataSnapshot): ?array
    {
        if (! isset($dataSnapshot['referencia_iii']) || empty($dataSnapshot['referencia_iii'])) {
            return null;
        }

        $referenciaIII = $dataSnapshot['referencia_iii'];

        // Extract general questions (1-64)
        $generalAnswers = [];
        for ($i = 1; $i <= 64; $i++) {
            if (isset($referenciaIII[$i])) {
                $generalAnswers[$i] = $referenciaIII[$i];
            }
        }

        return ! empty($generalAnswers) ? $generalAnswers : null;
    }

    /**
     * Extract conditional questions from Referencia III
     * Now expects customer_service and management with condition + indices
     */
    protected function extractConditionals(array $dataSnapshot): ?array
    {
        if (! isset($dataSnapshot['referencia_iii']) || empty($dataSnapshot['referencia_iii'])) {
            return null;
        }

        $referenciaIII = $dataSnapshot['referencia_iii'];
        $conditionals = [];

        // Extract customer service conditional (65-68) - ONLY if exists
        if (isset($referenciaIII['customer_service']) && isset($referenciaIII['customer_service']['condition'])) {
            $customerService = $referenciaIII['customer_service'];
            $conditionals['customer_service'] = [
                'condition' => $customerService['condition'],
            ];

            // Add questions 65-68 if condition is true
            if ($customerService['condition'] === true) {
                for ($i = 65; $i <= 68; $i++) {
                    if (isset($customerService[$i])) {
                        $conditionals['customer_service'][$i] = $customerService[$i];
                    }
                }
            }
        }

        // Extract management conditional (69-72) - ONLY if exists
        if (isset($referenciaIII['management']) && isset($referenciaIII['management']['condition'])) {
            $management = $referenciaIII['management'];
            $conditionals['management'] = [
                'condition' => $management['condition'],
            ];

            // Add questions 69-72 if condition is true
            if ($management['condition'] === true) {
                for ($i = 69; $i <= 72; $i++) {
                    if (isset($management[$i])) {
                        $conditionals['management'][$i] = $management[$i];
                    }
                }
            }
        }

        return ! empty($conditionals) ? $conditionals : null;
    }

    /**
     * Extract Cisneros answers (Mobbing scale)
     */
    protected function extractCisneros(array $dataSnapshot): ?array
    {
        if (! isset($dataSnapshot['escala_cisneros']) || empty($dataSnapshot['escala_cisneros'])) {
            return null;
        }

        if (! is_array($dataSnapshot['escala_cisneros'])) {
            return null;
        }

        return $this->normalizeCisnerosAnswers($dataSnapshot['escala_cisneros']);
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
     * Extract CITSATS S1 (Acontecimientos Traumáticos) from Referencia III
     * Now expects ats_s1 with indices 1-6
     */
    protected function extractCitsatsS1(array $dataSnapshot): ?array
    {
        // ✅ UNIFIED: Both Take.vue and TakeReduced.vue now send ATS in referencia_i
        if (isset($dataSnapshot['referencia_i']['acontecimientos_traumaticos']) && ! empty($dataSnapshot['referencia_i']['acontecimientos_traumaticos'])) {
            $atsS1 = $dataSnapshot['referencia_i']['acontecimientos_traumaticos'];
        }
        // Fallback for legacy data with ats_s1 in referencia_iii
        elseif (isset($dataSnapshot['referencia_iii']['ats_s1']) && ! empty($dataSnapshot['referencia_iii']['ats_s1'])) {
            $atsS1 = $dataSnapshot['referencia_iii']['ats_s1'];
        } else {
            return null;
        }

        // Filter only string keys "1"-"6"
        $answers = [];
        for ($i = 1; $i <= 6; $i++) {
            $key = (string) $i;
            if (isset($atsS1[$key])) {
                $answers[$key] = $atsS1[$key];
            }
        }

        return ! empty($answers) ? $answers : null;
    }

    /**
     * Extract folio components (evaluation_type_code, organization_code, personal_folio)
     */
    protected function extractFolioComponents(string $folio): array
    {
        try {
            $parsed = PaperEvaluation::parseFolio($folio);

            return [
                'evaluation_type_code' => $parsed['evaluation_type_code'],
                'organization_code' => $parsed['organization_code'],
                'work_center_code' => $parsed['work_center_code'],
                'personal_folio' => $parsed['personal_folio'],
                'evaluation_type' => $parsed['evaluation_type'],
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Create dual instrument records (Referencia III + Referencia I)
     * Replicates OCR pattern: 2 paper forms → 2 JSON files → 2 PaperEvaluation records
     */
    protected function createDualInstrumentRecords(SubmissionStatus $submissionStatus): PaperEvaluation
    {
        $dataSnapshot = $submissionStatus->data_snapshot;

        // RECORD 1: Referencia III (original folio - type 02)
        $refIIIEvaluation = $this->createReferenciaIIIRecord($submissionStatus);

        // RECORD 2: Referencia I (new folio - type 01)
        $refIEvaluation = $this->createReferenciaIRecord($submissionStatus);

        // Link both records bidirectionally (like OCR does with same personal_folio)
        $refIIIEvaluation->related_evaluation_folio = $refIEvaluation->folio;
        $refIIIEvaluation->save();

        $refIEvaluation->related_evaluation_folio = $refIIIEvaluation->folio;
        $refIEvaluation->save();

        Log::info('Dual instrument records created', [
            'ref_iii_folio' => $refIIIEvaluation->folio,
            'ref_i_folio' => $refIEvaluation->folio,
            'submission_id' => $submissionStatus->id,
        ]);

        // Return the primary record (Ref III)
        return $refIIIEvaluation;
    }

    /**
     * Create Referencia III record (Workplace factors)
     * Contains: 64 general questions + conditional sections + traumatic events
     */
    protected function createReferenciaIIIRecord(SubmissionStatus $submissionStatus): PaperEvaluation
    {
        $dataSnapshot = $submissionStatus->data_snapshot;
        $folio = $submissionStatus->folio; // Original folio (type 02 for Ref III)
        $folioComponents = $this->extractFolioComponents($folio);

        // Extract ONLY Referencia III data (like OCR)
        $referenciaIIIAnswers = $this->extractReferenciaIII($dataSnapshot);
        $referenciaIIIConditional = $this->extractConditionals($dataSnapshot);
        $citsatsS1 = $this->extractCitsatsS1($dataSnapshot);

        // Prepare demographic data
        $demographicData = $dataSnapshot['referencia_v'] ?? [];
        if (isset($dataSnapshot['organization_info'])) {
            $demographicData['organization_info'] = $dataSnapshot['organization_info'];
        }

        // Build raw_data for Referencia III
        $rawData = $this->buildRawDataForReferenciaIII($dataSnapshot, $submissionStatus);

        return PaperEvaluation::updateOrCreate(
            ['folio' => $folio, 'source' => 'online'],
            [
                'evaluation_type_code' => $folioComponents['evaluation_type_code'],
                'organization_code' => $folioComponents['organization_code'],
                'work_center_code' => $folioComponents['work_center_code'] ?? '01',
                'personal_folio' => $folioComponents['personal_folio'],
                'organization_id' => $submissionStatus->organization_id,
                'work_center_id' => $submissionStatus->work_center_id,
                'evaluation_type' => 'referencia_iii', // Explicit type
                'source' => 'online',
                'processing_status' => 'completed',
                'processed_at' => now(),
                'evaluee_name' => null, // Ref III does not have evaluee name
                'demographic_data' => $demographicData,

                // ONLY Referencia III fields
                'referencia_iii_answers' => $referenciaIIIAnswers,
                'referencia_iii_conditional' => $referenciaIIIConditional,
                'citsats_s1' => $citsatsS1,

                // Ref I fields set to NULL (explicit separation)
                'referencia_i_answers' => null,

                // Other fields
                'cisneros_answers' => null,
                'raw_data' => $rawData,
            ]
        );
    }

    /**
     * Create Referencia I record (PTSD - Guide I)
     * Contains: 13 follow-up questions + traumatic events context + evaluee name
     */
    protected function createReferenciaIRecord(SubmissionStatus $submissionStatus): PaperEvaluation
    {
        $dataSnapshot = $submissionStatus->data_snapshot;

        // Generate NEW folio type 01 (same work center, different type)
        $refIFolio = $this->generateReferenciaIFolio($submissionStatus);
        $folioComponents = $this->extractFolioComponents($refIFolio);

        // Extract ONLY Referencia I data
        $referenciaIAnswers = $this->extractReferenciaI($dataSnapshot);
        $citsatsS1 = $this->extractCitsatsS1($dataSnapshot); // Intentional duplicate (context)

        // Prepare demographic data (intentional duplicate for independent analysis)
        $demographicData = $dataSnapshot['referencia_v'] ?? [];
        if (isset($dataSnapshot['organization_info'])) {
            $demographicData['organization_info'] = $dataSnapshot['organization_info'];
        }

        $evalueeNameToSave = $dataSnapshot['evaluee_name'] ?? null;

        // Build raw_data for Referencia I
        $rawData = $this->buildRawDataForReferenciaI($dataSnapshot, $submissionStatus);

        return PaperEvaluation::create([
            'folio' => $refIFolio, // NEW folio type 01
            'evaluation_type_code' => '01', // Explicit type 01 for Referencia I
            'organization_code' => $folioComponents['organization_code'],
            'work_center_code' => $folioComponents['work_center_code'] ?? '01',
            'personal_folio' => $folioComponents['personal_folio'],
            'organization_id' => $submissionStatus->organization_id,
            'work_center_id' => $submissionStatus->work_center_id,
            'evaluation_type' => 'referencia_i', // Explicit type
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'evaluee_name' => $evalueeNameToSave, // Name ONLY in Ref I
            'demographic_data' => $demographicData,

            // ONLY Referencia I fields
            'referencia_i_answers' => $referenciaIAnswers,
            'citsats_s1' => $citsatsS1, // Context for independent analysis

            // Ref III fields set to NULL (explicit separation)
            'referencia_iii_answers' => null,
            'referencia_iii_conditional' => null,

            // Other fields
            'cisneros_answers' => null,
            'raw_data' => $rawData,
        ]);
    }

    /**
     * Create single instrument record (for reduced quiz or Cisneros)
     */
    protected function createSingleInstrumentRecord(SubmissionStatus $submissionStatus, string $instrumentType): PaperEvaluation
    {
        $dataSnapshot = $submissionStatus->data_snapshot;
        $folio = $submissionStatus->folio;
        $folioComponents = $this->extractFolioComponents($folio);

        // Prepare demographic data
        $demographicData = $dataSnapshot['referencia_v'] ?? [];
        if (isset($dataSnapshot['organization_info'])) {
            $demographicData['organization_info'] = $dataSnapshot['organization_info'];
        }

        // Extract data based on instrument type
        $referenciaIAnswers = null;
        $referenciaIIIAnswers = null;
        $referenciaIIIConditional = null;
        $cisnerosAnswers = null;
        $citsatsS1 = null;
        $evalueeNameToSave = null;

        switch ($instrumentType) {
            case 'referencia_i':
                $referenciaIAnswers = $this->extractReferenciaI($dataSnapshot);
                $citsatsS1 = $this->extractCitsatsS1($dataSnapshot);
                $evalueeNameToSave = $dataSnapshot['evaluee_name'] ?? null;
                break;

            case 'referencia_iii':
                $referenciaIIIAnswers = $this->extractReferenciaIII($dataSnapshot);
                $referenciaIIIConditional = $this->extractConditionals($dataSnapshot);
                $citsatsS1 = $this->extractCitsatsS1($dataSnapshot);
                break;

            case 'cisneros':
                $cisnerosAnswers = $this->extractCisneros($dataSnapshot);
                break;
        }

        // Build raw_data
        $rawData = $this->buildStandardizedRawData($dataSnapshot, $submissionStatus);

        return PaperEvaluation::updateOrCreate(
            ['folio' => $folio, 'source' => 'online'],
            [
                'evaluation_type_code' => $folioComponents['evaluation_type_code'],
                'organization_code' => $folioComponents['organization_code'],
                'work_center_code' => $folioComponents['work_center_code'] ?? '01',
                'personal_folio' => $folioComponents['personal_folio'],
                'organization_id' => $submissionStatus->organization_id,
                'work_center_id' => $submissionStatus->work_center_id,
                'evaluation_type' => $instrumentType,
                'source' => 'online',
                'processing_status' => 'completed',
                'processed_at' => now(),
                'evaluee_name' => $evalueeNameToSave,
                'demographic_data' => $demographicData,
                'referencia_i_answers' => $referenciaIAnswers,
                'referencia_iii_answers' => $referenciaIIIAnswers,
                'referencia_iii_conditional' => $referenciaIIIConditional,
                'citsats_s1' => $citsatsS1,
                'cisneros_answers' => $cisnerosAnswers,
                'raw_data' => $rawData,
            ]
        );
    }

    /**
     * Generate new folio for Referencia I (type 01)
     * Uses same personal_folio as original but with type 01
     * Handles both old (9-digit) and new (11-digit) folio formats WITHOUT dashes
     */
    protected function generateReferenciaIFolio(SubmissionStatus $submissionStatus): string
    {
        $originalFolio = $submissionStatus->folio;
        $folioLength = strlen($originalFolio);

        // Parse original folio
        $originalFolioComponents = $this->extractFolioComponents($originalFolio);

        if ($folioLength === 11) {
            // New format (11 chars): [TT][OO][CC][PPPPP] → TTOOCCPPPPP (no dashes)
            // Example: D0201000015 → 01 02 01 00015 (Type 01 for Ref I)
            $newFolio = sprintf(
                '%s%s%s%s',
                '01', // Type code '01' for Referencia I
                $originalFolioComponents['organization_code'],
                $originalFolioComponents['work_center_code'],
                $originalFolioComponents['personal_folio']
            );
        } elseif ($folioLength === 9) {
            // Legacy format (9 chars): [TT][OOO][PPPP] → TTOOOOPPPP (no dashes)
            // Example: 020010009 → 01 001 0009 (Type 01 for Ref I)
            $newFolio = sprintf(
                '%s%s%s',
                '01', // Type code '01' for Referencia I
                $originalFolioComponents['organization_code'],
                $originalFolioComponents['personal_folio']
            );
        } else {
            throw new \InvalidArgumentException("Cannot generate Ref I folio from invalid folio: {$originalFolio}");
        }

        return $newFolio;
    }

    /**
     * Check if submission has Referencia III data
     */
    protected function hasReferenciaIII(array $dataSnapshot): bool
    {
        return isset($dataSnapshot['referencia_iii']) &&
               ! empty($dataSnapshot['referencia_iii']);
    }

    /**
     * Check if submission has Referencia I data
     * For complete quizzes (Ref III + Ref I), Ref I record should exist even if ATS are all "No"
     */
    protected function hasReferenciaI(array $dataSnapshot): bool
    {
        if (! isset($dataSnapshot['referencia_i']) || empty($dataSnapshot['referencia_i'])) {
            return false;
        }

        // If this is a complete quiz (has Ref III), create Ref I record whenever Ref I block exists.
        // This guarantees same folio coverage between Ref III and Ref I.
        if (isset($dataSnapshot['referencia_iii']) && ! empty($dataSnapshot['referencia_iii'])) {
            return true;
        }

        // For reduced quizzes (no Ref III), allow Ref I without traumatic events check
        return true;
    }

    /**
     * Check if submission has Cisneros data
     */
    protected function hasCisneros(array $dataSnapshot): bool
    {
        return isset($dataSnapshot['escala_cisneros']) &&
               ! empty($dataSnapshot['escala_cisneros']);
    }

    /**
     * Build raw_data specifically for Referencia III
     * Includes all submission data for audit trail (not just Ref III)
     */
    protected function buildRawDataForReferenciaIII(array $dataSnapshot, SubmissionStatus $submissionStatus): array
    {
        $quiz = $submissionStatus->quiz;
        $organization = $submissionStatus->organization;
        $userOrgInfo = $dataSnapshot['organization_info'] ?? [];

        return [
            'source' => 'online',
            'source_metadata' => [
                'quiz_id' => $quiz?->id,
                'quiz_name' => $quiz?->name,
                'quiz_type' => 'normal',
                'instrument' => 'referencia_iii',
                'submitted_at' => $submissionStatus->created_at->toIso8601String(),
                'submission_ip' => $dataSnapshot['submission_ip'] ?? null,
                'user_agent' => $dataSnapshot['user_agent'] ?? null,
                'organization_info' => [
                    'nombre_comercial' => $userOrgInfo['nombre_comercial'] ?? $organization?->nombre_comercial,
                    'division_sucursal' => $userOrgInfo['division_sucursal'] ?? $organization?->division_sucursal,
                    'estado' => $userOrgInfo['estado'] ?? $organization?->estado,
                    'ciudad' => $userOrgInfo['ciudad'] ?? $organization?->ciudad,
                ],
            ],
            'custom_fields' => $dataSnapshot['custom_fields'] ?? [],
            'file_uploads' => $this->extractFileUploads($dataSnapshot),
            // Include ALL sections for audit trail (complete snapshot)
            'referencia_i' => $dataSnapshot['referencia_i'] ?? null,
            'referencia_iii' => $dataSnapshot['referencia_iii'] ?? null,
            'referencia_v' => $dataSnapshot['referencia_v'] ?? null,
        ];
    }

    /**
     * Build raw_data specifically for Referencia I
     * Includes all submission data for audit trail (not just Ref I)
     */
    protected function buildRawDataForReferenciaI(array $dataSnapshot, SubmissionStatus $submissionStatus): array
    {
        $quiz = $submissionStatus->quiz;
        $organization = $submissionStatus->organization;
        $userOrgInfo = $dataSnapshot['organization_info'] ?? [];

        return [
            'source' => 'online',
            'source_metadata' => [
                'quiz_id' => $quiz?->id,
                'quiz_name' => $quiz?->name,
                'quiz_type' => 'normal',
                'instrument' => 'referencia_i',
                'submitted_at' => $submissionStatus->created_at->toIso8601String(),
                'submission_ip' => $dataSnapshot['submission_ip'] ?? null,
                'user_agent' => $dataSnapshot['user_agent'] ?? null,
                'organization_info' => [
                    'nombre_comercial' => $userOrgInfo['nombre_comercial'] ?? $organization?->nombre_comercial,
                    'division_sucursal' => $userOrgInfo['division_sucursal'] ?? $organization?->division_sucursal,
                    'estado' => $userOrgInfo['estado'] ?? $organization?->estado,
                    'ciudad' => $userOrgInfo['ciudad'] ?? $organization?->ciudad,
                ],
            ],
            'custom_fields' => $dataSnapshot['custom_fields'] ?? [],
            'file_uploads' => $this->extractFileUploads($dataSnapshot),
            // Include ALL sections for audit trail (complete snapshot)
            'referencia_i' => $dataSnapshot['referencia_i'] ?? null,
            'referencia_iii' => $dataSnapshot['referencia_iii'] ?? null,
            'referencia_v' => $dataSnapshot['referencia_v'] ?? null,
        ];
    }

    /**
     * Send completion notification to organization users
     */
    protected function sendCompletionNotification(
        SubmissionStatus $submissionStatus,
        PaperEvaluation $paperEvaluation
    ): void {
        try {
            // Get users who should receive notifications
            // 1. If work_center_id exists, notify work center users and system admins
            // 2. Otherwise, notify organization users
            $users = collect();

            if ($submissionStatus->work_center_id) {
                // Get work center users and system admins (admin/super-admin)
                $users = User::where(function ($query) use ($submissionStatus) {
                    $query->whereHas('workCenters', function ($q) use ($submissionStatus) {
                        $q->where('work_centers.id', $submissionStatus->work_center_id);
                    })
                        ->orWhereHas('roles', function ($r) {
                            $r->whereIn('name', ['admin', 'super-admin']);
                        });
                })->get();
            } elseif ($submissionStatus->organization_id) {
                // Get organization users
                $users = User::where('organization_id', $submissionStatus->organization_id)
                    ->get();
            }

            if ($users->isEmpty()) {
                return;
            }

            // Send notification
            Notification::send($users, new EvaluationCompletedNotification(
                folio: $submissionStatus->folio,
                personalId: $submissionStatus->personal_id,
                organizationId: $submissionStatus->organization_id,
                workCenterId: $submissionStatus->work_center_id,
                organizationName: $submissionStatus->organization?->name,
                workCenterName: $submissionStatus->workCenter?->name
            ));

        } catch (\Exception $e) {
            // Don't fail the job if notification fails
            Log::error('Error sending completion notification', [
                'submission_id' => $submissionStatus->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
