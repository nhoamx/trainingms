<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanOldFolioImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folios:clean-old-images {--days=7 : Number of days to keep images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete folio images older than specified days (default: 7 days)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $foliosPath = storage_path('app/public/folios');

        if (! File::exists($foliosPath)) {
            $this->warn('Folios directory does not exist: '.$foliosPath);

            return self::SUCCESS;
        }

        try {
            $files = File::files($foliosPath);
            $threshold = Carbon::now()->subDays($days)->timestamp;
            $deletedCount = 0;
            $keptCount = 0;

            $this->info("Checking {$foliosPath} for images older than {$days} days...");

            foreach ($files as $file) {
                $fileTime = $file->getMTime();

                if ($fileTime < $threshold) {
                    $fileName = $file->getFilename();
                    $age = Carbon::createFromTimestamp($fileTime)->diffForHumans();

                    File::delete($file->getPathname());
                    $deletedCount++;

                    $this->line("Deleted: {$fileName} (age: {$age})");
                    Log::info("Deleted old folio image: {$fileName}", ['age' => $age]);
                } else {
                    $keptCount++;
                }
            }

            $this->newLine();
            $this->info('✓ Cleanup completed');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Deleted', $deletedCount],
                    ['Kept', $keptCount],
                    ['Total', count($files)],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Error during cleanup: '.$e->getMessage());
            Log::error('Error cleaning old folio images: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
