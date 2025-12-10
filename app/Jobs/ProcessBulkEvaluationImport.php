<?php

namespace App\Jobs;

use App\Events\BulkImportProgress;
use App\Imports\EvaluationBulkUpdateImport;
use App\Models\BulkImportJob;
use App\Services\OrganizationReportCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessBulkEvaluationImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes for large imports

    public int $tries = 1; // Don't retry on failure

    /**
     * Create a new job instance.
     *
     * Important: This job must invalidate organization report caches after importing evaluations.
     * Since Excel::import() performs bulk updates that bypass Eloquent Observers, we manually
     * invalidate the caches to ensure next page loads show fresh data (not stale cached results).
     */
    public function __construct(
        public BulkImportJob $bulkImportJob,
        protected OrganizationReportCacheService $cacheService = new OrganizationReportCacheService
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

            Log::info('Starting bulk import job', [
                'bulk_import_job_id' => $this->bulkImportJob->id,
                'organization_id' => $this->bulkImportJob->organization_id,
                'file_path' => $this->bulkImportJob->file_path,
            ]);

            // Broadcast initial progress
            $this->broadcastProgress();

            // Create import with progress callback
            $import = new EvaluationBulkUpdateImport(
                $this->bulkImportJob->organization_id,
                $this->bulkImportJob->source,
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

            Log::info('Bulk import job completed', [
                'bulk_import_job_id' => $this->bulkImportJob->id,
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'errors_count' => count($import->getErrors()),
            ]);

            // Invalidate organization caches so next page load shows fresh data
            $this->cacheService->forgetOrganizationCaches($this->bulkImportJob->organization_id);

            // Broadcast final progress
            $this->broadcastProgress();

            // Clean up the temporary file
            Storage::disk('local')->delete($this->bulkImportJob->file_path);

        } catch (\Throwable $e) {
            Log::error('Bulk import job failed', [
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

            // Clean up the temporary file even on failure
            if (Storage::disk('local')->exists($this->bulkImportJob->file_path)) {
                Storage::disk('local')->delete($this->bulkImportJob->file_path);
            }

            throw $e;
        }
    }

    /**
     * Update progress in database and broadcast
     */
    protected function updateProgress(int $processedRows, int $totalRows, int $updatedCount, int $skippedCount): void
    {
        // Only update every 10 rows to reduce database writes
        if ($processedRows % 10 === 0 || $processedRows === $totalRows) {
            $this->bulkImportJob->update([
                'processed_rows' => $processedRows,
                'total_rows' => $totalRows,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
            ]);

            $this->broadcastProgress();
        }
    }

    /**
     * Broadcast progress event
     */
    protected function broadcastProgress(): void
    {
        // Refresh to get latest data
        $this->bulkImportJob->refresh();

        broadcast(new BulkImportProgress($this->bulkImportJob))->toOthers();
    }
}
