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
        $this->onQueue('quiz_processing');
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

            Log::info('Processing online evaluation', [
                'submission_id' => $submissionStatus->id,
                'folio' => $submissionStatus->folio,
                'organization_id' => $submissionStatus->organization_id,
            ]);

            // 2. Mark as processing
            $submissionStatus->markAsProcessing();

            // 3. Create PaperEvaluation within a transaction
            DB::beginTransaction();

            $paperEvaluation = $this->createPaperEvaluation($submissionStatus);

            // 4. Create DemographicData using service if referencia_v exists
            if (isset($submissionStatus->data_snapshot['referencia_v']) &&
                ! empty($submissionStatus->data_snapshot['referencia_v'])) {
                $this->createDemographicData(
                    $paperEvaluation,
                    $demographicService,
                    $submissionStatus->data_snapshot
                );
            }

            DB::commit();

            // 5. Mark as completed
            $submissionStatus->markAsCompleted();

            Log::info('Online evaluation processed successfully', [
                'submission_id' => $submissionStatus->id,
                'folio' => $submissionStatus->folio,
                'paper_evaluation_id' => $paperEvaluation->id,
            ]);

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

                Log::info('Online evaluation will be retried', [
                    'submission_id' => $this->submissionStatusId,
                    'retry_count' => $submissionStatus->retry_count,
                    'delay_seconds' => $delay,
                ]);
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
     */
    protected function createPaperEvaluation(SubmissionStatus $submissionStatus): PaperEvaluation
    {
        $dataSnapshot = $submissionStatus->data_snapshot;

        // Extract folio components
        $folioComponents = $this->extractFolioComponents($submissionStatus->folio);

        // Build standardized raw_data
        $rawData = $this->buildStandardizedRawData($dataSnapshot, $submissionStatus);

        // Extract answers from data_snapshot
        $referenciaIAnswers = $this->extractReferenciaI($dataSnapshot);
        $referenciaIIIAnswers = $this->extractReferenciaIII($dataSnapshot);
        $referenciaIIIConditional = $this->extractConditionals($dataSnapshot);
        $cisnerosAnswers = $this->extractCisneros($dataSnapshot);
        $citsatsS1 = $this->extractCitsatsS1($dataSnapshot);

        // Fusionar organization_info con demographic_data (referencia_v)
        $demographicData = $dataSnapshot['referencia_v'] ?? [];
        if (isset($dataSnapshot['organization_info'])) {
            $demographicData['organization_info'] = $dataSnapshot['organization_info'];
        }

        // Create PaperEvaluation
        $paperEvaluation = PaperEvaluation::create([
            'folio' => $submissionStatus->folio,
            'evaluation_type_code' => $folioComponents['evaluation_type_code'],
            'organization_code' => $folioComponents['organization_code'],
            'personal_folio' => $folioComponents['personal_folio'],
            'organization_id' => $submissionStatus->organization_id,
            'work_center_id' => $submissionStatus->work_center_id,
            'evaluation_type' => $folioComponents['evaluation_type'],
            'source' => 'online',
            'processing_status' => 'completed',
            'processed_at' => now(),
            'demographic_data' => $demographicData,
            'referencia_i_answers' => $referenciaIAnswers,
            'referencia_iii_answers' => $referenciaIIIAnswers,
            'referencia_iii_conditional' => $referenciaIIIConditional,
            'citsats_s1' => $citsatsS1,
            'cisneros_answers' => $cisnerosAnswers,
            'raw_data' => $rawData,
        ]);

        Log::info('PaperEvaluation created from online submission', [
            'paper_evaluation_id' => $paperEvaluation->id,
            'folio' => $paperEvaluation->folio,
            'evaluation_type' => $paperEvaluation->evaluation_type,
        ]);

        return $paperEvaluation;
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
                Log::info('No referencia_v data found, skipping demographic data creation');

                return;
            }

            $demographicData = $demographicService->updateOrCreate($paperEvaluation, $referenciaV);

            Log::info('Demographic data created successfully', [
                'paper_evaluation_id' => $paperEvaluation->id,
                'demographic_data_id' => $demographicData->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating demographic data', [
                'paper_evaluation_id' => $paperEvaluation->id,
                'error' => $e->getMessage(),
            ]);

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

        // Filter only numeric keys (1-13)
        $answers = [];
        for ($i = 1; $i <= 13; $i++) {
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

        return $dataSnapshot['escala_cisneros'];
    }

    /**
     * Extract CITSATS S1 (Acontecimientos Traumáticos) from Referencia III
     * Now expects ats_s1 with indices 1-6
     */
    protected function extractCitsatsS1(array $dataSnapshot): ?array
    {
        if (! isset($dataSnapshot['referencia_iii']['ats_s1']) || empty($dataSnapshot['referencia_iii']['ats_s1'])) {
            return null;
        }

        $atsS1 = $dataSnapshot['referencia_iii']['ats_s1'];

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
                'personal_folio' => $parsed['personal_folio'],
                'evaluation_type' => $parsed['evaluation_type'],
            ];
        } catch (\Exception $e) {
            Log::error('Error parsing folio', [
                'folio' => $folio,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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
                Log::info('No users to notify for evaluation completion', [
                    'submission_id' => $submissionStatus->id,
                ]);

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

            Log::info('Completion notification sent', [
                'submission_id' => $submissionStatus->id,
                'folio' => $submissionStatus->folio,
                'users_notified' => $users->count(),
            ]);

        } catch (\Exception $e) {
            // Don't fail the job if notification fails
            Log::error('Error sending completion notification', [
                'submission_id' => $submissionStatus->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
