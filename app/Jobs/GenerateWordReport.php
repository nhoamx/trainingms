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
     * Uses parallel image generation for improved performance
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

        // Step 1: Collect all sections data
        $sectionsData = $this->collectAllSectionsData($likertData, $reportPdfService);

        // Step 2: Pre-generate all chart images in parallel
        Log::info('Starting parallel chart generation', [
            'sections_count' => count($sectionsData),
        ]);

        $chartPaths = $this->preGenerateAllCharts($sectionsData, $chartImageService, $tempDir, $reportPdfService, $likertData);

        Log::info('Parallel chart generation completed', [
            'charts_generated' => count($chartPaths),
        ]);

        // Step 3: Create PHPWord document with pre-generated images
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

        // Add each section using pre-generated images
        $isFirstSection = true;
        foreach ($sectionsData as $sectionData) {
            $this->addFilteredReportSectionWithPregenerated(
                $section,
                $likertData,
                $sectionData['evaluations'],
                $sectionData['climaLaboralDistribution'],
                $sectionData['dimensions'],
                $sectionData['title'],
                $sectionData['totalPeople'],
                $chartPaths,
                $sectionData['imageSuffix'],
                ! $isFirstSection,
                $isFirstSection
            );
            $isFirstSection = false;
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
     * Collect all sections data before generating charts
     *
     * @return array<int, array{title: string, totalPeople: int, evaluations: array, climaLaboralDistribution: array, dimensions: array, imageSuffix: string}>
     */
    protected function collectAllSectionsData(array $likertData, ReportPdfService $reportPdfService): array
    {
        $sections = [];

        // 1. TOTAL Section
        $sections[] = [
            'title' => 'TOTAL - Todas las Evaluaciones',
            'totalPeople' => $likertData['totalPeople'],
            'evaluations' => $likertData['evaluations'],
            'climaLaboralDistribution' => $likertData['climaLaboralDistribution'],
            'dimensions' => $likertData['dimensions'],
            'imageSuffix' => md5('TOTAL'.time().mt_rand()),
        ];

        // 2. Turno Matutino
        $matutinoData = $reportPdfService->filterEvaluationsAndRecalculate($likertData, 'turno', 'Matutino');
        if ($matutinoData['totalPeople'] > 0) {
            $sections[] = [
                'title' => 'TURNO MATUTINO',
                'totalPeople' => $matutinoData['totalPeople'],
                'evaluations' => $matutinoData['evaluations'],
                'climaLaboralDistribution' => $matutinoData['climaLaboralDistribution'],
                'dimensions' => $matutinoData['dimensions'],
                'imageSuffix' => md5('MATUTINO'.time().mt_rand()),
            ];
        }

        // 3. Turno Nocturno
        $nocturnoData = $reportPdfService->filterEvaluationsAndRecalculate($likertData, 'turno', 'Nocturno');
        if ($nocturnoData['totalPeople'] > 0) {
            $sections[] = [
                'title' => 'TURNO NOCTURNO',
                'totalPeople' => $nocturnoData['totalPeople'],
                'evaluations' => $nocturnoData['evaluations'],
                'climaLaboralDistribution' => $nocturnoData['climaLaboralDistribution'],
                'dimensions' => $nocturnoData['dimensions'],
                'imageSuffix' => md5('NOCTURNO'.time().mt_rand()),
            ];
        }

        // 4. Per-Area Sections
        $uniqueAreas = $reportPdfService->getUniqueDemographicValues($likertData, 'area');
        foreach ($uniqueAreas as $areaValue) {
            if (empty($areaValue)) {
                continue;
            }

            $areaData = $reportPdfService->filterEvaluationsAndRecalculate($likertData, 'area', $areaValue);
            if ($areaData['totalPeople'] > 0) {
                $sections[] = [
                    'title' => 'ÁREA: '.strtoupper($areaValue),
                    'totalPeople' => $areaData['totalPeople'],
                    'evaluations' => $areaData['evaluations'],
                    'climaLaboralDistribution' => $areaData['climaLaboralDistribution'],
                    'dimensions' => $areaData['dimensions'],
                    'imageSuffix' => md5('AREA_'.$areaValue.time().mt_rand()),
                ];
            }
        }

        return $sections;
    }

    /**
     * Pre-generate all charts for all sections in parallel
     *
     * @param  array<int, array>  $sectionsData
     * @return array<string, string> Map of chart key => file path
     */
    protected function preGenerateAllCharts(array $sectionsData, LikertChartImageService $chartImageService, string $tempDir, ReportPdfService $reportPdfService, array $likertData): array
    {
        $chartDefinitions = [];

        // Add demographic charts for TOTAL section only (first section)
        if (! empty($sectionsData)) {
            $firstSection = $sectionsData[0];
            $imageSuffix = $firstSection['imageSuffix'];

            // Calculate demographic distributions
            $demographicDistributions = $reportPdfService->calculateDemographicDistributions($likertData);

            // Gender distribution
            if (! empty($demographicDistributions['gender'])) {
                $chartDefinitions[] = [
                    'type' => 'vertical-bar',
                    'distribution' => $demographicDistributions['gender'],
                    'outputPath' => $tempDir.'/demo_gender_'.$imageSuffix.'.png',
                    'title' => 'Distribución por Género',
                ];
            }

            // Contract type distribution
            if (! empty($demographicDistributions['contract'])) {
                $chartDefinitions[] = [
                    'type' => 'vertical-bar',
                    'distribution' => $demographicDistributions['contract'],
                    'outputPath' => $tempDir.'/demo_contract_'.$imageSuffix.'.png',
                    'title' => 'Distribución por Tipo de Contrato',
                ];
            }

            // Position distribution (horizontal bar - many categories)
            if (! empty($demographicDistributions['position'])) {
                $chartDefinitions[] = [
                    'type' => 'demographic-horizontal-bar',
                    'distribution' => $demographicDistributions['position'],
                    'outputPath' => $tempDir.'/demo_position_'.$imageSuffix.'.png',
                    'title' => 'Distribución por Puesto',
                ];
            }

            // Shift distribution
            if (! empty($demographicDistributions['shift'])) {
                $chartDefinitions[] = [
                    'type' => 'vertical-bar',
                    'distribution' => $demographicDistributions['shift'],
                    'outputPath' => $tempDir.'/demo_shift_'.$imageSuffix.'.png',
                    'title' => 'Distribución por Turno',
                ];
            }

            // Area distribution (horizontal bar - many categories)
            if (! empty($demographicDistributions['area'])) {
                $chartDefinitions[] = [
                    'type' => 'demographic-horizontal-bar',
                    'distribution' => $demographicDistributions['area'],
                    'outputPath' => $tempDir.'/demo_area_'.$imageSuffix.'.png',
                    'title' => 'Distribución por Área',
                ];
            }

            // Gender cross-tabulation charts
            $genderCrossDistributions = $reportPdfService->calculateGenderCrossDistributions($likertData);

            // Gender x Position
            if (! empty($genderCrossDistributions['genderByPosition'])) {
                $chartDefinitions[] = [
                    'type' => 'gender-cross',
                    'crossData' => $genderCrossDistributions['genderByPosition'],
                    'outputPath' => $tempDir.'/cross_gender_position_'.$imageSuffix.'.png',
                    'title' => 'Género por Puesto',
                ];
            }

            // Gender x Area
            if (! empty($genderCrossDistributions['genderByArea'])) {
                $chartDefinitions[] = [
                    'type' => 'gender-cross',
                    'crossData' => $genderCrossDistributions['genderByArea'],
                    'outputPath' => $tempDir.'/cross_gender_area_'.$imageSuffix.'.png',
                    'title' => 'Género por Área',
                ];
            }

            // Gender x Shift
            if (! empty($genderCrossDistributions['genderByShift'])) {
                $chartDefinitions[] = [
                    'type' => 'gender-cross',
                    'crossData' => $genderCrossDistributions['genderByShift'],
                    'outputPath' => $tempDir.'/cross_gender_shift_'.$imageSuffix.'.png',
                    'title' => 'Género por Turno',
                ];
            }

            // Gender x Contract
            if (! empty($genderCrossDistributions['genderByContract'])) {
                $chartDefinitions[] = [
                    'type' => 'gender-cross',
                    'crossData' => $genderCrossDistributions['genderByContract'],
                    'outputPath' => $tempDir.'/cross_gender_contract_'.$imageSuffix.'.png',
                    'title' => 'Género por Tipo de Contrato',
                ];
            }

            // Question scores chart (wide)
            $questionScores = $reportPdfService->calculateQuestionScoreTotals($likertData);
            if (! empty($questionScores)) {
                $chartDefinitions[] = [
                    'type' => 'question-scores',
                    'questionScores' => $questionScores,
                    'outputPath' => $tempDir.'/question_scores_'.$imageSuffix.'.png',
                    'title' => 'Puntaje Total por Pregunta',
                ];
            }

            // Dimension distribution charts (one per factor)
            $dimensionDistributions = $reportPdfService->calculateDimensionDistributions($likertData);
            if (! empty($dimensionDistributions)) {
                foreach ($dimensionDistributions as $dimensionName => $levelDistribution) {
                    $safeDimName = preg_replace('/[^a-zA-Z0-9]/', '_', $dimensionName);
                    $chartDefinitions[] = [
                        'type' => 'factor-distribution',
                        'distribution' => $levelDistribution,
                        'outputPath' => $tempDir.'/factor_'.$safeDimName.'_'.$imageSuffix.'.png',
                        'title' => $dimensionName,
                    ];
                }
            }
        }

        foreach ($sectionsData as $sectionData) {
            $imageSuffix = $sectionData['imageSuffix'];

            // Clima Laboral charts
            $pieClimaPath = $tempDir.'/pie_clima_'.$imageSuffix.'.png';
            $barClimaPath = $tempDir.'/bar_clima_'.$imageSuffix.'.png';

            $chartDefinitions[] = [
                'type' => 'pie',
                'distribution' => $sectionData['climaLaboralDistribution'],
                'outputPath' => $pieClimaPath,
                'title' => 'Distribución de Clima Laboral',
            ];

            $chartDefinitions[] = [
                'type' => 'bar',
                'distribution' => $sectionData['climaLaboralDistribution'],
                'outputPath' => $barClimaPath,
                'title' => 'Conteo por Nivel',
            ];

            // Dimension charts
            foreach ($sectionData['dimensions'] as $dimName => $dimData) {
                $safeDimName = preg_replace('/[^a-zA-Z0-9]/', '_', $dimName);
                $distribution = $dimData['distribution'] ?? [];

                $pieDimPath = $tempDir.'/pie_'.$safeDimName.'_'.$imageSuffix.'.png';
                $barDimPath = $tempDir.'/bar_'.$safeDimName.'_'.$imageSuffix.'.png';

                $chartDefinitions[] = [
                    'type' => 'pie',
                    'distribution' => $distribution,
                    'outputPath' => $pieDimPath,
                    'title' => $dimName,
                ];

                $chartDefinitions[] = [
                    'type' => 'bar',
                    'distribution' => $distribution,
                    'outputPath' => $barDimPath,
                    'title' => 'Distribución - '.$dimName,
                ];
            }
        }

        // Generate all charts in parallel batches
        $results = $chartImageService->generateChartsBatch($chartDefinitions);

        // Build path map and track for cleanup
        $pathMap = [];
        foreach ($results as $outputPath => $success) {
            if ($success && file_exists($outputPath)) {
                $pathMap[$outputPath] = $outputPath;
                $this->tempImagePaths[] = $outputPath;
            }
        }

        return $pathMap;
    }

    /**
     * Add a complete filtered report section using pre-generated images
     *
     * @param  array<string, mixed>  $fullLikertData
     * @param  array<int, array>  $evaluations
     * @param  array<string, int>  $climaLaboralDistribution
     * @param  array<string, array>  $dimensionSummaries
     * @param  array<string, string>  $chartPaths
     */
    protected function addFilteredReportSectionWithPregenerated(
        \PhpOffice\PhpWord\Element\Section $section,
        array $fullLikertData,
        array $evaluations,
        array $climaLaboralDistribution,
        array $dimensionSummaries,
        string $sectionTitle,
        int $totalPeople,
        array $chartPaths,
        string $imageSuffix,
        bool $addPageBreak = true,
        bool $isFirstSection = false
    ): void {
        $tempDir = storage_path('app/temp');

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

        // Add demographic charts only for first section (TOTAL)
        if ($isFirstSection) {
            $this->addDemographicCharts($section, $chartPaths, $tempDir, $imageSuffix);
        }

        // Clima Laboral section with pre-generated charts
        $this->addClimaLaboralSectionWithPregeneratedCharts(
            $section,
            $climaLaboralDistribution,
            $evaluations,
            $fullLikertData,
            $chartPaths,
            $tempDir,
            $imageSuffix
        );

        // Each dimension section with pre-generated charts
        foreach ($dimensionSummaries as $dimName => $dimData) {
            $this->addDimensionSectionWithPregeneratedCharts(
                $section,
                $dimName,
                $dimData,
                $evaluations,
                $chartPaths,
                $tempDir,
                $imageSuffix
            );
        }
    }

    /**
     * Add Clima Laboral section using pre-generated chart images
     *
     * @param  array<string, int>  $distribution
     * @param  array<int, array>  $evaluations
     * @param  array<string, mixed>  $fullLikertData
     * @param  array<string, string>  $chartPaths
     */
    protected function addClimaLaboralSectionWithPregeneratedCharts(
        \PhpOffice\PhpWord\Element\Section $section,
        array $distribution,
        array $evaluations,
        array $fullLikertData,
        array $chartPaths,
        string $tempDir,
        string $imageSuffix
    ): void {
        $section->addTitle('Clima Laboral General', 2);
        $section->addTextBreak(1);

        // Distribution table
        $this->addDistributionTable($section, $distribution);
        $section->addTextBreak(1);

        // Pie chart (pre-generated)
        $pieChartPath = $tempDir.'/pie_clima_'.$imageSuffix.'.png';
        if (isset($chartPaths[$pieChartPath]) && file_exists($pieChartPath)) {
            $section->addImage($pieChartPath, [
                'width' => 350,
                'height' => 300,
                'alignment' => Jc::CENTER,
            ]);
        }
        $section->addTextBreak(1);

        // Bar chart (pre-generated)
        $barChartPath = $tempDir.'/bar_clima_'.$imageSuffix.'.png';
        if (isset($chartPaths[$barChartPath]) && file_exists($barChartPath)) {
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

        // Add native Word table heat map
        $this->addHeatMapTableNative($section, $evaluations, $fullLikertData['dimensions']);
    }

    /**
     * Add dimension section using pre-generated chart images
     *
     * @param  array<string, mixed>  $dimData
     * @param  array<int, array>  $evaluations
     * @param  array<string, string>  $chartPaths
     */
    protected function addDimensionSectionWithPregeneratedCharts(
        \PhpOffice\PhpWord\Element\Section $section,
        string $dimName,
        array $dimData,
        array $evaluations,
        array $chartPaths,
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

        // Pie chart (pre-generated)
        $pieChartPath = $tempDir.'/pie_'.$safeDimName.'_'.$imageSuffix.'.png';
        if (isset($chartPaths[$pieChartPath]) && file_exists($pieChartPath)) {
            $section->addImage($pieChartPath, [
                'width' => 350,
                'height' => 300,
                'alignment' => Jc::CENTER,
            ]);
        }
        $section->addTextBreak(1);

        // Bar chart (pre-generated)
        $barChartPath = $tempDir.'/bar_'.$safeDimName.'_'.$imageSuffix.'.png';
        if (isset($chartPaths[$barChartPath]) && file_exists($barChartPath)) {
            $section->addImage($barChartPath, [
                'width' => 400,
                'height' => 280,
                'alignment' => Jc::CENTER,
            ]);
        }
        $section->addTextBreak(1);

        // Dimension-specific heat map (native Word table)
        $section->addText(
            'Mapa de Calor - '.$dimName,
            ['bold' => true, 'size' => 11, 'color' => '1e40af']
        );
        $section->addTextBreak(1);

        $this->addDimensionHeatMapTableNative($section, $evaluations, $questionNumbers);
    }

    /**
     * Add demographic charts section to Word document
     *
     * @param  array<string, string>  $chartPaths
     */
    protected function addDemographicCharts(
        \PhpOffice\PhpWord\Element\Section $section,
        array $chartPaths,
        string $tempDir,
        string $imageSuffix
    ): void {
        $section->addTitle('Datos Demográficos', 2);
        $section->addTextBreak(1);

        $section->addText(
            'Distribución de participantes según características demográficas',
            ['size' => 11, 'italic' => true, 'color' => '666666']
        );
        $section->addTextBreak(1);

        // Gender distribution chart
        $genderChartPath = $tempDir.'/demo_gender_'.$imageSuffix.'.png';
        if (isset($chartPaths[$genderChartPath]) && file_exists($genderChartPath)) {
            $section->addText(
                'Distribución por Género',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($genderChartPath, [
                'width' => 400,
                'height' => 300,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Contract type distribution chart
        $contractChartPath = $tempDir.'/demo_contract_'.$imageSuffix.'.png';
        if (isset($chartPaths[$contractChartPath]) && file_exists($contractChartPath)) {
            $section->addText(
                'Distribución por Tipo de Contrato',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($contractChartPath, [
                'width' => 400,
                'height' => 300,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Position distribution chart (horizontal bar for many categories)
        $positionChartPath = $tempDir.'/demo_position_'.$imageSuffix.'.png';
        if (isset($chartPaths[$positionChartPath]) && file_exists($positionChartPath)) {
            $section->addPageBreak();
            $section->addText(
                'Distribución por Puesto',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($positionChartPath, [
                'width' => 550,
                'height' => 450,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Shift distribution chart
        $shiftChartPath = $tempDir.'/demo_shift_'.$imageSuffix.'.png';
        if (isset($chartPaths[$shiftChartPath]) && file_exists($shiftChartPath)) {
            $section->addText(
                'Distribución por Turno',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($shiftChartPath, [
                'width' => 400,
                'height' => 300,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Area distribution chart (horizontal bar for many categories)
        $areaChartPath = $tempDir.'/demo_area_'.$imageSuffix.'.png';
        if (isset($chartPaths[$areaChartPath]) && file_exists($areaChartPath)) {
            $section->addPageBreak();
            $section->addText(
                'Distribución por Área',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($areaChartPath, [
                'width' => 550,
                'height' => 450,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Add page break before cross-tabulation charts
        $section->addPageBreak();

        // Cross-tabulation section title
        $section->addTitle('Cruces Demográficos por Género', 2);
        $section->addTextBreak(1);

        $section->addText(
            'Distribución de género por diferentes categorías demográficas',
            ['size' => 11, 'italic' => true, 'color' => '666666']
        );
        $section->addTextBreak(1);

        // Gender x Position chart
        $genderPositionPath = $tempDir.'/cross_gender_position_'.$imageSuffix.'.png';
        if (isset($chartPaths[$genderPositionPath]) && file_exists($genderPositionPath)) {
            $section->addText(
                'Género por Puesto',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($genderPositionPath, [
                'width' => 550,
                'height' => 340,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Gender x Area chart
        $genderAreaPath = $tempDir.'/cross_gender_area_'.$imageSuffix.'.png';
        if (isset($chartPaths[$genderAreaPath]) && file_exists($genderAreaPath)) {
            $section->addText(
                'Género por Área',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($genderAreaPath, [
                'width' => 550,
                'height' => 340,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Gender x Shift chart
        $genderShiftPath = $tempDir.'/cross_gender_shift_'.$imageSuffix.'.png';
        if (isset($chartPaths[$genderShiftPath]) && file_exists($genderShiftPath)) {
            $section->addText(
                'Género por Turno',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($genderShiftPath, [
                'width' => 550,
                'height' => 340,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Gender x Contract chart
        $genderContractPath = $tempDir.'/cross_gender_contract_'.$imageSuffix.'.png';
        if (isset($chartPaths[$genderContractPath]) && file_exists($genderContractPath)) {
            $section->addText(
                'Género por Tipo de Contrato',
                ['bold' => true, 'size' => 11, 'color' => '1e40af']
            );
            $section->addTextBreak(0.5);
            $section->addImage($genderContractPath, [
                'width' => 550,
                'height' => 340,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Add page break before question scores chart
        $section->addPageBreak();

        // Question scores section
        $section->addTitle('Puntaje Total por Pregunta', 2);
        $section->addTextBreak(1);

        $section->addText(
            'Suma de los puntajes de todos los participantes por cada pregunta (máximo = 4 × número de participantes)',
            ['size' => 11, 'italic' => true, 'color' => '666666']
        );
        $section->addTextBreak(1);

        // Question scores chart (wide)
        $questionScoresPath = $tempDir.'/question_scores_'.$imageSuffix.'.png';
        if (isset($chartPaths[$questionScoresPath]) && file_exists($questionScoresPath)) {
            $section->addImage($questionScoresPath, [
                'width' => 650,
                'height' => 350,
                'alignment' => Jc::CENTER,
            ]);
            $section->addTextBreak(1);
        }

        // Add page break before factor distribution charts
        $section->addPageBreak();

        // Factor distribution section
        $section->addTitle('Distribución de Personas por Factor', 2);
        $section->addTextBreak(1);

        $section->addText(
            'Cantidad de personas clasificadas en cada nivel de acuerdo para cada factor evaluado',
            ['size' => 11, 'italic' => true, 'color' => '666666']
        );
        $section->addTextBreak(1);

        // Individual factor distribution charts (vertical bars)
        $niveles = config('likert-value.niveles', []);
        $factorCount = 0;
        foreach ($niveles as $dimensionName => $dimConfig) {
            $safeDimName = preg_replace('/[^a-zA-Z0-9]/', '_', $dimensionName);
            $factorChartPath = $tempDir.'/factor_'.$safeDimName.'_'.$imageSuffix.'.png';

            if (isset($chartPaths[$factorChartPath]) && file_exists($factorChartPath)) {
                // Add page break every 2 charts (after the first 2)
                if ($factorCount > 0 && $factorCount % 2 === 0) {
                    $section->addPageBreak();
                }

                $section->addText(
                    $dimensionName,
                    ['bold' => true, 'size' => 11, 'color' => '1e40af']
                );
                $section->addTextBreak(0.5);
                $section->addImage($factorChartPath, [
                    'width' => 450,
                    'height' => 280,
                    'alignment' => Jc::CENTER,
                ]);
                $section->addTextBreak(1);
                $factorCount++;
            }
        }

        $section->addTextBreak(1);
    }

    /**
     * Add a complete filtered report section (Clima Laboral + all dimensions)
     *
     * @deprecated Use addFilteredReportSectionWithPregenerated instead
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

        // Get all question numbers from dimensions for native table
        $allQuestions = [];
        foreach ($fullLikertData['dimensions'] as $dimData) {
            $questionKeys = array_keys($dimData['questions'] ?? []);
            $allQuestions = array_merge($allQuestions, $questionKeys);
        }

        // Add native Word table heat map (no images - eliminates ~3,500 image generations)
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

        // Dimension-specific heat map (native Word table only - no images)
        $section->addText(
            'Mapa de Calor - '.$dimName,
            ['bold' => true, 'size' => 11, 'color' => '1e40af']
        );
        $section->addTextBreak(1);

        // Add native Word table for this dimension (eliminates generation of ~3,500 images per report)
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
