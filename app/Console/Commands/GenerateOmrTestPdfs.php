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
    protected $signature = 'omr:generate-test-pdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera archivos PDF de prueba para cada template OMR con folios y respuestas de muestra';

    /**
     * Test folios for each template type
     */
    private const TEST_FOLIOS = [
        'referencia-i' => '130000001', // Template 13
        'referencia-iii' => '120000001', // Template 12
        'referencia-v' => '170000001', // Template 17
        'escala-cisneros' => '140000001', // Template 14 (if needed)
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Generando PDFs de prueba para templates OMR...');
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
                $this->generateTestPdf($template, $outputPath);
                $this->info("✅ {$name} generado exitosamente");
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
     * Generate test PDF for a specific template
     */
    private function generateTestPdf(string $template, string $outputPath): void
    {
        $folio = self::TEST_FOLIOS[$template];
        $viewData = $this->getTemplateData($template, $folio);

        // Generate HTML content with filled bubbles
        $html = view("omr.{$template}", $viewData)->render();

        // Inject JavaScript to fill sample bubbles
        $html = $this->injectSampleAnswers($html, $template);

        // Generate PDF filename
        $filename = "{$template}.pdf";
        $pdfPath = storage_path("app/{$outputPath}/{$filename}");

        // Configure Browsershot
        $browsershot = Browsershot::html($html)
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

    /**
     * Inject sample answers by adding CSS to fill specific bubbles
     */
    private function injectSampleAnswers(string $html, string $template): string
    {
        // Add CSS to fill sample bubbles for testing
        $sampleAnswersCSS = $this->getSampleAnswersCSS($template);

        // Inject CSS before closing </head> tag
        $html = str_replace('</head>', "{$sampleAnswersCSS}</head>", $html);

        return $html;
    }

    /**
     * Generate CSS to fill sample bubbles based on template type
     */
    private function getSampleAnswersCSS(string $template): string
    {
        $css = '<style type="text/css">';

        switch ($template) {
            case 'referencia-i':
                // Fill folio bubbles for "130000001"
                $css .= $this->getFolioBubbleCSS(['1', '3', '0', '0', '0', '0', '0', '0', '1']);
                // Fill some question answers (SÍ for questions 1, 3, 5, 7, NO for 2, 4, 6, 8)
                for ($i = 1; $i <= 24; $i++) {
                    $answer = ($i % 2 === 1) ? 0 : 1; // 0 = SÍ, 1 = NO (alternating)
                    $css .= ".question-row:nth-of-type({$i}) .option-group:nth-of-type(".($answer + 1).') .bubble { background-color: black !important; }';
                }
                break;

            case 'referencia-iii':
                // Fill folio bubbles for "120000001"
                $css .= $this->getFolioBubbleCSS(['1', '2', '0', '0', '0', '0', '0', '0', '1']);
                // Fill sample answers for questions (A, B, C, D alternating pattern)
                for ($i = 1; $i <= 30; $i++) {
                    $answer = ($i - 1) % 4; // Rotate through A, B, C, D
                    $css .= ".question-row:nth-of-type({$i}) .option-group:nth-of-type(".($answer + 1).') .bubble { background-color: black !important; }';
                }
                break;

            case 'referencia-v':
                // Fill folio bubbles for "170000001"
                $css .= $this->getFolioBubbleCSS(['1', '7', '0', '0', '0', '0', '0', '0', '1']);
                // Fill some demographic bubbles (this is more complex, add basic pattern)
                $css .= '.demographic-row:nth-of-type(1) .bubble-small:first-of-type { background-color: black !important; }';
                $css .= '.demographic-row:nth-of-type(3) .bubble-small:nth-of-type(2) { background-color: black !important; }';
                break;
        }

        $css .= '</style>';

        return $css;
    }

    /**
     * Generate CSS to fill folio bubbles based on folio digits
     */
    private function getFolioBubbleCSS(array $digits): string
    {
        $css = '';

        foreach ($digits as $position => $digit) {
            // Position is 0-indexed, but CSS nth-of-type is 1-indexed
            $positionIndex = $position + 1;
            // Digit row: row 1 = digit 0, row 2 = digit 1, etc.
            $digitRow = intval($digit) + 1;

            // Select the bubble in the correct position and digit row
            $css .= ".folio-row:nth-of-type({$digitRow}) .bubble-small:nth-of-type({$positionIndex}) { background-color: black !important; }";
        }

        return $css;
    }
}
