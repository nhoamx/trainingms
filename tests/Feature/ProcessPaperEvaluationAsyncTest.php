<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaperEvaluationAsync;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ProcessPaperEvaluationAsyncTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Keep table clean to avoid test data collisions.
     */
    protected function setUp(): void
    {
        parent::setUp();
        PaperEvaluation::query()->delete();
    }

    public function test_async_job_submits_and_polls_until_finished(): void
    {
        Organization::factory()->create([
            'folio_organization' => '953',
        ]);

        config()->set('services.ocr.url', 'http://ocr.local');
        config()->set('services.ocr.instrument', 'gri');
        config()->set('services.ocr.poll_interval_ms', 1);
        config()->set('services.ocr.poll_max_wait_sec', 5);

        $tmpFile = tempnam(sys_get_temp_dir(), 'async_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        Http::fake([
            'http://ocr.local/jobs' => Http::response([
                'job_id' => 'job-123',
                'status' => 'queued',
            ], 202),
            'http://ocr.local/jobs/job-123' => Http::sequence()
                ->push(['job_id' => 'job-123', 'status' => 'started'], 200)
                ->push([
                    'job_id' => 'job-123',
                    'status' => 'finished',
                    'result' => [
                        'result' => [
                            'pages' => [
                                [
                                    'status' => 'completed',
                                    'folio' => '019530001',
                                    'answers' => ['1' => 'SI', '2' => 'NO'],
                                ],
                            ],
                        ],
                    ],
                ], 200),
        ]);

        Event::fake();

        $job = new ProcessPaperEvaluationAsync(
            $tmpFile,
            null,
            null,
            1,
            1,
            'async-test.pdf'
        );

        $job->handle();

        $this->assertDatabaseHas('paper_evaluations', [
            'folio' => '019530001',
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        @unlink($tmpFile);
    }

    public function test_async_job_marks_failed_without_throwing_for_terminal_submit_error(): void
    {
        config()->set('services.ocr.url', 'http://ocr.local');
        config()->set('services.ocr.instrument', 'gri');

        $tmpFile = tempnam(sys_get_temp_dir(), 'async_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        Http::fake([
            'http://ocr.local/jobs' => Http::response([
                'error' => 'invalid_instrument',
                'detail' => 'Instrumento no soportado.',
            ], 400),
        ]);

        Event::fake();

        $job = (new ProcessPaperEvaluationAsync(
            $tmpFile,
            null,
            null,
            1,
            1,
            'async-fail.pdf'
        ))->withFakeQueueInteractions();

        try {
            $job->handle();
            $job->assertFailed();
        } finally {
            @unlink($tmpFile);
        }
    }

    public function test_async_job_throws_for_retryable_submit_error(): void
    {
        config()->set('services.ocr.url', 'http://ocr.local');
        config()->set('services.ocr.instrument', 'gri');

        $tmpFile = tempnam(sys_get_temp_dir(), 'async_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        Http::fake([
            'http://ocr.local/jobs' => Http::response([
                'error' => 'queue_unavailable',
                'detail' => 'Redis offline',
            ], 503),
        ]);

        Event::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/OCR async submit error/');

        try {
            (new ProcessPaperEvaluationAsync(
                $tmpFile,
                null,
                null,
                1,
                1,
                'async-retryable-fail.pdf'
            ))->handle();
        } finally {
            @unlink($tmpFile);
        }
    }

    public function test_async_job_is_batchable_for_bus_batch_dispatch(): void
    {
        $traits = class_uses_recursive(ProcessPaperEvaluationAsync::class);

        $this->assertContains('Illuminate\\Bus\\Batchable', $traits);
    }
}
