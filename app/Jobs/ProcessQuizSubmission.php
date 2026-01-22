<?php

namespace App\Jobs;

use App\Models\OnlineAnswer;
use App\Models\Quiz;
use App\Models\SubmissionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessQuizSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout

    public $tries = 3; // Allow 3 attempts

    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $submissionStatusId,
        public bool $processImages = true
    ) {
        // Asignar a la cola específica de procesamiento de evaluaciones
        $this->onQueue('quiz_processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $submissionStatus = SubmissionStatus::find($this->submissionStatusId);

        if (! $submissionStatus) {
            Log::error('SubmissionStatus not found', ['id' => $this->submissionStatusId]);

            return;
        }

        $submissionStatus->markAsProcessing();

        try {
            $this->processSubmission($submissionStatus);
            $submissionStatus->markAsCompleted();

        } catch (\Exception $e) {
            Log::error('Quiz submission processing failed', [
                'submission_id' => $this->submissionStatusId,
                'folio' => $submissionStatus->folio,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $submissionStatus->markAsFailed($e->getMessage());

            // If we can retry, dispatch a new job with delay
            if ($submissionStatus->canRetry()) {
                $delay = $submissionStatus->retry_count * 60; // Progressive delay
                self::dispatch($this->submissionStatusId, $this->processImages)
                    ->delay(now()->addSeconds($delay));

            }

            throw $e;
        }
    }

    /**
     * Process the quiz submission data
     */
    private function processSubmission(SubmissionStatus $submissionStatus): void
    {
        $data = $submissionStatus->data_snapshot;

        // Process in chunks to handle large datasets
        // For normal submissions, answers are nested under 'answers'
        // For hybrid submissions, they're at the root level as referencia_iii, referencia_i
        $answers = $data['answers'] ?? [
            'referencia_iii' => $data['referencia_iii'] ?? null,
            'referencia_i' => $data['referencia_i'] ?? null,
            'referencia_v' => $data['referencia_v'] ?? null,
            'escala_cisneros' => $data['escala_cisneros'] ?? null,
            'custom_fields' => $data['custom_fields'] ?? null,
        ];
        $ineImages = $data['ine_images'] ?? [];

        DB::transaction(function () use ($submissionStatus, $answers, $ineImages) {
            // Store online answers using chunked processing
            $this->storeOnlineAnswersChunked(
                $submissionStatus->folio,
                $submissionStatus->personal_id,
                $submissionStatus->organization_id,
                $submissionStatus->quiz_id,
                $answers,
                $ineImages
            );

            // Create virtual folio
            $this->createVirtualFolio($submissionStatus->organization_id, $submissionStatus->folio);
        });

        // Dispatch INE image processing job if needed
        if ($this->processImages && ! empty($ineImages)) {
            ProcessIneImages::dispatch(
                $submissionStatus->folio,
                $submissionStatus->personal_id,
                $ineImages
            )->onQueue('images');
        }
    }

    /**
     * Store online answers using chunked processing for better performance
     */
    private function storeOnlineAnswersChunked(
        string $folio,
        string $personalId,
        string $organizationId,
        ?int $quizId,
        array $answers,
        array $ineImages = []
    ): void {
        $records = [];
        $chunkSize = 500; // Process in chunks of 500 records

        // Process each answer section
        $this->buildAnswerRecords($records, $folio, $personalId, $organizationId, $quizId, $answers, $ineImages);

        // Insert in chunks for better memory management
        $chunks = array_chunk($records, $chunkSize);

        foreach ($chunks as $chunk) {
            OnlineAnswer::insert($chunk);
        }

    }

    /**
     * Build answer records array
     */
    private function buildAnswerRecords(
        array &$records,
        string $folio,
        string $personalId,
        string $organizationId,
        ?int $quizId,
        array $answers,
        array $ineImages
    ): void {
        // Process referencia_iii answers
        if (isset($answers['referencia_iii']) && is_array($answers['referencia_iii'])) {
            foreach ($answers['referencia_iii'] as $key => $value) {
                $records[] = [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'organization_id' => $organizationId,
                    'quiz_id' => $quizId,
                    'question_key' => $key,
                    'answer_value' => $this->formatAnswerValue($value),
                    'reference_guide' => 'III',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Process referencia_i answers
        if (isset($answers['referencia_i']) && is_array($answers['referencia_i'])) {
            foreach ($answers['referencia_i'] as $key => $value) {
                $records[] = [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'organization_id' => $organizationId,
                    'quiz_id' => $quizId,
                    'question_key' => $key,
                    'answer_value' => $this->formatAnswerValue($value),
                    'reference_guide' => 'I',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Process referencia_v answers
        if (isset($answers['referencia_v']) && is_array($answers['referencia_v'])) {
            $this->processNestedAnswers($records, $answers['referencia_v'], $folio, $personalId, $organizationId, $quizId, 'V');
        }

        // Process escala_cisneros answers
        if (isset($answers['escala_cisneros']) && is_array($answers['escala_cisneros'])) {
            foreach ($answers['escala_cisneros'] as $key => $value) {
                $records[] = [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'organization_id' => $organizationId,
                    'quiz_id' => $quizId,
                    'question_key' => $key,
                    'answer_value' => $this->formatAnswerValue($value),
                    'reference_guide' => 'Cisneros',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Process custom fields
        if (isset($answers['custom_fields']) && is_array($answers['custom_fields'])) {
            foreach ($answers['custom_fields'] as $fieldId => $value) {
                $records[] = [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'organization_id' => $organizationId,
                    'quiz_id' => $quizId,
                    'question_key' => "custom_field_{$fieldId}",
                    'answer_value' => $this->formatAnswerValue($value),
                    'reference_guide' => 'V', // Custom fields are part of reference guide V
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Process INE images
        foreach ($ineImages as $imageType => $imagePath) {
            $records[] = [
                'folio' => $folio,
                'personal_id' => $personalId,
                'organization_id' => $organizationId,
                'quiz_id' => $quizId,
                'question_key' => $imageType,
                'answer_value' => $imagePath,
                'reference_guide' => 'V',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }

    /**
     * Process nested answers recursively
     */
    private function processNestedAnswers(
        array &$records,
        array $answers,
        string $folio,
        string $personalId,
        string $organizationId,
        int $quizId,
        string $referenceGuide,
        string $prefix = ''
    ): void {
        foreach ($answers as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->processNestedAnswers($records, $value, $folio, $personalId, $organizationId, $quizId, $referenceGuide, $fullKey);
            } else {
                $records[] = [
                    'folio' => $folio,
                    'personal_id' => $personalId,
                    'organization_id' => $organizationId,
                    'quiz_id' => $quizId,
                    'question_key' => $fullKey,
                    'answer_value' => $this->formatAnswerValue($value),
                    'reference_guide' => $referenceGuide,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
    }

    /**
     * Format answer value for storage
     */
    private function formatAnswerValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * Create virtual folio for tracking
     */
    private function createVirtualFolio(string $organizationId, string $folioNumber): void
    {
        try {
            // Use the same logic as in QuizController but simplified
            $virtualBatch = \App\Models\FolioBatch::firstOrCreate([
                'organization_id' => $organizationId,
                'name' => 'Quiz Virtual Batch',
                'type' => 'en_linea',
            ], [
                'description' => 'Lote virtual para folios generados por quiz',
                'start_number' => 1,
                'end_number' => 9999,
                'quantity' => 9999,
            ]);

            \App\Models\Folio::create([
                'folio_batch_id' => $virtualBatch->id,
                'folio_number' => $folioNumber,
                'numeric_value' => intval($folioNumber),
                'used' => true,
                'used_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to create virtual folio', [
                'organization_id' => $organizationId,
                'folio_number' => $folioNumber,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the entire job for virtual folio creation issues
        }
    }
}
