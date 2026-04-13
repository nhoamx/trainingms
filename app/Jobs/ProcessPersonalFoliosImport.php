<?php

namespace App\Jobs;

use App\Events\BulkImportProgress;
use App\Imports\WorkCenterPersonalFoliosImport;
use App\Models\BulkImportJob;
use App\Services\OrganizationReportCacheService;
use App\Support\BatchModeContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessPersonalFoliosImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public BulkImportJob $bulkImportJob,
        protected OrganizationReportCacheService $cacheService = new OrganizationReportCacheService,
    ) {}

    public function handle(): void
    {
        $organizationId = $this->bulkImportJob->organization_id;

        try {
            $this->bulkImportJob->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            $this->broadcastProgress();

            BatchModeContext::enableForOrganization($organizationId);

            $import = new WorkCenterPersonalFoliosImport(
                organizationId: $organizationId,
                workCenterId: $this->bulkImportJob->work_center_id,
                progressCallback: function (int $processedRows, int $totalRows, int $updatedCount, int $skippedCount): void {
                    $this->updateProgress($processedRows, $totalRows, $updatedCount, $skippedCount);
                },
            );

            $filePath = Storage::disk('local')->path($this->bulkImportJob->file_path);
            Excel::import($import, $filePath);

            BatchModeContext::disableForOrganization($organizationId);

            $this->bulkImportJob->update([
                'status' => 'completed',
                'updated_count' => $import->getSummary()['updated'],
                'skipped_count' => $import->getSummary()['skipped'],
                'errors' => $import->getSummary()['errors'],
                'completed_at' => now(),
            ]);

            $this->cacheService->forgetOrganizationCaches($organizationId, warmCache: true);
            $this->broadcastProgress();
            Storage::disk('local')->delete($this->bulkImportJob->file_path);
        } catch (\Throwable $e) {
            BatchModeContext::disableForOrganization($organizationId);

            Log::error('Personal folios import job failed', [
                'bulk_import_job_id' => $this->bulkImportJob->id,
                'error' => $e->getMessage(),
            ]);

            $this->bulkImportJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $this->broadcastProgress();

            if (Storage::disk('local')->exists($this->bulkImportJob->file_path)) {
                Storage::disk('local')->delete($this->bulkImportJob->file_path);
            }

            throw $e;
        }
    }

    protected function updateProgress(int $processedRows, int $totalRows, int $updatedCount, int $skippedCount): void
    {
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

    protected function broadcastProgress(): void
    {
        $this->bulkImportJob->refresh();
        broadcast(new BulkImportProgress($this->bulkImportJob))->toOthers();
    }
}
