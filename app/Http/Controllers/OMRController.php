<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOMRPdfRequest;
use App\Models\FolioBatch;
use App\Models\Organization;
use Illuminate\Http\Request;
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
    ];

    /**
     * Generate extended folio format: [template_type(2)][organization(3)][person(4)]
     * For Referencia I, person code is left empty (filled manually later)
     */
    private function generateExtendedFolio(string $templateType, int $organizationFolio, string $personFolio): string
    {
        $typeCode = self::TEMPLATE_TYPES[$templateType] ?? '00';
        $orgCode = str_pad((string) $organizationFolio, 3, '0', STR_PAD_LEFT);
        
        // For Referencia I, leave person code empty (to be filled manually)
        if ($templateType === 'referencia-i') {
            $personCode = ''; // 4 spaces - no bubbles filled
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
        $validated = $request->validated();

        // Get organization and batch
        $organization = Organization::findOrFail($validated['organization_id']);
        $batch = FolioBatch::where('id', $validated['folio_batch_id'])
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        // Determine which folios to generate
        $foliosToGenerate = [];
        if ($validated['generate_all'] ?? false) {
            // Generate all folios in the batch range
            for ($i = $batch->start_number; $i <= $batch->end_number; $i++) {
                $foliosToGenerate[] = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            }
        } else {
            $foliosToGenerate = $validated['folios'] ?? [];
        }

        if (empty($foliosToGenerate)) {
            return back()->with('error', 'No se proporcionaron folios para generar el PDF.');
        }

        // Get guide configuration
        $guideType = $validated['guide_type'];
        $viewData = $this->getGuideData($guideType);

        // Generate extended folios and HTML content
        $htmlContent = '';
        foreach ($foliosToGenerate as $index => $personFolio) {
            $extendedFolio = $this->generateExtendedFolio(
                $guideType,
                $organization->folio_organization ?? 0,
                $personFolio
            );

            $pageData = array_merge($viewData, ['folio' => $extendedFolio]);
            $htmlContent .= view("omr.{$guideType}", $pageData)->render();

            // Add page break except for the last page
            if ($index < count($foliosToGenerate) - 1) {
                $htmlContent .= '<div style="page-break-after: always;"></div>';
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
            ->margins(10, 10, 10, 10)
            ->showBackground()
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
}
