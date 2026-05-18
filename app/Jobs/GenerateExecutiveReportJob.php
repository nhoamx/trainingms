<?php

namespace App\Jobs;

use App\Http\Controllers\WorkCenter\ExecutiveReportDownloadController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateExecutiveReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    private string $reportId;

    private string $workCenterId;

    private string $organizationId;

    public function __construct(
        string $reportId,
        string $workCenterId,
        string $organizationId
    ) {
        $this->reportId = $reportId;
        $this->workCenterId = $workCenterId;
        $this->organizationId = $organizationId;
    }

    public function handle(): void
        {
            ini_set('memory_limit', '1024M');
            set_time_limit(0);

            $this->writeStatus([
                'status' => 'processing',
                'report_id' => $this->reportId,
                'work_center_id' => $this->workCenterId,
                'organization_id' => $this->organizationId,
                'started_at' => now()->toDateTimeString(),
            ]);

            try {
                $result = app(ExecutiveReportDownloadController::class)->generateReportFile(
                    $this->workCenterId,
                    $this->organizationId,
                    $this->reportId
                );

                $this->writeStatus(array_merge($result, [
                    'status' => 'ready',
                    'completed_at' => now()->toDateTimeString(),
                ]));
            } catch (Throwable $e) {
                $this->writeStatus([
                    'status' => 'failed',
                    'error_class' => get_class($e),
                    'error_message' => $e->getMessage(),
                    'failed_at' => now()->toDateTimeString(),
                ]);

                throw $e;
            }
        }

    private function statusFilePath(): string
{
    $statusDir = storage_path('app/reports/nom035/status');

    if (! is_dir($statusDir)) {
        mkdir($statusDir, 0755, true);
    }

    return $statusDir . DIRECTORY_SEPARATOR . $this->reportId . '.json';
}

    private function readStatus(): array
    {
        $statusPath = $this->statusFilePath();

        if (! is_file($statusPath)) {
            return [];
        }

        $content = file_get_contents($statusPath);

        if ($content === false || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeStatus(array $payload): void
    {
        $data = array_merge($this->readStatus(), $payload, [
            'updated_at' => now()->toDateTimeString(),
        ]);

        file_put_contents(
            $this->statusFilePath(),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}
