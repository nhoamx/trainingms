<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

class GenerateOmrTestPdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omr:generate-test-pdfs 
                            {--pages=5 : Número de páginas a generar por PDF (5-10)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera archivos PDF de prueba para cada template OMR con folios y respuestas de muestra';

    /**
     * Template type codes (2 digits) based on OMRController structure
     */
    private const TEMPLATE_TYPES = [
        'referencia-i' => '01',
        'referencia-iii' => '02',
        'referencia-v' => '03',
        'escala-cisneros' => '04',
    ];

    /**
     * Test organization code (3 digits) - can be customized
     */
    private const TEST_ORGANIZATION_CODE = '001';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Generando PDFs de prueba para templates OMR...');
        $this->newLine();

        // Validate and get number of pages
        $pages = (int) $this->option('pages');
        if ($pages < 5 || $pages > 10) {
            $this->error('❌ El número de páginas debe estar entre 5 y 10');

            return Command::FAILURE;
        }

        $this->info("📄 Generando {$pages} páginas por PDF");
        $this->newLine();

        // Create output directory if it doesn't exist
        $outputPath = 'omr-test-pdfs';
        $fullPath = storage_path("app/{$outputPath}");

        // Ensure directory exists with proper permissions
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $this->info("📁 Los archivos se guardarán en: {$fullPath}");
        $this->newLine();

        $templates = [
            'referencia-i' => 'Guía de Referencia I',
            'referencia-iii' => 'Guía de Referencia III',
            'referencia-v' => 'Guía de Referencia V',
        ];

        foreach ($templates as $template => $name) {
            $this->info("📄 Generando: {$name}");

            try {
                $this->generateTestPdf($template, $outputPath, $pages);
                $this->info("✅ {$name} generado exitosamente con {$pages} páginas");
            } catch (\Exception $e) {
                $this->error("❌ Error generando {$name}: {$e->getMessage()}");

                continue;
            }

            $this->newLine();
        }

        $this->info('✨ Proceso completado!');
        $this->info("📂 Archivos disponibles en: {$fullPath}");

        return Command::SUCCESS;
    }

    /**
     * Generate extended folio format: [template_type(2)][organization(3)][person(4)]
     */
    private function generateExtendedFolio(string $templateType, int $personNumber): string
    {
        $typeCode = self::TEMPLATE_TYPES[$templateType] ?? '00';
        $orgCode = self::TEST_ORGANIZATION_CODE;
        $personCode = str_pad((string) $personNumber, 4, '0', STR_PAD_LEFT);

        return $typeCode.$orgCode.$personCode;
    }

    /**
     * Generate test PDF for a specific template with multiple pages
     */
    private function generateTestPdf(string $template, string $outputPath, int $pages): void
    {
        $htmlContent = '';

        // Generate HTML for each page with different person folio (only last 4 digits change)
        for ($i = 1; $i <= $pages; $i++) {
            // Generate folio with structure: [template(2)][org(3)][person(4)]
            $folio = $this->generateExtendedFolio($template, $i);
            $viewData = $this->getTemplateData($template, $folio);

            // Generate HTML content (empty - no pre-filled answers for manual testing)
            $html = view("omr.{$template}", $viewData)->render();

            $htmlContent .= $html;

            // Add page break except for the last page
            if ($i < $pages) {
                $htmlContent .= '<div style="page-break-after: always;"></div>';
            }
        }

        // Generate PDF filename
        $filename = "{$template}.pdf";
        $pdfPath = storage_path("app/{$outputPath}/{$filename}");

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

        $browsershot->save($pdfPath);
    }

    /**
     * Get template-specific data
     */
    private function getTemplateData(string $template, string $folio): array
    {
        return match ($template) {
            'referencia-i' => [
                'questions' => $this->flattenQuestions(config('referencia_i')),
                'totalQuestions' => count($this->flattenQuestions(config('referencia_i'))),
                'folio' => $folio,
            ],
            'referencia-iii' => [
                'generalQuestions' => config('referencia_iii.general'),
                'conditionalSections' => config('referencia_iii.conditional_sections'),
                'totalGeneralQuestions' => count(config('referencia_iii.general')),
                'totalConditionalSections' => count(config('referencia_iii.conditional_sections')),
                'folio' => $folio,
            ],
            'referencia-v' => [
                'config' => config('referencia_v'),
                'folio' => $folio,
            ],
            'escala-cisneros' => [
                'questions' => config('escala_cisneros'),
                'totalQuestions' => count(config('escala_cisneros')),
                'folio' => $folio,
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
}
