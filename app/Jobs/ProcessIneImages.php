<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessIneImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes for image processing
    public $tries = 3;
    public $queue = 'image_processing'; // Cola específica para procesamiento de imágenes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $folio,
        public string $personalId,
        public array $ineImages
    ) {
        // Asignar a la cola específica de procesamiento de imágenes
        $this->onQueue('image_processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting INE image processing', [
                'folio' => $this->folio,
                'personal_id' => $this->personalId,
                'images' => array_keys($this->ineImages)
            ]);

            foreach ($this->ineImages as $imageType => $imagePath) {
                $this->processImage($imageType, $imagePath);
            }

            Log::info('INE image processing completed successfully', [
                'folio' => $this->folio,
                'personal_id' => $this->personalId
            ]);

        } catch (\Exception $e) {
            Log::error('INE image processing failed', [
                'folio' => $this->folio,
                'personal_id' => $this->personalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Process individual image
     */
    private function processImage(string $imageType, string $imagePath): void
    {
        try {
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::warning('Image file not found', [
                    'image_type' => $imageType,
                    'path' => $imagePath,
                    'folio' => $this->folio
                ]);
                return;
            }

            // Validate file
            $this->validateImageFile($imagePath);
            
            // Create organized directory structure
            $this->organizeImageFile($imagePath, $imageType);

            Log::info('Image processed successfully', [
                'image_type' => $imageType,
                'folio' => $this->folio,
                'original_path' => $imagePath
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process individual image', [
                'image_type' => $imageType,
                'path' => $imagePath,
                'folio' => $this->folio,
                'error' => $e->getMessage()
            ]);
            // Continue processing other images even if one fails
        }
    }

    /**
     * Validate image file
     */
    private function validateImageFile(string $imagePath): void
    {
        $fullPath = Storage::disk('public')->path($imagePath);
        
        // Check file size (max 10MB)
        $fileSize = filesize($fullPath);
        if ($fileSize > 10 * 1024 * 1024) {
            throw new \Exception("Image file is too large: {$fileSize} bytes");
        }

        // Check if it's a valid image
        $imageInfo = getimagesize($fullPath);
        if ($imageInfo === false) {
            throw new \Exception('Invalid image file');
        }

        Log::info('Image validation passed', [
            'path' => $imagePath,
            'size' => $fileSize,
            'dimensions' => $imageInfo[0] . 'x' . $imageInfo[1],
            'type' => $imageInfo['mime']
        ]);
    }

    /**
     * Organize image file into proper directory structure
     */
    private function organizeImageFile(string $imagePath, string $imageType): void
    {
        try {
            // Create organized path: ine_images/YYYY/MM/folio/
            $organizedDir = 'ine_images/' . date('Y') . '/' . date('m') . '/' . $this->folio;
            
            // Ensure directory exists
            if (!Storage::disk('public')->exists($organizedDir)) {
                Storage::disk('public')->makeDirectory($organizedDir);
            }

            // Generate new filename
            $pathInfo = pathinfo($imagePath);
            $newFilename = $this->personalId . '_' . $imageType . '_' . time() . '.' . $pathInfo['extension'];
            $newPath = $organizedDir . '/' . $newFilename;

            // Move file to organized location
            if (Storage::disk('public')->move($imagePath, $newPath)) {
                Log::info('Image file organized', [
                    'original_path' => $imagePath,
                    'new_path' => $newPath,
                    'folio' => $this->folio
                ]);
            } else {
                Log::warning('Failed to move image file', [
                    'original_path' => $imagePath,
                    'new_path' => $newPath
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to organize image file', [
                'path' => $imagePath,
                'error' => $e->getMessage()
            ]);
        }
    }
}
