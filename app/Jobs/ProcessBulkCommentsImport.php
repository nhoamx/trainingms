<?php

namespace App\Jobs;

use App\Events\BulkImportProgress;
use App\Imports\EvaluationBulkCommentsImport;
use App\Models\BulkImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessBulkCommentsImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes for large imports

    public int $tries = 1; // Don't retry on failure

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BulkImportJob $bulkImportJob
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->bulkImportJob->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info('Starting bulk comments import job', [
                'bulk_import_job_id' => $this->bulkImportJob->id,
                'organization_id' => $this->bulkImportJob->organization_id,
                'file_path' => $this->bulkImportJob->file_path,
            ]);

            // Broadcast initial progress
            $this->broadcastProgress();

            // Create import with progress callback
            $import = new EvaluationBulkCommentsImport(
                $this->bulkImportJob->organization_id,
                function ($processedRows, $totalRows, $updatedCount, $skippedCount) {
                    $this->updateProgress($processedRows, $totalRows, $updatedCount, $skippedCount);
                }
            );

            // Get file path from storage
            $filePath = Storage::disk('local')->path($this->bulkImportJob->file_path);

            // Run import
            Excel::import($import, $filePath);

            // Update final stats
            $this->bulkImportJob->update([
                'status' => 'completed',
                'updated_count' => $import->getUpdatedCount(),
                'skipped_count' => $import->getSkippedCount(),
                'errors' => $import->getErrors(),
                'completed_at' => now(),
            ]);

            Log::info('Bulk comments import job completed', [
                'bulk_import_job_id' => $this->bulkImportJob->id,
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'errors_count' => count($import->getErrors()),
            ]);

            // Broadcast final progress
            $this->broadcastProgress();

            // Clean up the temporary file
            Storage::disk('local')->delete($this->bulkImportJob->file_path);

        } catch (\Throwable $e) {
            Log::error('Bulk comments import job failed', [
                'bulk_import_job_id' => $this->bulkImportJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->bulkImportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            // Broadcast failure
            $this->broadcastProgress();

            throw $e;
        }
    }

    /**
     * Update progress in database and broadcast
     */
    protected function updateProgress(int $processedRows, int $totalRows, int $updatedCount, int $skippedCount): void
    {
        $this->bulkImportJob->update([
            'processed_rows' => $processedRows,
            'total_rows' => $totalRows,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
        ]);

        $this->broadcastProgress();
    }

    /**
     * Broadcast progress event
     */
    protected function broadcastProgress(): void
    {
        broadcast(new BulkImportProgress($this->bulkImportJob))->toOthers();
    }
}
