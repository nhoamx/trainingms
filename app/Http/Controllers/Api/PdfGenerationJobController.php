<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PdfGenerationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class PdfGenerationJobController extends Controller
{
    /**
     * Display the status of a PDF generation job
     */
    public function show(PdfGenerationJob $pdfJob): JsonResponse
    {
        return response()->json([
            'id' => $pdfJob->id,
            'status' => $pdfJob->status,
            'progress_percentage' => $pdfJob->getProgressPercentage(),
            'processed' => $pdfJob->processed_folios,
            'total' => $pdfJob->total_folios,
            'files' => $pdfJob->status === 'completed' ? $pdfJob->file_paths : null,
            'error_message' => $pdfJob->error_message,
            'started_at' => $pdfJob->started_at?->toIso8601String(),
            'completed_at' => $pdfJob->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * Download the generated PDF files
     */
    public function download(PdfGenerationJob $pdfJob): BinaryFileResponse
    {
        if ($pdfJob->status !== 'completed') {
            abort(400, 'La generación de PDF aún no ha terminado');
        }

        if (empty($pdfJob->file_paths)) {
            abort(404, 'No se encontraron archivos generados');
        }

        // Si hay múltiples archivos, crear ZIP
        if (count($pdfJob->file_paths) > 1) {
            return $this->downloadAsZip($pdfJob);
        }

        // Si es un solo archivo, descarga directa
        $relativePath = $pdfJob->file_paths[0];
        $fullPath = storage_path('app/'.$relativePath);

        if (! file_exists($fullPath)) {
            abort(404, 'El archivo no existe');
        }

        return response()->download($fullPath, $this->buildBatchDownloadFilename($pdfJob));
    }

    /**
     * Download multiple files as a ZIP archive
     */
    private function downloadAsZip(PdfGenerationJob $pdfJob): BinaryFileResponse
    {
        $zipName = str_replace('.pdf', '.zip', $this->buildBatchDownloadFilename($pdfJob));
        $zipPath = storage_path('app/temp/'.$zipName);

        // Crear directorio temporal si no existe
        if (! file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        // Crear ZIP
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo crear el archivo ZIP');
        }

        // Agregar cada PDF al ZIP
        foreach ($pdfJob->file_paths as $relativePath) {
            $fullPath = storage_path('app/'.$relativePath);

            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, basename($fullPath));
            }
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function buildBatchDownloadFilename(PdfGenerationJob $pdfJob): string
    {
        $pdfJob->loadMissing(['organization', 'folioBatch.workCenter']);

        $organizationSegment = $this->toAsciiSlug($pdfJob->organization?->name ?? 'organizacion', 'organizacion');
        $workCenterSegment = $this->toAsciiSlug(
            $pdfJob->folioBatch?->workCenter?->name
            ?? $pdfJob->folioBatch?->workCenter?->code
            ?? 'centro-trabajo',
            'centro-trabajo'
        );

        $start = (string) ($pdfJob->folioBatch?->start_number ?? 1);
        $end = (string) ($pdfJob->folioBatch?->end_number ?? $start);

        return $organizationSegment.'-'.$workCenterSegment.'-'.$start.'-'.$end.'.pdf';
    }

    private function toAsciiSlug(string $value, string $fallback): string
    {
        $slug = Str::slug($value);

        return $slug !== '' ? $slug : $fallback;
    }
}
