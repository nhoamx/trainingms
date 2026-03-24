<?php

namespace App\Jobs;

use App\Exceptions\TerminalOcrException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessPaperEvaluationAsync extends ProcessPaperEvaluation
{
    public int $tries = 3;

    public function handle(): void
    {
        try {
            parent::handle();
        } catch (TerminalOcrException $exception) {
            Log::warning('ProcessPaperEvaluationAsync terminal error', [
                'file' => $this->fullPath,
                'message' => $exception->getMessage(),
            ]);

            $this->cleanupFiles();
            $this->fail($exception);
        }
    }

    /**
     * The number of seconds to wait before retrying after failure.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        $backoff = config('services.ocr.job_backoff_seconds', [5, 15, 30]);

        if (is_array($backoff) && ! empty($backoff)) {
            return array_map(static fn (mixed $value): int => (int) $value, $backoff);
        }

        return [5, 15, 30];
    }

    /**
     * Send PDF to async OCR API and poll status until completion.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function callOcrService(): array
    {
        $serviceUrl = rtrim((string) config('services.ocr.url'), '/');
        $timeout = (int) config('services.ocr.timeout', 300);
        $pollIntervalMs = (int) config('services.ocr.poll_interval_ms', 1500);
        $maxWaitSeconds = (int) config('services.ocr.poll_max_wait_sec', 300);
        $instrument = trim((string) ($this->instrument ?? config('services.ocr.instrument', '')));

        if ($instrument === '') {
            throw new TerminalOcrException('OCR async requires services.ocr.instrument (set OCR_INSTRUMENT) or disable OCR_ASYNC_ENABLED.');
        }

        Log::info('Submitting PDF to async OCR service', [
            'file' => $this->fileName,
            'url' => $serviceUrl,
            'instrument' => $instrument,
        ]);

        $submitResponse = Http::timeout($timeout)
            ->attach('file', file_get_contents($this->fullPath), basename($this->fullPath))
            ->post($serviceUrl.'/jobs', [
                'instrument' => $instrument,
            ]);

        if ($submitResponse->failed()) {
            $this->throwWithOcrError($submitResponse, 'submit');
        }

        $jobId = (string) $submitResponse->json('job_id', '');
        if ($jobId === '') {
            throw new \RuntimeException('OCR async response missing job_id during submit.');
        }

        $statusUrl = $serviceUrl.'/jobs/'.$jobId;
        $startedAt = now()->getTimestamp();

        while (true) {
            $statusResponse = Http::timeout($timeout)->get($statusUrl);

            if ($statusResponse->failed()) {
                $this->throwWithOcrError($statusResponse, 'poll');
            }

            $status = (string) $statusResponse->json('status', '');
            if ($status === 'finished') {
                $results = $this->extractResultsFromAsyncPayload($statusResponse);
                Log::info('Async OCR completed', [
                    'job_id' => $jobId,
                    'results' => count($results),
                    'file' => $this->fileName,
                ]);

                return $results;
            }

            if ($status === 'failed') {
                $detail = (string) $statusResponse->json('detail', $statusResponse->json('error', 'job_failed'));
                $errorCode = (string) $statusResponse->json('error', 'job_failed');

                if ($this->isTerminalErrorCode($errorCode)) {
                    throw new TerminalOcrException("OCR async job failed ({$jobId}): {$detail}");
                }

                throw new \RuntimeException("OCR async job failed ({$jobId}): {$detail}");
            }

            if ((now()->getTimestamp() - $startedAt) >= $maxWaitSeconds) {
                throw new \RuntimeException("OCR async polling timeout ({$jobId}) after {$maxWaitSeconds} seconds.");
            }

            usleep(max($pollIntervalMs, 100) * 1000);
        }
    }

    protected function throwWithOcrError(Response $response, string $stage): never
    {
        $statusCode = $response->status();
        $error = (string) $response->json('error', 'Unknown error');
        $detail = (string) $response->json('detail', '');

        if ($this->isTerminalHttpStatus($statusCode) || $this->isTerminalErrorCode($error)) {
            throw new TerminalOcrException("OCR async {$stage} error ({$statusCode}): {$error}. {$detail}");
        }

        throw new \RuntimeException("OCR async {$stage} error ({$statusCode}): {$error}. {$detail}");
    }

    protected function isTerminalHttpStatus(int $statusCode): bool
    {
        return in_array($statusCode, [400, 404, 413, 415, 422], true);
    }

    protected function isTerminalErrorCode(string $errorCode): bool
    {
        $terminalCodes = [
            'invalid_instrument',
            'missing_file',
            'empty_filename',
            'file_too_large',
            'job_not_found',
            'invalid_payload',
            'unprocessable_pdf',
        ];

        return in_array(strtolower(trim($errorCode)), $terminalCodes, true);
    }

    /**
     * Normalize async OCR payload to the legacy result shape.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractResultsFromAsyncPayload(Response $statusResponse): array
    {
        $pages = $statusResponse->json('result.result.pages', []);
        if (! is_array($pages) || empty($pages)) {
            throw new \RuntimeException('OCR async finished but returned no pages.');
        }

        $results = [];
        foreach ($pages as $page) {
            if (! is_array($page)) {
                continue;
            }

            if (($page['status'] ?? null) !== 'completed') {
                continue;
            }

            $folio = $page['folio'] ?? null;
            $answers = $page['answers'] ?? null;
            if (! is_string($folio) || empty($folio) || ! is_array($answers)) {
                continue;
            }

            $results[] = [
                'folio' => $folio,
                'answers' => $answers,
                'marked_image_base64' => null,
            ];
        }

        if (empty($results)) {
            throw new \RuntimeException('OCR async completed without valid folio results.');
        }

        return $results;
    }
}
