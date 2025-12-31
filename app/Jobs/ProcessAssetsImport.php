<?php

namespace App\Jobs;

use App\Imports\AssetsImport;
use App\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessAssetsImport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $filePath,
        public string $organizationId,
        public ?string $userId = null
    ) {}

    public function handle(): void
    {
        try {
            $organization = Organization::findOrFail($this->organizationId);

            Log::info('Iniciando importación de assets', [
                'organization_id' => $this->organizationId,
                'file' => $this->filePath,
            ]);

            // Progress callback
            $progressCallback = function ($processed, $total, $created, $updated, $skipped) {
                Log::info('Progreso de importación de assets', [
                    'processed' => $processed,
                    'total' => $total,
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                ]);
            };

            $import = new AssetsImport($organization, $progressCallback);

            Excel::import($import, Storage::path($this->filePath));

            Log::info('Importación de assets completada', [
                'created' => $import->getCreatedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'errors' => count($import->getErrors()),
            ]);

            // Limpiar archivo temporal
            Storage::delete($this->filePath);
        } catch (\Exception $e) {
            Log::error('Error en importación de assets', [
                'error' => $e->getMessage(),
                'file' => $this->filePath,
            ]);

            throw $e;
        }
    }
}
