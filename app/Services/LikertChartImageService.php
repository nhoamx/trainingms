<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class LikertChartImageService
{
    protected const MAX_TABLE_HEIGHT_PX = 1000;

    protected const ROWS_PER_HEATMAP_PAGE = 40;

    /**
     * Standardized colors for Likert levels
     */
    protected const LEVEL_COLORS = [
        'Totalmente de Acuerdo' => '#60a5fa', // blue-400
        'De Acuerdo' => '#16a34a',            // green-600
        'Desacuerdo' => '#eab308',            // yellow-500
        'Totalmente Desacuerdo' => '#dc2626', // red-600
    ];

    /**
     * Generate pie chart image for clima laboral distribution
     *
     * @param  array<string, int>  $distribution
     */
    public function generatePieChartImage(array $distribution, string $outputPath, ?string $title = null): bool
    {
        try {
            $html = $this->renderPieChartHtml($distribution, $title);

            $this->configureBrowsershot($html)
                ->windowSize(600, 550)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating Likert pie chart image', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath,
            ]);

            return false;
        }
    }

    /**
     * Generate horizontal bar chart image showing count and percentage per level
     *
     * @param  array<string, int>  $distribution
     */
    public function generateBarChartImage(array $distribution, string $outputPath, ?string $title = null): bool
    {
        try {
            $html = $this->renderBarChartHtml($distribution, $title);

            $this->configureBrowsershot($html)
                ->windowSize(700, 450)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating Likert bar chart image', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath,
            ]);

            return false;
        }
    }

    /**
     * Generate heat map image for a specific dimension or all questions
     *
     * @param  array<int, array{folio: string, personal_folio: string, answers: array<int|string, string>}>  $evaluations
     * @param  array<int>  $questionNumbers  Array of question numbers to include
     * @return array<string> Array of generated image paths (paginated)
     */
    public function generateDimensionHeatMapImages(
        array $evaluations,
        array $questionNumbers,
        string $outputDir,
        string $prefix = 'heatmap',
        ?string $title = null
    ): array {
        if (empty($evaluations) || empty($questionNumbers)) {
            return [];
        }

        try {
            if (! file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $imagePaths = [];
            $chunks = array_chunk($evaluations, self::ROWS_PER_HEATMAP_PAGE);

            foreach ($chunks as $index => $chunk) {
                $html = $this->renderDimensionHeatMapHtml($chunk, $questionNumbers, $title, $index + 1, count($chunks));
                $outputPath = $outputDir.'/'.$prefix.'_'.($index + 1).'.png';

                $this->configureBrowsershot($html)
                    ->windowSize(1200, self::MAX_TABLE_HEIGHT_PX)
                    ->fullPage()
                    ->save($outputPath);

                if (file_exists($outputPath)) {
                    $imagePaths[] = $outputPath;
                }
            }

            return $imagePaths;
        } catch (\Exception $e) {
            Log::error('Error generating dimension heat map images', [
                'error' => $e->getMessage(),
                'output_dir' => $outputDir,
            ]);

            return [];
        }
    }

    /**
     * Generate heat map table images (may produce multiple images for large tables)
     *
     * @param  array<int, array{folio: string, answers: array<int|string, string>}>  $evaluations
     * @param  array<string, array{questions: array<int>}>  $dimensions
     * @return array<string> Array of generated image paths
     */
    public function generateHeatMapImages(array $evaluations, array $dimensions, string $outputDir, string $prefix = 'heatmap'): array
    {
        if (empty($evaluations)) {
            return [];
        }

        try {
            // Ensure output directory exists
            if (! file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $imagePaths = [];
            $rowsPerChunk = $this->calculateRowsPerChunk(count($evaluations));
            $chunks = array_chunk($evaluations, $rowsPerChunk);

            foreach ($chunks as $index => $chunk) {
                $html = $this->renderHeatMapHtml($chunk, $dimensions, $index + 1, count($chunks));
                $outputPath = $outputDir.'/'.$prefix.'_'.($index + 1).'.png';

                $this->configureBrowsershot($html)
                    ->windowSize(1200, self::MAX_TABLE_HEIGHT_PX)
                    ->fullPage()
                    ->save($outputPath);

                if (file_exists($outputPath)) {
                    $imagePaths[] = $outputPath;
                }
            }

            return $imagePaths;
        } catch (\Exception $e) {
            Log::error('Error generating Likert heat map images', [
                'error' => $e->getMessage(),
                'output_dir' => $outputDir,
            ]);

            return [];
        }
    }

    /**
     * Calculate how many rows fit in each chunk based on max height
     */
    protected function calculateRowsPerChunk(int $totalRows): int
    {
        // Estimate: header ~60px, each row ~30px
        $availableHeight = self::MAX_TABLE_HEIGHT_PX - 100; // Leave room for header
        $rowHeight = 30;
        $rowsPerChunk = (int) floor($availableHeight / $rowHeight);

        return max(10, min($rowsPerChunk, $totalRows)); // At least 10 rows, max all rows
    }

    /**
     * Render pie chart HTML with Chart.js
     *
     * @param  array<string, int>  $distribution
     */
    protected function renderPieChartHtml(array $distribution, ?string $title = null): string
    {
        $labels = array_keys($distribution);
        $data = array_values($distribution);

        $backgroundColors = [];
        foreach ($labels as $label) {
            $backgroundColors[] = self::LEVEL_COLORS[$label] ?? '#9ca3af';
        }

        $labelsJson = json_encode($labels);
        $dataJson = json_encode($data);
        $colorsJson = json_encode($backgroundColors);
        $titleHtml = $title ? "<h2 class=\"chart-title\">{$title}</h2>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: white;
            font-family: Arial, sans-serif;
        }
        .chart-title {
            text-align: center;
            color: #1e40af;
            font-size: 16px;
            margin: 0 0 10px 0;
        }
        .chart-container {
            width: 560px;
            height: 460px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        canvas {
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-container">
        <canvas id="pieChart"></canvas>
    </div>
    <script>
        Chart.register(ChartDataLabels);
        
        const ctx = document.getElementById('pieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: {$labelsJson},
                datasets: [{
                    data: {$dataJson},
                    backgroundColor: {$colorsJson},
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 12 },
                            padding: 15
                        }
                    },
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: (value, context) => {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return value > 0 ? value + ' (' + percentage + '%)' : '';
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render horizontal bar chart HTML with Chart.js
     *
     * @param  array<string, int>  $distribution
     */
    protected function renderBarChartHtml(array $distribution, ?string $title = null): string
    {
        $labels = array_keys($distribution);
        $data = array_values($distribution);
        $total = array_sum($data);

        $backgroundColors = [];
        foreach ($labels as $label) {
            $backgroundColors[] = self::LEVEL_COLORS[$label] ?? '#9ca3af';
        }

        $labelsJson = json_encode($labels);
        $dataJson = json_encode($data);
        $colorsJson = json_encode($backgroundColors);
        $titleHtml = $title ? "<h2 class=\"chart-title\">{$title}</h2>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: white;
            font-family: Arial, sans-serif;
        }
        .chart-title {
            text-align: center;
            color: #1e40af;
            font-size: 16px;
            margin: 0 0 10px 0;
        }
        .chart-container {
            width: 660px;
            height: 380px;
        }
        canvas {
            max-width: 100%;
            max-height: 100%;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-container">
        <canvas id="barChart"></canvas>
    </div>
    <script>
        Chart.register(ChartDataLabels);
        
        const total = {$total};
        const ctx = document.getElementById('barChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {$labelsJson},
                datasets: [{
                    data: {$dataJson},
                    backgroundColor: {$colorsJson},
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#333',
                        font: {
                            weight: 'bold',
                            size: 12
                        },
                        formatter: (value) => {
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return value + ' (' + percentage + '%)';
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render dimension-specific heat map HTML
     *
     * @param  array<int, array{folio: string, personal_folio: string, answers: array<int|string, string>}>  $evaluations
     * @param  array<int>  $questionNumbers
     */
    protected function renderDimensionHeatMapHtml(array $evaluations, array $questionNumbers, ?string $title, int $pageNumber, int $totalPages): string
    {
        // Build table rows
        $tableRows = '';
        foreach ($evaluations as $eval) {
            $folio = $eval['personal_folio'] ?? $eval['folio'] ?? 'N/A';
            $answers = $eval['answers'] ?? [];

            $cells = "<td class=\"folio-cell\">{$folio}</td>";
            foreach ($questionNumbers as $qNum) {
                $answer = $answers[$qNum] ?? $answers[(string) $qNum] ?? '';
                $numericValue = $this->getAnswerNumericValue($answer);
                $colorClass = $this->getAnswerColorClass($answer);
                $cells .= "<td class=\"answer-cell {$colorClass}\">{$numericValue}</td>";
            }
            $tableRows .= "<tr>{$cells}</tr>";
        }

        // Build question numbers header row
        $questionHeaderCells = '<th class="folio-header">Folio</th>';
        foreach ($questionNumbers as $qNum) {
            $questionHeaderCells .= "<th class=\"question-header\">{$qNum}</th>";
        }

        $titleHtml = $title ? "<h2 class=\"heatmap-title\">{$title}</h2>" : '';
        $pageInfo = $totalPages > 1 ? "<div class=\"page-info\">Página {$pageNumber} de {$totalPages}</div>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 10px;
            background: white;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .heatmap-title {
            text-align: center;
            color: #1e40af;
            font-size: 14px;
            margin: 0 0 10px 0;
        }
        .page-info {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: center;
            font-size: 9px;
        }
        .folio-header, .folio-cell {
            background: #f9fafb;
            font-weight: bold;
            position: sticky;
            left: 0;
            min-width: 60px;
            text-align: left;
        }
        .question-header {
            background: #f3f4f6;
            font-weight: bold;
        }
        .answer-cell {
            font-weight: bold;
            min-width: 30px;
        }
        .color-4 { background: #60a5fa; color: white; }
        .color-3 { background: #16a34a; color: white; }
        .color-2 { background: #eab308; color: black; }
        .color-1 { background: #dc2626; color: white; }
        .color-0 { background: #9ca3af; color: white; }
    </style>
</head>
<body>
    {$titleHtml}
    {$pageInfo}
    <table>
        <thead>
            <tr>{$questionHeaderCells}</tr>
        </thead>
        <tbody>
            {$tableRows}
        </tbody>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Render heat map table HTML
     *
     * @param  array<int, array{folio: string, personal_folio: string, answers: array<int|string, string>}>  $evaluations
     * @param  array<string, array{questions: array<int>}>  $dimensions
     */
    protected function renderHeatMapHtml(array $evaluations, array $dimensions, int $pageNumber, int $totalPages): string
    {
        // Build question numbers list from dimensions
        $allQuestions = [];
        $dimensionHeaders = [];
        foreach ($dimensions as $dimName => $dimData) {
            $questions = $dimData['questions'] ?? [];
            if (is_array($questions)) {
                $questionNumbers = array_keys($questions);
                $dimensionHeaders[] = [
                    'name' => $dimName,
                    'colspan' => count($questionNumbers),
                ];
                $allQuestions = array_merge($allQuestions, $questionNumbers);
            }
        }

        // Build table rows
        $tableRows = '';
        foreach ($evaluations as $eval) {
            $folio = $eval['personal_folio'] ?? $eval['folio'] ?? 'N/A';
            $answers = $eval['answers'] ?? [];

            $cells = "<td class=\"folio-cell\">{$folio}</td>";
            foreach ($allQuestions as $qNum) {
                $answer = $answers[$qNum] ?? $answers[(string) $qNum] ?? '';
                $numericValue = $this->getAnswerNumericValue($answer);
                $colorClass = $this->getAnswerColorClass($answer);
                $cells .= "<td class=\"answer-cell {$colorClass}\">{$numericValue}</td>";
            }
            $tableRows .= "<tr>{$cells}</tr>";
        }

        // Build dimension headers row
        $dimensionHeaderCells = '<th class="folio-header">Folio</th>';
        foreach ($dimensionHeaders as $header) {
            $dimensionHeaderCells .= "<th colspan=\"{$header['colspan']}\" class=\"dimension-header\">{$header['name']}</th>";
        }

        // Build question numbers row
        $questionHeaderCells = '<th class="folio-header">#</th>';
        foreach ($allQuestions as $qNum) {
            $questionHeaderCells .= "<th class=\"question-header\">{$qNum}</th>";
        }

        $pageInfo = $totalPages > 1 ? "<div class=\"page-info\">Página {$pageNumber} de {$totalPages}</div>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 10px;
            background: white;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .page-info {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: center;
            font-size: 9px;
        }
        .folio-header, .folio-cell {
            background: #f9fafb;
            font-weight: bold;
            position: sticky;
            left: 0;
            min-width: 60px;
            text-align: left;
        }
        .dimension-header {
            background: #e5e7eb;
            font-weight: bold;
            font-size: 8px;
        }
        .question-header {
            background: #f3f4f6;
            font-weight: bold;
        }
        .answer-cell {
            font-weight: bold;
            min-width: 25px;
        }
        /* Color classes matching Likert values (4=best, 1=worst) */
        .color-4 { background: #60a5fa; color: white; } /* blue-400 - Totalmente de Acuerdo */
        .color-3 { background: #16a34a; color: white; } /* green-600 - De Acuerdo */
        .color-2 { background: #eab308; color: black; } /* yellow-500 - Desacuerdo */
        .color-1 { background: #dc2626; color: white; } /* red-600 - Totalmente Desacuerdo */
        .color-0 { background: #9ca3af; color: white; } /* gray - No answer */
    </style>
</head>
<body>
    {$pageInfo}
    <table>
        <thead>
            <tr>{$dimensionHeaderCells}</tr>
            <tr>{$questionHeaderCells}</tr>
        </thead>
        <tbody>
            {$tableRows}
        </tbody>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Get numeric value for answer letter
     */
    protected function getAnswerNumericValue(?string $answer): string
    {
        $values = ['A' => '4', 'B' => '3', 'C' => '2', 'D' => '1'];

        return $values[strtoupper($answer ?? '')] ?? '-';
    }

    /**
     * Get color class for answer
     */
    protected function getAnswerColorClass(?string $answer): string
    {
        $values = ['A' => 4, 'B' => 3, 'C' => 2, 'D' => 1];
        $value = $values[strtoupper($answer ?? '')] ?? 0;

        return 'color-'.$value;
    }

    /**
     * Configure Browsershot with common settings
     */
    protected function configureBrowsershot(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->deviceScaleFactor(2);

        // Add --no-sandbox flag for production Linux servers
        if (PHP_OS_FAMILY === 'Linux' && app()->isProduction()) {
            $browsershot->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
            ]);
        }

        return $browsershot;
    }
}
