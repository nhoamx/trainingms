<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\ReportGeneration;
use App\Services\LikertChartImageService;
use App\Services\ReportPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Spatie\Browsershot\Browsershot;

class GenerateWordReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutes for large reports with multiple filtered sections

    /**
     * Temporary image paths to clean up after generation
     *
     * @var array<string>
     */
    protected array $tempImagePaths = [];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ReportGeneration $reportGeneration
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->reportGeneration->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info('Starting Word report generation', [
                'report_generation_id' => $this->reportGeneration->id,
                'report_type' => $this->reportGeneration->report_type,
                'organization_id' => $this->reportGeneration->organization_id,
            ]);

            $organization = Organization::findOrFail($this->reportGeneration->organization_id);
            $reportPdfService = app(ReportPdfService::class);

            // For Likert reports, use native PHPWord generation
            if ($this->reportGeneration->report_type === 'likert') {
                $docxPath = $this->generateLikertWordNative($organization, $reportPdfService);
            } else {
                // For other report types, use PDF conversion
                $docxPath = $this->generateWordViaPdfConversion($organization, $reportPdfService);
            }

            // Verify DOCX file actually exists before marking as completed
            if (! file_exists($docxPath)) {
                throw new \Exception('DOCX file was not created: '.$docxPath);
            }

            $fileSize = filesize($docxPath);
            if ($fileSize === 0) {
                throw new \Exception('DOCX file is empty (0 bytes)');
            }

            Log::info('DOCX file created successfully', [
                'path' => $docxPath,
                'size' => $fileSize,
                'exists' => file_exists($docxPath),
            ]);

            // Update report generation record
            $docxFilename = basename($docxPath);
            $this->reportGeneration->update([
                'status' => 'completed',
                'file_path' => $docxPath,
                'original_filename' => $docxFilename,
                'completed_at' => now(),
            ]);

            Log::info('Word report generation completed successfully', [
                'report_generation_id' => $this->reportGeneration->id,
                'file_path' => $docxPath,
                'file_size' => $fileSize,
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating Word report', [
                'report_generation_id' => $this->reportGeneration->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->reportGeneration->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate Word document via PDF conversion (for demographic, diagnostic, executive)
     */
    protected function generateWordViaPdfConversion(Organization $organization, ReportPdfService $reportPdfService): string
    {
        // Generate HTML based on report type
        $html = $this->generateReportHtml($organization, $reportPdfService);

        // Generate PDF first
        $pdfFilename = $this->generatePdfFilename($organization);
        $pdfPath = storage_path('app/temp/'.$pdfFilename);

        // Ensure temp directory exists
        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        Log::info('Generating PDF', ['path' => $pdfPath]);
        $this->configureBrowsershot($html)->save($pdfPath);

        // Convert PDF to Word
        $docxFilename = str_replace('.pdf', '.docx', $pdfFilename);
        $docxPath = storage_path('app/temp/'.$docxFilename);

        Log::info('Converting PDF to DOCX', [
            'pdf_path' => $pdfPath,
            'docx_path' => $docxPath,
        ]);

        if (! $this->convertPdfToDocx($pdfPath, $docxPath)) {
            throw new \Exception('Failed to convert PDF to DOCX');
        }

        // Clean up PDF file
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        return $docxPath;
    }

    /**
     * Generate Likert Word document natively using PHPWord
     */
    protected function generateLikertWordNative(Organization $organization, ReportPdfService $reportPdfService): string
    {
        $chartImageService = app(LikertChartImageService::class);
        $likertData = $reportPdfService->getLikertReportWordData($organization->id);

        if (empty($likertData['evaluations'])) {
            throw new \Exception('No hay evaluaciones Likert disponibles para generar el reporte');
        }

        // Ensure temp directory exists
        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Create PHPWord document
        $phpWord = new PhpWord;

        // Set default font
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        // Define styles
        $this->defineLikertStyles($phpWord);

        // Add content sections
        $section = $phpWord->addSection([
            'marginTop' => 720, // 0.5 inch
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        // Header
        $this->addLikertHeader($section, $organization, $likertData);

        // 1. TOTAL Section (all evaluations)
        $this->addFilteredReportSection(
            $section,
            $likertData,
            $likertData['evaluations'],
            $likertData['climaLaboralDistribution'],
            $likertData['dimensions'],
            'TOTAL - Todas las Evaluaciones',
            $likertData['totalPeople'],
            $chartImageService,
            $tempDir,
            false // No page break for first section
        );

        // 2. Turno Matutino Section
        $matutinoData = $reportPdfService->filterEvaluationsAndRecalculate($likertData, 'turno', 'Matutino');
        if ($matutinoData['totalPeople'] > 0) {
            $this->addFilteredReportSection(
                $section,
                $likertData,
                $matutinoData['evaluations'],
                $matutinoData['climaLaboralDistribution'],
                $matutinoData['dimensions'],
                'TURNO MATUTINO',
                $matutinoData['totalPeople'],
                $chartImageService,
                $tempDir
            );
        }

        // 3. Turno Nocturno Section
        $nocturnoData = $reportPdfService->filterEvaluationsAndRecalculate($likertData, 'turno', 'Nocturno');
        if ($nocturnoData['totalPeople'] > 0) {
            $this->addFilteredReportSection(
                $section,
                $likertData,
                $nocturnoData['evaluations'],
                $nocturnoData['climaLaboralDistribution'],
                $nocturnoData['dimensions'],
                'TURNO NOCTURNO',
                $nocturnoData['totalPeople'],
                $chartImageService,
                $tempDir
            );
        }

        // 4. Per-Area Sections
        $uniqueAreas = $reportPdfService->getUniqueDemographicValues($likertData, 'area');
        foreach ($uniqueAreas as $areaValue) {
            if (empty($areaValue)) {
                continue;
            }

            $areaData = $reportPdfService->filterEvaluationsAndRecalculate($likertData, 'area', $areaValue);
            if ($areaData['totalPeople'] > 0) {
                $this->addFilteredReportSection(
                    $section,
                    $likertData,
                    $areaData['evaluations'],
                    $areaData['climaLaboralDistribution'],
                    $areaData['dimensions'],
                    'ÁREA: '.strtoupper($areaValue),
                    $areaData['totalPeople'],
                    $chartImageService,
                    $tempDir
                );
            }
        }

        // Footer
        $this->addLikertFooter($section);

        // Save document
        $docxFilename = 'informe-clima-laboral-'.$organization->name.'-'.now()->format('Y-m-d-His').'.docx';
        $docxPath = $tempDir.'/'.$docxFilename;

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($docxPath);

        // Clean up all temporary images
        $this->cleanupTempImages();

        Log::info('Likert Word report generated successfully', [
            'path' => $docxPath,
            'evaluations_count' => count($likertData['evaluations']),
            'temp_images_cleaned' => count($this->tempImagePaths),
        ]);

        return $docxPath;
    }

    /**
     * Add a complete filtered report section (Clima Laboral + all dimensions)
     *
     * @param  array<string, mixed>  $fullLikertData  Full data with all dimensions config
     * @param  array<int, array>  $evaluations  Filtered evaluations
     * @param  array<string, int>  $climaLaboralDistribution  Distribution for this filter
     * @param  array<string, array>  $dimensionSummaries  Dimension data for this filter
     */
    protected function addFilteredReportSection(
        \PhpOffice\PhpWord\Element\Section $section,
        array $fullLikertData,
        array $evaluations,
        array $climaLaboralDistribution,
        array $dimensionSummaries,
        string $sectionTitle,
        int $totalPeople,
        LikertChartImageService $chartImageService,
        string $tempDir,
        bool $addPageBreak = true
    ): void {
        if ($addPageBreak) {
            $section->addPageBreak();
        }

        // Section title
        $section->addText(
            $sectionTitle,
            ['bold' => true, 'size' => 18, 'color' => '1e40af'],
            ['alignment' => Jc::CENTER]
        );
        $section->addText(
            "{$totalPeople} evaluaciones",
            ['size' => 12, 'italic' => true, 'color' => '666666'],
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(1);

        // Generate unique suffix for this section's images
        $imageSuffix = md5($sectionTitle.time().mt_rand());

        // Clima Laboral section with charts
        $this->addClimaLaboralSectionWithCharts(
            $section,
            $climaLaboralDistribution,
            $evaluations,
            $fullLikertData,
            $chartImageService,
            $tempDir,
            $imageSuffix
        );

        // Each dimension section
        foreach ($dimensionSummaries as $dimName => $dimData) {
            $this->addDimensionSection(
                $section,
                $dimName,
                $dimData,
                $evaluations,
                $chartImageService,
                $tempDir,
                $imageSuffix
            );
        }
    }

    /**
     * Add Clima Laboral section with pie chart, bar chart, and heat map
     *
     * @param  array<string, int>  $distribution
     * @param  array<int, array>  $evaluations
     * @param  array<string, mixed>  $fullLikertData
     */
    protected function addClimaLaboralSectionWithCharts(
        \PhpOffice\PhpWord\Element\Section $section,
        array $distribution,
        array $evaluations,
        array $fullLikertData,
        LikertChartImageService $chartImageService,
        string $tempDir,
        string $imageSuffix
    ): void {
        $section->addTitle('Clima Laboral General', 2);
        $section->addTextBreak(1);

        // Distribution table
        $this->addDistributionTable($section, $distribution);
        $section->addTextBreak(1);

        // Pie chart
        $pieChartPath = $tempDir.'/pie_clima_'.$imageSuffix.'.png';
        if ($chartImageService->generatePieChartImage($distribution, $pieChartPath, 'Distribución de Clima Laboral')) {
            $this->tempImagePaths[] = $pieChartPath;
            $section->addImage($pieChartPath, [
                'width' => 350,
                'height' => 300,
                'alignment' => Jc::CENTER,
            ]);
        }
        $section->addTextBreak(1);

        // Bar chart
        $barChartPath = $tempDir.'/bar_clima_'.$imageSuffix.'.png';
        if ($chartImageService->generateBarChartImage($distribution, $barChartPath, 'Conteo por Nivel')) {
            $this->tempImagePaths[] = $barChartPath;
            $section->addImage($barChartPath, [
                'width' => 400,
                'height' => 280,
                'alignment' => Jc::CENTER,
            ]);
        }
        $section->addTextBreak(1);

        // Heat map for all questions
        $section->addText(
            'Mapa de Calor - Todas las Preguntas',
            ['bold' => true, 'size' => 12, 'color' => '1e40af']
        );
        $section->addText(
            'Valores: 4 = Totalmente de Acuerdo (azul), 3 = De Acuerdo (verde), 2 = Desacuerdo (amarillo), 1 = Totalmente Desacuerdo (rojo)',
            ['size' => 9, 'italic' => true, 'color' => '666666']
        );
        $section->addTextBreak(1);

        // Get all question numbers from dimensions
        $allQuestions = [];
        foreach ($fullLikertData['dimensions'] as $dimData) {
            $questionKeys = array_keys($dimData['questions'] ?? []);
            $allQuestions = array_merge($allQuestions, $questionKeys);
        }

        // Generate heat map images
        $heatMapImages = $chartImageService->generateDimensionHeatMapImages(
            $evaluations,
            $allQuestions,
            $tempDir,
            'heatmap_all_'.$imageSuffix,
            'Mapa de Calor - Todas las Preguntas'
        );

        foreach ($heatMapImages as $heatMapPath) {
            $this->tempImagePaths[] = $heatMapPath;
            $section->addImage($heatMapPath, [
                'width' => 650,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Also add native table heat map
        $this->addHeatMapTableNative($section, $evaluations, $fullLikertData['dimensions']);
    }

    /**
     * Add a single dimension section with pie chart, bar chart, and dimension-specific heat map
     *
     * @param  array<string, mixed>  $dimData
     * @param  array<int, array>  $evaluations
     */
    protected function addDimensionSection(
        \PhpOffice\PhpWord\Element\Section $section,
        string $dimName,
        array $dimData,
        array $evaluations,
        LikertChartImageService $chartImageService,
        string $tempDir,
        string $imageSuffix
    ): void {
        $section->addPageBreak();
        $section->addTitle($dimName, 2);

        $questionNumbers = $dimData['questionNumbers'] ?? [];
        $section->addText(
            count($questionNumbers).' preguntas: '.implode(', ', $questionNumbers),
            ['size' => 9, 'italic' => true, 'color' => '666666']
        );
        $section->addTextBreak(1);

        $distribution = $dimData['distribution'] ?? [];

        // Distribution table
        $this->addDistributionTable($section, $distribution);
        $section->addTextBreak(1);

        // Create safe filename from dimension name
        $safeDimName = preg_replace('/[^a-zA-Z0-9]/', '_', $dimName);

        // Pie chart for dimension
        $pieChartPath = $tempDir.'/pie_'.$safeDimName.'_'.$imageSuffix.'.png';
        if ($chartImageService->generatePieChartImage($distribution, $pieChartPath, $dimName)) {
            $this->tempImagePaths[] = $pieChartPath;
            $section->addImage($pieChartPath, [
                'width' => 350,
                'height' => 300,
                'alignment' => Jc::CENTER,
            ]);
        }
        $section->addTextBreak(1);

        // Bar chart for dimension
        $barChartPath = $tempDir.'/bar_'.$safeDimName.'_'.$imageSuffix.'.png';
        if ($chartImageService->generateBarChartImage($distribution, $barChartPath, 'Distribución - '.$dimName)) {
            $this->tempImagePaths[] = $barChartPath;
            $section->addImage($barChartPath, [
                'width' => 400,
                'height' => 280,
                'alignment' => Jc::CENTER,
            ]);
        }
        $section->addTextBreak(1);

        // Dimension-specific heat map
        $section->addText(
            'Mapa de Calor - '.$dimName,
            ['bold' => true, 'size' => 11, 'color' => '1e40af']
        );
        $section->addTextBreak(1);

        // Generate heat map images for this dimension only
        $heatMapImages = $chartImageService->generateDimensionHeatMapImages(
            $evaluations,
            $questionNumbers,
            $tempDir,
            'heatmap_'.$safeDimName.'_'.$imageSuffix,
            'Preguntas: '.implode(', ', $questionNumbers)
        );

        foreach ($heatMapImages as $heatMapPath) {
            $this->tempImagePaths[] = $heatMapPath;
            $section->addImage($heatMapPath, [
                'width' => 550,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Also add native table for this dimension
        $this->addDimensionHeatMapTableNative($section, $evaluations, $questionNumbers);
    }

    /**
     * Add distribution table for a level
     *
     * @param  array<string, int>  $distribution
     */
    protected function addDistributionTable(\PhpOffice\PhpWord\Element\Section $section, array $distribution): void
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'd1d5db', 'cellMargin' => 80]);

        // Header row
        $table->addRow();
        $table->addCell(4500, ['bgColor' => 'e5e7eb'])->addText('Nivel', ['bold' => true]);
        $table->addCell(2000, ['bgColor' => 'e5e7eb'])->addText('Cantidad', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(2000, ['bgColor' => 'e5e7eb'])->addText('Porcentaje', ['bold' => true], ['alignment' => Jc::CENTER]);

        // Data rows
        $total = array_sum($distribution);
        $colors = [
            'Totalmente de Acuerdo' => '60a5fa',
            'De Acuerdo' => '16a34a',
            'Desacuerdo' => 'eab308',
            'Totalmente Desacuerdo' => 'dc2626',
        ];

        foreach ($distribution as $level => $count) {
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            $bgColor = $colors[$level] ?? 'ffffff';
            $textColor = $level === 'Desacuerdo' ? '000000' : 'ffffff';

            $table->addRow();
            $table->addCell(4500, ['bgColor' => $bgColor])->addText($level, ['color' => $textColor, 'bold' => true]);
            $table->addCell(2000)->addText((string) $count, [], ['alignment' => Jc::CENTER]);
            $table->addCell(2000)->addText($percentage.'%', [], ['alignment' => Jc::CENTER]);
        }
    }

    /**
     * Add heat map table as native PHPWord table (all dimensions)
     *
     * @param  array<int, array>  $evaluations
     * @param  array<string, array>  $dimensions
     */
    protected function addHeatMapTableNative(\PhpOffice\PhpWord\Element\Section $section, array $evaluations, array $dimensions): void
    {
        if (empty($evaluations) || empty($dimensions)) {
            return;
        }

        $section->addText(
            'Tabla de Respuestas (formato nativo)',
            ['bold' => true, 'size' => 11, 'color' => '1e40af']
        );
        $section->addTextBreak(1);

        // Build question list from dimensions
        $allQuestions = [];
        $dimensionSpans = [];
        foreach ($dimensions as $dimName => $dimData) {
            $questions = $dimData['questions'] ?? [];
            if (is_array($questions)) {
                $questionNumbers = array_keys($questions);
                $dimensionSpans[] = [
                    'name' => $dimName,
                    'colspan' => count($questionNumbers),
                ];
                $allQuestions = array_merge($allQuestions, $questionNumbers);
            }
        }

        $this->renderHeatMapTable($section, $evaluations, $allQuestions, $dimensionSpans);
    }

    /**
     * Add dimension-specific heat map table as native PHPWord table
     *
     * @param  array<int, array>  $evaluations
     * @param  array<int>  $questionNumbers
     */
    protected function addDimensionHeatMapTableNative(\PhpOffice\PhpWord\Element\Section $section, array $evaluations, array $questionNumbers): void
    {
        if (empty($evaluations) || empty($questionNumbers)) {
            return;
        }

        $section->addText(
            'Tabla de Respuestas',
            ['bold' => true, 'size' => 10, 'color' => '666666']
        );
        $section->addTextBreak(1);

        $this->renderHeatMapTable($section, $evaluations, $questionNumbers);
    }

    /**
     * Render heat map table with optional dimension headers
     *
     * @param  array<int, array>  $evaluations
     * @param  array<int>  $questionNumbers
     * @param  array<array{name: string, colspan: int}>|null  $dimensionSpans
     */
    protected function renderHeatMapTable(
        \PhpOffice\PhpWord\Element\Section $section,
        array $evaluations,
        array $questionNumbers,
        ?array $dimensionSpans = null
    ): void {
        $totalQuestions = count($questionNumbers);
        $folioWidth = 1200;
        $availableWidth = 9000;
        $questionCellWidth = $totalQuestions > 0 ? (int) ($availableWidth / $totalQuestions) : 400;
        $questionCellWidth = max(350, min(500, $questionCellWidth));

        $answerColors = [
            'A' => ['bg' => '60a5fa', 'text' => 'ffffff'],
            'B' => ['bg' => '16a34a', 'text' => 'ffffff'],
            'C' => ['bg' => 'eab308', 'text' => '000000'],
            'D' => ['bg' => 'dc2626', 'text' => 'ffffff'],
        ];

        $answerValues = ['A' => '4', 'B' => '3', 'C' => '2', 'D' => '1'];

        $tableStyle = [
            'borderSize' => 4,
            'borderColor' => 'd1d5db',
            'cellMargin' => 30,
        ];

        $table = $section->addTable($tableStyle);

        // Dimension headers row (if provided)
        if ($dimensionSpans !== null) {
            $table->addRow();
            $table->addCell($folioWidth, ['bgColor' => 'e5e7eb', 'vMerge' => 'restart'])->addText(
                'Folio',
                ['bold' => true, 'size' => 8],
                ['alignment' => Jc::CENTER]
            );

            foreach ($dimensionSpans as $dimSpan) {
                $cellWidth = $questionCellWidth * $dimSpan['colspan'];
                $table->addCell($cellWidth, ['bgColor' => 'e5e7eb', 'gridSpan' => $dimSpan['colspan']])->addText(
                    $dimSpan['name'],
                    ['bold' => true, 'size' => 7],
                    ['alignment' => Jc::CENTER]
                );
            }
        }

        // Question numbers row
        $table->addRow();
        if ($dimensionSpans !== null) {
            $table->addCell($folioWidth, ['bgColor' => 'f3f4f6', 'vMerge' => 'continue']);
        } else {
            $table->addCell($folioWidth, ['bgColor' => 'f3f4f6'])->addText(
                'Folio',
                ['bold' => true, 'size' => 8],
                ['alignment' => Jc::CENTER]
            );
        }

        foreach ($questionNumbers as $qNum) {
            $table->addCell($questionCellWidth, ['bgColor' => 'f3f4f6'])->addText(
                (string) $qNum,
                ['bold' => true, 'size' => 8],
                ['alignment' => Jc::CENTER]
            );
        }

        // Data rows
        foreach ($evaluations as $eval) {
            $folio = $eval['personal_folio'] ?? $eval['folio'] ?? 'N/A';
            $answers = $eval['answers'] ?? [];

            $table->addRow();
            $table->addCell($folioWidth, ['bgColor' => 'f9fafb'])->addText(
                $folio,
                ['bold' => true, 'size' => 7]
            );

            foreach ($questionNumbers as $qNum) {
                $answer = $answers[$qNum] ?? $answers[(string) $qNum] ?? '';
                $answerUpper = strtoupper($answer);
                $numericValue = $answerValues[$answerUpper] ?? '-';
                $colors = $answerColors[$answerUpper] ?? ['bg' => 'e5e7eb', 'text' => '666666'];

                $table->addCell($questionCellWidth, ['bgColor' => $colors['bg']])->addText(
                    $numericValue,
                    ['bold' => true, 'size' => 8, 'color' => $colors['text']],
                    ['alignment' => Jc::CENTER]
                );
            }
        }

        $section->addTextBreak(1);
    }

    /**
     * Clean up all temporary image files
     */
    protected function cleanupTempImages(): void
    {
        foreach ($this->tempImagePaths as $imagePath) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $this->tempImagePaths = [];
    }

    /**
     * Define styles for Likert report
     */
    protected function defineLikertStyles(PhpWord $phpWord): void
    {
        $phpWord->addTitleStyle(1, [
            'bold' => true,
            'size' => 18,
            'color' => '1e40af',
        ], ['alignment' => Jc::CENTER]);

        $phpWord->addTitleStyle(2, [
            'bold' => true,
            'size' => 14,
            'color' => '3b82f6',
        ]);

        $phpWord->addTitleStyle(3, [
            'bold' => true,
            'size' => 12,
            'color' => '1e40af',
        ]);
    }

    /**
     * Add header section to Likert report
     *
     * @param  array<string, mixed>  $likertData
     */
    protected function addLikertHeader(\PhpOffice\PhpWord\Element\Section $section, Organization $organization, array $likertData): void
    {
        $section->addTitle('Reporte Clima Laboral', 1);
        $section->addText($organization->name, ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addText('Fecha de generación: '.now()->format('d/m/Y'), ['size' => 10, 'color' => '666666'], ['alignment' => Jc::CENTER]);
        $section->addText($likertData['totalPeople'].' evaluaciones completadas', ['size' => 10, 'italic' => true], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
    }

    /**
     * Add footer section
     */
    protected function addLikertFooter(\PhpOffice\PhpWord\Element\Section $section): void
    {
        $section->addTextBreak(2);
        $section->addText(
            'Reporte generado automáticamente por el Sistema de Evaluación NOM-035-STPS-2018',
            ['size' => 8, 'italic' => true, 'color' => '999999'],
            ['alignment' => Jc::CENTER]
        );
        $section->addText(
            'Este documento es confidencial y para uso interno de la organización.',
            ['size' => 8, 'italic' => true, 'color' => '999999'],
            ['alignment' => Jc::CENTER]
        );
    }

    protected function generateReportHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        return match ($this->reportGeneration->report_type) {
            'demographic' => $this->generateDemographicHtml($organization, $reportPdfService),
            'diagnostic' => $this->generateDiagnosticHtml($organization, $reportPdfService),
            'executive' => $this->generateExecutiveHtml($organization, $reportPdfService),
            default => throw new \Exception('Invalid report type'),
        };
    }

    protected function generateDemographicHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        $demographicData = $reportPdfService->getDemographicDistributionData($organization->id);

        if (empty($demographicData)) {
            throw new \Exception('No demographic data available');
        }

        return view('pdfs.demographic-report-browsershot', [
            'organization' => $organization,
            'demographicData' => $demographicData,
            'generatedDate' => now()->format('d/m/Y'),
        ])->render();
    }

    protected function generateDiagnosticHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        $diagnosticData = $reportPdfService->getDiagnosticResultsData($organization->id);
        $demographicData = $reportPdfService->getDemographicDistributionData($organization->id);
        $traumaticEventsData = $reportPdfService->getTraumaticEventsData($organization->id);

        if (empty($diagnosticData['final_risk'])) {
            throw new \Exception('No diagnostic data available');
        }

        return view('pdfs.diagnostic-report-browsershot', [
            'organization' => $organization,
            'diagnosticData' => $diagnosticData,
            'demographicData' => $demographicData,
            'traumaticEventsData' => $traumaticEventsData,
            'generatedDate' => now()->format('d/m/Y'),
        ])->render();
    }

    protected function generateExecutiveHtml(Organization $organization, ReportPdfService $reportPdfService): string
    {
        $executiveData = $reportPdfService->getExecutiveReportData($organization->id);
        $demographicData = $reportPdfService->getDemographicDistributionData($organization->id);

        if (empty($executiveData['analisis_cuantitativo_final']['total'])) {
            throw new \Exception('No executive data available');
        }

        return view('pdfs.executive-report', [
            'organization' => $organization,
            'executiveData' => $executiveData,
            'demographicData' => $demographicData,
            'generatedDate' => now()->format('d/m/Y'),
        ])->render();
    }

    protected function generatePdfFilename(Organization $organization): string
    {
        $typeMap = [
            'demographic' => 'demografico',
            'diagnostic' => 'diagnostico',
            'executive' => 'ejecutivo',
        ];

        $type = $typeMap[$this->reportGeneration->report_type] ?? $this->reportGeneration->report_type;

        return 'informe-'.$type.'-'.$organization->name.'-'.now()->format('Y-m-d-His').'.pdf';
    }

    protected function configureBrowsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->paperSize(8.5, 11, 'in')
            ->margins(0, 0, 0, 0)
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->showBackground();

        if (PHP_OS_FAMILY === 'Linux' && app()->isProduction()) {
            $browsershot->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
            ]);
        }

        return $browsershot;
    }

    protected function convertPdfToDocx(string $pdfPath, string $docxPath): bool
    {
        try {
            $containerName = config('services.docker.omr_container', 'training-and-ms');
            $dockerPdfPath = '/app/temp_pdf_input.pdf';
            $dockerDocxPath = '/app/temp_docx_output.docx';

            Log::info('Starting PDF to DOCX conversion', [
                'pdf_path' => $pdfPath,
                'docx_path' => $docxPath,
                'container' => $containerName,
            ]);

            // Copy PDF to Docker
            $copyToDocketCommand = "docker cp \"{$pdfPath}\" {$containerName}:{$dockerPdfPath}";
            $copyResult = Process::timeout(30)->run($copyToDocketCommand);

            if (! $copyResult->successful()) {
                Log::error('Failed to copy PDF to Docker', [
                    'output' => $copyResult->output(),
                    'error' => $copyResult->errorOutput(),
                ]);

                return false;
            }

            // Convert
            $convertCommand = "docker exec {$containerName} python /app/pdf_converter/convert_pdf_to_word.py {$dockerPdfPath} {$dockerDocxPath}";
            $convertResult = Process::timeout(300)->run($convertCommand);

            if (! $convertResult->successful()) {
                Log::error('Failed to convert PDF to DOCX', [
                    'output' => $convertResult->output(),
                    'error' => $convertResult->errorOutput(),
                ]);

                return false;
            }

            // Copy back
            $copyFromDockerCommand = "docker cp {$containerName}:{$dockerDocxPath} \"{$docxPath}\"";
            $copyBackResult = Process::timeout(30)->run($copyFromDockerCommand);

            if (! $copyBackResult->successful()) {
                Log::error('Failed to copy DOCX from Docker', [
                    'output' => $copyBackResult->output(),
                    'error' => $copyBackResult->errorOutput(),
                ]);

                return false;
            }

            // Cleanup
            Process::timeout(10)->run("docker exec {$containerName} rm -f {$dockerPdfPath} {$dockerDocxPath}");

            return file_exists($docxPath);
        } catch (\Exception $e) {
            Log::error('Error in PDF to DOCX conversion', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
