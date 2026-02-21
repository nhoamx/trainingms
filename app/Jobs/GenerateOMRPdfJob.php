<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\PdfGenerationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Throwable;

class GenerateOMRPdfJob implements ShouldQueue
{
    // TODO: Use  Ghostscript to merge pdfs
    use Queueable;

    public $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
        public string $guideType,
        public Organization $organization,
        public array $foliosToGenerate,
        public array $viewData,
        public int $batchNumber = 1,
        public int $totalBatches = 1,
        public ?string $pdfJobId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();
        $totalFolios = count($this->foliosToGenerate);

        // Load tracking job if provided
        $pdfJob = $this->pdfJobId ? PdfGenerationJob::find($this->pdfJobId) : null;

        // Mark as started (only first batch should do this)
        if ($pdfJob && $this->batchNumber === 1) {
            $pdfJob->markAsStarted();
        }

        Log::info('=== INICIANDO GENERACIÓN DE PDFs OMR ===', [
            'organization' => $this->organization->name,
            'guide_type' => $this->guideType,
            'total_folios' => $totalFolios,
            'batch_number' => $this->batchNumber,
            'total_batches' => $this->totalBatches,
            'pdf_job_id' => $this->pdfJobId,
            'start_time' => $startTime->toDateTimeString(),
        ]);

        // Get configuration values
        $chunkSize = config('omr.pdf_generation.chunk_size', 100);
        $memoryLimit = config('omr.pdf_generation.memory_limit', 512).'M';
        $executionTime = config('omr.pdf_generation.execution_time', 1800);

        // Increase memory limit and execution time
        ini_set('memory_limit', $memoryLimit);
        ini_set('max_execution_time', (string) $executionTime);
        set_time_limit($executionTime);

        // Split folios into chunks to avoid memory issues
        $chunks = array_chunk($this->foliosToGenerate, $chunkSize);
        $timestamp = date('Y-m-d_H-i-s');

        Log::info('Dividiendo en chunks', [
            'total_chunks' => count($chunks),
            'pages_per_chunk' => $chunkSize,
            'configured_chunk_size' => $chunkSize,
        ]);

        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkStartTime = now();
            $chunkNumber = $chunkIndex + 1;
            $totalChunks = count($chunks);

            Log::info("→ Procesando chunk {$chunkNumber}/{$totalChunks}", [
                'chunk_folios' => count($chunk),
                'chunk_start' => $chunkStartTime->toDateTimeString(),
            ]);

            // Generate HTML content for this chunk only
            $htmlContent = '';
            foreach ($chunk as $index => $personFolio) {
                $extendedFolio = $this->generateExtendedFolio(
                    $this->guideType,
                    $this->organization->folio_organization ?? 0,
                    $personFolio
                );

                $pageData = array_merge($this->viewData, ['folio' => $extendedFolio]);
                $htmlContent .= view("omr.{$this->guideType}", $pageData)->render();

                if ($index < count($chunk) - 1) {
                    $htmlContent .= '<div style="page-break-after: always;"></div>';
                }

                // Free memory every 50 pages
                if (($index + 1) % 50 === 0) {
                    gc_collect_cycles();
                }
            }

            Log::info("  HTML generado para chunk {$chunkNumber}", [
                'html_size_kb' => round(strlen($htmlContent) / 1024, 2),
            ]);

            // Generate PDF for this chunk
            // Get folio range for this chunk
            $firstFolio = $chunk[0];
            $lastFolio = $chunk[count($chunk) - 1];

            // Format: {empresa}-folio-del-{folio inicial}-al-{folio final}-{fecha}
            $filename = $this->organization->name.
                       '-folio-del-'.$firstFolio.
                       '-al-'.$lastFolio.
                       ($totalChunks > 1 ? '-parte-'.$chunkNumber.'-de-'.$totalChunks : '').
                       '-'.$timestamp.'.pdf';

            $storagePath = 'public/pdfs/'.$filename;
            $fullPath = storage_path('app/'.$storagePath);

            // Create directory if it doesn't exist
            if (! file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }

            Log::info("  Generando PDF para chunk {$chunkNumber}...");

            // Get Browsershot configuration
            $browsershotTimeout = config('omr.pdf_generation.browsershot_timeout', 300);
            $scaleFactor = config('omr.pdf_generation.scale_factor', 0.96);

            // Configure Browsershot with optimized settings for chunks
            $browsershot = Browsershot::html($htmlContent)
                ->noSandbox()
                ->format('Letter')
                ->margins(0, 0, 0, 0)
                ->scale($scaleFactor)
                ->showBackground()
                ->timeout($browsershotTimeout)
                ->setOption('args', ['--disable-dev-shm-usage', '--no-sandbox'])
                ->waitUntilNetworkIdle();

            // Configure for WSL if needed
            if (PHP_OS_FAMILY === 'Linux' && file_exists('/mnt/c/Program Files/nodejs/node.exe')) {
                $browsershot->setNodeBinary('/mnt/c/Program Files/nodejs/node.exe');
                $browsershot->setNpmBinary('/mnt/c/Program Files/nodejs/npm');
            }

            $browsershot->save($fullPath);

            $chunkEndTime = now();
            $chunkDuration = $chunkStartTime->diffInSeconds($chunkEndTime);
            $fileSize = file_exists($fullPath) ? round(filesize($fullPath) / 1024 / 1024, 2) : 0;

            Log::info("✓ Chunk {$chunkNumber}/{$totalChunks} completado", [
                'filename' => $filename,
                'file_size_mb' => $fileSize,
                'duration_seconds' => $chunkDuration,
                'pages' => count($chunk),
            ]);

            // Update tracking job if provided
            if ($pdfJob) {
                $pdfJob->incrementProcessed(count($chunk));
                $pdfJob->addFilePath($storagePath);
            }

            // Clear memory after each chunk
            unset($htmlContent);
            gc_collect_cycles();
        }

        $endTime = now();
        $totalDuration = $startTime->diffInSeconds($endTime);

        Log::info('=== GENERACIÓN DE PDFs COMPLETADA ===', [
            'organization' => $this->organization->name,
            'total_chunks_generated' => count($chunks),
            'total_folios' => $totalFolios,
            'total_duration_seconds' => $totalDuration,
            'total_duration_minutes' => round($totalDuration / 60, 2),
            'avg_seconds_per_chunk' => round($totalDuration / count($chunks), 2),
            'end_time' => $endTime->toDateTimeString(),
        ]);

        // Mark tracking job as completed (only last batch should do this)
        if ($pdfJob && $this->batchNumber === $this->totalBatches) {
            $pdfJob->markAsCompleted();
        }
    }

    /**
     * Handle job failure
     */
    public function failed(?Throwable $exception): void
    {
        if ($this->pdfJobId) {
            $pdfJob = PdfGenerationJob::find($this->pdfJobId);
            if ($pdfJob) {
                $pdfJob->markAsFailed($exception ? $exception->getMessage() : 'Error desconocido');
            }
        }

        Log::error('=== ERROR EN GENERACIÓN DE PDFs OMR ===', [
            'organization' => $this->organization->name,
            'batch_number' => $this->batchNumber,
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }

    private const TEMPLATE_TYPES = [
        'referencia-i' => '01',
        'referencia-iii' => '02',
        'referencia-v' => '03',
        'escala-cisneros' => '04',
        'likert' => '05',
    ];

    private function generateExtendedFolio(string $templateType, int $organizationFolio, string $personFolio): string
    {
        $typeCode = self::TEMPLATE_TYPES[$templateType] ?? '00';
        $orgCode = str_pad((string) $organizationFolio, 3, '0', STR_PAD_LEFT);

        if ($templateType === 'referencia-i') {
            $personCode = '';
        } else {
            $personCode = str_pad($personFolio, 4, '0', STR_PAD_LEFT);
        }

        return $typeCode.$orgCode.$personCode;
    }
}
