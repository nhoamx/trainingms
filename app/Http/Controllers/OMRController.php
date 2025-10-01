<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOMRPdfRequest;
use App\Models\FolioBatch;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
     */
    private function generateExtendedFolio(string $templateType, int $organizationFolio, string $personFolio): string
    {
        Log::info('OMR - Iniciando generación de folio extendido', [
            'template_type' => $templateType,
            'organization_folio' => $organizationFolio,
            'person_folio' => $personFolio,
        ]);

        $typeCode = self::TEMPLATE_TYPES[$templateType] ?? '00';
        $orgCode = str_pad((string) $organizationFolio, 3, '0', STR_PAD_LEFT);
        $personCode = str_pad($personFolio, 4, '0', STR_PAD_LEFT);

        $extendedFolio = $typeCode.$orgCode.$personCode;

        Log::info('OMR - Folio extendido generado', [
            'template_type' => $templateType,
            'type_code' => $typeCode,
            'org_code' => $orgCode,
            'person_code' => $personCode,
            'extended_folio' => $extendedFolio,
        ]);

        return $extendedFolio;
    }

    /**
     * Generate and download PDF for batch of folios
     */
    public function generatePdf(StoreOMRPdfRequest $request)
    {
        Log::info('OMR - Inicio de generación de PDF', [
            'request_data' => $request->all(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        $validated = $request->validated();

        Log::info('OMR - Datos validados', [
            'validated_data' => $validated,
        ]);

        // Get organization and batch
        $organization = Organization::findOrFail($validated['organization_id']);

        Log::info('OMR - Organización encontrada', [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'organization_folio' => $organization->folio_organization,
        ]);

        $batch = FolioBatch::where('id', $validated['folio_batch_id'])
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        Log::info('OMR - Lote de folios encontrado', [
            'batch_id' => $batch->id,
            'start_number' => $batch->start_number,
            'end_number' => $batch->end_number,
            'organization_id' => $batch->organization_id,
        ]);

        // Determine which folios to generate
        $foliosToGenerate = [];
        if ($validated['generate_all'] ?? false) {
            Log::info('OMR - Generando todos los folios del lote');
            // Generate all folios in the batch range
            for ($i = $batch->start_number; $i <= $batch->end_number; $i++) {
                $foliosToGenerate[] = str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            }
        } else {
            Log::info('OMR - Generando folios específicos', [
                'folios_requested' => $validated['folios'] ?? [],
            ]);
            $foliosToGenerate = $validated['folios'] ?? [];
        }

        Log::info('OMR - Folios a generar determinados', [
            'folios_count' => count($foliosToGenerate),
            'folios_list' => $foliosToGenerate,
        ]);

        if (empty($foliosToGenerate)) {
            Log::warning('OMR - No hay folios para generar');

            return back()->with('error', 'No se proporcionaron folios para generar el PDF.');
        }

        // Get guide configuration
        $guideType = $validated['guide_type'];

        Log::info('OMR - Obteniendo configuración de guía', [
            'guide_type' => $guideType,
        ]);

        $viewData = $this->getGuideData($guideType);

        Log::info('OMR - Datos de guía obtenidos', [
            'guide_type' => $guideType,
            'view_data_keys' => array_keys($viewData),
        ]);

        // Generate extended folios and HTML content
        $htmlContent = '';
        $generatedFolios = [];

        foreach ($foliosToGenerate as $index => $personFolio) {
            Log::info('OMR - Procesando folio individual', [
                'index' => $index,
                'person_folio' => $personFolio,
                'organization_folio' => $organization->folio_organization ?? 0,
            ]);

            $extendedFolio = $this->generateExtendedFolio(
                $guideType,
                $organization->folio_organization ?? 0,
                $personFolio
            );

            $generatedFolios[] = $extendedFolio;

            Log::info('OMR - Folio extendido generado para índice', [
                'index' => $index,
                'person_folio' => $personFolio,
                'extended_folio' => $extendedFolio,
            ]);

            $pageData = array_merge($viewData, ['folio' => $extendedFolio]);

            Log::debug('OMR - Generando vista para folio', [
                'extended_folio' => $extendedFolio,
                'view_name' => "omr.{$guideType}",
                'page_data_keys' => array_keys($pageData),
            ]);

            $htmlContent .= view("omr.{$guideType}", $pageData)->render();

            // Add page break except for the last page
            if ($index < count($foliosToGenerate) - 1) {
                $htmlContent .= '<div style="page-break-after: always;"></div>';
            }
        }

        Log::info('OMR - HTML content generado completamente', [
            'html_length' => strlen($htmlContent),
            'generated_folios' => $generatedFolios,
            'total_folios_generated' => count($generatedFolios),
        ]);

        // Generate PDF
        $filename = str_replace('-', '_', $guideType).'_'.$organization->name.'_'.date('Y-m-d_H-i-s').'.pdf';
        $tempPath = storage_path('app/temp/'.$filename);

        Log::info('OMR - Configurando generación de PDF', [
            'filename' => $filename,
            'temp_path' => $tempPath,
        ]);

        // Create temp directory if it doesn't exist
        if (! file_exists(dirname($tempPath))) {
            Log::info('OMR - Creando directorio temporal', [
                'directory' => dirname($tempPath),
            ]);
            mkdir(dirname($tempPath), 0755, true);
        }

        // Configure Browsershot
        Log::info('OMR - Configurando Browsershot para generación de PDF');

        $browsershot = Browsershot::html($htmlContent)
            ->noSandbox()
            ->format('Letter')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle();

        // Configure for WSL if needed
        if (PHP_OS_FAMILY === 'Linux' && file_exists('/mnt/c/Program Files/nodejs/node.exe')) {
            Log::info('OMR - Configurando Browsershot para WSL');
            $browsershot->setNodeBinary('/mnt/c/Program Files/nodejs/node.exe');
            $browsershot->setNpmBinary('/mnt/c/Program Files/nodejs/npm');
        }

        try {
            Log::info('OMR - Iniciando generación de PDF con Browsershot', [
                'temp_path' => $tempPath,
            ]);

            $browsershot->save($tempPath);

            Log::info('OMR - PDF generado exitosamente', [
                'temp_path' => $tempPath,
                'file_exists' => file_exists($tempPath),
                'file_size' => file_exists($tempPath) ? filesize($tempPath) : 0,
            ]);

            return response()->download($tempPath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('OMR - Error al generar PDF', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'temp_path' => $tempPath,
            ]);

            return back()->with('error', 'Error al generar el PDF: '.$e->getMessage());
        }
    }

    /**
     * Get guide-specific data for rendering
     */
    private function getGuideData(string $guideType): array
    {
        Log::info('OMR - Obteniendo datos específicos de guía', [
            'guide_type' => $guideType,
        ]);

        $data = match ($guideType) {
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

        Log::info('OMR - Datos de guía obtenidos', [
            'guide_type' => $guideType,
            'data_keys' => array_keys($data),
            'data_structure' => array_map(function ($value) {
                return is_array($value) ? 'array['.count($value).']' : gettype($value);
            }, $data),
        ]);

        return $data;
    }

    /**
     * Flatten nested questions array
     */
    private function flattenQuestions(array $config): array
    {
        Log::debug('OMR - Aplanando preguntas anidadas', [
            'config_sections' => count($config),
            'config_keys' => array_keys($config),
        ]);

        $questions = [];
        foreach ($config as $sectionKey => $sectionQuestions) {
            Log::debug('OMR - Procesando sección de preguntas', [
                'section_key' => $sectionKey,
                'section_questions_count' => is_array($sectionQuestions) ? count($sectionQuestions) : 0,
            ]);

            if (is_array($sectionQuestions)) {
                $questions = array_merge($questions, $sectionQuestions);
            }
        }

        Log::debug('OMR - Preguntas aplanadas', [
            'total_questions' => count($questions),
        ]);

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
