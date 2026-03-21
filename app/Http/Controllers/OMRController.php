<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOMRPdfRequest;
use App\Jobs\GenerateOMRPdfJob;
use App\Models\FolioBatch;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\PdfGenerationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class OMRController extends Controller
{
    /**
     * Template type codes for extended folio format
     */
    private const TEMPLATE_TYPES = [
        'referencia-i' => '01',
        'referencia-iii' => '02',
        'referencia-v' => '03',
        'escala-cisneros' => '04',
        'likert' => '05',
        'likert-planta-3' => '06',
    ];

    /**
     * Generate extended folio format with work center support.
     *
     * New format (11 digits): [template_type(2)][organization(2)][work_center(2)][person(5)]
     * Legacy format (9 digits): [template_type(2)][organization(3)][person(4)]
     *
     * For Referencia I, person code is left empty (filled manually later).
     */
    private function generateExtendedFolio(string $templateType, int $organizationFolio, string $personFolio, ?string $workCenterCode = null): string
    {
        $typeCode = self::TEMPLATE_TYPES[$templateType] ?? '00';

        if ($workCenterCode !== null) {
            $orgCode = str_pad((string) $organizationFolio, 2, '0', STR_PAD_LEFT);
            $centerCode = str_pad($workCenterCode, 2, '0', STR_PAD_LEFT);

            if ($templateType === 'referencia-i') {
                $personCode = str_repeat(' ', 5);
            } else {
                $personCode = str_pad($personFolio, 5, '0', STR_PAD_LEFT);
            }

            return $typeCode.$orgCode.$centerCode.$personCode;
        }

        $orgCode = str_pad((string) $organizationFolio, 3, '0', STR_PAD_LEFT);

        if ($templateType === 'referencia-i') {
            $personCode = '';
        } else {
            $personCode = str_pad($personFolio, 4, '0', STR_PAD_LEFT);
        }

        return $typeCode.$orgCode.$personCode;
    }

    /**
     * Generate and download PDF for batch of folios
     */
    public function generatePdf(StoreOMRPdfRequest $request)
    {
        // Get configuration values
        $memoryLimit = config('omr.pdf_generation.memory_limit', 512).'M';
        $executionTime = config('omr.pdf_generation.execution_time', 1800);

        // Increase memory limit and execution time for large batch generation
        ini_set('memory_limit', $memoryLimit);
        ini_set('max_execution_time', (string) $executionTime);

        $validated = $request->validated();

        // Get organization and batch
        $organization = Organization::findOrFail($validated['organization_id']);
        $batch = FolioBatch::where('id', $validated['folio_batch_id'])
            ->where('organization_id', $organization->id)
            ->with('workCenter')
            ->firstOrFail();

        // Resolve work center code for folio generation
        $workCenterCode = $batch->workCenter?->code;

        // Determine folio padding based on work center presence
        $folioPadLength = $workCenterCode !== null ? 5 : 4;

        // Determine which folios to generate
        $foliosToGenerate = [];
        if ($validated['generate_all'] ?? false) {
            // Generate all folios in the batch range
            for ($i = $batch->start_number; $i <= $batch->end_number; $i++) {
                $foliosToGenerate[] = str_pad((string) $i, $folioPadLength, '0', STR_PAD_LEFT);
            }
        } else {
            $foliosToGenerate = array_map(
                fn ($f) => str_pad($f, $folioPadLength, '0', STR_PAD_LEFT),
                $validated['folios'] ?? []
            );
        }

        if (empty($foliosToGenerate)) {
            return back()->with('error', 'No se proporcionaron folios para generar el PDF.');
        }

        // Get configuration values
        $jobThreshold = config('omr.pdf_generation.job_threshold', 100);
        $chunkSize = config('omr.pdf_generation.chunk_size', 100);

        // For large batches, split into multiple PDFs and use tracking
        if (count($foliosToGenerate) > $jobThreshold) {
            // Create tracking record
            $pdfJob = PdfGenerationJob::create([
                'organization_id' => $organization->id,
                'folio_batch_id' => $batch->id,
                'guide_type' => $validated['guide_type'],
                'total_folios' => count($foliosToGenerate),
                'status' => 'pending',
            ]);

            // Split into configurable chunks
            $chunks = array_chunk($foliosToGenerate, $chunkSize);

            // Get guide configuration
            $guideType = $validated['guide_type'];
            $viewData = $this->getGuideData($guideType);

            // Add organization logo if available
            if ($organization->logo) {
                $logoPath = Storage::disk('public')->path($organization->logo);
                if (file_exists($logoPath)) {
                    $imageData = file_get_contents($logoPath);
                    $base64 = base64_encode($imageData);
                    $mimeType = mime_content_type($logoPath);
                    $viewData['logo'] = "data:{$mimeType};base64,{$base64}";
                }
            }

            // Add positions and areas for likert templates
            if ($guideType === 'likert' || $guideType === 'likert-planta-3') {
                $positions = $organization->occupationPositions()->get(['name']);
                $areas = $organization->departmentAreas()->get(['name']);
                $viewData['positions'] = $positions->isEmpty() ? collect([['name' => 'Puesto 1']]) : $positions;
                $viewData['areas'] = $areas->isEmpty() ? collect([['name' => 'Área 1']]) : $areas;
            }

            // Dispatch multiple jobs, one per chunk
            foreach ($chunks as $chunkIndex => $chunk) {
                GenerateOMRPdfJob::dispatch(
                    $validated,
                    $guideType,
                    $organization,
                    $chunk,
                    $viewData,
                    $chunkIndex + 1, // Batch number
                    count($chunks),   // Total batches
                    $pdfJob->id       // Pass the tracking job ID
                );
            }

            $totalFolios = count($foliosToGenerate);
            $estimatedMinutes = ceil($totalFolios / 60); // Estimate ~1 folio/second

            // Return JSON for AJAX handling
            return response()->json([
                'job_id' => $pdfJob->id,
                'total_folios' => $totalFolios,
                'total_chunks' => count($chunks),
                'estimated_minutes' => $estimatedMinutes,
            ]);
        }

        // Get guide configuration
        $guideType = $validated['guide_type'];
        $viewData = $this->getGuideData($guideType);

        // Add organization logo if available (convert to base64 for Browsershot compatibility)
        if ($organization->logo) {
            $logoPath = Storage::disk('public')->path($organization->logo);
            if (file_exists($logoPath)) {
                // Convert image to base64 to avoid file:// issues with Browsershot
                $imageData = file_get_contents($logoPath);
                $base64 = base64_encode($imageData);
                $mimeType = mime_content_type($logoPath);
                $viewData['logo'] = "data:{$mimeType};base64,{$base64}";
            }
        }

        // Add positions and areas for likert templates
        if ($guideType === 'likert' || $guideType === 'likert-planta-3') {
            $positions = $organization->occupationPositions()->get(['name']);
            $areas = $organization->departmentAreas()->get(['name']);

            // Use defaults if empty
            $viewData['positions'] = $positions->isEmpty() ? collect([['name' => 'Puesto 1']]) : $positions;
            $viewData['areas'] = $areas->isEmpty() ? collect([['name' => 'Área 1']]) : $areas;
        }

        // Generate extended folios and HTML content
        $htmlContent = '';
        foreach ($foliosToGenerate as $index => $personFolio) {
            $extendedFolio = $this->generateExtendedFolio(
                $guideType,
                $organization->folio_organization ?? 0,
                $personFolio,
                $workCenterCode
            );

            // For hybrid batches generating Referencia V, create empty PaperEvaluation records
            if ($batch->isHibrido() && $guideType === 'referencia-v') {
                $this->createHybridPaperEvaluation($extendedFolio, $organization->id);
            }

            $pageData = array_merge($viewData, [
                'folio' => $extendedFolio,
                'isHybrid' => $batch->isHibrido() && $guideType === 'referencia-v',
            ]);
            $htmlContent .= view("omr.{$guideType}", $pageData)->render();

            // Add page break except for the last page
            if ($index < count($foliosToGenerate) - 1) {
                $htmlContent .= '<div style="page-break-after: always;"></div>';
            }

            // Free memory every 100 pages
            if (($index + 1) % 100 === 0) {
                gc_collect_cycles();
            }
        }

        // Generate PDF
        $filename = str_replace('-', '_', $guideType).'_'.$organization->name.'_'.date('Y-m-d_H-i-s').'.pdf';
        $tempPath = storage_path('app/temp/'.$filename);

        // Create temp directory if it doesn't exist
        if (! file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        // Configure Browsershot
        $browsershot = Browsershot::html($htmlContent)
            ->noSandbox()
            ->format('Letter')
            // Force zero PDF margins; internal spacing is handled by the .page container padding
            ->margins(0, 0, 0, 0)
            // Slightly shrink the entire page to avoid accidental overflow to a second page
            // This preserves the relative geometry between alignment markers and bubbles
            ->scale(0.96)
            ->showBackground()
            ->timeout(300) // 5 minutes timeout for large batches
            ->waitUntilNetworkIdle();

        // Configure for WSL if needed
        if (PHP_OS_FAMILY === 'Linux' && file_exists('/mnt/c/Program Files/nodejs/node.exe')) {
            $browsershot->setNodeBinary('/mnt/c/Program Files/nodejs/node.exe');
            $browsershot->setNpmBinary('/mnt/c/Program Files/nodejs/npm');
        }

        $browsershot->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    /**
     * Get guide-specific data for rendering
     */
    private function getGuideData(string $guideType): array
    {
        return match ($guideType) {
            'referencia-i' => [
                'questions' => $this->flattenQuestions(config('referencia_i')),
                'totalQuestions' => count($this->flattenQuestions(config('referencia_i'))),
            ],
            'referencia-iii' => [
                'generalQuestions' => config('referencia_iii.general'),
                'conditionalSections' => config('referencia_iii.conditional_sections'),
                'totalGeneralQuestions' => count(config('referencia_iii.general')),
                'totalConditionalSections' => count(config('referencia_iii.conditional_sections')),
            ],
            'referencia-v' => [
                'config' => config('referencia_v'),
            ],
            'escala-cisneros' => [
                'questions' => config('escala_cisneros'),
                'totalQuestions' => count(config('escala_cisneros')),
            ],
            'likert' => [
                'totalQuestions' => 23,
            ],
            'likert-planta-3' => [
                'totalQuestions' => 23,
            ],
            default => [],
        };
    }

    /**
     * Flatten nested questions array
     */
    private function flattenQuestions(array $config): array
    {
        $questions = [];
        foreach ($config as $sectionQuestions) {
            $questions = array_merge($questions, $sectionQuestions);
        }

        return $questions;
    }

    /**
     * Mostrar hoja OMR para Guía de Referencia I
     */
    public function referenciaI(Request $request)
    {
        $questions = $this->flattenQuestions(config('referencia_i'));

        return view('omr.referencia-i', [
            'questions' => $questions,
            'totalQuestions' => count($questions),
            'folio' => $request->input('folio', '000000000'), // Default 9-digit folio
            'showPrefilledFolio' => false,
        ]);
    }

    /**
     * Mostrar hoja OMR para Guía de Referencia III
     */
    public function referenciaIII(Request $request)
    {
        $config = config('referencia_iii');

        return view('omr.referencia-iii', [
            'generalQuestions' => $config['general'],
            'conditionalSections' => $config['conditional_sections'],
            'totalGeneralQuestions' => count($config['general']),
            'totalConditionalSections' => count($config['conditional_sections']),
            'folio' => $request->input('folio', '000000000'),
        ]);
    }

    /**
     * Mostrar hoja OMR para Guía de Referencia V
     */
    public function referenciaV(Request $request)
    {
        $config = config('referencia_v');

        return view('omr.referencia-v', [
            'config' => $config,
            'folio' => $request->input('folio', '000000000'),
        ]);
    }

    /**
     * Mostrar hoja OMR para Escala Cisneros
     */
    public function escalaCisneros(Request $request)
    {
        $questions = config('escala_cisneros');

        return view('omr.escala-cisneros', [
            'questions' => $questions,
            'totalQuestions' => count($questions),
            'folio' => $request->input('folio', '000000000'),
        ]);
    }

    /**
     * Mostrar hoja OMR para Escala Likert
     */
    public function likert(Request $request)
    {
        // Get organization if provided to load positions and areas
        $organizationId = $request->input('organization_id');
        $positions = collect([['name' => 'Puesto 1']]);
        $areas = collect([['name' => 'Área 1']]);

        if ($organizationId) {
            $organization = Organization::find($organizationId);
            if ($organization) {
                $positions = $organization->occupationPositions()->get(['name']);
                $areas = $organization->departmentAreas()->get(['name']);

                // Use defaults if empty
                if ($positions->isEmpty()) {
                    $positions = collect([['name' => 'Puesto 1']]);
                }
                if ($areas->isEmpty()) {
                    $areas = collect([['name' => 'Área 1']]);
                }
            }
        }

        return view('omr.likert', [
            'totalQuestions' => 23,
            'folio' => $request->input('folio', '000000000'),
            'logo' => $request->input('logo'),
            'positions' => $positions,
            'areas' => $areas,
        ]);
    }

    /**
     * Mostrar hoja OMR para Escala Likert (Planta 3)
     */
    public function likertPlanta3(Request $request)
    {
        // Get organization if provided to load positions and areas
        $organizationId = $request->input('organization_id');
        $positions = collect([['name' => 'Puesto 1']]);
        $areas = collect([['name' => 'Área 1']]);

        if ($organizationId) {
            $organization = Organization::find($organizationId);
            if ($organization) {
                $positions = $organization->occupationPositions()->get(['name']);
                $areas = $organization->departmentAreas()->get(['name']);

                // Use defaults if empty
                if ($positions->isEmpty()) {
                    $positions = collect([['name' => 'Puesto 1']]);
                }
                if ($areas->isEmpty()) {
                    $areas = collect([['name' => 'Área 1']]);
                }
            }
        }

        return view('omr.likert-planta-3', [
            'totalQuestions' => 23,
            'folio' => $request->input('folio', '00000000000'),
            'logo' => $request->input('logo'),
            'positions' => $positions,
            'areas' => $areas,
            'showPrefilledFolio' => false,
        ]);
    }

    /**
     * Download blank template for Likert Planta 3 (admin only route).
     */
    public function downloadBlankLikertPlanta3()
    {
        return $this->downloadBlankTemplate('likert-planta-3');
    }

    public function downloadBlankReferenciaI()
    {
        return $this->downloadBlankTemplate('referencia-i');
    }

    /**
     * Download blank template for Referencia III (admin only route).
     */
    public function downloadBlankReferenciaIII()
    {
        return $this->downloadBlankTemplate('referencia-iii');
    }

    /**
     * Download blank template for Referencia V (admin only route).
     */
    public function downloadBlankReferenciaV()
    {
        return $this->downloadBlankTemplate('referencia-v');
    }

    /**
     * Download blank template for Escala Cisneros (admin only route).
     */
    public function downloadBlankEscalaCisneros()
    {
        return $this->downloadBlankTemplate('escala-cisneros');
    }

    /**
     * Generate a single blank OMR template PDF without folio prefill.
     */
    private function downloadBlankTemplate(string $guideType)
    {
        $viewData = $this->getGuideData($guideType);

        // Add default positions and areas for likert templates
        if ($guideType === 'likert' || $guideType === 'likert-planta-3') {
            $viewData['positions'] = collect([['name' => 'Puesto 1']]);
            $viewData['areas'] = collect([['name' => 'Área 1']]);
        }

        $pageData = array_merge($viewData, [
            'folio' => null,
            'showPrefilledFolio' => false,
        ]);

        $htmlContent = view("omr.{$guideType}", $pageData)->render();

        $filename = str_replace('-', '_', $guideType).'_blank_'.date('Y-m-d_H-i-s').'.pdf';
        $tempPath = storage_path('app/temp/'.$filename);

        if (! file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $browsershot = Browsershot::html($htmlContent)
            ->noSandbox()
            ->format('Letter')
            ->margins(0, 0, 0, 0)
            ->scale(0.96)
            ->showBackground()
            ->timeout(180)
            ->waitUntilNetworkIdle();

        if (PHP_OS_FAMILY === 'Linux' && file_exists('/mnt/c/Program Files/nodejs/node.exe')) {
            $browsershot->setNodeBinary('/mnt/c/Program Files/nodejs/node.exe');
            $browsershot->setNpmBinary('/mnt/c/Program Files/nodejs/npm');
        }

        $browsershot->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    /**
     * Create empty PaperEvaluation record for hybrid mode
     * This pre-creates the record with UUID so it exists when user completes online evaluation
     */
    private function createHybridPaperEvaluation(string $folio, string $organizationId): void
    {
        // Check if record already exists (avoid duplicates on regeneration)
        if (PaperEvaluation::where('folio', $folio)->exists()) {
            return;
        }

        // Parse folio to get components
        $folioData = PaperEvaluation::parseFolio($folio);

        // Create empty record with pending status
        PaperEvaluation::create([
            'folio' => $folio,
            'evaluation_type_code' => $folioData['evaluation_type_code'],
            'organization_code' => $folioData['organization_code'],
            'personal_folio' => $folioData['personal_folio'],
            'organization_id' => $organizationId,
            'evaluation_type' => $folioData['evaluation_type'],
            'source' => 'hybrid',
            'processing_status' => 'pending',
            // All data fields are null initially
            'demographic_data' => null,
            'referencia_i_answers' => null,
            'referencia_iii_answers' => null,
            'referencia_iii_conditional' => null,
            'citsats_s1' => null,
            'raw_data' => json_encode([
                'created_via' => 'pdf_generation',
                'created_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}
