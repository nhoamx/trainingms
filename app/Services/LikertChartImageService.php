<?php

namespace App\Services;

use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class LikertChartImageService
{
    protected const MAX_TABLE_HEIGHT_PX = 1000;

    protected const ROWS_PER_HEATMAP_PAGE = 40;

    /**
     * Maximum number of concurrent chart generations
     * Optimized for typical server resources (4-6 cores)
     */
    protected const MAX_CONCURRENT_CHARTS = 4;

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
     * Generate vertical bar chart image for demographic data
     *
     * @param  array<string, int>  $distribution
     */
    public function generateVerticalBarChartImage(array $distribution, string $outputPath, ?string $title = null): bool
    {
        try {
            $html = $this->renderVerticalBarChartHtml($distribution, $title);

            $this->configureBrowsershot($html)
                ->windowSize(700, 500)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating demographic vertical bar chart image', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath,
            ]);

            return false;
        }
    }

    /**
     * Generate horizontal bar chart image for demographic distributions with many categories
     * Ideal for Area and Puesto distributions
     *
     * @param  array<string, int>  $distribution
     */
    public function generateDemographicHorizontalBarChartImage(array $distribution, string $outputPath, ?string $title = null): bool
    {
        try {
            // Sort by count descending
            arsort($distribution);

            // Calculate height based on number of categories (min 400, max 1000)
            $categoryCount = count($distribution);
            $height = min(1000, max(400, 80 + ($categoryCount * 35)));

            $html = $this->renderDemographicHorizontalBarChartHtml($distribution, $title);

            $this->configureBrowsershot($html)
                ->windowSize(900, $height)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating demographic horizontal bar chart image', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath,
            ]);

            return false;
        }
    }

    /**
     * Generate grouped bar chart image for gender cross-tabulation
     *
     * @param  array<string, array<string, int>>  $crossData  Format: ['Category' => ['Masculino' => 5, 'Femenino' => 3]]
     */
    public function generateGenderCrossChartImage(array $crossData, string $outputPath, ?string $title = null): bool
    {
        try {
            $html = $this->renderGenderCrossChartHtml($crossData, $title);

            $this->configureBrowsershot($html)
                ->windowSize(900, 550)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating gender cross chart image', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath,
            ]);

            return false;
        }
    }

    /**
     * Generate stacked horizontal bar chart for dimension distributions
     * Shows how many people are in each level for each dimension
     *
     * @param  array<string, array<string, int>>  $dimensionDistributions  Format: ['Dimension' => ['Level' => count]]
     */
    public function generateDimensionDistributionChartImage(array $dimensionDistributions, string $outputPath, ?string $title = null): bool
    {
        try {
            // Calculate height based on number of dimensions (min 400, max 800)
            $dimensionCount = count($dimensionDistributions);
            $height = min(800, max(400, 100 + ($dimensionCount * 50)));

            $html = $this->renderDimensionDistributionChartHtml($dimensionDistributions, $title);

            $this->configureBrowsershot($html)
                ->windowSize(950, $height)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating dimension distribution chart image', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath,
            ]);

            return false;
        }
    }

    /**
     * Generate vertical bar chart for a single factor (dimension) distribution
     * Shows count of people in each level for one factor
     *
     * @param  array<string, int>  $levelDistribution  Format: ['Level' => count]
     */
    public function generateFactorDistributionChartImage(array $levelDistribution, string $outputPath, ?string $title = null): bool
    {
        try {
            $html = $this->renderFactorDistributionChartHtml($levelDistribution, $title);

            $this->configureBrowsershot($html)
                ->windowSize(700, 450)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating factor distribution chart image', [
                'error' => $e->getMessage(),
                'output_path' => $outputPath,
            ]);

            return false;
        }
    }

    /**
     * Generate wide vertical bar chart for question scores
     *
     * @param  array<int, array{question: int, score: int, maxScore: int, label: string}>  $questionScores
     */
    public function generateQuestionScoresChartImage(array $questionScores, string $outputPath, ?string $title = null): bool
    {
        try {
            $html = $this->renderQuestionScoresChartHtml($questionScores, $title);

            $this->configureBrowsershot($html)
                ->windowSize(1200, 600)
                ->save($outputPath);

            return file_exists($outputPath);
        } catch (\Exception $e) {
            Log::error('Error generating question scores chart image', [
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
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 520px;
            height: 340px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        canvas {
            max-width: 100%;
            max-height: 100%;
        }
        .legend-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            line-height: 1.4;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
            flex-shrink: 0;
        }
        .legend-label {
            flex: 1;
            font-weight: 600;
            color: #374151;
        }
        .legend-stats {
            display: flex;
            gap: 10px;
            font-size: 12px;
            color: #6b7280;
        }
        .legend-count {
            font-weight: 500;
        }
        .legend-percentage {
            font-weight: 500;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>
        <div class="legend-container" id="customLegend"></div>
    </div>
    <script>
        // Datos
        const labels = {$labelsJson};
        const data = {$dataJson};
        const colors = {$colorsJson};
        const total = data.reduce((a, b) => a + b, 0);
        
        // Generar leyenda personalizada
        const legendContainer = document.getElementById('customLegend');
        labels.forEach((label, index) => {
            const value = data[index];
            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
            
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            
            const colorBox = document.createElement('div');
            colorBox.className = 'legend-color';
            colorBox.style.backgroundColor = colors[index];
            
            const labelSpan = document.createElement('div');
            labelSpan.className = 'legend-label';
            labelSpan.textContent = label;
            
            const statsDiv = document.createElement('div');
            statsDiv.className = 'legend-stats';
            
            const countSpan = document.createElement('span');
            countSpan.className = 'legend-count';
            countSpan.textContent = value + ' personas';
            
            const percentSpan = document.createElement('span');
            percentSpan.className = 'legend-percentage';
            percentSpan.textContent = percentage + '%';
            
            statsDiv.appendChild(countSpan);
            statsDiv.appendChild(percentSpan);
            
            legendItem.appendChild(colorBox);
            legendItem.appendChild(labelSpan);
            legendItem.appendChild(statsDiv);
            
            legendContainer.appendChild(legendItem);
        });
        
        const ctx = document.getElementById('pieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value + ' (' + percentage + '%)';
                            }
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
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 620px;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        canvas {
            max-width: 100%;
            max-height: 100%;
        }
        .legend-container {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 0 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            line-height: 1.4;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
            flex-shrink: 0;
        }
        .legend-label {
            flex: 1;
            font-weight: 600;
            color: #374151;
        }
        .legend-stats {
            display: flex;
            gap: 10px;
            font-size: 12px;
            color: #6b7280;
        }
        .legend-count {
            font-weight: 500;
        }
        .legend-percentage {
            font-weight: 500;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>
        <div class="legend-container" id="customLegend"></div>
    </div>
    <script>
        // Datos
        const labels = {$labelsJson};
        const data = {$dataJson};
        const colors = {$colorsJson};
        const total = data.reduce((a, b) => a + b, 0);
        
        // Generar leyenda personalizada en dos columnas
        const legendContainer = document.getElementById('customLegend');
        labels.forEach((label, index) => {
            const value = data[index];
            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
            
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            
            const colorBox = document.createElement('div');
            colorBox.className = 'legend-color';
            colorBox.style.backgroundColor = colors[index];
            
            const labelSpan = document.createElement('div');
            labelSpan.className = 'legend-label';
            labelSpan.textContent = label;
            
            const statsDiv = document.createElement('div');
            statsDiv.className = 'legend-stats';
            
            const countSpan = document.createElement('span');
            countSpan.className = 'legend-count';
            countSpan.textContent = value + ' personas';
            
            const percentSpan = document.createElement('span');
            percentSpan.className = 'legend-percentage';
            percentSpan.textContent = percentage + '%';
            
            statsDiv.appendChild(countSpan);
            statsDiv.appendChild(percentSpan);
            
            legendItem.appendChild(colorBox);
            legendItem.appendChild(labelSpan);
            legendItem.appendChild(statsDiv);
            
            legendContainer.appendChild(legendItem);
        });
        
        const ctx = document.getElementById('barChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
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
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.x;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
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
     * Render vertical bar chart HTML for demographic data
     *
     * @param  array<string, int>  $distribution
     */
    protected function renderVerticalBarChartHtml(array $distribution, ?string $title = null): string
    {
        $labels = array_keys($distribution);
        $data = array_values($distribution);
        $total = array_sum($data);

        // Use distinct colors for demographic data
        $demographicColors = [
            '#3b82f6', // blue-500
            '#10b981', // green-500
            '#f59e0b', // amber-500
            '#ef4444', // red-500
            '#8b5cf6', // violet-500
            '#ec4899', // pink-500
            '#14b8a6', // teal-500
            '#f97316', // orange-500
        ];

        $backgroundColors = [];
        foreach ($labels as $index => $label) {
            $backgroundColors[] = $demographicColors[$index % count($demographicColors)];
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
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 620px;
            height: 320px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        canvas {
            max-width: 100%;
            max-height: 100%;
        }
        .legend-container {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px 20px;
            padding: 0 20px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            line-height: 1.3;
        }
        .legend-color {
            width: 18px;
            height: 18px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
            flex-shrink: 0;
        }
        .legend-label {
            flex: 1;
            font-weight: 600;
            color: #374151;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .legend-stats {
            display: flex;
            gap: 8px;
            font-size: 11px;
            color: #6b7280;
            flex-shrink: 0;
        }
        .legend-count {
            font-weight: 500;
        }
        .legend-percentage {
            font-weight: 500;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="verticalBarChart"></canvas>
        </div>
        <div class="legend-container" id="customLegend"></div>
    </div>
    <script>
        // Datos
        const labels = {$labelsJson};
        const data = {$dataJson};
        const colors = {$colorsJson};
        const total = data.reduce((a, b) => a + b, 0);
        
        // Generar leyenda personalizada
        const legendContainer = document.getElementById('customLegend');
        labels.forEach((label, index) => {
            const value = data[index];
            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
            
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            
            const colorBox = document.createElement('div');
            colorBox.className = 'legend-color';
            colorBox.style.backgroundColor = colors[index];
            
            const labelSpan = document.createElement('div');
            labelSpan.className = 'legend-label';
            labelSpan.textContent = label;
            labelSpan.title = label; // Tooltip for long labels
            
            const statsDiv = document.createElement('div');
            statsDiv.className = 'legend-stats';
            
            const countSpan = document.createElement('span');
            countSpan.className = 'legend-count';
            countSpan.textContent = value;
            
            const percentSpan = document.createElement('span');
            percentSpan.className = 'legend-percentage';
            percentSpan.textContent = percentage + '%';
            
            statsDiv.appendChild(countSpan);
            statsDiv.appendChild(percentSpan);
            
            legendItem.appendChild(colorBox);
            legendItem.appendChild(labelSpan);
            legendItem.appendChild(statsDiv);
            
            legendContainer.appendChild(legendItem);
        });
        
        const ctx = document.getElementById('verticalBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 1,
                    borderColor: '#ffffff',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value + ' personas (' + percentage + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280'
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const value = dataset.data[index];
                            if (value > 0) {
                                ctx.save();
                                ctx.fillStyle = '#374151';
                                ctx.font = 'bold 12px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'bottom';
                                ctx.fillText(value.toString(), bar.x, bar.y - 5);
                                ctx.restore();
                            }
                        });
                    });
                }
            }]
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render horizontal bar chart HTML for demographic distributions with many categories
     *
     * @param  array<string, int>  $distribution
     */
    protected function renderDemographicHorizontalBarChartHtml(array $distribution, ?string $title = null): string
    {
        // Sort by count descending
        arsort($distribution);

        $labels = array_keys($distribution);
        $data = array_values($distribution);
        $total = array_sum($data);

        // Use distinct colors for demographic data
        $demographicColors = [
            '#3b82f6', // blue-500
            '#10b981', // green-500
            '#f59e0b', // amber-500
            '#ef4444', // red-500
            '#8b5cf6', // violet-500
            '#ec4899', // pink-500
            '#14b8a6', // teal-500
            '#f97316', // orange-500
            '#6366f1', // indigo-500
            '#84cc16', // lime-500
            '#06b6d4', // cyan-500
            '#a855f7', // purple-500
        ];

        $backgroundColors = [];
        foreach ($labels as $index => $label) {
            $backgroundColors[] = $demographicColors[$index % count($demographicColors)];
        }

        $labelsJson = json_encode($labels);
        $dataJson = json_encode($data);
        $colorsJson = json_encode($backgroundColors);
        $titleHtml = $title ? "<h2 class=\"chart-title\">{$title}</h2>" : '';

        // Calculate height based on number of categories
        $categoryCount = count($labels);
        $chartHeight = max(300, $categoryCount * 30);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 850px;
            height: {$chartHeight}px;
        }
        canvas {
            max-width: 100%;
        }
        .total-label {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="horizontalBarChart"></canvas>
        </div>
        <div class="total-label">Total: {$total} personas</div>
    </div>
    <script>
        const labels = {$labelsJson};
        const data = {$dataJson};
        const colors = {$colorsJson};
        const total = data.reduce((a, b) => a + b, 0);
        
        const ctx = document.getElementById('horizontalBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 1,
                    borderColor: '#ffffff',
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.x;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return value + ' personas (' + percentage + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280'
                        },
                        title: {
                            display: true,
                            text: 'Número de Personas',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#374151'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '500'
                            },
                            color: '#374151',
                            padding: 8
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const value = dataset.data[index];
                            if (value > 0) {
                                ctx.save();
                                ctx.fillStyle = '#374151';
                                ctx.font = 'bold 11px Arial';
                                ctx.textAlign = 'left';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(value.toString(), bar.x + 5, bar.y);
                                ctx.restore();
                            }
                        });
                    });
                }
            }]
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render stacked horizontal bar chart HTML for dimension distributions
     * Shows each dimension as a horizontal bar with stacked levels
     *
     * @param  array<string, array<string, int>>  $dimensionDistributions  Format: ['Dimension' => ['Level' => count]]
     */
    protected function renderDimensionDistributionChartHtml(array $dimensionDistributions, ?string $title = null): string
    {
        // Dimensions are the Y-axis labels
        $dimensions = array_keys($dimensionDistributions);

        // Levels in correct order (Totalmente Desacuerdo to Totalmente de Acuerdo)
        $levels = ['Totalmente Desacuerdo', 'Desacuerdo', 'De Acuerdo', 'Totalmente de Acuerdo'];

        // Level colors (matching the Likert scale)
        $levelColors = [
            'Totalmente Desacuerdo' => '#3b82f6', // blue-500
            'Desacuerdo' => '#22c55e',            // green-500
            'De Acuerdo' => '#eab308',            // yellow-500
            'Totalmente de Acuerdo' => '#ef4444', // red-500
        ];

        // Build datasets for each level
        $datasets = [];
        foreach ($levels as $level) {
            $data = [];
            foreach ($dimensions as $dimension) {
                $data[] = $dimensionDistributions[$dimension][$level] ?? 0;
            }
            $datasets[] = [
                'label' => $level,
                'data' => $data,
                'backgroundColor' => $levelColors[$level],
                'borderRadius' => 2,
            ];
        }

        // Calculate total people
        $total = 0;
        foreach ($dimensionDistributions as $dimension => $levelData) {
            $total = max($total, array_sum($levelData));
        }

        $dimensionsJson = json_encode($dimensions);
        $datasetsJson = json_encode($datasets);
        $titleHtml = $title ? "<h2 class=\"chart-title\">{$title}</h2>" : '';

        // Calculate chart height based on number of dimensions
        $dimensionCount = count($dimensions);
        $chartHeight = max(350, $dimensionCount * 45);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 900px;
            height: {$chartHeight}px;
        }
        canvas {
            max-width: 100%;
        }
        .total-label {
            text-align: center;
            margin-top: 10px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="dimensionChart"></canvas>
        </div>
        <div class="total-label">Total: {$total} personas</div>
    </div>
    <script>
        const dimensions = {$dimensionsJson};
        const datasets = {$datasetsJson};
        
        const ctx = document.getElementById('dimensionChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dimensions,
                datasets: datasets
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'rect',
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.x + ' personas';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280'
                        },
                        title: {
                            display: true,
                            text: 'Número de Personas',
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            color: '#374151'
                        }
                    },
                    y: {
                        stacked: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: '500'
                            },
                            color: '#374151',
                            padding: 8,
                            callback: function(value, index) {
                                const label = this.getLabelForValue(value);
                                if (label.length > 25) {
                                    return label.substring(0, 25) + '...';
                                }
                                return label;
                            }
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    const datasets = chart.data.datasets;
                    const meta = chart.getDatasetMeta(datasets.length - 1);
                    
                    meta.data.forEach((bar, index) => {
                        let total = 0;
                        datasets.forEach(ds => {
                            total += ds.data[index] || 0;
                        });
                        if (total > 0) {
                            ctx.save();
                            ctx.fillStyle = '#374151';
                            ctx.font = 'bold 11px Arial';
                            ctx.textAlign = 'left';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(total.toString(), bar.x + 5, bar.y);
                            ctx.restore();
                        }
                    });
                }
            }]
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render vertical bar chart HTML for a single factor distribution
     *
     * @param  array<string, int>  $levelDistribution  Format: ['Level' => count]
     */
    protected function renderFactorDistributionChartHtml(array $levelDistribution, ?string $title = null): string
    {
        // Ensure levels are in correct order
        $orderedLevels = ['Totalmente Desacuerdo', 'Desacuerdo', 'De Acuerdo', 'Totalmente de Acuerdo'];
        $orderedDistribution = [];
        foreach ($orderedLevels as $level) {
            $orderedDistribution[$level] = $levelDistribution[$level] ?? 0;
        }

        $labels = array_keys($orderedDistribution);
        $data = array_values($orderedDistribution);
        $total = array_sum($data);

        // Level colors (matching the Likert scale)
        $levelColors = [
            '#3b82f6', // blue-500 - Totalmente Desacuerdo
            '#22c55e', // green-500 - Desacuerdo
            '#eab308', // yellow-500 - De Acuerdo
            '#ef4444', // red-500 - Totalmente de Acuerdo
        ];

        $labelsJson = json_encode($labels);
        $dataJson = json_encode($data);
        $colorsJson = json_encode($levelColors);
        $titleHtml = $title ? "<h2 class=\"chart-title\">{$title}</h2>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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
            font-size: 14px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 650px;
            height: 350px;
        }
        canvas {
            max-width: 100%;
        }
        .total-label {
            text-align: center;
            margin-top: 8px;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="factorChart"></canvas>
        </div>
        <div class="total-label">Total: {$total} personas</div>
    </div>
    <script>
        const labels = {$labelsJson};
        const data = {$dataJson};
        const colors = {$colorsJson};
        const total = data.reduce((a, b) => a + b, 0);
        
        const ctx = document.getElementById('factorChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 1,
                    borderColor: '#ffffff',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return value + ' personas (' + percentage + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: '500'
                            },
                            color: '#374151',
                            maxRotation: 15,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280'
                        },
                        title: {
                            display: true,
                            text: 'Número de Personas',
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            color: '#374151'
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const value = dataset.data[index];
                            if (value > 0) {
                                ctx.save();
                                ctx.fillStyle = '#374151';
                                ctx.font = 'bold 11px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'bottom';
                                ctx.fillText(value.toString(), bar.x, bar.y - 5);
                                ctx.restore();
                            }
                        });
                    });
                }
            }]
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render grouped bar chart HTML for gender cross-tabulation
     *
     * @param  array<string, array<string, int>>  $crossData
     */
    protected function renderGenderCrossChartHtml(array $crossData, ?string $title = null): string
    {
        $categories = array_keys($crossData);
        
        // Collect all unique genders
        $allGenders = [];
        foreach ($crossData as $genderData) {
            foreach (array_keys($genderData) as $gender) {
                $allGenders[$gender] = true;
            }
        }
        $genders = array_keys($allGenders);
        
        // Gender colors
        $genderColors = [
            'Masculino' => '#3b82f6', // blue-500
            'Femenino' => '#ec4899',  // pink-500
            'No especificado' => '#9ca3af', // gray-400
        ];
        
        // Build datasets for each gender
        $datasets = [];
        foreach ($genders as $gender) {
            $data = [];
            foreach ($categories as $category) {
                $data[] = $crossData[$category][$gender] ?? 0;
            }
            $datasets[] = [
                'label' => $gender,
                'data' => $data,
                'backgroundColor' => $genderColors[$gender] ?? '#6b7280',
                'borderRadius' => 4,
            ];
        }
        
        $categoriesJson = json_encode($categories);
        $datasetsJson = json_encode($datasets);
        $titleHtml = $title ? "<h2 class=\"chart-title\">{$title}</h2>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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
            margin: 0 0 15px 0;
            font-weight: 600;
        }
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 850px;
            height: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }
        canvas {
            max-width: 100%;
            max-height: 100%;
        }
        .legend-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            padding: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
        }
        .legend-label {
            font-weight: 600;
            color: #374151;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="groupedBarChart"></canvas>
        </div>
        <div class="legend-container" id="customLegend"></div>
    </div>
    <script>
        const categories = {$categoriesJson};
        const datasets = {$datasetsJson};
        
        // Build custom legend
        const legendContainer = document.getElementById('customLegend');
        datasets.forEach(ds => {
            const legendItem = document.createElement('div');
            legendItem.className = 'legend-item';
            
            const colorBox = document.createElement('div');
            colorBox.className = 'legend-color';
            colorBox.style.backgroundColor = ds.backgroundColor;
            
            const labelSpan = document.createElement('span');
            labelSpan.className = 'legend-label';
            labelSpan.textContent = ds.label;
            
            legendItem.appendChild(colorBox);
            legendItem.appendChild(labelSpan);
            legendContainer.appendChild(legendItem);
        });
        
        const ctx = document.getElementById('groupedBarChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' personas';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10
                            },
                            color: '#374151',
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280'
                        }
                    }
                }
            },
            plugins: [{
                afterDatasetsDraw: function(chart) {
                    const ctx = chart.ctx;
                    chart.data.datasets.forEach((dataset, i) => {
                        const meta = chart.getDatasetMeta(i);
                        meta.data.forEach((bar, index) => {
                            const value = dataset.data[index];
                            if (value > 0) {
                                ctx.save();
                                ctx.fillStyle = '#374151';
                                ctx.font = 'bold 10px Arial';
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'bottom';
                                ctx.fillText(value.toString(), bar.x, bar.y - 3);
                                ctx.restore();
                            }
                        });
                    });
                }
            }]
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render wide vertical bar chart HTML for question scores
     *
     * @param  array<int, array{question: int, score: int, maxScore: int, label: string, fullText: string}>  $questionScores
     */
    protected function renderQuestionScoresChartHtml(array $questionScores, ?string $title = null): string
    {
        $labels = [];
        $data = [];
        $maxScores = [];
        $fullTexts = [];
        
        foreach ($questionScores as $q) {
            $labels[] = $q['label'];
            $data[] = $q['score'];
            $maxScores[] = $q['maxScore'];
            $fullTexts[] = $q['fullText'] ?? '';
        }
        
        // Calculate percentages for color gradient
        $percentages = [];
        foreach ($data as $i => $score) {
            $percentages[] = $maxScores[$i] > 0 ? ($score / $maxScores[$i]) * 100 : 0;
        }
        
        // Generate colors based on percentage (red to green)
        $colors = [];
        foreach ($percentages as $pct) {
            if ($pct >= 75) {
                $colors[] = '#16a34a'; // green-600
            } elseif ($pct >= 50) {
                $colors[] = '#84cc16'; // lime-500
            } elseif ($pct >= 25) {
                $colors[] = '#eab308'; // yellow-500
            } else {
                $colors[] = '#dc2626'; // red-600
            }
        }
        
        $labelsJson = json_encode($labels);
        $dataJson = json_encode($data);
        $maxScoresJson = json_encode($maxScores);
        $colorsJson = json_encode($colors);
        $fullTextsJson = json_encode($fullTexts);
        $titleHtml = $title ? "<h2 class=\"chart-title\">{$title}</h2>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        body {
            margin: 0;
            padding: 15px;
            background: white;
            font-family: Arial, sans-serif;
        }
        .chart-title {
            text-align: center;
            color: #1e40af;
            font-size: 16px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        .chart-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .chart-container {
            width: 1150px;
            height: 450px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
        }
        canvas {
            max-width: 100%;
            max-height: 100%;
        }
        .color-legend {
            display: flex;
            justify-content: center;
            gap: 25px;
            padding: 10px;
            font-size: 12px;
        }
        .color-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .color-box {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body>
    {$titleHtml}
    <div class="chart-wrapper">
        <div class="chart-container">
            <canvas id="questionScoresChart"></canvas>
        </div>
        <div class="color-legend">
            <div class="color-legend-item">
                <div class="color-box" style="background-color: #16a34a;"></div>
                <span>≥75% (Excelente)</span>
            </div>
            <div class="color-legend-item">
                <div class="color-box" style="background-color: #84cc16;"></div>
                <span>50-74% (Bueno)</span>
            </div>
            <div class="color-legend-item">
                <div class="color-box" style="background-color: #eab308;"></div>
                <span>25-49% (Regular)</span>
            </div>
            <div class="color-legend-item">
                <div class="color-box" style="background-color: #dc2626;"></div>
                <span>&lt;25% (Crítico)</span>
            </div>
        </div>
    </div>
    <script>
        const labels = {$labelsJson};
        const data = {$dataJson};
        const maxScores = {$maxScoresJson};
        const colors = {$colorsJson};
        const fullTexts = {$fullTextsJson};
        
        const ctx = document.getElementById('questionScoresChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 1,
                    borderColor: '#ffffff',
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const idx = context[0].dataIndex;
                                return fullTexts[idx].substring(0, 80) + (fullTexts[idx].length > 80 ? '...' : '');
                            },
                            label: function(context) {
                                const idx = context.dataIndex;
                                const score = data[idx];
                                const max = maxScores[idx];
                                const pct = max > 0 ? ((score / max) * 100).toFixed(1) : 0;
                                return 'Puntaje: ' + score + ' / ' + max + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            color: '#374151'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            display: true,
                            color: '#e5e7eb'
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280'
                        },
                        title: {
                            display: true,
                            text: 'Puntaje Total',
                            font: {
                                size: 12
                            },
                            color: '#374151'
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
     * Generate multiple chart images in parallel batches
     *
     * @param  array<array{type: string, distribution: array<string, int>, outputPath: string, title: ?string}>  $chartDefinitions
     * @return array<string, bool> Map of outputPath => success
     */
    public function generateChartsBatch(array $chartDefinitions): array
    {
        if (empty($chartDefinitions)) {
            return [];
        }

        $results = [];
        $batches = array_chunk($chartDefinitions, self::MAX_CONCURRENT_CHARTS);

        foreach ($batches as $batch) {
            $closures = [];

            foreach ($batch as $chartDef) {
                $type = $chartDef['type'];
                $outputPath = $chartDef['outputPath'];
                $title = $chartDef['title'] ?? null;

                $closures[$outputPath] = function () use ($type, $chartDef, $outputPath, $title): bool {
                    try {
                        $service = new self;

                        if ($type === 'pie') {
                            return $service->generatePieChartImage($chartDef['distribution'], $outputPath, $title);
                        } elseif ($type === 'bar') {
                            return $service->generateBarChartImage($chartDef['distribution'], $outputPath, $title);
                        } elseif ($type === 'vertical-bar') {
                            return $service->generateVerticalBarChartImage($chartDef['distribution'], $outputPath, $title);
                        } elseif ($type === 'demographic-horizontal-bar') {
                            return $service->generateDemographicHorizontalBarChartImage($chartDef['distribution'], $outputPath, $title);
                        } elseif ($type === 'gender-cross') {
                            return $service->generateGenderCrossChartImage($chartDef['crossData'], $outputPath, $title);
                        } elseif ($type === 'question-scores') {
                            return $service->generateQuestionScoresChartImage($chartDef['questionScores'], $outputPath, $title);
                        } elseif ($type === 'dimension-distribution') {
                            return $service->generateDimensionDistributionChartImage($chartDef['dimensionDistributions'], $outputPath, $title);
                        } elseif ($type === 'factor-distribution') {
                            return $service->generateFactorDistributionChartImage($chartDef['distribution'], $outputPath, $title);
                        }

                        return false;
                    } catch (\Exception $e) {
                        Log::error('Error in parallel chart generation', [
                            'type' => $type,
                            'outputPath' => $outputPath,
                            'error' => $e->getMessage(),
                        ]);

                        return false;
                    }
                };
            }

            try {
                $batchResults = Concurrency::run($closures);

                foreach ($batchResults as $outputPath => $success) {
                    $results[$outputPath] = $success;
                }
            } catch (\Exception $e) {
                Log::error('Error running parallel chart batch', [
                    'error' => $e->getMessage(),
                    'batch_size' => count($batch),
                ]);

                foreach ($batch as $chartDef) {
                    $results[$chartDef['outputPath']] = false;
                }
            }
        }

        Log::info('Parallel chart batch generation completed', [
            'total_charts' => count($chartDefinitions),
            'successful' => count(array_filter($results)),
            'failed' => count(array_filter($results, fn ($v) => ! $v)),
        ]);

        return $results;
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
