<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateExecutiveReportJob;
use App\Models\Organization;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\WorkCenter\WorkCenterNom035CalculationService;
class ExecutiveReportDownloadController extends Controller
{
    public function download(
        string $workCenter,
        string $organization
    ) {
        $organizationModel = Organization::query()->findOrFail($organization);

        $workCenterModel = WorkCenter::query()
            ->where('organization_id', $organization)
            ->where('id', $workCenter)
            ->firstOrFail();

        $reportId = (string) Str::uuid();

        $this->writeReportStatus($reportId, [
            'status' => 'queued',
            'report_id' => $reportId,
            'organization_id' => (string) $organizationModel->id,
            'organization_name' => (string) ($organizationModel->name ?? ''),
            'work_center_id' => (string) $workCenterModel->id,
            'work_center_name' => (string) ($workCenterModel->name ?? ''),
            'return_url' => url()->previous(),
            'created_at' => now()->toDateTimeString(),
        ]);


        GenerateExecutiveReportJob::dispatch(
            $reportId,
            (string) $workCenterModel->id,
            (string) $organizationModel->id
        );

        return redirect()->route('executive-report.file', [
            'reportId' => $reportId,
            'return_url' => url()->previous(),
        ]);
    }


    public function status(string $reportId): JsonResponse
    {
        $status = $this->readReportStatus($reportId);

        $generatedPath = $this->findGeneratedReportPath($reportId);

        if ($generatedPath !== null && is_file($generatedPath)) {
            $status = array_merge(is_array($status) ? $status : [], [
                'status' => 'ready',
                'report_id' => $reportId,
                'output_path' => $generatedPath,
                'file_name' => basename($generatedPath),
                'file_size_bytes' => filesize($generatedPath),
                'completed_at' => now()->toDateTimeString(),
            ]);

            $this->writeReportStatus($reportId, $status);
        }

        if (! is_array($status)) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'No se encontró el proceso del informe o ya expiró.',
            ], 404)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        return response()->json($status)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function file(string $reportId)
        {
            $status = $this->readReportStatus($reportId);
            $returnUrl = $this->resolveReportReturnUrl($status);

            $generatedPath = $this->findGeneratedReportPath($reportId);

            if ($generatedPath !== null && is_file($generatedPath)) {
                if (request()->boolean('download')) {
                    return response()->download($generatedPath, basename($generatedPath), [
                        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                        'Pragma' => 'no-cache',
                        'Expires' => '0',
                    ]);
                }

                return response(
                    $this->renderReportReadyPage($reportId, basename($generatedPath), $returnUrl),
                    200
                )
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }

            if (is_array($status) && ($status['status'] ?? null) === 'ready') {
                $outputPath = (string) ($status['output_path'] ?? '');
                $fileName = (string) ($status['file_name'] ?? basename($outputPath));

                if ($outputPath !== '' && is_file($outputPath)) {
                    if (request()->boolean('download')) {
                        return response()->download($outputPath, $fileName, [
                            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                            'Pragma' => 'no-cache',
                            'Expires' => '0',
                        ]);
                    }

                    return response(
                        $this->renderReportReadyPage($reportId, $fileName, $returnUrl),
                        200
                    )
                        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
                }
            }

            if (is_array($status) && ($status['status'] ?? null) === 'failed') {
                return response(
                    $this->renderReportErrorPage((string) ($status['error_message'] ?? 'Error desconocido al generar el informe.')),
                    500
                )
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }

            return response(
                $this->renderReportWaitingPage(
                    $reportId,
                    is_array($status) ? (string) ($status['status'] ?? 'queued') : 'queued',
                    $returnUrl
                ),
                200
            )
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

    private function renderReportWaitingPage(string $reportId, string $status, string $returnUrl): string
        {
            $refreshUrl = route('executive-report.file', [
                'reportId' => $reportId,
                'return_url' => $returnUrl,
            ]);

            $safeStatus = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
            $safeRefreshUrl = htmlspecialchars($refreshUrl, ENT_QUOTES, 'UTF-8');

            return '<!doctype html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Generando informe</title>
            <meta http-equiv="refresh" content="4;url=' . $safeRefreshUrl . '">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f3f4f6;
                    color: #111827;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .card {
                    background: #ffffff;
                    border-radius: 12px;
                    padding: 32px;
                    width: 460px;
                    box-shadow: 0 10px 25px rgba(0,0,0,.12);
                    text-align: center;
                }
                .title {
                    font-size: 20px;
                    font-weight: bold;
                    margin-bottom: 12px;
                }
                .message {
                    font-size: 14px;
                    color: #4b5563;
                    margin-bottom: 18px;
                }
                .status {
                    font-size: 13px;
                    color: #1f2937;
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 12px;
                    word-break: break-word;
                }
                .spinner {
                    width: 36px;
                    height: 36px;
                    border: 4px solid #d1d5db;
                    border-top: 4px solid #2563eb;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 18px auto;
                }
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="spinner"></div>
                <div class="title">Generando informe</div>
                <div class="message">
                    El archivo se está generando en segundo plano. Esta pantalla se actualizará automáticamente.
                </div>
                <div class="status">Estado: ' . $safeStatus . '. Esperando archivo...</div>
            </div>
        </body>
        </html>';
        }

    private function renderReportReadyPage(string $reportId, string $fileName, string $returnUrl): string
        {
            $downloadUrl = route('executive-report.file', [
                'reportId' => $reportId,
                'download' => 1,
                'return_url' => $returnUrl,
            ]);

            $safeDownloadUrl = htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8');
            $safeFileName = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
            $returnUrlJson = json_encode($returnUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $downloadUrlJson = json_encode($downloadUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return '<!doctype html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Descargando informe</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f3f4f6;
                    color: #111827;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .card {
                    background: #ffffff;
                    border-radius: 12px;
                    padding: 32px;
                    width: 540px;
                    box-shadow: 0 10px 25px rgba(0,0,0,.12);
                    text-align: center;
                }
                .title {
                    font-size: 20px;
                    font-weight: bold;
                    margin-bottom: 12px;
                    color: #166534;
                }
                .message {
                    font-size: 14px;
                    color: #4b5563;
                    margin-bottom: 18px;
                }
                .status {
                    font-size: 13px;
                    color: #1f2937;
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 12px;
                    word-break: break-word;
                }
                a {
                    color: #2563eb;
                    font-weight: bold;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="title">Informe listo</div>
                <div class="message">
                    La descarga del archivo iniciará automáticamente. No cierres esta pantalla hasta que veas la descarga en el navegador.
                </div>
                <div class="status" id="downloadStatus">
                    Archivo: ' . $safeFileName . '<br>
                    Preparando descarga...
                    <br><br>
                    Si no descarga, <a id="manualDownloadLink" href="' . $safeDownloadUrl . '">haz clic aquí</a>.
                </div>
            </div>

            <iframe id="downloadFrame" style="display:none;"></iframe>

            <script>
                const downloadUrl = ' . $downloadUrlJson . ';
                const returnUrl = ' . $returnUrlJson . ';
                const returnDelayMs = 25000;
                let secondsLeft = Math.ceil(returnDelayMs / 1000);

                const statusBox = document.getElementById("downloadStatus");
                const downloadFrame = document.getElementById("downloadFrame");

                setTimeout(function () {
                    downloadFrame.src = downloadUrl;

                    statusBox.innerHTML =
                        "Descarga solicitada.<br>" +
                        "Archivo: ' . $safeFileName . '<br><br>" +
                        "Regresando automáticamente en " + secondsLeft + " segundos..." +
                        "<br><br>Si no descarga, <a href=\"' . $safeDownloadUrl . '\">haz clic aquí</a>.";
                }, 800);

                const countdown = setInterval(function () {
                    secondsLeft--;

                    if (secondsLeft > 0) {
                        statusBox.innerHTML =
                            "Descarga solicitada.<br>" +
                            "Archivo: ' . $safeFileName . '<br><br>" +
                            "Regresando automáticamente en " + secondsLeft + " segundos..." +
                            "<br><br>Si no descarga, <a href=\"' . $safeDownloadUrl . '\">haz clic aquí</a>.";
                    }
                }, 1000);

                setTimeout(function () {
                    clearInterval(countdown);
                    window.location.href = returnUrl;
                }, returnDelayMs);
            </script>
        </body>
        </html>';
        }

        private function renderReportErrorPage(string $message): string
        {
            $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            return '<!doctype html>
        <html lang="es">
        <head>
            <meta charset="utf-8">
            <title>Error al generar informe</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f3f4f6;
                    color: #111827;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                }
                .card {
                    background: #ffffff;
                    border-radius: 12px;
                    padding: 32px;
                    width: 520px;
                    box-shadow: 0 10px 25px rgba(0,0,0,.12);
                    text-align: center;
                }
                .title {
                    font-size: 20px;
                    font-weight: bold;
                    margin-bottom: 12px;
                    color: #b91c1c;
                }
                .message {
                    font-size: 14px;
                    color: #4b5563;
                    word-break: break-word;
                }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="title">Error al generar informe</div>
                <div class="message">' . $safeMessage . '</div>
            </div>
        </body>
        </html>';
        }

    public function generateReportFile(
        string $workCenter,
        string $organization,
        ?string $reportId = null
    ): array {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $organizationModel = Organization::query()->findOrFail($organization);

        $workCenterModel = WorkCenter::query()
            ->where('organization_id', $organization)
            ->where('id', $workCenter)
            ->firstOrFail();

        $phpWord = $this->buildReport($organizationModel, $workCenterModel);

        $outputDir = storage_path('app/reports/nom035');

if (! is_dir($outputDir)) {
    $created = mkdir($outputDir, 0775, true);

    if (! $created && ! is_dir($outputDir)) {
        throw new \RuntimeException('No se pudo crear el directorio de reportes: ' . $outputDir);
    }
}

if (! is_writable($outputDir)) {
    throw new \RuntimeException('El directorio de reportes no tiene permisos de escritura: ' . $outputDir);
}

$runId = $reportId ?: now()->format('Ymd_His_u') . '_' . Str::random(8);

$fileName = 'Informe_Analitico_NOM035_' .
    Str::slug($organizationModel->name ?? 'empresa', '_') . '_' .
    Str::slug($workCenterModel->name ?? 'centro_trabajo', '_') . '_' .
    $runId .
    '.docx';

$outputPath = $outputDir . DIRECTORY_SEPARATOR . $fileName;

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save($outputPath);

        return [
            'output_path' => $outputPath,
            'file_name' => $fileName,
            'file_size_bytes' => is_file($outputPath) ? filesize($outputPath) : null,
            'organization_id' => (string) $organizationModel->id,
            'organization_name' => (string) ($organizationModel->name ?? ''),
            'work_center_id' => (string) $workCenterModel->id,
            'work_center_name' => (string) ($workCenterModel->name ?? ''),
        ];
    }

    private function reportStatusFilePath(string $reportId): string
{
    $statusDir = storage_path('app/reports/nom035/status');

    if (! is_dir($statusDir)) {
        $created = mkdir($statusDir, 0775, true);

        if (! $created && ! is_dir($statusDir)) {
            throw new \RuntimeException('No se pudo crear el directorio de status: ' . $statusDir);
        }
    }

    if (! is_writable($statusDir)) {
        throw new \RuntimeException('El directorio de status no tiene permisos de escritura: ' . $statusDir);
    }

    return $statusDir . DIRECTORY_SEPARATOR . $reportId . '.json';
}

    private function readReportStatus(string $reportId): ?array
    {
        $statusPath = $this->reportStatusFilePath($reportId);

        if (! is_file($statusPath)) {
            return null;
        }

        $content = file_get_contents($statusPath);

        if ($content === false || trim($content) === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function writeReportStatus(string $reportId, array $payload): void
{
    $current = $this->readReportStatus($reportId) ?? [];

    $data = array_merge($current, $payload, [
        'updated_at' => now()->toDateTimeString(),
    ]);

    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    if ($json === false) {
        throw new \RuntimeException('No se pudo codificar el status JSON del reporte.');
    }

    $statusPath = $this->reportStatusFilePath($reportId);

    $written = file_put_contents($statusPath, $json, LOCK_EX);

    if ($written === false) {
        throw new \RuntimeException('No se pudo escribir el status JSON del reporte en: ' . $statusPath);
    }
}

    private function resolveReportReturnUrl(?array $status = null): string
        {
            $candidate = (string) request()->query('return_url', '');

            if ($candidate === '' && is_array($status)) {
                $candidate = (string) ($status['return_url'] ?? '');
            }

            if ($candidate === '') {
                $candidate = url()->previous();
            }

            if ($candidate === '' || $candidate === url()->current()) {
                return url('/');
            }

            $appHost = parse_url(url('/'), PHP_URL_HOST);
            $candidateHost = parse_url($candidate, PHP_URL_HOST);

            if ($candidateHost !== null && $candidateHost !== $appHost) {
                return url('/');
            }

            return $candidate;
        }

    private function findGeneratedReportPath(string $reportId): ?string
        {
            $outputDir = storage_path('app/reports/nom035');

            if (! is_dir($outputDir)) {
                return null;
            }

            $matches = glob($outputDir . DIRECTORY_SEPARATOR . '*' . $reportId . '*.docx');

            if (! is_array($matches) || count($matches) === 0) {
                return null;
            }

            foreach ($matches as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }

            return null;
        }

            private function makeUniqueChartPath(string $prefix): string
        {
            $chartDir = storage_path('app/tmp/nom035/charts');

            if (! is_dir($chartDir)) {
                mkdir($chartDir, 0755, true);
            }

            $runId = now()->format('Ymd_His_u') . '_' . Str::random(8);

            return $chartDir . DIRECTORY_SEPARATOR . $prefix . '_' . $runId . '.png';
        }

    private function buildReport(Organization $organization, WorkCenter $workCenter): PhpWord
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->getSettings()->setUpdateFields(true);

        $phpWord->addTitleStyle(
            1,
            ['bold' => true, 'size' => 14, 'color' => '111111'],
            ['spaceAfter' => 140]
        );
        $phpWord->addTitleStyle(
            2,
            ['bold' => true, 'size' => 12, 'color' => '1F2937'],
            ['spaceBefore' => 120, 'spaceAfter' => 120]
        );

        $phpWord->addTableStyle(
            'InfoTable',
            [
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 55,
                'alignment' => JcTable::CENTER,
            ]
        );

        $phpWord->addTableStyle(
            'StatsTable',
            [
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 55,
                'alignment' => JcTable::CENTER,
            ]
        );

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginRight' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
        ]);

        $empresaNumero = $this->firstFilled(
        $organization->company_number,
        $organization->numero_empresa,
        $organization->codigo_empresa,
        'E-' . str_pad((string) $organization->id, 3, '0', STR_PAD_LEFT)
    );

    $fechaFooter = now()->format('dmY');

    $footerLabel = 'Informe Analítico TMSN35 – ' .
        $fechaFooter . ' ' .
        $empresaNumero . ' ' .
        mb_strtoupper($this->safeValue($organization->name)) . ' – ' .
        mb_strtoupper($this->safeValue($workCenter->name));

    $footer = $section->addFooter();

    $footer->addPreserveText(
        $footerLabel . ' - Página {PAGE} | {NUMPAGES}',
        ['size' => 9, 'color' => '6B7280'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
    );

                $this->addCover($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addIndexSection($section);
                $section->addPageBreak();

                $this->addGeneralInformationSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addPaperDemographicSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeGlobalRiskSection($section, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeQuestionGlobalSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeQuestionGenderSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeQuestionWorkScheduleSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeQuestionDepartmentSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeQuestionPositionSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeQuestionRiskFactorSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addWorkerIdentificationByDimensionSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addWorkerIdentificationByDepartmentSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addWorkerIdentificationByPositionSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addWorkerIdentificationByWorkScheduleSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addSevereTraumaticEventsSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addWorkplaceViolenceQuantitativeSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addWorkplaceViolenceWorkersSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addFinalRiskWorkersSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addDomainQuantitativeAnalysisSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addWorkerIdentificationByCategorySection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeCategorySection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeDomainSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeDimensionSection($section, $workCenter);
                $section->addPageBreak();

                $this->addReferenceThreeQuestionRiskTableSection($section, $workCenter);
                $section->addPageBreak();

                $this->addCriticalAreasSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addCriticalPositionsSection($section, $organization, $workCenter);
                $section->addPageBreak();

                $this->addConsultantInformationSection($section);

                return $phpWord;
            }

        private function addCover(Section $section, Organization $organization, WorkCenter $workCenter): void
            {
                $legalName = $this->firstFilled(
                    $workCenter->legal_name,
                    $organization->razon_social,
                    $organization->name
                );

                $plazaName = $this->firstFilled(
                    $workCenter->name,
                    $organization->name
                );

                $fecha = $this->formatDate(now());
                $periodo = now()->year . ' – ' . (now()->year + 2);

                $green = '0B684B';
                $orange = 'F26A21';
                $lightGray = 'EEF2F1';
                $textDark = '0B684B';
                $white = 'FFFFFF';
                $lineGray = 'D8E0DD';

                // espacio superior
                $section->addTextBreak(1);

                // Encabezado verde NOM-035
                $headerTable = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 0,
                ]);
                $headerTable->addRow();
                $headerCell = $headerTable->addCell(9400, ['bgColor' => $green]);
                $headerCell->addText(
                    'NOM-035-STPS-2018',
                    ['bold' => true, 'size' => 24, 'color' => $white],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => 220, 'spaceAfter' => 120]
                );
                $headerCell->addText(
                    'Factores de Riesgo Psicosocial en el Trabajo',
                    ['size' => 10, 'color' => 'F7C66A'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 220]
                );

                $section->addTextBreak(1);

                // Barra naranja superior
                $topOrange = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 0,
                ]);
                $topOrange->addRow(220);
                $topOrange->addCell(9400, ['bgColor' => $orange])->addText(
                    ' ',
                    ['size' => 1],
                    ['spaceAfter' => 0]
                );

                $section->addTextBreak(2);

                // Bloque central INFORME ANALÍTICO
                $titleWrap = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 0,
                ]);

                $titleWrap->addRow();

                $left = $titleWrap->addCell(5200, [
                    'borderRightSize' => 6,
                    'borderRightColor' => $lineGray,
                    'valign' => 'center',
                ]);

                $left->addText(
                    'INFORME',
                    ['bold' => true, 'size' => 28, 'color' => $textDark],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 220]
                );
                $left->addText(
                    'ANALÍTICO',
                    ['bold' => true, 'size' => 28, 'color' => $textDark],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 220]
                );

                // línea naranja decorativa
                $lineTable = $left->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 0,
                ]);
                $lineTable->addRow(40);
                $lineTable->addCell(3600, ['bgColor' => $orange])->addText(
                    ' ',
                    ['size' => 1],
                    ['spaceAfter' => 0]
                );

                $right = $titleWrap->addCell(4200, ['valign' => 'center']);
                $right->addText(
                    mb_strtoupper($this->safeValue($organization->name)),
                    ['bold' => true, 'size' => 18, 'color' => $orange],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
                );
                $right->addText(
                    $this->safeValue($plazaName),
                    ['bold' => false, 'size' => 12, 'color' => $textDark],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $section->addTextBreak(2);

                // Barra naranja media
                $midOrange = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 0,
                ]);
                $midOrange->addRow(220);
                $midOrange->addCell(9400, ['bgColor' => $orange])->addText(
                    ' ',
                    ['size' => 1],
                    ['spaceAfter' => 0]
                );

                $section->addTextBreak(1);

                // Tabla de datos portada
                $infoTable = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 70,
                ]);

                $rows = [
                    ['Centro de trabajo', $this->safeValue($legalName)],
                    ['Plaza', $this->safeValue($plazaName)],
                    ['Fecha', $fecha],
                    ['Período', $periodo],
                ];

                foreach ($rows as [$label, $value]) {
                    $infoTable->addRow(700);

                    $infoTable->addCell(2600, ['bgColor' => $green])->addText(
                        $label,
                        ['bold' => true, 'size' => 10, 'color' => $orange],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $infoTable->addCell(6800, ['bgColor' => $lightGray])->addText(
                        $value,
                        ['bold' => true, 'size' => 10, 'color' => $textDark],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                $section->addTextBreak(3);

                // Flujo inferior simple
                $flowTable = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 30,
                ]);
                $flowTable->addRow();

                $flowTable->addCell(2200, ['bgColor' => '1E7C38'])->addText(
                    'Identificación',
                    ['bold' => true, 'size' => 10, 'color' => $white],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $flowTable->addCell(500)->addText(
                    '▶',
                    ['bold' => true, 'size' => 18, 'color' => '1E7C38'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $flowTable->addCell(2200, ['bgColor' => '6DBB75'])->addText(
                    'Análisis',
                    ['bold' => true, 'size' => 10, 'color' => $white],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $flowTable->addCell(500)->addText(
                    '▶',
                    ['bold' => true, 'size' => 18, 'color' => 'AAB7B0'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $flowTable->addCell(2200, ['bgColor' => 'A7D9AD'])->addText(
                    'Prevención',
                    ['bold' => true, 'size' => 10, 'color' => $white],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $section->addTextBreak(2);
            }

            private function addIndexSection(Section $section): void
                {
                    $titleBlue = '17365D';
                    $lineGray = 'D9DEE5';
                    $textDark = '111111';

                    $section->addText(
                        'Índice',
                        ['bold' => true, 'size' => 14, 'color' => $textDark],
                        ['alignment' => Jc::LEFT, 'spaceAfter' => 80]
                    );

                    $headerTable = $section->addTable([
                        'alignment' => JcTable::CENTER,
                        'borderSize' => 0,
                        'cellMargin' => 0,
                    ]);

                    $headerTable->addRow(280);
                    $headerTable->addCell(8100, ['bgColor' => $titleBlue])->addText(
                        'Contenido',
                        ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                    $headerTable->addCell(1300, ['bgColor' => $titleBlue])->addText(
                        'Pág.',
                        ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $section->addTextBreak(1);

                    $section->addTOC(
                        [
                            'name' => 'Arial',
                            'size' => 10,
                            'color' => '111111',
                        ],
                        [
                            'tabLeader' => 'dot',
                            'tabPos' => 9400,
                            'indent' => 60,
                        ],
                        1,
                        1
                    );

                    $section->addTextBreak(1);

                    $lineTable = $section->addTable([
                        'alignment' => JcTable::CENTER,
                        'borderSize' => 0,
                        'cellMargin' => 0,
                    ]);
                    $lineTable->addRow(40);
                    $lineTable->addCell(9400, ['bgColor' => $lineGray])->addText(
                        ' ',
                        ['size' => 1],
                        ['spaceAfter' => 0]
                    );
                }

    private function addGeneralInformationSection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $blueDark = '062A78';
            $blueMid = '1F4E78';
            $blueGray = '374151';

           $section->addTitle('I. Centro de Trabajo', 1);

            // Tarjetas superiores
            $cards = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 30,
            ]);

            $cards->addRow();

            $companyCell = $cards->addCell(3133, ['bgColor' => $blueDark]);
            $companyCell->addText(
                'Empresa',
                ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 30]
            );
            $companyCell->addText(
                mb_strtoupper($this->safeValue($organization->name)),
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $centerCell = $cards->addCell(3133, ['bgColor' => $blueMid]);
            $centerCell->addText(
                'Centro de trabajo',
                ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 30]
            );
            $centerCell->addText(
                mb_strtoupper($this->safeValue($workCenter->name)),
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $codeCell = $cards->addCell(3134, ['bgColor' => $blueGray]);
            $codeCell->addText(
                'Código',
                ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 30]
            );
            $codeCell->addText(
                $this->safeValue($workCenter->code),
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $section->addTextBreak(1);

            // Franja azul Datos Generales
            $bandOne = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $bandOne->addRow();
            $bandOne->addCell(9400, ['bgColor' => $blueDark])->addText(
                'Datos Generales',
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $section->addTextBreak(1);

            $infoTable = $section->addTable('InfoTable');
            $this->addInfoRow($infoTable, 'Empresa', $this->firstFilled($organization->name));
            $this->addInfoRow($infoTable, 'Centro de trabajo', $this->firstFilled($workCenter->name));
            $this->addInfoRow($infoTable, 'Código', $this->firstFilled($workCenter->code));
            $this->addInfoRow($infoTable, 'Razón social', $this->firstFilled($workCenter->legal_name, $organization->razon_social));
            $this->addInfoRow($infoTable, 'RFC', $this->firstFilled($workCenter->tax_id, $organization->rfc));
            $this->addInfoRow($infoTable, 'Registro patronal', $this->firstFilled($workCenter->employer_registration, $organization->registro_patronal));
            $this->addInfoRow($infoTable, 'Domicilio', $this->formatAddress($workCenter, $organization));
            $this->addInfoRow($infoTable, 'Teléfono', $this->firstFilled($workCenter->phone, $organization->contacto_movil));

            $section->addTextBreak(1);

            // Franja azul Contactos
            $bandTwo = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $bandTwo->addRow();
            $bandTwo->addCell(9400, ['bgColor' => $blueDark])->addText(
                'Contactos',
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $section->addTextBreak(1);

            $contactTable = $section->addTable('InfoTable');
            $this->addInfoRow($contactTable, 'Nombre', $this->firstFilled($workCenter->contact_name, $organization->contacto_nombre));
            $this->addInfoRow($contactTable, 'Puesto', $this->firstFilled($workCenter->contact_position, $organization->contacto_puesto));
            $this->addInfoRow($contactTable, 'Email', $this->firstFilled($workCenter->contact_email, $organization->contacto_email));
            $this->addInfoRow($contactTable, 'Teléfono', $this->firstFilled($workCenter->contact_phone, $organization->contacto_movil));
            $this->addInfoRow($contactTable, 'Responsable', $this->firstFilled($workCenter->responsible_name, $organization->responsable_nombre));
            $this->addInfoRow($contactTable, 'Puesto', $this->firstFilled($workCenter->responsible_position, $organization->responsable_puesto));
            $this->addInfoRow($contactTable, 'Email', $this->firstFilled($workCenter->responsible_email, $organization->responsable_email));
            $this->addInfoRow($contactTable, 'Teléfono', $this->firstFilled($workCenter->responsible_phone, $organization->responsable_movil));
        }

        private function addPaperDemographicSection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $summary = $this->getParticipantSummary($organization->id, $workCenter->id);

            $section->addTitle('II. Análisis demográfico', 1);

            $cards = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 40,
            ]);

            $cards->addRow();

            $card1 = $cards->addCell(2350, ['bgColor' => '062A78']);
            $card1->addText('Participantes totales', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $card1->addText((string) $summary['total_participants'], ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $card2 = $cards->addCell(2350, ['bgColor' => '1F4E78']);
            $card2->addText('Presencial', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $card2->addText((string) $summary['paper_participants'], ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $card3 = $cards->addCell(2350, ['bgColor' => '374151']);
            $card3->addText('En línea', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $card3->addText((string) $summary['online_participants'], ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $card4 = $cards->addCell(2350, ['bgColor' => '16A34A']);
            $card4->addText('H / M', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $card4->addText(
                (string) $summary['men_total'] . ' / ' . (string) $summary['women_total'],
                ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $section->addTextBreak(1);

            $totalsTable = $section->addTable('StatsTable');
            $this->addInfoRow($totalsTable, 'Empresa', $this->firstFilled($organization->name));
            $this->addInfoRow($totalsTable, 'Centro de trabajo', $this->firstFilled($workCenter->name));
            $this->addInfoRow($totalsTable, 'Plantilla registrada', (string) ($workCenter->total_workers ?? 0));
            $this->addInfoRow($totalsTable, 'Hombres', (string) $summary['men_total']);
            $this->addInfoRow($totalsTable, 'Mujeres', (string) $summary['women_total']);
            $this->addInfoRow($totalsTable, 'Sexo no especificado', (string) $summary['unspecified_gender_total']);

            $section->addTextBreak(1);

            $this->addDistributionTable($section, 'Rango de edad', $summary['age']);
            $this->addDistributionTable($section, 'Estado civil', $summary['marital_status']);
            $this->addDistributionTable($section, 'Nivel de estudios', $summary['education_level']);
            $this->addDistributionTable($section, 'Área', $summary['department']);
            $this->addDistributionTable($section, 'Puesto', $summary['position']);
            $this->addDistributionTable($section, 'Tipo de puesto', $summary['position_type']);
            $this->addDistributionTable($section, 'Tipo de personal', $summary['personnel_type']);
            $this->addDistributionTable($section, 'Tipo de contratación', $summary['contract_type']);
            $this->addDistributionTable($section, 'Jornada laboral', $summary['work_schedule']);
            $this->addDistributionTable($section, 'Rotación de turno', $summary['shift_rotation']);
            $this->addDistributionTable($section, 'Antigüedad en el puesto actual', $summary['time_in_current_position']);
            $this->addDistributionTable($section, 'Experiencia laboral', $summary['work_experience']);
        }

    private function addReferenceThreeGlobalRiskSection(Section $section, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeGlobalDashboardSummary(
                (string) $workCenter->organization_id,
                (string) $workCenter->id
            );

            $distribution = $summary['distribution'] ?? $this->initializeRiskLevelCounts();

            foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                $distribution[$levelKey] = (int) ($distribution[$levelKey] ?? 0);
            }

            $totalEvaluations = (int) ($summary['total_evaluations'] ?? 0);
            $averageGlobalScore = (float) ($summary['average_global_score'] ?? 0);
            $maxGlobalScore = (int) ($summary['max_global_score'] ?? config('nom035_risk_levels.global.max_score', 288));
            $averageGlobalPercentage = (float) ($summary['average_global_percentage'] ?? 0);

            $globalLevel = $this->classifyNom035Score(
                'global',
                null,
                (int) round($averageGlobalScore, 0, PHP_ROUND_HALF_UP)
            );

            $dominantLevelKey = (string) ($globalLevel['key'] ?? 'nulo');
            $dominantLevelLabel = (string) (
                $globalLevel['label']
                ?? config("nom035_risk_levels.labels.$dominantLevelKey", ucfirst($dominantLevelKey))
            );


                $section->addTitle('III. Análisis general referencia nivel de riesgo', 1);

                $section->addText(
                    'Referencia: Calificación Total',
                    ['bold' => true, 'size' => 12],
                    ['spaceAfter' => 180]
                );

                if ($totalEvaluations === 0) {
                    $this->addNoDataNotice(
                        $section,
                        'No hay evaluaciones de Referencia III disponibles para este centro de trabajo.'
                    );

                    return;
                }

                $cards = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 40,
                ]);

                $cards->addRow();

                $c1 = $cards->addCell(2350, ['bgColor' => '062A78']);
                $c1->addText(
                    'Evaluaciones',
                    ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 40]
                );
                $c1->addText(
                    (string) $totalEvaluations,
                    ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $c2 = $cards->addCell(2350, ['bgColor' => '1F4E78']);
                $c2->addText(
                    'Promedio',
                    ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 40]
                );
                $c2->addText(
                    number_format($averageGlobalScore, 1) . ' / ' . $maxGlobalScore,
                    ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $c3 = $cards->addCell(2350, ['bgColor' => '374151']);
                $c3->addText(
                    '% Promedio',
                    ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 40]
                );
                $c3->addText(
                    number_format($averageGlobalPercentage, 2) . '%',
                    ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $levelStyle = $this->getWordRiskCellStyle($dominantLevelKey);
                $c4 = $cards->addCell(2350, ['bgColor' => $levelStyle['bg']]);
                $c4->addText(
                    'Nivel de riesgo',
                    ['bold' => true, 'size' => 9, 'color' => $levelStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 40]
                );
                $c4->addText(
                    $dominantLevelLabel,
                    ['bold' => true, 'size' => 13, 'color' => $levelStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $section->addTextBreak(1);

                $globalChartPath = $this->generateRiskDistributionChart(
                    'Distribución Global de Niveles de Riesgo',
                    $distribution,
                    $this->makeUniqueChartPath('global')
                );

                $this->addChartImageIfExists($section, $globalChartPath, 500);

                $section->addText(
                    '*Referencia: NORMA Oficial Mexicana NOM-035-STPS-2018. Guía de Referencia III.3 inciso a) Tabla 5. Pág. 39. inciso c) Pág. 40',
                    ['size' => 8, 'color' => '374151'],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => 40, 'spaceAfter' => 0]
                );
            }

    private function addWorkplaceViolenceQuantitativeSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $summary = $this->getWorkplaceViolenceQuantitativeSummaryFromService($workCenter);

            $section->addTitle('XV. Análisis de trabajadores referencia violencia laboral', 1);

            $paragraphs = [
                'La Organización Mundial de la Salud (OMS) define el acoso laboral o mobbing como el comportamiento agresivo de uno o más miembros de un equipo de trabajo hacia un individuo de dicho grupo, con el objetivo de producir miedo, desprecio o depresión en ese trabajador, hasta que renuncie o sea despedido.',
                'La violencia laboral, se establece de conformidad con lo siguiente:',
                '1) Acoso, acoso psicológico: Aquellos actos que dañan la estabilidad psicológica, la personalidad, la dignidad o integridad del trabajador. Consiste en acciones de intimidación sistemática y persistente, tales como: descrédito, insultos, humillaciones, devaluación, marginación, indiferencia, comparaciones destructivas, rechazo, restricción a la autodeterminación y amenazas, las cuales llevan al trabajador a la depresión, al aislamiento, a la pérdida de su autoestima. Para efectos de esta Norma no se considera el acoso sexual;',
                '2) Hostigamiento: El ejercicio de poder en una relación de subordinación real de la víctima frente al agresor en el ámbito laboral, que se expresa en conductas verbales, físicas o ambas, y',
                '3) Malos tratos: Aquellos actos consistentes en insultos, burlas, humillaciones y/o ridiculizaciones del trabajador, realizados de manera continua y persistente (más de una vez y/o en diferentes ocasiones).',
            ];

            foreach ($paragraphs as $text) {
                $section->addText(
                    $text,
                    ['size' => 10],
                    ['spaceAfter' => 70]
                );
            }

            $section->addTextBreak(1);

            if (($summary['total_participants'] ?? 0) === 0) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron evaluaciones con datos de violencia laboral.'
                );

                return;
            }

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 40,
            ]);

            $table->addRow(620);

            $table->addCell(2600, ['bgColor' => 'D9D9D9'])->addText(
                'Calificación Total del Dominio',
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $headers = [
                ['label' => 'Nulo o despreciable', 'bg' => '3B82F6', 'text' => 'FFFFFF'],
                ['label' => 'Bajo', 'bg' => '16A34A', 'text' => 'FFFFFF'],
                ['label' => 'Medio', 'bg' => 'F8FF03', 'text' => '111111'],
                ['label' => 'Alto', 'bg' => 'F59E0B', 'text' => 'FFFFFF'],
                ['label' => 'Muy Alto', 'bg' => 'EF4444', 'text' => 'FFFFFF'],
            ];

            foreach ($headers as $header) {
                $table->addCell(1300, ['bgColor' => $header['bg']])->addText(
                    $header['label'],
                    ['bold' => true, 'size' => 10, 'color' => $header['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $table->addRow(760);

            $table->addCell(2600)->addText(
                'Violencia',
                ['size' => 10],
                ['spaceAfter' => 0]
            );

            $domainRows = [
            'nulo' => (int) ($summary['distribution']['nulo'] ?? 0),
            'bajo' => (int) ($summary['distribution']['bajo'] ?? 0),
            'medio' => (int) ($summary['distribution']['medio'] ?? 0),
            'alto' => (int) ($summary['distribution']['alto'] ?? 0),
            'muy_alto' => (int) ($summary['distribution']['muy_alto'] ?? 0),
        ];

        foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $key) {
            $style = $this->getWordRiskCellStyle($key);

            $table->addCell(1300, ['bgColor' => $style['bg']])->addText(
                (string) $domainRows[$key],
                ['bold' => true, 'size' => 11, 'color' => $style['text']],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

            $section->addTextBreak(1);

            $chartPath = $this->generateRiskDistributionChart(
                'Violencia laboral',
                $summary['distribution'],
                $this->makeUniqueChartPath('violencia_laboral')
            );

            $this->addChartImageIfExists($section, $chartPath, 560);

            $section->addTextBreak(1);

            $section->addText(
                'Preguntas realizadas en los cuestionarios que dan origen a los datos mostrados:',
                ['bold' => true, 'size' => 11],
                ['spaceAfter' => 100]
            );

            $questionTable = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 35,
            ]);

            $questionTable->addRow();
            $questionTable->addCell(5200, ['bgColor' => 'D9D9D9'])->addText(
                'Pregunta',
                ['bold' => true, 'size' => 10],
                ['spaceAfter' => 0]
            );

            foreach ([
                ['key' => 'nulo', 'label' => 'Nulo'],
                ['key' => 'bajo', 'label' => 'Bajo'],
                ['key' => 'medio', 'label' => 'Medio'],
                ['key' => 'alto', 'label' => 'Alto'],
                ['key' => 'muy_alto', 'label' => 'Muy Alto'],
            ] as $header) {
                $style = $this->getWordRiskCellStyle($header['key']);

                $questionTable->addCell(800, ['bgColor' => $style['bg']])->addText(
                    $header['label'],
                    ['bold' => true, 'size' => 10, 'color' => $style['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            foreach ($summary['questions'] as $question) {
                $questionTable->addRow();

                $questionTable->addCell(5200)->addText(
                    $question['label'],
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                    $questionTable->addCell(800)->addText(
                        (string) ($question['distribution'][$levelKey] ?? 0),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }
            }
        }

    private function addReferenceThreeCategorySection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeCategorySummary(
            (string) $organization->id,
            (string) $workCenter->id
        );

            $section->addTitle('XIX. Evaluación del Entorno Organizacional.', 1);

            $section->addText(
                'Análisis Cuantitativo Global',
                ['bold' => true, 'size' => 12],
                ['spaceAfter' => 140]
            );

            $section->addText(
                'Entorno Organizacional Favorable: Aquel en el que se promueve el sentido de pertenencia de los trabajadores a la empresa; la formación para la adecuada realización de las tareas encomendadas; la definición precisa de responsabilidades para los trabajadores del centro de trabajo; la participación proactiva y comunicación entre trabajadores; la distribución adecuada de cargas de trabajo, con jornadas de trabajo regulares conforme a la Ley Federal del Trabajo, y la evaluación y el reconocimiento del desempeño.',
                ['size' => 10],
                ['spaceAfter' => 180]
            );

            if (($summary['total_evaluations'] ?? 0) === 0 || empty($summary['categories'])) {
                $this->addNoDataNotice(
                    $section,
                    'No hay categorías aplicables de Referencia III para este centro de trabajo.'
                );

                return;
            }

            $categoryChunks = collect($summary['categories'])->chunk(4)->values();

            foreach ($categoryChunks as $index => $chunk) {
                if ($index > 0) {
                    $section->addPageBreak();
                    $section->addText(
                        'XIX. Evaluación del Entorno Organizacional. (continuación)',
                        ['bold' => true, 'size' => 14],
                        ['spaceAfter' => 30]
                    );
                }

                $chartPath = $this->generateCategoryDashboardChart(
                    $chunk->values()->all(),
                    (int) $summary['total_evaluations'],
                    $this->makeUniqueChartPath('category_dashboard_' . ($index + 1)),
                    $index + 1,
                    $categoryChunks->count()
                );

                $this->addChartImageIfExists($section, $chartPath, 640);
            }
        }

        private function addReferenceThreeDomainSection(Section $section, Organization $organization, WorkCenter $workCenter): void
    {
        $summary = $this->getReferenceThreeDomainSummary(
            (string) $organization->id,
            (string) $workCenter->id
        );

        $section->addTitle('XX. Análisis cuantitativo referencia nivel de riesgo por dominio', 1);

        $section->addText(
            'Distribución consolidada por dominio con conteos por nivel y gráficas de atención.',
            ['size' => 10, 'color' => '4B5563'],
            ['spaceAfter' => 120]
        );

        if (($summary['total_evaluations'] ?? 0) === 0 || empty($summary['domains'])) {
            $this->addNoDataNotice(
                $section,
                'No hay dominios aplicables de Referencia III para este centro de trabajo.'
            );

            return;
        }

        $domainChunks = collect($summary['domains'])->chunk(4)->values();

        foreach ($domainChunks as $index => $chunk) {
            if ($index > 0) {
                $section->addPageBreak();
                $section->addText(
                    'XX. Análisis cuantitativo referencia nivel de riesgo por dominio (continuación)',
                    ['bold' => true, 'size' => 14],
                    ['spaceAfter' => 30]
                );
            }

            $chartPath = $this->generateDomainDashboardChart(
                $chunk->values()->all(),
                (int) $summary['total_evaluations'],
                $this->makeUniqueChartPath('domain_dashboard_' . ($index + 1)),
                $index + 1,
                $domainChunks->count()
            );

            $this->addChartImageIfExists($section, $chartPath, 640);
        }

    }

   private function addReferenceThreeDimensionSection(Section $section, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeDimensionSummary(
            (string) $workCenter->organization_id,
            (string) $workCenter->id
        );

        $dimensions = $summary['dimensions'] ?? [];
        $totalEvaluations = (int) ($summary['total_evaluations'] ?? 0);

            $section->addTitle('XXI. Análisis cuantitativo referencia nivel de riesgo por dimensión', 1);

            $section->addText(
                'Distribución consolidada por dimensión con conteos por nivel y gráficas de atención.',
                ['size' => 10, 'color' => '4B5563'],
                ['spaceAfter' => 10]
            );

            if ($totalEvaluations === 0 || empty($dimensions)) {
                $this->addNoDataNotice(
                    $section,
                    'No hay dimensiones aplicables de Referencia III con datos para este centro de trabajo.'
                );

                return;
            }

            $dimensionChunks = collect($dimensions)->chunk(4)->values();

            foreach ($dimensionChunks as $index => $chunk) {
                if ($index > 0) {
                    $section->addPageBreak();
                    $section->addText(
                        'XXI. Análisis cuantitativo referencia nivel de riesgo por dimensión (continuación)',
                        ['bold' => true, 'size' => 14],
                        ['spaceAfter' => 30]
                    );
                }

                $chartPath = $this->generateDimensionDashboardChart(
                    $chunk->values()->all(),
                    $totalEvaluations,
                    $this->makeUniqueChartPath('dimension_dashboard_' . ($index + 1)),
                    $index + 1,
                    $dimensionChunks->count()
                );

                $this->addChartImageIfExists($section, $chartPath, 520);
            }
        }

        private function addReferenceThreeQuestionRiskTableSection(Section $section, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeQuestionRiskTableSummary($workCenter);

            $section->addTitle('XXII. Tabla de preguntas por nivel de riesgo', 1);

            if (($summary['total_evaluations'] ?? 0) === 0 || empty($summary['questions'])) {
                $this->addNoDataNotice(
                    $section,
                    'No hay evaluaciones de Referencia III disponibles para generar la tabla de preguntas por nivel de riesgo.'
                );

                return;
            }

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '5B6472',
                'cellMargin' => 28,
            ]);

            $titleBlue = '17365D';
            $headerBlue = '1F3A63';
            $headerGray = '4B5563';
            $softGray = 'F3F4F6';
            $questionBg = 'D9E2F3';

            $table->addRow(460);
            $table->addCell(7800, ['gridSpan' => 7, 'bgColor' => $titleBlue])->addText(
                'TABLA DE PREGUNTAS POR NIVEL DE RIESGO  |  Encuestados: ' . (string) ($summary['total_evaluations'] ?? 0),
                ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $table->addRow(420);

            $table->addCell(1000, ['bgColor' => $headerBlue])->addText(
                'PREGUNTA',
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            foreach ([
                ['key' => 'nulo', 'label' => 'NULO'],
                ['key' => 'bajo', 'label' => 'BAJO'],
                ['key' => 'medio', 'label' => 'MEDIO'],
                ['key' => 'alto', 'label' => 'ALTO'],
                ['key' => 'muy_alto', 'label' => 'MUY ALTO'],
            ] as $header) {
                $style = $this->getWordRiskCellStyle($header['key']);

                $table->addCell(1000, ['bgColor' => $style['bg']])->addText(
                    $header['label'],
                    ['bold' => true, 'size' => 10, 'color' => $style['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $table->addCell(1000, ['bgColor' => $headerGray])->addText(
                'TOTAL',
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            foreach ($summary['questions'] as $row) {
                $distribution = $row['distribution'] ?? [];
                $maxCount = max([
                    (int) ($distribution['nulo'] ?? 0),
                    (int) ($distribution['bajo'] ?? 0),
                    (int) ($distribution['medio'] ?? 0),
                    (int) ($distribution['alto'] ?? 0),
                    (int) ($distribution['muy_alto'] ?? 0),
                ]);

                $table->addRow(300);

                $table->addCell(1000, ['bgColor' => $questionBg])->addText(
                    $row['question'],
                    ['bold' => true, 'size' => 9, 'color' => '111111'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                    $value = (int) ($distribution[$levelKey] ?? 0);
                    $style = $this->getWordRiskCellStyle($levelKey);
                    $cellStyle = ['bgColor' => $softGray];
                    $fontStyle = ['size' => 9, 'color' => '111111'];

                    if (
                    $value > 0
                    && $value === $maxCount
                    && in_array($levelKey, ['alto', 'muy_alto'], true)
                ) {
                    $cellStyle['bgColor'] = $style['bg'];
                    $fontStyle = ['bold' => true, 'size' => 9, 'color' => $style['text']];
                }

                    $table->addCell(1000, $cellStyle)->addText(
                        (string) $value,
                        $fontStyle,
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                $table->addCell(1000, ['bgColor' => $softGray])->addText(
                    (string) ($row['total'] ?? 0),
                    ['bold' => true, 'size' => 9, 'color' => '111111'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $table->addRow(360);

            $table->addCell(1000, ['bgColor' => $headerBlue])->addText(
                'TOTAL',
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                $style = $this->getWordRiskCellStyle($levelKey);

                $table->addCell(1000, ['bgColor' => $style['bg']])->addText(
                    (string) ($summary['totals'][$levelKey] ?? 0),
                    ['bold' => true, 'size' => 10, 'color' => $style['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $table->addCell(1000, ['bgColor' => $headerGray])->addText(
                (string) ($summary['totals']['total'] ?? 0),
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        private function getReferenceThreeQuestionRiskTableSummary(WorkCenter $workCenter): array
            {
                $globalSummary = $this->getReferenceThreeGlobalSummary(
                    (string) $workCenter->organization_id,
                    (string) $workCenter->id
                );

                $evaluations = $globalSummary['evaluations'] ?? [];
                $totalEvaluations = count($evaluations);

                $questions = [];

                for ($i = 1; $i <= 72; $i++) {
                    $questions[$i] = [
                        'question' => 'P' . $i,
                        'distribution' => $this->initializeRiskLevelCounts(),
                        'total' => 0,
                    ];
                }

                foreach ($evaluations as $evaluation) {
                    $questionScores = $evaluation['question_scores'] ?? [];

                    if (! is_array($questionScores)) {
                        continue;
                    }

                    foreach ($questionScores as $questionKey => $score) {
                        $questionKey = (int) $questionKey;

                        if ($questionKey < 1 || $questionKey > 72) {
                            continue;
                        }

                        if (! is_numeric($score)) {
                            continue;
                        }

                        $riskLevelKey = $this->mapQuestionScoreToRiskLevelKey((int) $score);

                        if (isset($questions[$questionKey]['distribution'][$riskLevelKey])) {
                            $questions[$questionKey]['distribution'][$riskLevelKey]++;
                            $questions[$questionKey]['total']++;
                        }
                    }
                }

                $questionRows = array_values(array_filter(
                    $questions,
                    fn (array $row): bool => (int) ($row['total'] ?? 0) > 0
                ));

                usort($questionRows, function (array $a, array $b): int {
                    return (int) str_replace('P', '', $a['question']) <=> (int) str_replace('P', '', $b['question']);
                });

                $totals = $this->initializeRiskLevelCounts();
                $totals['total'] = 0;

                foreach ($questionRows as $row) {
                    foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                        $totals[$levelKey] += (int) ($row['distribution'][$levelKey] ?? 0);
                    }

                    $totals['total'] += (int) ($row['total'] ?? 0);
                }

                return [
                    'total_evaluations' => $totalEvaluations,
                    'questions' => $questionRows,
                    'totals' => $totals,
                ];
            }

            private function addCriticalAreasSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $summary = $this->getCriticalGroupingSummary($organization->id, $workCenter->id, 'area');

            $this->renderCriticalGroupingSection(
                $section,
                'XXIII. Áreas críticas (agrupación)',
                'Área',
                $summary,
                $workCenter
            );
        }

        private function addCriticalPositionsSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $summary = $this->getCriticalGroupingSummary($organization->id, $workCenter->id, 'position');

            $this->renderCriticalGroupingSection(
                $section,
                'XXIV. Puestos críticos (agrupación)',
                'Puesto',
                $summary,
                $workCenter
            );
        }

            private function renderCriticalGroupingSection(
                Section $section,
                string $title,
                string $groupLabel,
                array $summary,
                WorkCenter $workCenter
            ): void {
                $section->addTitle($title, 1);

                if (($summary['total_evaluations'] ?? 0) === 0 || empty($summary['rows'])) {
                    $emptyLabel = $groupLabel === 'Área'
                        ? 'áreas críticas'
                        : 'puestos críticos';

                    $this->addNoDataNotice(
                        $section,
                        'No se encontraron ' . $emptyLabel . ' para este centro de trabajo.'
                    );

                    return;
                }

                $topBarColor = '0E5F4C';
                $subBarColor = '169A86';
                $headerBlue = '324A64';
                $rowBlue = 'DCE6F1';
                $rowGray = 'F3F4F6';
                $borderColor = '5B6472';

                $mainTitle = mb_strtoupper($this->safeValue($workCenter->name)) . ' — ' .
                    mb_strtoupper($groupLabel === 'Área' ? 'ÁREAS CRÍTICAS (AGRUPACIÓN)' : 'PUESTOS CRÍTICOS (AGRUPACIÓN)');

                $headerTable = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 0,
                ]);

                $headerTable->addRow(560);
                $headerTable->addCell(9800, $this->centeredCellStyle(['bgColor' => $topBarColor]))->addText(
                    $mainTitle,
                    ['bold' => true, 'size' => 18, 'color' => 'FFFFFF'],
                    $this->centeredTextStyle()
                );

                $headerTable->addRow(520);
                $headerTable->addCell(9800, $this->centeredCellStyle(['bgColor' => $subBarColor]))->addText(
                    'Ordenadas de mayor a menor calificación · Nivel ALTO = prioridad de intervención',
                    ['bold' => true, 'size' => 16, 'color' => 'FFFFFF'],
                    $this->centeredTextStyle()
                );

                $section->addTextBreak(1);

                $table = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 6,
                    'borderColor' => $borderColor,
                    'cellMargin' => 28,
                ]);

                $table->addRow(460);

                foreach ([
                    [450, '#'],
                    [1700, $groupLabel],
                    [900, 'Participantes'],
                    [1050, 'Calif. (/288)'],
                    [950, '% Promedio'],
                    [1300, 'Nivel de riesgo'],
                    [2200, 'Principal dominio afectado'],
                    [1300, 'Priorización'],
                ] as [$width, $label]) {
                    $table->addCell($width, $this->centeredCellStyle(['bgColor' => $headerBlue]))->addText(
                        $label,
                        ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                        $this->centeredTextStyle()
                    );
                }

                foreach ($summary['rows'] as $index => $row) {
                    $rowBg = $index % 2 === 0 ? $rowBlue : $rowGray;
                    $levelStyle = $this->getWordRiskCellStyle((string) ($row['global_level_key'] ?? 'nulo'));
                    $priorityMeta = $this->resolveCriticalPriorityMeta($row);

                    $scoreValue = (float) ($row['average_score'] ?? 0);
                    $scoreText = abs($scoreValue - round($scoreValue)) < 0.01
                        ? number_format($scoreValue, 0)
                        : number_format($scoreValue, 2);

                    $domainText = ! empty($row['top_domains'])
                        ? implode(' · ', $row['top_domains'])
                        : 'N/D';

                    $table->addRow(440);

                    $table->addCell(450, $this->centeredCellStyle(['bgColor' => $rowBg]))->addText(
                        (string) ($index + 1),
                        ['size' => 10],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(1700, $this->centeredCellStyle(['bgColor' => $rowBg]))->addText(
                        $this->safeValue($row['name'] ?? 'N/D'),
                        ['size' => 10],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(900, $this->centeredCellStyle(['bgColor' => $rowBg]))->addText(
                        (string) ($row['participants'] ?? 0),
                        ['size' => 10],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(1050, $this->centeredCellStyle(['bgColor' => $rowBg]))->addText(
                        $scoreText,
                        ['size' => 10],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(950, $this->centeredCellStyle(['bgColor' => $rowBg]))->addText(
                        number_format((float) ($row['average_percentage'] ?? 0), 2) . '%',
                        ['size' => 10],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(1300, $this->centeredCellStyle(['bgColor' => $levelStyle['bg']]))->addText(
                        $this->safeValue($row['global_level_label'] ?? ucfirst((string) ($row['global_level_key'] ?? 'nulo'))),
                        ['bold' => true, 'size' => 10, 'color' => $levelStyle['text']],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(2200, $this->centeredCellStyle(['bgColor' => $rowBg]))->addText(
                        $domainText,
                        ['size' => 10],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(1300, $this->centeredCellStyle(['bgColor' => $priorityMeta['bg']]))->addText(
                        $priorityMeta['label'],
                        ['bold' => true, 'size' => 10, 'color' => $priorityMeta['text']],
                        $this->centeredTextStyle()
                    );
                }

                $center = $summary['center'] ?? [];
                $centerStyle = $this->getWordRiskCellStyle((string) ($center['global_level_key'] ?? 'nulo'));

                $centerScoreValue = (float) ($center['average_score'] ?? 0);
                $centerScoreText = abs($centerScoreValue - round($centerScoreValue)) < 0.01
                    ? number_format($centerScoreValue, 0)
                    : number_format($centerScoreValue, 2);

                $table->addRow(420);

                $table->addCell(450, $this->centeredCellStyle(['bgColor' => $rowGray]))->addText(
                    'TOTAL',
                    ['bold' => true, 'size' => 10],
                    $this->centeredTextStyle()
                );

                $table->addCell(1700, $this->centeredCellStyle(['bgColor' => $rowGray]))->addText(
                    'Centro completo',
                    ['bold' => true, 'size' => 10],
                    $this->centeredTextStyle()
                );

                $table->addCell(900, $this->centeredCellStyle(['bgColor' => $rowGray]))->addText(
                    (string) ($center['participants'] ?? 0),
                    ['bold' => true, 'size' => 10],
                    $this->centeredTextStyle()
                );

                $table->addCell(1050, $this->centeredCellStyle(['bgColor' => $rowGray]))->addText(
                    $centerScoreText,
                    ['bold' => true, 'size' => 10],
                    $this->centeredTextStyle()
                );

                $table->addCell(950, $this->centeredCellStyle(['bgColor' => $rowGray]))->addText(
                    number_format((float) ($center['average_percentage'] ?? 0), 2) . '%',
                    ['bold' => true, 'size' => 10],
                    $this->centeredTextStyle()
                );

                $table->addCell(1300, $this->centeredCellStyle(['bgColor' => $centerStyle['bg']]))->addText(
                    $this->safeValue($center['global_level_label'] ?? ucfirst((string) ($center['global_level_key'] ?? 'nulo'))),
                    ['bold' => true, 'size' => 10, 'color' => $centerStyle['text']],
                    $this->centeredTextStyle()
                );

                $table->addCell(2200, $this->centeredCellStyle(['bgColor' => $rowGray]))->addText(
                    '',
                    ['size' => 10],
                    $this->centeredTextStyle()
                );

                $table->addCell(1300, $this->centeredCellStyle(['bgColor' => $rowGray]))->addText(
                    '',
                    ['size' => 10],
                    $this->centeredTextStyle()
                );
            }

        private function getCriticalGroupingSummary(
            string $organizationId,
            string $workCenterId,
            string $groupField
        ): array {
            $rows = DB::table('evaluation_answers as ea')
                ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.source',
                    'pe.personal_folio',
                    'pe.evaluee_name',
                    'ea.question_key',
                    'ea.answer_value',
                    'dd.department',
                    'dd.position',
                    'dd.extra_fields'
                )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            $evaluations = $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) {
                    $first = $items->first();

                    $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);
                    if (! is_array($extra)) {
                        $extra = [];
                    }

                    $isBoss = $this->extractWorkerFlag($extra, [
                        'jefe',
                        'soy_jefe',
                        'is_boss',
                        'is_manager',
                        'supervises_people',
                        'supervisa_personal',
                        'jefe_trabajadores',
                    ]);

                    $attendsPublic = $this->extractWorkerFlag($extra, [
                        'atiende',
                        'atiende_clientes',
                        'atencion_clientes',
                        'servicio_clientes',
                        'servicio_usuarios',
                        'client_service',
                        'attends_public',
                    ]);

                    $result = $this->buildReferenceThreeEvaluationResult(
                        (string) $evaluationId,
                        (string) ($first->source ?? 'paper'),
                        $items,
                        $attendsPublic,
                        $isBoss
                    );

                    $result['folio'] = $this->safeValue($first->personal_folio);
                    $result['name'] = $this->safeValue($first->evaluee_name);
                    $result['area'] = $this->safeValue($first->department);
                    $result['position'] = $this->safeValue($first->position);

                    return $result;
                })
                ->values()
                ->all();

            $totalEvaluations = count($evaluations);

            $centerAverageScore = $totalEvaluations > 0
                ? round(collect($evaluations)->sum(fn ($row) => (int) ($row['global_score'] ?? 0)) / $totalEvaluations, 2)
                : 0;

            $centerLevel = $this->classifyNom035Score('global', null, (int) round($centerAverageScore));

            $groups = collect($evaluations)
                ->groupBy(function ($row) use ($groupField) {
                    $label = trim((string) ($row[$groupField] ?? ''));
                    return $label !== '' ? $label : 'N/D';
                })
                ->map(function ($items, $groupName) {
                    $participants = count($items);

                    $averageScore = $participants > 0
                        ? round(collect($items)->sum(fn ($row) => (int) ($row['global_score'] ?? 0)) / $participants, 2)
                        : 0;

                    $averagePercentage = round(($averageScore / 288) * 100, 2);
                    $globalLevel = $this->classifyNom035Score('global', null, (int) round($averageScore));

                    $domainAverages = [];

                    foreach ($this->getReferenceThreeDomainMaxScores() as $domainName => $maxScore) {
                        $applicableItems = collect($items)->filter(function ($row) use ($domainName) {
                            $domainScores = $row['domain_scores'] ?? [];

                            return is_array($domainScores) && array_key_exists($domainName, $domainScores);
                        });

                        $applicableCount = $applicableItems->count();

                        if ($applicableCount === 0) {
                            continue;
                        }

                        $domainAverage = round(
                            $applicableItems->sum(fn ($row) => (int) ($row['domain_scores'][$domainName] ?? 0)) / $applicableCount,
                            2
                        );

                        $domainAverages[$domainName] = $domainAverage;
                    }

                    uasort($domainAverages, function ($a, $b) {
                        return $b <=> $a;
                    });

                    $topDomains = [];
                    foreach ($domainAverages as $domainName => $score) {
                        if ($score <= 0) {
                            continue;
                        }

                        $scoreLabel = abs($score - round($score)) < 0.01
                            ? number_format($score, 0)
                            : number_format($score, 2);

                        $topDomains[] = $domainName . ' (' . $scoreLabel . ')';

                        if (count($topDomains) === 2) {
                            break;
                        }
                    }

                    return [
                        'name' => $groupName,
                        'participants' => $participants,
                        'average_score' => $averageScore,
                        'average_percentage' => $averagePercentage,
                        'global_level_key' => (string) ($globalLevel['key'] ?? 'nulo'),
                        'global_level_label' => (string) ($globalLevel['label'] ?? 'Nulo'),
                        'top_domains' => $topDomains,
                    ];
                })
                ->filter(function ($row) {
                    return $this->riskLevelWeight((string) ($row['global_level_key'] ?? 'nulo'))
                        >= $this->riskLevelWeight('medio');
                })
                ->values()
                ->all();

            usort($groups, function ($a, $b) {
                $riskCompare = $this->riskLevelWeight((string) ($b['global_level_key'] ?? 'nulo'))
                    <=> $this->riskLevelWeight((string) ($a['global_level_key'] ?? 'nulo'));

                if ($riskCompare !== 0) {
                    return $riskCompare;
                }

                $scoreCompare = ((float) ($b['average_score'] ?? 0))
                    <=> ((float) ($a['average_score'] ?? 0));

                if ($scoreCompare !== 0) {
                    return $scoreCompare;
                }

                $participantCompare = ((int) ($b['participants'] ?? 0))
                    <=> ((int) ($a['participants'] ?? 0));

                if ($participantCompare !== 0) {
                    return $participantCompare;
                }

                return strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });

            $priorityRank = 0;

            foreach ($groups as &$row) {
                if (in_array((string) ($row['global_level_key'] ?? 'nulo'), ['alto', 'muy_alto'], true)) {
                    $priorityRank++;
                    $row['priority_rank'] = $priorityRank;
                } else {
                    $row['priority_rank'] = 0;
                }
            }
            unset($row);

            return [
                'total_evaluations' => $totalEvaluations,
                'rows' => $groups,
                'center' => [
                    'participants' => $totalEvaluations,
                    'average_score' => $centerAverageScore,
                    'average_percentage' => round(($centerAverageScore / 288) * 100, 2),
                    'global_level_key' => (string) ($centerLevel['key'] ?? 'nulo'),
                    'global_level_label' => (string) ($centerLevel['label'] ?? 'Nulo'),
                ],
            ];
        }

        private function resolveCriticalPriorityMeta(array $row): array
        {
            $levelKey = (string) ($row['global_level_key'] ?? 'nulo');
            $priorityRank = (int) ($row['priority_rank'] ?? 0);

            if (in_array($levelKey, ['alto', 'muy_alto'], true)) {
                if ($priorityRank === 1) {
                    return ['label' => 'PRIORIDAD 1', 'bg' => 'EF4B3A', 'text' => 'FFFFFF'];
                }

                if ($priorityRank === 2) {
                    return ['label' => 'PRIORIDAD 2', 'bg' => 'F2B441', 'text' => '111111'];
                }

                if ($priorityRank === 3) {
                    return ['label' => 'PRIORIDAD 3', 'bg' => 'F2B441', 'text' => '111111'];
                }

                return ['label' => 'PRIORIDAD ' . $priorityRank, 'bg' => 'F2B441', 'text' => '111111'];
            }

            return ['label' => 'SEGUIMIENTO', 'bg' => 'E9D46A', 'text' => '111111'];
        }

    private function addReferenceThreeQuestionGlobalSection(
        Section $section,
        Organization $organization,
        WorkCenter $workCenter
    ): void {
        $summary = $this->getQuestionAverageMatrixSummary($organization->id, $workCenter->id);

        $section->addTitle('IV. Análisis general referencia (categoría / dominio / dimensiones / pregunta)', 1);

       if (($summary['participants'] ?? 0) === 0) {
            $this->addNoDataNotice(
                $section,
                'No hay evaluaciones de Referencia III disponibles para este centro de trabajo.'
            );

            return;
        }

        $this->renderQuestionAverageMatrixTable($section, $summary);
    }

    private function addReferenceThreeQuestionGenderSection(
        Section $section,
        Organization $organization,
        WorkCenter $workCenter
    ): void {
        $femaleSummary = $this->getQuestionAverageMatrixSummaryByGender(
            $organization->id,
            $workCenter->id,
            ['mujer', 'mujeres', 'femenino', 'femenina', 'f']
        );

        $maleSummary = $this->getQuestionAverageMatrixSummaryByGender(
            $organization->id,
            $workCenter->id,
            ['hombre', 'hombres', 'masculino', 'masculina', 'm']
        );

        $section->addTitle('V. Análisis referencia género', 1);

        $printed = false;

        if (($femaleSummary['participants'] ?? 0) > 0) {
            $this->addQuestionAverageGenderBand($section, 'Femenino');
            $this->renderQuestionAverageMatrixTable($section, $femaleSummary);
            $printed = true;
        }

        if (($maleSummary['participants'] ?? 0) > 0) {
            if ($printed) {
                $section->addPageBreak();
            }

            $this->addQuestionAverageGenderBand($section, 'Masculino');
            $this->renderQuestionAverageMatrixTable($section, $maleSummary);
            $printed = true;
        }

        if (! $printed) {
            $this->addQuestionAverageGenderBand($section, 'Sin información');

            $this->addNoDataNotice(
                $section,
                'No hay información de género disponible para generar las tablas de Femenino y Masculino.'
            );
        }
    }

        private function addReferenceThreeQuestionPositionSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $positions = $this->getQuestionAveragePositionLabels($organization->id, $workCenter->id);

            $blocks = [];

            foreach ($positions as $row) {
                $positionLabel = trim((string) ($row['label'] ?? ''));

                if ($positionLabel === '' || $positionLabel === 'N/D') {
                    continue;
                }

                $summary = $this->getQuestionAverageMatrixSummaryByPosition(
                    $organization->id,
                    $workCenter->id,
                    $positionLabel
                );

                if (($summary['participants'] ?? 0) === 0) {
                    unset($summary);
                    continue;
                }

                /*
                * Solo guardamos los datos mínimos que usa compareQuestionSummaryBlocks().
                * No guardamos toda la matriz para no saturar memoria.
                */
                $blocks[] = [
                    'label' => $positionLabel,
                    'summary' => [
                        'participants' => (int) ($summary['participants'] ?? 0),
                        'final_total' => (int) ($summary['final_total'] ?? 0),
                        'final_percentage' => (float) ($summary['final_percentage'] ?? 0),
                    ],
                ];

                unset($summary);
                gc_collect_cycles();
            }

            usort($blocks, function ($a, $b) {
                return $this->compareQuestionSummaryBlocks($a, $b);
            });

            $section->addTitle('VIII. Análisis referencia puesto', 1);

            if (empty($blocks)) {
                $this->addQuestionAverageGenderBand($section, 'Sin información');

                $this->addNoDataNotice(
                    $section,
                    'No hay información de puestos disponible para generar esta sección.'
                );

                return;
            }

            foreach ($blocks as $index => $block) {
            if ($index > 0) {
                $section->addPageBreak();

                $section->addText(
                    'VIII. Análisis referencia puesto',
                    ['bold' => true, 'size' => 14],
                    ['spaceAfter' => 120]
                );
            }

            $summary = $this->getQuestionAverageMatrixSummaryByPosition(
                $organization->id,
                $workCenter->id,
                (string) ($block['label'] ?? '')
            );

            if (($summary['participants'] ?? 0) === 0) {
                unset($summary);
                continue;
            }

            $this->addQuestionAverageGenderBand($section, $block['label']);
            $this->renderQuestionAverageMatrixTable($section, $summary);

            unset($summary);
            gc_collect_cycles();
        }
        }

        private function addReferenceThreeQuestionDepartmentSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $departments = $this->getQuestionAverageDepartmentLabels($organization->id, $workCenter->id);

            $blocks = [];

            foreach ($departments as $row) {
                $departmentLabel = trim((string) ($row['label'] ?? ''));

                if ($departmentLabel === '' || $departmentLabel === 'N/D') {
                    continue;
                }

                $summary = $this->getQuestionAverageMatrixSummaryByDepartment(
                    $organization->id,
                    $workCenter->id,
                    $departmentLabel
                );

                if (($summary['participants'] ?? 0) === 0) {
                    continue;
                }

                $blocks[] = [
                    'label' => $departmentLabel,
                    'summary' => $summary,
                ];
            }

            usort($blocks, function ($a, $b) {
                return $this->compareQuestionSummaryBlocks($a, $b);
            });

            $section->addTitle('VII. Análisis referencia área', 1);

            if (empty($blocks)) {
                $this->addQuestionAverageGenderBand($section, 'Sin información');

                $this->addNoDataNotice(
                    $section,
                    'No hay información de áreas disponible para generar esta sección.'
                );

                return;
            }

            foreach ($blocks as $index => $block) {
                if ($index > 0) {
                    $section->addPageBreak();

                    $section->addText(
                        'VII. Análisis referencia área',
                        ['bold' => true, 'size' => 14],
                        ['spaceAfter' => 120]
                    );
                }

                $this->addQuestionAverageGenderBand($section, $block['label']);
                $this->renderQuestionAverageMatrixTable($section, $block['summary']);
            }
        }

        private function addReferenceThreeQuestionWorkScheduleSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $schedules = $this->getQuestionAverageWorkScheduleLabels($organization->id, $workCenter->id);

            $blocks = [];

            foreach ($schedules as $row) {
                $scheduleLabel = trim((string) ($row['label'] ?? ''));

                if ($scheduleLabel === '' || $scheduleLabel === 'N/D') {
                    continue;
                }

                $summary = $this->getQuestionAverageMatrixSummaryByWorkSchedule(
                    $organization->id,
                    $workCenter->id,
                    $scheduleLabel
                );

                if (($summary['participants'] ?? 0) === 0) {
                    continue;
                }

                $blocks[] = [
                    'label' => $scheduleLabel,
                    'summary' => $summary,
                ];
            }

            usort($blocks, function ($a, $b) {
                return $this->compareQuestionSummaryBlocks($a, $b);
            });

            $section->addTitle('VI. Análisis referencia jornada laboral', 1);

            if (empty($blocks)) {
                $this->addQuestionAverageGenderBand($section, 'Sin información');

                $this->addNoDataNotice(
                    $section,
                    'No hay información de jornada laboral disponible para generar esta sección.'
                );

                return;
            }

            foreach ($blocks as $index => $block) {
                if ($index > 0) {
                    $section->addPageBreak();

                    $section->addText(
                        'VI. Análisis referencia jornada laboral',
                        ['bold' => true, 'size' => 14],
                        ['spaceAfter' => 120]
                    );
                }

                $this->addQuestionAverageGenderBand($section, $block['label']);
                $this->renderQuestionAverageMatrixTable($section, $block['summary']);
            }
        }

        private function addReferenceThreeQuestionRiskFactorSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $levels = [
                ['key' => 'nulo', 'roman' => 'I.', 'label' => 'Nulo'],
                ['key' => 'bajo', 'roman' => 'II.', 'label' => 'Bajo'],
                ['key' => 'medio', 'roman' => 'III.', 'label' => 'Medio'],
                ['key' => 'alto', 'roman' => 'IV.', 'label' => 'Alto'],
                ['key' => 'muy_alto', 'roman' => 'V.', 'label' => 'Muy Alto'],
            ];

            $printed = false;

            foreach ($levels as $level) {
                $summary = $this->getQuestionAverageMatrixSummaryByGlobalLevel(
                    $organization->id,
                    $workCenter->id,
                    $level['key']
                );

                if (($summary['participants'] ?? 0) === 0) {
                    continue;
                }

                if ($printed) {
                    $section->addPageBreak();
                }

                if (! $printed) {
                    $section->addTitle('IX. Análisis referencia nivel de riesgo', 1);
                } else {
                    $section->addText(
                        'IX. Análisis referencia nivel de riesgo',
                        ['bold' => true, 'size' => 14],
                        ['spaceAfter' => 120]
                    );
                }

                $this->addQuestionAverageGenderBand(
                    $section,
                    $level['roman'] . ' ' . $level['label']
                );

                $this->renderQuestionAverageMatrixTable($section, $summary);
                $printed = true;
            }

            if (! $printed) {
                $section->addTitle('IX. Análisis referencia nivel de riesgo', 1);

                $this->addQuestionAverageGenderBand($section, 'Sin información');

                $this->addNoDataNotice(
                    $section,
                    'No hay información de nivel de riesgo disponible para generar esta sección.'
                );
            }
        }

        private function addQuestionAverageGenderBand(Section $section, string $label): void
        {
            $band = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);

            $band->addRow();
            $band->addCell(9400, ['bgColor' => 'D9D9D9'])->addText(
            $this->safeValue($label),
            ['bold' => true, 'size' => 11, 'color' => '111111'],
            ['spaceAfter' => 0]
        );
        }

    private function renderQuestionAverageMatrixTable(Section $section, array $summary): void
        {
            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 8,
            ]);

            $table->addRow(420, ['cantSplit' => true]);

            $table->addCell(1700, $this->centeredCellStyle(['gridSpan' => 2, 'bgColor' => '062A78']))->addText(
                'Categorías',
                ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'],
                $this->centeredTextStyle()
            );

            $table->addCell(1800, $this->centeredCellStyle(['gridSpan' => 2, 'bgColor' => '062A78']))->addText(
                'Dominios',
                ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'],
                $this->centeredTextStyle()
            );

            $table->addCell(2950, $this->centeredCellStyle(['gridSpan' => 2, 'bgColor' => '062A78']))->addText(
                'Dimensiones',
                ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'],
                $this->centeredTextStyle()
            );

            $table->addCell(2750, $this->centeredCellStyle(['bgColor' => '062A78']))->addText(
                'Preguntas (ítems)',
                ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'],
                $this->centeredTextStyle()
            );

            foreach ($summary['categories'] as $category) {
                $categoryStarted = false;

                $categoryLevel = $this->classifyNom035Score('categories', $category['name'], (int) $category['score']);
                $categoryStyle = $this->getWordRiskCellStyle($categoryLevel['key']);

                foreach ($category['domains'] as $domain) {
                    $domainStarted = false;

                    $domainLevel = $this->classifyNom035Score('domains', $domain['name'], (int) $domain['score']);
                    $domainStyle = $this->getWordRiskCellStyle($domainLevel['key']);

                    foreach ($domain['dimensions'] as $dimension) {
                        $dimensionLevel = $this->classifyNom035Score('dimensions', $dimension['name'], (int) $dimension['score']);
                        $dimensionStyle = $this->getWordRiskCellStyle($dimensionLevel['key']);

                        $table->addRow(300, ['cantSplit' => true]);

                        if (! $categoryStarted) {
                            $table->addCell(1400, $this->centeredCellStyle(['vMerge' => 'restart']))->addText(
                                $category['name'],
                                ['bold' => true, 'size' => 7],
                                $this->centeredTextStyle()
                            );

                            $table->addCell(300, $this->centeredCellStyle([
                                'vMerge' => 'restart',
                                'bgColor' => $categoryStyle['bg'],
                            ]))->addText(
                                (string) $category['score'],
                                ['bold' => true, 'size' => 7, 'color' => $categoryStyle['text']],
                                $this->centeredTextStyle()
                            );

                            $categoryStarted = true;
                        } else {
                            $table->addCell(1400, $this->centeredCellStyle(['vMerge' => 'continue']))->addText(
                                '',
                                ['size' => 1],
                                $this->centeredTextStyle()
                            );

                            $table->addCell(300, $this->centeredCellStyle(['vMerge' => 'continue']))->addText(
                                '',
                                ['size' => 1],
                                $this->centeredTextStyle()
                            );
                        }

                        if (! $domainStarted) {
                            $table->addCell(1500, $this->centeredCellStyle(['vMerge' => 'restart']))->addText(
                                $domain['name'],
                                ['bold' => true, 'size' => 7],
                                $this->centeredTextStyle()
                            );

                            $table->addCell(300, $this->centeredCellStyle([
                                'vMerge' => 'restart',
                                'bgColor' => $domainStyle['bg'],
                            ]))->addText(
                                (string) $domain['score'],
                                ['bold' => true, 'size' => 7, 'color' => $domainStyle['text']],
                                $this->centeredTextStyle()
                            );

                            $domainStarted = true;
                        } else {
                            $table->addCell(1500, $this->centeredCellStyle(['vMerge' => 'continue']))->addText(
                                '',
                                ['size' => 1],
                                $this->centeredTextStyle()
                            );

                            $table->addCell(300, $this->centeredCellStyle(['vMerge' => 'continue']))->addText(
                                '',
                                ['size' => 1],
                                $this->centeredTextStyle()
                            );
                        }

                        $table->addCell(2600, $this->centeredCellStyle())->addText(
                            $dimension['name'],
                            ['size' => 7],
                            $this->centeredTextStyle()
                        );

                        $table->addCell(350, $this->centeredCellStyle(['bgColor' => $dimensionStyle['bg']]))->addText(
                            (string) $dimension['score'],
                            ['bold' => true, 'size' => 7, 'color' => $dimensionStyle['text']],
                            $this->centeredTextStyle()
                        );

                        $itemsCell = $table->addCell(2750, $this->centeredCellStyle());
                        $this->addQuestionAverageItemsToCell($itemsCell, $dimension['items'], $dimension['note'] ?? null);
                    }
                }
            }

            $footer = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 8,
            ]);

            $footer->addRow(320, ['cantSplit' => true]);

            $globalLevel = $this->classifyNom035Score('global', null, (int) $summary['final_total']);
            $globalStyle = $this->getWordRiskCellStyle($globalLevel['key']);

            $footerCell = $footer->addCell(4600, $this->centeredCellStyle(['bgColor' => $globalStyle['bg']]));
            $footerRun = $footerCell->addTextRun($this->centeredTextStyle());
            $footerRun->addText(
                'Calificación Total Final',
                ['bold' => true, 'size' => 8, 'color' => $globalStyle['text']]
            );
            $footerRun->addTextBreak();
            $footerRun->addText(
                $summary['final_total'] . ' / 288 - ' . number_format($summary['final_percentage'], 2) . ' %',
                ['bold' => true, 'size' => 8, 'color' => $globalStyle['text']]
            );

            $footer->addCell(4600, $this->centeredCellStyle(['bgColor' => 'D9D9D9']))->addText(
                $summary['participants'] . ' Participantes',
                ['bold' => true, 'size' => 8, 'color' => '111111'],
                $this->centeredTextStyle()
            );
        }

        private function stripFirstTwoLeadingZeros(?string $value): string
        {
            $value = trim((string) $value);

            if ($value === '') {
                return 'N/D';
            }

            if (preg_match('/^[A-Za-z]+\d+$/', $value)) {
                return preg_replace('/^([A-Za-z]+)00/', '$1', $value) ?? $value;
            }

            return preg_replace('/^00/', '', $value) ?: $value;
        }

    private function formatFolioWithSource(?string $folio, ?string $source = null): string
        {
            $folio = $this->stripFirstTwoLeadingZeros($folio);

            if ($folio === 'N/D') {
                return $folio;
            }

            if (preg_match('/^[LP][-\s]?\d+$/i', $folio)) {
                return strtoupper(substr($folio, 0, 1)) . substr($folio, 1);
            }

            $prefix = match ($source) {
                'online' => 'L',
                'paper' => 'P',
                default => '',
            };

            return $prefix !== '' ? $prefix . $folio : $folio;
        }

    private function addWorkerIdentificationByDimensionSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $groups = $this->getWorkerIdentificationByDimensionSummary($organization->id, $workCenter->id);

            $section->addTitle('X. Análisis de trabajadores referencia dimensión', 1);

            if (empty($groups)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores con nivel Medio, Alto o Muy Alto por dimensión para el centro de trabajo seleccionado.'
                );

                return;
}

            $this->addWorkerIdentificationRiskLegend($section);

            foreach ($groups as $groupIndex => $group) {
                if ($groupIndex > 0) {
                    $section->addTextBreak(1);
                }

                $section->addText(
                str_pad((string) ($group['number'] ?? 0), 2, '0', STR_PAD_LEFT) . ' ' . $group['name'],
                ['bold' => true, 'underline' => 'single', 'size' => 11],
                ['spaceBefore' => 120, 'spaceAfter' => 80]
            );

                $table = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 6,
                    'borderColor' => '808080',
                    'cellMargin' => 55,
                ]);

                // encabezado
                $table->addRow();
                $this->addWorkerHeaderCell($table, 900, 'Riesgo');
                $this->addWorkerHeaderCell($table, 1200, 'Folio');
                $this->addWorkerHeaderCell($table, 400, 'Calif.');
                $this->addWorkerHeaderCell($table, 4900, 'Nombre');
                $this->addWorkerHeaderCell($table, 2200, 'Área');
                $this->addWorkerHeaderCell($table, 1700, 'Puesto');

                foreach ($group['rows'] as $row) {
                    $table->addRow();

                    $dimensionStyle = $this->getWordRiskCellStyle($row['dimension_level_key'] ?? 'nulo');
                    $globalStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                    $riskCode = 'D' . str_pad((string) ($group['number'] ?? 0), 2, '0', STR_PAD_LEFT);
                    $folioValue = $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null);

                    $table->addCell(900, [
                        'bgColor' => $dimensionStyle['bg'],
                        'valign' => 'center',
                    ])->addText(
                        $riskCode,
                        [
                            'bold' => true,
                            'size' => 10,
                            'color' => $dimensionStyle['text'],
                        ],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(1200, [
                        'valign' => 'center',
                    ])->addText(
                        $folioValue,
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(400, [
                        'bgColor' => $globalStyle['bg'],
                        'valign' => 'center',
                    ])->addText(
                        (string) ($row['global_score'] ?? 0),
                        [
                            'bold' => true,
                            'size' => 10,
                            'color' => $globalStyle['text'],
                        ],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(4900, [
                        'valign' => 'center',
                    ])->addText(
                        $this->safeValue($row['name']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(2200, [
                        'valign' => 'center',
                    ])->addText(
                        $this->safeValue($row['area']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(1700, [
                        'valign' => 'center',
                    ])->addText(
                        $this->safeValue($row['position']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                // fila total
                $table->addRow();

                $table->addCell(9450, [
                    'gridSpan' => 5,
                    'bgColor' => 'D9D9D9',
                    'valign' => 'center',
                ])->addText(
                    'T o t a l',
                    ['bold' => true, 'size' => 10, 'color' => '111111'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(1850, [
                    'bgColor' => '062A78',
                    'valign' => 'center',
                ])->addText(
                    (string) count($group['rows']),
                    ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }
        }

        private function addWorkerIdentificationByPositionSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $groups = $this->getWorkerIdentificationByPositionSummary($organization->id, $workCenter->id);

            $section->addTitle('XII. Análisis de trabajadores nivel de riesgo referencia puesto', 1);

            if (empty($groups)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores para generar la identificación por puestos.'
                );

                return;
            }

            $this->addWorkerIdentificationRiskLegend($section);

            foreach ($groups as $groupIndex => $group) {
                if ($groupIndex > 0) {
                    $section->addTextBreak(1);
                }

                $this->addQuestionAverageGenderBand($section, $group['name']);

                $table = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 6,
                    'borderColor' => '808080',
                    'cellMargin' => 55,
                ]);

                $table->addRow();
                $this->addWorkerHeaderCell($table, 900, 'Folio');
                $this->addWorkerHeaderCell($table, 900, 'Calif.');
                $this->addWorkerHeaderCell($table, 5200, 'Nombre');
                $this->addWorkerHeaderCell($table, 2200, 'Área');
                $this->addWorkerHeaderCell($table, 1700, 'Jornada');

                foreach ($group['rows'] as $row) {
                    $table->addRow();

                    $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                    $table->addCell(900)->addText(
                        $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(900, ['bgColor' => $riskStyle['bg']])->addText(
                        (string) ($row['global_score'] ?? 0),
                        ['bold' => true, 'size' => 10, 'color' => $riskStyle['text']],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(5200)->addText(
                        $this->safeValue($row['name']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(2200)->addText(
                        $this->safeValue($row['area']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(1700)->addText(
                        $this->safeValue($row['work_schedule']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }
            }
        }

        private function addWorkerIdentificationByDepartmentSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $groups = $this->getWorkerIdentificationByDepartmentSummary($organization->id, $workCenter->id);

            $section->addTitle('XI. Análisis de trabajadores nivel de riesgo referencia área', 1);

            if (empty($groups)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores para generar la identificación por áreas.'
                );

                return;
            }

            $this->addWorkerIdentificationRiskLegend($section);

            foreach ($groups as $groupIndex => $group) {
                if ($groupIndex > 0) {
                    $section->addTextBreak(1);
                }

                $this->addQuestionAverageGenderBand($section, $group['name']);

                $table = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 6,
                    'borderColor' => '808080',
                    'cellMargin' => 55,
                ]);

                $table->addRow();
                $this->addWorkerHeaderCell($table, 1200, 'Folio');
                $this->addWorkerHeaderCell($table, 450, 'Calif.');
                $this->addWorkerHeaderCell($table, 4300, 'Nombre');
                $this->addWorkerHeaderCell($table, 2400, 'Área');
                $this->addWorkerHeaderCell($table, 1450, 'Jornada');

                foreach ($group['rows'] as $row) {
                    $table->addRow();

                    $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                    $table->addCell(1200, [
                        'valign' => 'center',
                    ])->addText(
                        $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(450, [
                        'bgColor' => $riskStyle['bg'],
                        'valign' => 'center',
                    ])->addText(
                        (string) ($row['global_score'] ?? 0),
                        ['bold' => true, 'size' => 10, 'color' => $riskStyle['text']],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(4300, [
                        'valign' => 'center',
                    ])->addText(
                        $this->safeValue($row['name']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(2400, [
                        'valign' => 'center',
                    ])->addText(
                        $this->safeValue($row['position']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(1450, [
                        'valign' => 'center',
                    ])->addText(
                        $this->safeValue($row['work_schedule']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }
            }
        }

        private function addWorkerIdentificationByWorkScheduleSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $groups = $this->getWorkerIdentificationByWorkScheduleSummary($organization->id, $workCenter->id);

           $section->addTitle('XIII. Análisis de trabajadores nivel de riesgo referencia jornada laboral', 1);

            if (empty($groups)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores para generar la identificación por jornada laboral.'
                );

                return;
            }

            $this->addWorkerIdentificationRiskLegend($section);

            foreach ($groups as $groupIndex => $group) {
                if ($groupIndex > 0) {
                    $section->addTextBreak(1);
                }

                $this->addQuestionAverageGenderBand($section, $group['name']);

                $table = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 6,
                    'borderColor' => '808080',
                    'cellMargin' => 55,
                ]);

                $table->addRow();
                $this->addWorkerHeaderCell($table, 900, 'Folio');
                $this->addWorkerHeaderCell($table, 900, 'Calif.');
                $this->addWorkerHeaderCell($table, 5200, 'Nombre');
                $this->addWorkerHeaderCell($table, 2200, 'Área');
                $this->addWorkerHeaderCell($table, 2200, 'Puesto');

                foreach ($group['rows'] as $row) {
                    $table->addRow();

                    $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                    $table->addCell(900)->addText(
                        $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(900, ['bgColor' => $riskStyle['bg']])->addText(
                        (string) ($row['global_score'] ?? 0),
                        ['bold' => true, 'size' => 10, 'color' => $riskStyle['text']],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(5200)->addText(
                        $this->safeValue($row['name']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(2200)->addText(
                        $this->safeValue($row['area']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );

                    $table->addCell(2200)->addText(
                        $this->safeValue($row['position']),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }
            }
        }

        private function addSevereTraumaticEventsSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $summary = $this->getSevereTraumaticEventsSummary($organization->id, $workCenter->id);
            $panorama = $this->getAtsPanoramaSummary($organization->id, $workCenter->id);

            $section->addTitle('XIV. Análisis de trabajadores referencia acontecimientos traumáticos severos', 1);

            $paragraphs = [
                'Un Acontecimiento Traumático Severo es aquel experimentado durante o con motivo del trabajo que se caracteriza por la ocurrencia de la muerte o que representa un peligro real para la integridad física de una o varias personas y que puede generar trastorno de estrés postraumático para quien lo sufre o lo presencia.',
                'La obligación de identificar a los trabajadores que fueron sujetos a acontecimientos traumáticos severos, canalizarlos para su atención aplica para todos los centros de trabajo.',
                'Es conveniente reiterar que esta obligación comprende todos los acontecimientos traumáticos severos que ocurran con motivo o en ejercicio del trabajo y debe considerarse al trabajador que directamente padece el acontecimiento, pero también aquellos que lo presenciaron.',
                'La Norma prevé que los exámenes médicos y evaluaciones psicológicas puedan efectuarse a través de la institución de seguridad social o privada, médico, psiquiatra o psicólogo del centro de trabajo.',
                'En todos los casos el médico será el responsable de determinar la necesidad de practicar exámenes médicos a los trabajadores y/o de canalizarlos para que reciban atención psicológica.',
            ];

            foreach ($paragraphs as $text) {
                $section->addText(
                    $text,
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
                );
            }

            $section->addTextBreak(1);

            if (($panorama['participants_considered'] ?? 0) > 0) {
                $section->addText(
                    'Panorama general de Acontecimientos',
                    ['bold' => true, 'size' => 16, 'color' => '1F2937'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
                );

                $section->addText(
                    'Esta gráfica muestra el panorama general de las personas que respondieron sí a alguna de las 6 preguntas de los acontecimientos traumáticos severos.',
                    ['size' => 10, 'color' => '4B5563'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 40]
                );

                $section->addText(
                    'Participantes considerados: ' .
                    $panorama['participants_considered'] .
                    ' (' . $panorama['without_events'] . ' sin acontecimientos traumáticos)',
                    ['size' => 10, 'color' => '6B7280'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
                );

                $chartPath = $this->generateAtsPanoramaChart(
                    $panorama['event_counts'],
                    $this->makeUniqueChartPath('ats_panorama')
                );

                $this->addChartImageIfExists($section, $chartPath, 560);

                $section->addTextBreak(1);
            }

            $section->addText(
                'Trabajadores que Fueron Sujetos a Acontecimientos Traumáticos Severos',
                ['bold' => true, 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
            );

            if (empty($summary['rows'])) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores con registro de acontecimientos traumáticos severos.'
                );

                return;
            }

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 45,
            ]);

            $table->addRow();
            $this->addWorkerHeaderCell($table, 700, 'Folio');
            $this->addWorkerHeaderCell($table, 4000, 'Nombre');
            $this->addWorkerHeaderCell($table, 1300, 'Género');
            $this->addWorkerHeaderCell($table, 1800, 'Puesto');
            $this->addWorkerHeaderCell($table, 500, 'S-I');
            $this->addWorkerHeaderCell($table, 500, 'S-II');
            $this->addWorkerHeaderCell($table, 500, 'S-III');
            $this->addWorkerHeaderCell($table, 500, 'S-IV');
            $this->addWorkerHeaderCell($table, 1000, 'Valoración');

                        foreach ($summary['rows'] as $row) {
                $table->addRow();

                $atsTotal = (int) ($row['s1'] ?? 0)
                    + (int) ($row['s2'] ?? 0)
                    + (int) ($row['s3'] ?? 0)
                    + (int) ($row['s4'] ?? 0);

                $atsLevelKey = match (true) {
                    $atsTotal >= 6 => 'muy_alto',
                    $atsTotal >= 4 => 'alto',
                    $atsTotal >= 2 => 'medio',
                    $atsTotal >= 1 => 'bajo',
                    default => 'nulo',
                };

                $atsStyle = $this->getWordRiskCellStyle($atsLevelKey);
                $neutralCell = ['bgColor' => ! empty($row['requires_valuation']) ? 'F3F4F6' : 'FFFFFF'];
                $highlightCell = ['bgColor' => $atsStyle['bg']];

                $table->addCell(700, $highlightCell)->addText(
                    $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null),
                    ['bold' => true, 'size' => 10, 'color' => $atsStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(4000, $highlightCell)->addText(
                    $this->safeValue($row['name']),
                    ['bold' => true, 'size' => 10, 'color' => $atsStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(1300, $neutralCell)->addText(
                    $this->safeValue($row['gender']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(1800, $neutralCell)->addText(
                    $this->safeValue($row['position']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                foreach (['s1', 's2', 's3', 's4'] as $key) {
                    $value = (int) ($row[$key] ?? 0);

                    $cellStyle = $value > 0 ? $highlightCell : $neutralCell;
                    $fontStyle = $value > 0
                        ? ['bold' => true, 'size' => 10, 'color' => $atsStyle['text']]
                        : ['size' => 10, 'color' => '111111'];

                    $table->addCell(500, $cellStyle)->addText(
                        (string) $value,
                        $fontStyle,
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                $table->addCell(1000, $highlightCell)->addText(
                    ! empty($row['requires_valuation']) ? 'Sí' : 'No',
                    ['bold' => true, 'size' => 10, 'color' => $atsStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $section->addTextBreak(1);

            $resume = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 50,
            ]);

            $resume->addRow();
            $cell = $resume->addCell(3600, ['bgColor' => 'D9D9D9']);
            $run = $cell->addTextRun(['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $run->addText(
                $summary['requires_valuation_total'] . ' Trabajadores',
                ['bold' => true, 'size' => 11]
            );
            $run->addTextBreak();
            $run->addText(
                'Requieren Valoración',
                ['bold' => true, 'size' => 11]
            );

            $rightCell = $resume->addCell(3600, ['bgColor' => 'D9D9D9']);
            $rightTable = $rightCell->addTable([
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 40,
            ]);

            $rightTable->addRow();
            $rightTable->addCell(3600)->addText(
                $summary['requires_valuation_men'] . ' Hombres',
                ['bold' => true, 'size' => 11],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $rightTable->addRow();
            $rightTable->addCell(3600)->addText(
                $summary['requires_valuation_women'] . ' Mujeres',
                ['bold' => true, 'size' => 11],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        private function addWorkplaceViolenceWorkersSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
           $rows = $this->getWorkplaceViolenceWorkersSummaryFromService($workCenter);

            $section->addText(
                'XV. Análisis de trabajadores referencia violencia laboral (detalle por trabajador)',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 120]
            );
            if (empty($rows)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores con respuestas asociadas a violencia laboral.'
                );

                return;
            }

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 35,
            ]);

            $table->addRow();
            $this->addWorkerHeaderCell($table, 650, 'Folio');
            $this->addWorkerHeaderCell($table, 3900, 'Nombre');
            $this->addWorkerHeaderCell($table, 1200, 'Género');
            $this->addWorkerHeaderCell($table, 650, 'ATS');
            $this->addWorkerHeaderCell($table, 800, 'Puntos');

            foreach ([57, 58, 59, 60, 61, 62, 63, 64] as $item) {
                $this->addWorkerHeaderCell($table, 420, 'P' . $item);
            }

            foreach ($rows as $row) {
                $table->addRow();

                $pointsStyle = $this->getWordRiskCellStyle((string) ($row['risk_level'] ?? 'nulo'));
                

                $table->addCell(650)->addText(
                    $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(3900)->addText(
                    $this->safeValue($row['name']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(1200)->addText(
                    $this->safeValue($row['gender']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(650)->addText(
                    ! empty($row['ats']) ? 'Sí' : '',
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(800, ['bgColor' => $pointsStyle['bg']])->addText(
                    (string) $row['points'],
                    ['bold' => true, 'size' => 10, 'color' => $pointsStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                foreach ([57, 58, 59, 60, 61, 62, 63, 64] as $item) {
                    $value = (int) ($row['items'][$item] ?? 0);
                    $bg = $this->getQuestionValueHex($value);
                    $textColor = $value === 2 ? '111111' : 'FFFFFF';

                    $table->addCell(420, ['bgColor' => $bg])->addText(
                        (string) $value,
                        ['bold' => true, 'size' => 10, 'color' => $textColor],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }
            }
        }

        private function addFinalRiskWorkersSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $section->addTitle('XVI. Análisis de trabajadores con nivel de riesgo final', 1);

            $rows = $this->getFinalRiskWorkersSummary($workCenter);

            if (empty($rows)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores con calificación final para este centro de trabajo.'
                );

                return;
            }

            $this->addWorkerIdentificationRiskLegend($section);

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 55,
            ]);

            $table->addRow();
            $this->addWorkerHeaderCell($table, 900, 'Folio');
            $this->addWorkerHeaderCell($table, 900, 'Calif.');
            $this->addWorkerHeaderCell($table, 5200, 'Nombre');
            $this->addWorkerHeaderCell($table, 2200, 'Área');
            $this->addWorkerHeaderCell($table, 1700, 'Puesto');

            foreach ($rows as $row) {
                $table->addRow();

                $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                $table->addCell(900)->addText(
                    $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(900, ['bgColor' => $riskStyle['bg']])->addText(
                    (string) ($row['global_score'] ?? 0),
                    ['bold' => true, 'size' => 10, 'color' => $riskStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(5200)->addText(
                    $this->safeValue($row['name']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(2200)->addText(
                    $this->safeValue($row['area']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(1700)->addText(
                    $this->safeValue($row['position']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }
        }

        private function addDomainQuantitativeAnalysisSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $groups = $this->getDomainQuantitativeAnalysisSummary($organization->id, $workCenter->id);

            $section->addTitle('XVII. Análisis cuantitativo referencia nivel de riesgo por dominio y puesto', 1);

            if (empty($groups)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron datos para generar el análisis cuantitativo de dominios.'
                );

                return;
            }

            foreach ($groups as $index => $group) {
                if ($index > 0) {
                    $section->addTextBreak(1);
                }

                $this->addQuestionAverageGenderBand($section, $group['name']);
                $this->renderDomainQuantitativeGroupTable($section, $group);
            }
        }

        private function renderDomainQuantitativeGroupTable(Section $section, array $group): void
        {
            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 45,
            ]);

            $table->addRow(700);

            $table->addCell(4200, ['bgColor' => 'D9D9D9'])->addText(
                'Dominio',
                ['bold' => true, 'size' => 11],
                ['spaceAfter' => 0]
            );

            $headers = [
                ['label' => 'Muy Alto', 'bg' => 'EF4444', 'text' => 'FFFFFF'],
                ['label' => 'Alto', 'bg' => 'F59E0B', 'text' => 'FFFFFF'],
                ['label' => 'Medio', 'bg' => 'F8FF03', 'text' => '111111'],
                ['label' => 'Bajo', 'bg' => '16A34A', 'text' => 'FFFFFF'],
                ['label' => 'Nulo', 'bg' => '3B82F6', 'text' => 'FFFFFF'],
                ['label' => 'MA+Al+Me', 'bg' => '991B1B', 'text' => 'FFFFFF'],
            ];

            foreach ($headers as $header) {
                $table->addCell(700, ['bgColor' => $header['bg']])->addText(
                    $header['label'],
                    ['bold' => true, 'size' => 11, 'color' => $header['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            foreach ($group['rows'] as $row) {
                $table->addRow(580);

                $table->addCell(4200)->addText(
                    $row['label'],
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                foreach (['muy_alto', 'alto', 'medio', 'bajo', 'nulo'] as $levelKey) {
                    $value = (int) ($row['distribution'][$levelKey] ?? 0);

                    $table->addCell(700)->addText(
                        $value > 0 ? (string) $value : '',
                        ['bold' => true, 'size' => 11],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                $table->addCell(900)->addText(
                    (string) ($row['attention'] ?? 0),
                    ['bold' => true, 'size' => 11],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $section->addTextBreak(1);

            $footer = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 30,
            ]);

            $footer->addRow();
            $footer->addCell(9400, ['bgColor' => 'D9D9D9'])->addText(
                'T o t a l    ' . $this->safeValue($group['name']) . ': ' . $group['participants'],
                ['bold' => true, 'size' => 11],
                ['alignment' => Jc::END, 'spaceAfter' => 0]
            );
        }

        private function addWorkerIdentificationByCategorySection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $summary = $this->getWorkerIdentificationByCategorySummary($organization->id, $workCenter->id);

            $rows = $summary['rows'] ?? [];
            $categoryNames = $summary['categories'] ?? [];

            $section->addTitle('XVIII. Análisis de trabajadores referencia nivel de riesgo por categoría', 1);

            if (empty($rows) || empty($categoryNames)) {
                $this->addNoDataNotice(
                    $section,
                    'No se encontraron trabajadores con evaluación de Referencia III para la identificación por categoría aplicable.'
                );

                return;
            }
            $categoryLegendText = collect($categoryNames)
                ->values()
                ->map(fn ($name, $index): string => ((int) $index + 1) . '. ' . $this->safeValue($name))
                ->implode('   ');

            $section->addText(
                $categoryLegendText,
                ['size' => 10],
                ['spaceAfter' => 120]
            );

            $this->addWorkerIdentificationRiskLegend($section);

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 45,
            ]);

            $categoryCount = max(count($categoryNames), 1);
            $categoryWidth = (int) floor((9800 - 4950) / $categoryCount);
            $categoryWidth = max(850, min(1450, $categoryWidth));

            $table->addRow();
            $this->addWorkerHeaderCell($table, 800, 'Folio');
            $this->addWorkerHeaderCell($table, 750, 'Calif.');
            $this->addWorkerHeaderCell($table, 3400, 'Nombre');

            foreach ($categoryNames as $index => $categoryName) {
                $this->addWorkerHeaderCell($table, $categoryWidth, 'Cat. ' . ((int) $index + 1));
            }

            foreach ($rows as $row) {
                $table->addRow();

                $globalStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                $table->addCell(800, $this->centeredCellStyle())->addText(
                    $this->formatFolioWithSource((string) ($row['folio'] ?? ''), $row['source'] ?? null),
                    ['size' => 10],
                    $this->centeredTextStyle()
                );

                $table->addCell(750, $this->centeredCellStyle(['bgColor' => $globalStyle['bg']]))->addText(
                    (string) ($row['global_score'] ?? 0),
                    ['bold' => true, 'size' => 10, 'color' => $globalStyle['text']],
                    $this->centeredTextStyle()
                );

                $table->addCell(3400, $this->centeredCellStyle())->addText(
                    $this->safeValue($row['name']),
                    ['size' => 10],
                    $this->centeredTextStyle()
                );

                foreach ($categoryNames as $categoryName) {
                    $category = $row['categories'][$categoryName] ?? null;

                    if (! $category) {
                        $table->addCell($categoryWidth, $this->centeredCellStyle(['bgColor' => 'E5E7EB']))->addText(
                            'N/A',
                            ['bold' => true, 'size' => 9, 'color' => '6B7280'],
                            $this->centeredTextStyle()
                        );

                        continue;
                    }

                    $categoryStyle = $this->getWordRiskCellStyle($category['level_key'] ?? 'nulo');

                    $table->addCell($categoryWidth, $this->centeredCellStyle(['bgColor' => $categoryStyle['bg']]))->addText(
                        (string) ($category['score'] ?? 0),
                        ['bold' => true, 'size' => 10, 'color' => $categoryStyle['text']],
                        $this->centeredTextStyle()
                    );
                }
            }
        }

        private function addWorkerIdentificationRiskLegend(Section $section): void
        {
           $section->addText(
            'Nivel de riesgo',
            ['size' => 10],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]
        );

        $legend = $section->addTable([
            'alignment' => JcTable::CENTER,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 20,
        ]);

        $legend->addRow();

        $items = [
            ['Nulo', '3B82F6', 'FFFFFF'],
            ['Bajo', '16A34A', 'FFFFFF'],
            ['Medio', 'F8FF03', '111111'],
            ['Alto', 'F59E0B', 'FFFFFF'],
            ['Muy Alto', 'EF4444', 'FFFFFF'],
        ];

        foreach ($items as [$label, $bg, $text]) {
            $legend->addCell(1700, [
                'bgColor' => $bg,
                'valign' => 'center',
            ])->addText(
                $label,
                ['bold' => true, 'size' => 10, 'color' => $text],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        $section->addTextBreak(1);
        }

        private function getQuestionAverageMatrixSummary(string $organizationId, string $workCenterId): array
        {
            return $this->getQuestionAverageMatrixSummaryFiltered($organizationId, $workCenterId, null);
        }

        private function getQuestionAverageMatrixSummaryByGender(
            string $organizationId,
            string $workCenterId,
            array $genderLabels
        ): array {
            return $this->getQuestionAverageMatrixSummaryFiltered(
                $organizationId,
                $workCenterId,
                $genderLabels,
                'dd.gender'
            );
        }

        private function getQuestionAverageMatrixSummaryByPosition(
            string $organizationId,
            string $workCenterId,
            string $positionLabel
        ): array {
            return $this->getQuestionAverageMatrixSummaryFiltered(
                $organizationId,
                $workCenterId,
                [$positionLabel],
                'dd.position'
            );
        }

        private function getQuestionAverageMatrixSummaryByDepartment(
            string $organizationId,
            string $workCenterId,
            string $departmentLabel
        ): array {
            return $this->getQuestionAverageMatrixSummaryFiltered(
                $organizationId,
                $workCenterId,
                [$departmentLabel],
                'dd.department'
            );
        }

        private function getQuestionAverageMatrixSummaryByWorkSchedule(
            string $organizationId,
            string $workCenterId,
            string $workScheduleLabel
        ): array {
            return $this->getQuestionAverageMatrixSummaryFiltered(
                $organizationId,
                $workCenterId,
                [$workScheduleLabel],
                'dd.work_schedule'
            );
        }

        private function getQuestionAverageWorkScheduleLabels(string $organizationId, string $workCenterId): array
        {
            $base = DB::table('paper_evaluations as pe')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at');

            return collect($this->groupDemographicCounts($base, 'dd.work_schedule'))
                ->filter(function ($row) {
                    $label = trim((string) ($row['label'] ?? ''));
                    return $label !== '' && $label !== 'N/D';
                })
                ->values()
                ->all();
        }

        private function getQuestionAverageDepartmentLabels(string $organizationId, string $workCenterId): array
        {
            $base = DB::table('paper_evaluations as pe')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at');

            return collect($this->groupDemographicCounts($base, 'dd.department'))
                ->filter(function ($row) {
                    $label = trim((string) ($row['label'] ?? ''));
                    return $label !== '' && $label !== 'N/D';
                })
                ->values()
                ->all();
        }

        private function getQuestionAveragePositionLabels(string $organizationId, string $workCenterId): array
        {
            $base = DB::table('paper_evaluations as pe')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at');

            return collect($this->groupDemographicCounts($base, 'dd.position'))
                ->filter(function ($row) {
                    $label = trim((string) ($row['label'] ?? ''));
                    return $label !== '' && $label !== 'N/D';
                })
                ->values()
                ->all();
        }

        private function getQuestionAverageMatrixSummaryByGlobalLevel(
            string $organizationId,
            string $workCenterId,
            string $globalLevelKey
        ): array {
            $rows = DB::table('paper_evaluations as pe')
                ->join('evaluation_answers as ea', 'ea.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.source',
                    'ea.question_key',
                    'ea.answer_value'
                )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            $evaluations = $this->getReferenceThreeEvaluations($rows);

            $evaluationIds = collect($evaluations)
                ->filter(fn ($evaluation) => ($evaluation['global_level_key'] ?? null) === $globalLevelKey)
                ->pluck('evaluation_id')
                ->values()
                ->all();

            return $this->getQuestionAverageMatrixSummaryByEvaluationIds(
                $organizationId,
                $workCenterId,
                $evaluationIds
            );
        }

        private function getQuestionAverageMatrixSummaryByEvaluationIds(
            string $organizationId,
            string $workCenterId,
            array $evaluationIds
        ): array {
            if (empty($evaluationIds)) {
                $layout = $this->getQuestionAverageMatrixLayout();

                $categories = [];

                foreach ($layout as $category) {
                    $domains = [];

                    foreach ($category['domains'] as $domain) {
                        $dimensions = [];

                        foreach ($domain['dimensions'] as $dimension) {
                            $items = [];

                            foreach ($dimension['items'] as $itemNumber) {
                                $items[] = [
                                    'number' => $itemNumber,
                                    'score' => 0,
                                ];
                            }

                            $dimensions[] = [
                                'name' => $dimension['name'],
                                'items' => $items,
                                'score' => 0,
                                'note' => ! empty($dimension['note_key'])
                                    ? ('*' . $dimension['note_key'] . ' / 0')
                                    : null,
                            ];
                        }

                        $domains[] = [
                            'name' => $domain['name'],
                            'score' => 0,
                            'dimensions' => $dimensions,
                        ];
                    }

                    $categories[] = [
                        'name' => $category['name'],
                        'score' => 0,
                        'domains' => $domains,
                    ];
                }

                return [
                    'participants' => 0,
                    'final_total' => 0,
                    'final_percentage' => 0,
                    'categories' => $categories,
                ];
            }

            $query = DB::table('paper_evaluations as pe')
                ->join('evaluation_answers as ea', 'ea.paper_evaluation_id', '=', 'pe.id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->whereIn('pe.id', $evaluationIds);

            $rows = $query
            ->select(
                'pe.id as evaluation_id',
                'ea.question_key',
                'ea.answer_value',
                'dd.extra_fields'
            )
            ->orderBy('pe.id')
            ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
            ->get();

        $participants = $rows->pluck('evaluation_id')->unique()->count();

        $questionTotals = [];
        $noteEvaluations = [
            'a' => [],
            'b' => [],
        ];

        foreach ($rows as $row) {
            $extra = json_decode((string) ($row->extra_fields ?? '[]'), true);
            if (! is_array($extra)) {
                $extra = [];
            }

            $key = (int) $row->question_key;

            $attendsPublic = $this->extractWorkerFlag($extra, [
                'atiende', 'atiende_clientes', 'atencion_clientes',
                'servicio_clientes', 'servicio_usuarios', 'client_service', 'attends_public',
            ]) || in_array($key, [65, 66, 67, 68], true);

            $isBoss = $this->extractWorkerFlag($extra, [
                'jefe', 'soy_jefe', 'is_boss', 'is_manager',
                'supervises_people', 'supervisa_personal', 'jefe_trabajadores',
            ]) || in_array($key, [69, 70, 71, 72], true);

            if ($attendsPublic) {
                $noteEvaluations['a'][$row->evaluation_id] = true;
            }

            if ($isBoss) {
                $noteEvaluations['b'][$row->evaluation_id] = true;
            }

            $key = (int) $row->question_key;

            if (in_array($key, [65, 66, 67, 68], true) && ! $attendsPublic) {
                continue;
            }

            if (in_array($key, [69, 70, 71, 72], true) && ! $isBoss) {
                continue;
            }

            $score = $this->getReferenceThreeScore($row->question_key, $row->answer_value);

            if ($score === null) {
                continue;
            }

            if (! isset($questionTotals[$key])) {
                $questionTotals[$key] = ['sum' => 0, 'count' => 0];
            }

            $questionTotals[$key]['sum'] += $score;
            $questionTotals[$key]['count']++;
        }

            $noteCounts = [
                'a' => count($noteEvaluations['a']),
                'b' => count($noteEvaluations['b']),
            ];

            $layout = $this->getQuestionAverageMatrixLayout();

            $categories = [];
            $finalTotal = 0;

            foreach ($layout as $category) {
                $categoryScore = 0;
                $domains = [];

                foreach ($category['domains'] as $domain) {
                    $domainScore = 0;
                    $dimensions = [];

                                        foreach ($domain['dimensions'] as $dimension) {
                        $dimensionScore = 0.0;
                        $items = [];

                        foreach ($dimension['items'] as $itemNumber) {
                            $avgRaw = 0.0;

                            if (! empty($questionTotals[$itemNumber]['count'])) {
                                $avgRaw = $questionTotals[$itemNumber]['sum'] / $questionTotals[$itemNumber]['count'];
                            }

                            $itemScore = max(0, min(4, (int) round($avgRaw, 0, PHP_ROUND_HALF_UP)));

                            $items[] = [
                                'number' => $itemNumber,
                                'score' => $itemScore,
                            ];

                            $dimensionScore += $itemScore;
                        }

                        $dimensionDisplayScore = (int) $dimensionScore;

                        $dimensions[] = [
                            'name' => $dimension['name'],
                            'items' => $items,
                            'score' => $dimensionDisplayScore,
                            'note' => ! empty($dimension['note_key'])
                                ? ('*' . $dimension['note_key'] . ' / ' . ($noteCounts[$dimension['note_key']] ?? 0))
                                : null,
                        ];

                        $domainScore += $dimensionDisplayScore;
                    }

                    $domainDisplayScore = (int) $domainScore;

                    $domains[] = [
                        'name' => $domain['name'],
                        'score' => $domainDisplayScore,
                        'dimensions' => $dimensions,
                    ];

                    $categoryScore += $domainDisplayScore;
                }

                $categoryDisplayScore = (int) $categoryScore;

                $categories[] = [
                    'name' => $category['name'],
                    'score' => $categoryDisplayScore,
                    'domains' => $domains,
                ];

                $finalTotal += $categoryDisplayScore;
            }

            $finalTotal = (int) $finalTotal;

            return [
                'participants' => $participants,
                'final_total' => $finalTotal,
                'final_percentage' => round(($finalTotal / 288) * 100, 2),
                'categories' => $categories,
            ];
        }

        private function getQuestionAverageMatrixSummaryFiltered(
            string $organizationId,
            string $workCenterId,
            ?array $filterValues = null,
            string $filterColumn = 'dd.gender'
        ): array {
            $query = DB::table('paper_evaluations as pe')
                ->join('evaluation_answers as ea', 'ea.paper_evaluation_id', '=', 'pe.id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at');

            if ($filterValues !== null && ! empty($filterValues)) {
                $normalized = array_map(
                    fn ($value) => mb_strtolower(trim((string) $value)),
                    $filterValues
                );

                $placeholders = implode(',', array_fill(0, count($normalized), '?'));

                $query->whereRaw(
                    "LOWER(TRIM(COALESCE($filterColumn, ''))) IN ($placeholders)",
                    $normalized
                );
            }

            $rows = $query
            ->select(
                'pe.id as evaluation_id',
                'ea.question_key',
                'ea.answer_value',
                'dd.extra_fields'
            )
            ->orderBy('pe.id')
            ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
            ->get();

        $participants = $rows->pluck('evaluation_id')->unique()->count();

        $questionTotals = [];
        $noteEvaluations = [
            'a' => [],
            'b' => [],
        ];

        foreach ($rows as $row) {
            $extra = json_decode((string) ($row->extra_fields ?? '[]'), true);
            if (! is_array($extra)) {
                $extra = [];
            }

           $key = (int) $row->question_key;

            $attendsPublic = $this->extractWorkerFlag($extra, [
                'atiende', 'atiende_clientes', 'atencion_clientes',
                'servicio_clientes', 'servicio_usuarios', 'client_service', 'attends_public',
            ]) || in_array($key, [65, 66, 67, 68], true);

            $isBoss = $this->extractWorkerFlag($extra, [
                'jefe', 'soy_jefe', 'is_boss', 'is_manager',
                'supervises_people', 'supervisa_personal', 'jefe_trabajadores',
            ]) || in_array($key, [69, 70, 71, 72], true);

            if ($attendsPublic) {
                $noteEvaluations['a'][$row->evaluation_id] = true;
            }

            if ($isBoss) {
                $noteEvaluations['b'][$row->evaluation_id] = true;
            }

            $key = (int) $row->question_key;

            if (in_array($key, [65, 66, 67, 68], true) && ! $attendsPublic) {
                continue;
            }

            if (in_array($key, [69, 70, 71, 72], true) && ! $isBoss) {
                continue;
            }

            $score = $this->getReferenceThreeScore($row->question_key, $row->answer_value);

            if ($score === null) {
                continue;
            }

            if (! isset($questionTotals[$key])) {
                $questionTotals[$key] = ['sum' => 0, 'count' => 0];
            }

            $questionTotals[$key]['sum'] += $score;
            $questionTotals[$key]['count']++;
        }

                        $noteCounts = [
                'a' => count($noteEvaluations['a']),
                'b' => count($noteEvaluations['b']),
            ];

            $layout = $this->getQuestionAverageMatrixLayout();

            $categories = [];
            $finalTotal = 0;

            foreach ($layout as $category) {
                $categoryScore = 0.0;
                $domains = [];

                foreach ($category['domains'] as $domain) {
                    $domainScore = 0.0;
                    $dimensions = [];

                    foreach ($domain['dimensions'] as $dimension) {
                        $dimensionScore = 0.0;
                        $items = [];

                        foreach ($dimension['items'] as $itemNumber) {
                            $avgRaw = 0.0;

                            if (! empty($questionTotals[$itemNumber]['count'])) {
                                $avgRaw = $questionTotals[$itemNumber]['sum'] / $questionTotals[$itemNumber]['count'];
                            }

                            $itemScore = max(0, min(4, (int) round($avgRaw, 0, PHP_ROUND_HALF_UP)));

                            $items[] = [
                                'number' => $itemNumber,
                                'score' => $itemScore,
                            ];

                            $dimensionScore += $itemScore;
                        }

                        $dimensionDisplayScore = (int) $dimensionScore;

                        $dimensions[] = [
                            'name' => $dimension['name'],
                            'items' => $items,
                            'score' => $dimensionDisplayScore,
                            'note' => ! empty($dimension['note_key'])
                                ? ('*' . $dimension['note_key'] . ' / ' . ($noteCounts[$dimension['note_key']] ?? 0))
                                : null,
                        ];

                        $domainScore += $dimensionDisplayScore;
                    }

                    $domainDisplayScore = (int) $domainScore;

                    $domains[] = [
                        'name' => $domain['name'],
                        'score' => $domainDisplayScore,
                        'dimensions' => $dimensions,
                    ];

                    $categoryScore += $domainDisplayScore;
                }

                $categoryDisplayScore = (int) $categoryScore;

                $categories[] = [
                    'name' => $category['name'],
                    'score' => $categoryDisplayScore,
                    'domains' => $domains,
                ];

                $finalTotal += $categoryDisplayScore;
            }

            $finalTotal = (int) round($finalTotal, 0, PHP_ROUND_HALF_UP);

            return [
                'participants' => $participants,
                'final_total' => $finalTotal,
                'final_percentage' => round(($finalTotal / 288) * 100, 2),
                'categories' => $categories,
            ];
        }

        private function getQuestionAverageMatrixLayout(): array
        {
            return [
                [
                    'name' => 'Ambiente de trabajo',
                    'domains' => [
                        [
                            'name' => 'Condiciones en el ambiente de trabajo',
                            'dimensions' => [
                                ['name' => 'Condiciones peligrosas e inseguras', 'items' => [1, 3]],
                                ['name' => 'Condiciones deficientes e insalubres', 'items' => [2, 4]],
                                ['name' => 'Trabajos peligrosos', 'items' => [5]],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Factores propios de la actividad',
                    'domains' => [
                        [
                            'name' => 'Carga de trabajo',
                            'dimensions' => [
                                ['name' => 'Cargas cuantitativas', 'items' => [6, 12]],
                                ['name' => 'Ritmos de trabajo acelerado', 'items' => [7, 8]],
                                ['name' => 'Carga mental', 'items' => [9, 10, 11]],
                                ['name' => 'Cargas psicológicas emocionales', 'items' => [65, 66, 67, 68], 'note_key' => 'a'],
                                ['name' => 'Cargas de alta responsabilidad', 'items' => [13, 14]],
                                ['name' => 'Cargas contradictorias o inconsistentes', 'items' => [15, 16]],
                            ],
                        ],
                        [
                            'name' => 'Falta de control sobre el trabajo',
                            'dimensions' => [
                                ['name' => 'Falta de control y autonomía sobre el trabajo', 'items' => [25, 26, 27, 28]],
                                ['name' => 'Limitada o nula posibilidad de desarrollo', 'items' => [23, 24]],
                                ['name' => 'Insuficiente participación y manejo del cambio', 'items' => [29, 30]],
                                ['name' => 'Limitada o inexistente capacitación', 'items' => [35, 36]],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Organización del tiempo de trabajo',
                    'domains' => [
                        [
                            'name' => 'Jornada de trabajo',
                            'dimensions' => [
                                ['name' => 'Jornadas de trabajo extensas', 'items' => [17, 18]],
                            ],
                        ],
                        [
                            'name' => 'Interferencia en la relación trabajo-familia',
                            'dimensions' => [
                                ['name' => 'Influencia del trabajo fuera del centro laboral', 'items' => [19, 20]],
                                ['name' => 'Influencia de las responsabilidades familiares', 'items' => [21, 22]],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Liderazgo y relaciones en el trabajo',
                    'domains' => [
                        [
                            'name' => 'Liderazgo',
                            'dimensions' => [
                                ['name' => 'Escasa claridad de funciones', 'items' => [31, 32, 33, 34]],
                                ['name' => 'Características del liderazgo', 'items' => [37, 38, 39, 40, 41]],
                            ],
                        ],
                        [
                            'name' => 'Relaciones en el trabajo',
                            'dimensions' => [
                                ['name' => 'Relaciones sociales en el trabajo', 'items' => [42, 43, 44, 45, 46]],
                                ['name' => 'Deficiente relación con los colaboradores que supervisa', 'items' => [69, 70, 71, 72], 'note_key' => 'b'],
                            ],
                        ],
                        [
                            'name' => 'Violencia',
                            'dimensions' => [
                                ['name' => 'Violencia laboral', 'items' => [57, 58, 59, 60, 61, 62, 63, 64]],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Entorno organizacional',
                    'domains' => [
                        [
                            'name' => 'Reconocimiento del desempeño',
                            'dimensions' => [
                                ['name' => 'Escasa o nula retroalimentación del desempeño', 'items' => [47, 48]],
                                ['name' => 'Escaso o nulo reconocimiento y compensación', 'items' => [49, 50, 51, 52]],
                            ],
                        ],
                        [
                            'name' => 'Insuficiente sentido de pertenencia e inestabilidad',
                            'dimensions' => [
                                ['name' => 'Limitado sentido de pertenencia', 'items' => [55, 56]],
                                ['name' => 'Inestabilidad laboral', 'items' => [53, 54]],
                            ],
                        ],
                    ],
                ],
            ];
        }

        private function getQuestionValueHex(int $score): string
        {
            return match ($score) {
                4 => 'EF4444',
                3 => 'F59E0B',
                2 => 'F8FF03',
                1 => '16A34A',
                default => '3B82F6',
            };
        }

        private function addWorkerHeaderCell($table, int $width, string $label): void
        {
            $table->addCell($width, [
                'bgColor' => '062A78',
                'valign' => 'center',
            ])->addText(
                $label,
                ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        private function addQuestionAverageItemsToCell($cell, array $items, ?string $note = null): void
            {
                $itemsTable = $cell->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 0,
                    'cellMargin' => 4,
                ]);

                $itemsTable->addRow(220, ['cantSplit' => true]);

                foreach ($items as $item) {
                    $hex = $this->getQuestionValueHex((int) $item['score']);
                    $textColor = ((int) $item['score'] === 2) ? '111111' : 'FFFFFF';

                    $itemsTable->addCell(280, [
                        'bgColor' => $hex,
                        'borderSize' => 6,
                        'borderColor' => '333333',
                        'valign' => 'center',
                    ])->addText(
                        (string) $item['number'],
                        ['bold' => true, 'size' => 7, 'color' => $textColor],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                if ($note) {
                    $itemsTable->addCell(600, [
                        'bgColor' => 'D9D9D9',
                        'borderSize' => 6,
                        'borderColor' => '333333',
                        'valign' => 'center',
                    ])->addText(
                        $note,
                        ['size' => 7, 'color' => '111111'],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }
            }

        private function addDistributionTable(Section $section, string $title, array $rows): void
            {
                $rows = collect($rows)
                    ->map(function ($row) {
                        return [
                            'label' => trim((string) ($row['label'] ?? '')),
                            'total' => (int) ($row['total'] ?? 0),
                        ];
                    })
                    ->filter(function ($row) {
                        return $row['total'] > 0 && $row['label'] !== '';
                    })
                    ->values()
                    ->all();

                $table = $section->addTable([
                    'alignment' => JcTable::CENTER,
                    'borderSize' => 6,
                    'borderColor' => '808080',
                    'cellMargin' => 45,
                ]);

                $table->addRow();

                $table->addCell(7000, $this->centeredCellStyle(['bgColor' => '062A78']))->addText(
                    $title,
                    ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                    $this->centeredTextStyle()
                );

                $table->addCell(1800, $this->centeredCellStyle(['bgColor' => '062A78']))->addText(
                    'Total',
                    ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                    $this->centeredTextStyle()
                );

                if (empty($rows)) {
                    $table->addRow();

                    $table->addCell(7000, $this->centeredCellStyle(['bgColor' => 'F3F4F6']))->addText(
                        'Sin datos registrados',
                        ['italic' => true, 'size' => 10, 'color' => '6B7280'],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(1800, $this->centeredCellStyle(['bgColor' => 'F3F4F6']))->addText(
                        '—',
                        ['bold' => true, 'size' => 10, 'color' => '6B7280'],
                        $this->centeredTextStyle()
                    );

                    $section->addTextBreak(1);

                    return;
                }

                foreach ($rows as $row) {
                    $table->addRow();

                    $table->addCell(7000, $this->centeredCellStyle())->addText(
                        $this->safeValue($row['label']),
                        ['size' => 10, 'color' => '111827'],
                        $this->centeredTextStyle()
                    );

                    $table->addCell(1800, $this->centeredCellStyle())->addText(
                        (string) $row['total'],
                        ['bold' => true, 'size' => 10, 'color' => '111827'],
                        $this->centeredTextStyle()
                    );
                }

                $section->addTextBreak(1);
            }

    private function addNoDataNotice(Section $section, string $message): void
        {
            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => 'CBD5E1',
                'cellMargin' => 80,
            ]);

            $table->addRow(460);

            $table->addCell(9400, [
                'bgColor' => 'F3F4F6',
                'valign' => 'center',
            ])->addText(
                $message,
                ['italic' => true, 'size' => 10, 'color' => '4B5563'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $section->addTextBreak(1);
        }

    private function getParticipantSummary(string $organizationId, string $workCenterId): array
        {
            $rows = DB::table('paper_evaluations as pe')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.source',
                    'pe.personal_folio',
                    'pe.raw_data',
                    'dd.gender as dd_gender',
                    'dd.age as dd_age',
                    'dd.marital_status as dd_marital_status',
                    'dd.education_level as dd_education_level',
                    'dd.department as dd_department',
                    'dd.position as dd_position',
                    'dd.position_type as dd_position_type',
                    'dd.contract_type as dd_contract_type',
                    'dd.personnel_type as dd_personnel_type',
                    'dd.work_schedule as dd_work_schedule',
                    'dd.shift_rotation as dd_shift_rotation',
                    'dd.time_in_current_position as dd_time_in_current_position',
                    'dd.work_experience as dd_work_experience',
                    'dd.extra_fields as dd_extra_fields'
                )
                ->orderBy('pe.source')
                ->orderBy('pe.personal_folio')
                ->orderBy('pe.id')
                ->get();

            $participants = [];

            foreach ($rows as $row) {
                $source = trim((string) ($row->source ?? 'paper'));
                $folio = trim((string) ($row->personal_folio ?? ''));

                $participantKey = $source . '|' . (
                    $folio !== ''
                        ? $folio
                        : (string) ($row->evaluation_id ?? '')
                );

                if (isset($participants[$participantKey])) {
                    continue;
                }

                $extraFields = $this->decodeDemographicPayload($row->dd_extra_fields ?? null);
                $rawData = $this->decodeDemographicPayload($row->raw_data ?? null);

                $participants[$participantKey] = [
                    'source' => $source,
                    'gender' => $this->resolveParticipantDemographicValue(
                        $row->dd_gender ?? null,
                        $extraFields,
                        $rawData,
                        ['gender', 'genero', 'género', 'sexo', 'sex']
                    ),
                    'age' => $this->resolveParticipantDemographicValue(
                        $row->dd_age ?? null,
                        $extraFields,
                        $rawData,
                        ['age', 'edad']
                    ),
                    'marital_status' => $this->resolveParticipantDemographicValue(
                        $row->dd_marital_status ?? null,
                        $extraFields,
                        $rawData,
                        ['marital_status', 'estado_civil', 'estado civil', 'estadoCivil']
                    ),
                    'education_level' => $this->resolveParticipantDemographicValue(
                        $row->dd_education_level ?? null,
                        $extraFields,
                        $rawData,
                        ['education_level', 'nivel_estudios', 'nivel de estudios', 'escolaridad']
                    ),
                    'department' => $this->resolveParticipantDemographicValue(
                        $row->dd_department ?? null,
                        $extraFields,
                        $rawData,
                        ['department', 'departamento', 'area', 'área']
                    ),
                    'position' => $this->resolveParticipantDemographicValue(
                        $row->dd_position ?? null,
                        $extraFields,
                        $rawData,
                        ['position', 'puesto', 'cargo']
                    ),
                    'position_type' => $this->resolveParticipantDemographicValue(
                        $row->dd_position_type ?? null,
                        $extraFields,
                        $rawData,
                        ['position_type', 'tipo_puesto', 'tipo de puesto']
                    ),
                    'contract_type' => $this->resolveParticipantDemographicValue(
                        $row->dd_contract_type ?? null,
                        $extraFields,
                        $rawData,
                        ['contract_type', 'tipo_contratacion', 'tipo de contratacion', 'tipo de contratación']
                    ),
                    'personnel_type' => $this->resolveParticipantDemographicValue(
                        $row->dd_personnel_type ?? null,
                        $extraFields,
                        $rawData,
                        ['personnel_type', 'tipo_personal', 'tipo de personal']
                    ),
                    'work_schedule' => $this->resolveParticipantDemographicValue(
                        $row->dd_work_schedule ?? null,
                        $extraFields,
                        $rawData,
                        ['work_schedule', 'jornada_laboral', 'jornada laboral', 'turno']
                    ),
                    'shift_rotation' => $this->resolveParticipantDemographicValue(
                        $row->dd_shift_rotation ?? null,
                        $extraFields,
                        $rawData,
                        ['shift_rotation', 'rotacion_turno', 'rotación de turno', 'rotacion de turno']
                    ),
                    'time_in_current_position' => $this->resolveParticipantDemographicValue(
                        $row->dd_time_in_current_position ?? null,
                        $extraFields,
                        $rawData,
                        ['time_in_current_position', 'antiguedad_puesto', 'antigüedad en el puesto actual', 'antiguedad en el puesto actual']
                    ),
                    'work_experience' => $this->resolveParticipantDemographicValue(
                        $row->dd_work_experience ?? null,
                        $extraFields,
                        $rawData,
                        ['work_experience', 'experiencia_laboral', 'experiencia laboral']
                    ),
                ];
            }

            $participantRows = array_values($participants);

            $paperParticipants = count(array_filter(
                $participantRows,
                fn (array $row): bool => ($row['source'] ?? '') === 'paper'
            ));

            $onlineParticipants = count(array_filter(
                $participantRows,
                fn (array $row): bool => ($row['source'] ?? '') === 'online'
            ));

            $totalParticipants = $paperParticipants + $onlineParticipants;

            $genderRows = $this->groupParticipantDemographicRows($participantRows, 'gender');
            $genderTotals = $this->summarizeGenderRows($genderRows);

            return [
                'total_participants' => $totalParticipants,
                'paper_participants' => $paperParticipants,
                'online_participants' => $onlineParticipants,
                'men_total' => $genderTotals['men'],
                'women_total' => $genderTotals['women'],
                'unspecified_gender_total' => $genderTotals['unspecified'],
                'gender' => $genderRows,
                'age' => $this->groupParticipantAgeRanges($participantRows, 'age'),
                'marital_status' => $this->groupParticipantDemographicRows($participantRows, 'marital_status'),
                'education_level' => $this->groupParticipantDemographicRows($participantRows, 'education_level'),
                'position' => $this->groupParticipantDemographicRows($participantRows, 'position'),
                'department' => $this->groupParticipantDemographicRows($participantRows, 'department'),
                'position_type' => $this->groupParticipantDemographicRows($participantRows, 'position_type'),
                'contract_type' => $this->groupParticipantDemographicRows($participantRows, 'contract_type'),
                'personnel_type' => $this->groupParticipantDemographicRows($participantRows, 'personnel_type'),
                'work_schedule' => $this->groupParticipantDemographicRows($participantRows, 'work_schedule'),
                'shift_rotation' => $this->groupParticipantDemographicRows($participantRows, 'shift_rotation'),
                'time_in_current_position' => $this->groupParticipantDemographicRows($participantRows, 'time_in_current_position'),
                'work_experience' => $this->groupParticipantDemographicRows($participantRows, 'work_experience'),
            ];
        }

    private function decodeDemographicPayload($payload): array
        {
            if (is_array($payload)) {
                return $payload;
            }

            if (! is_string($payload) || trim($payload) === '') {
                return [];
            }

            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : [];
        }

        private function resolveParticipantDemographicValue(
            $directValue,
            array $extraFields,
            array $rawData,
            array $keys
        ): ?string {
            if ($directValue !== null && trim((string) $directValue) !== '') {
                return trim((string) $directValue);
            }

            $fromExtra = $this->findDemographicPayloadValue($extraFields, $keys);

            if ($fromExtra !== null && trim((string) $fromExtra) !== '') {
                return trim((string) $fromExtra);
            }

            $fromRaw = $this->findDemographicPayloadValue($rawData, $keys);

            if ($fromRaw !== null && trim((string) $fromRaw) !== '') {
                return trim((string) $fromRaw);
            }

            return null;
        }

        private function findDemographicPayloadValue(array $payload, array $keys)
        {
            $normalizedKeys = array_map(
                fn (string $key): string => $this->normalizeDemographicPayloadKey($key),
                $keys
            );

            foreach ($payload as $key => $value) {
                $normalizedKey = $this->normalizeDemographicPayloadKey((string) $key);

                if (in_array($normalizedKey, $normalizedKeys, true)) {
                    if (is_scalar($value) || $value === null) {
                        return $value;
                    }
                }

                if (is_array($value)) {
                    $nestedValue = $this->findDemographicPayloadValue($value, $keys);

                    if ($nestedValue !== null && trim((string) $nestedValue) !== '') {
                        return $nestedValue;
                    }
                }
            }

            return null;
        }

        private function normalizeDemographicPayloadKey(string $key): string
        {
            return Str::of($key)
                ->ascii()
                ->lower()
                ->replace(['-', ' '], '_')
                ->replaceMatches('/_+/', '_')
                ->trim('_')
                ->toString();
        }

        private function groupParticipantDemographicRows(array $participants, string $field): array
        {
            $counts = [];

            foreach ($participants as $participant) {
                $label = trim((string) ($participant[$field] ?? ''));

                if ($label === '') {
                    $label = 'N/D';
                }

                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }

            arsort($counts);

            $rows = [];

            foreach ($counts as $label => $total) {
                $rows[] = [
                    'label' => (string) $label,
                    'total' => (int) $total,
                ];
            }

            return $rows;
        }

        private function groupParticipantAgeRanges(array $participants, string $field): array
        {
            $buckets = [
                '18 a 24' => 0,
                '25 a 34' => 0,
                '35 a 44' => 0,
                '45 a 54' => 0,
                '55 o más' => 0,
                'N/D' => 0,
            ];

            foreach ($participants as $participant) {
                $value = $participant[$field] ?? null;

                if ($value === null || trim((string) $value) === '') {
                    $buckets['N/D']++;
                    continue;
                }

                $age = (int) floor((float) $value);

                if ($age < 18) {
                    $buckets['N/D']++;
                } elseif ($age <= 24) {
                    $buckets['18 a 24']++;
                } elseif ($age <= 34) {
                    $buckets['25 a 34']++;
                } elseif ($age <= 44) {
                    $buckets['35 a 44']++;
                } elseif ($age <= 54) {
                    $buckets['45 a 54']++;
                } else {
                    $buckets['55 o más']++;
                }
            }

            $rows = [];

            foreach ($buckets as $label => $total) {
                if ($total > 0) {
                    $rows[] = [
                        'label' => $label,
                        'total' => $total,
                    ];
                }
            }

            return $rows;
        }

    private function groupDemographicCounts($baseQuery, string $column): array
    {
        $expression = "COALESCE(NULLIF(TRIM($column), ''), 'N/D')";

        $subQuery = (clone $baseQuery)
            ->selectRaw("DISTINCT pe.personal_folio, $expression as label");

        $rows = DB::query()
            ->fromSub($subQuery, 'demo')
            ->selectRaw('label, COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->orderBy('label')
            ->get();

        return $rows->map(function ($row) {
            return [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ];
        })->values()->all();
    }

    private function groupAgeRanges($baseQuery, string $column): array
    {
        $subQuery = (clone $baseQuery)
            ->selectRaw("DISTINCT pe.personal_folio, $column as raw_age");

        $rows = DB::query()
            ->fromSub($subQuery, 'demo')
            ->get();

        $buckets = [
            '18 a 24' => 0,
            '25 a 34' => 0,
            '35 a 44' => 0,
            '45 a 54' => 0,
            '55 o más' => 0,
            'N/D' => 0,
        ];

        foreach ($rows as $row) {
            $value = $row->raw_age;

            if ($value === null || trim((string) $value) === '') {
                $buckets['N/D']++;
                continue;
            }

            $age = (int) floor((float) $value);

            if ($age < 18) {
                $buckets['N/D']++;
            } elseif ($age <= 24) {
                $buckets['18 a 24']++;
            } elseif ($age <= 34) {
                $buckets['25 a 34']++;
            } elseif ($age <= 44) {
                $buckets['35 a 44']++;
            } elseif ($age <= 54) {
                $buckets['45 a 54']++;
            } else {
                $buckets['55 o más']++;
            }
        }

        $result = [];

        foreach ($buckets as $label => $total) {
            if ($total > 0) {
                $result[] = [
                    'label' => $label,
                    'total' => $total,
                ];
            }
        }

        return $result;
    }

    private function summarizeGenderRows(array $rows): array
    {
        $men = 0;
        $women = 0;
        $unspecified = 0;

        foreach ($rows as $row) {
            $label = mb_strtolower(trim((string) ($row['label'] ?? '')));
            $total = (int) ($row['total'] ?? 0);

            if (in_array($label, ['hombre', 'hombres', 'masculino', 'masculina', 'm'], true)) {
                $men += $total;
            } elseif (in_array($label, ['mujer', 'mujeres', 'femenino', 'femenina', 'f'], true)) {
                $women += $total;
            } else {
                $unspecified += $total;
            }
        }

        return [
            'men' => $men,
            'women' => $women,
            'unspecified' => $unspecified,
        ];
    }

    private function getReferenceThreeScore(int|string $questionKey, ?string $answerValue): ?int
    {
        $questionKey = (int) $questionKey;
        $answerValue = strtoupper(trim((string) $answerValue));

        if ($answerValue === '' || ! in_array($answerValue, ['A', 'B', 'C', 'D', 'E'], true)) {
            return null;
        }

        $groups = config('nom035_reference_iii_map.score_groups', []);
        $maps = config('nom035_reference_iii_map.score_maps', []);

        if (in_array($questionKey, $groups['group_0_to_4'] ?? [], true)) {
            return $maps['group_0_to_4'][$answerValue] ?? null;
        }

        if (in_array($questionKey, $groups['group_4_to_0'] ?? [], true)) {
            return $maps['group_4_to_0'][$answerValue] ?? null;
        }

        return null;
    }

    private function getReferenceThreeQuestionMeta(int|string $questionKey): ?array
    {
        $questionKey = (int) $questionKey;
        $dimensions = config('nom035_reference_iii_map.dimensions', []);

        foreach ($dimensions as $row) {
            if (in_array($questionKey, $row['items'], true)) {
                return $row;
            }
        }

        return null;
    }

    private function getReferenceThreeGlobalSummary(string $organizationId, string $workCenterId): array
    {
        $rows = DB::table('evaluation_answers as ea')
            ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
            ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
            ->where('pe.organization_id', $organizationId)
            ->where('pe.work_center_id', $workCenterId)
            ->where('pe.evaluation_type', 'referencia_iii')
            ->where('ea.instrument', 'referencia_iii')
            ->whereIn('pe.source', ['paper', 'online'])
            ->where('pe.processing_status', 'completed')
            ->whereNull('pe.deleted_at')
            ->select(
                'pe.id as evaluation_id',
                'pe.source',
                'pe.referencia_iii_conditional',
                'ea.question_key',
                'ea.answer_value',
                'dd.extra_fields'
            )
            ->orderBy('pe.id')
            ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
            ->get();

        $evaluations = $this->getReferenceThreeEvaluations($rows);

        $distribution = $this->initializeRiskLevelCounts();

        foreach ($evaluations as $evaluation) {
            $levelKey = $evaluation['global_level_key'] ?? 'nulo';

            if (array_key_exists($levelKey, $distribution)) {
                $distribution[$levelKey]++;
            }
        }

        $totalEvaluations = count($evaluations);
        $paperEvaluations = count(array_filter($evaluations, fn ($row) => ($row['source'] ?? null) === 'paper'));
        $onlineEvaluations = count(array_filter($evaluations, fn ($row) => ($row['source'] ?? null) === 'online'));

        $maxGlobalScore = (int) config('nom035_risk_levels.global.max_score', 288);
        $averageGlobalScore = $this->calculateReferenceThreeAverageScoreFromQuestionScores($evaluations);

        $averageGlobalPercentage = $maxGlobalScore > 0
            ? round(($averageGlobalScore / $maxGlobalScore) * 100, 2)
            : 0;

        $dominantLevelKey = 'nulo';
        $dominantCount = -1;

        foreach ($distribution as $levelKey => $count) {
            if ($count > $dominantCount) {
                $dominantCount = $count;
                $dominantLevelKey = $levelKey;
            }
        }

        return [
            'total_evaluations' => $totalEvaluations,
            'paper_evaluations' => $paperEvaluations,
            'online_evaluations' => $onlineEvaluations,
            'max_global_score' => $maxGlobalScore,
            'average_global_score' => $averageGlobalScore,
            'average_global_percentage' => $averageGlobalPercentage,
            'distribution' => $distribution,
            'dominant_level_key' => $dominantLevelKey,
            'dominant_level_label' => config("nom035_risk_levels.labels.$dominantLevelKey", ucfirst($dominantLevelKey)),
            'evaluations' => $evaluations,
        ];
    }

        private function getReferenceThreeGlobalDashboardSummary(string $organizationId, string $workCenterId): array
            {
                $rows = DB::table('evaluation_answers as ea')
                    ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                    ->where('pe.organization_id', $organizationId)
                    ->where('pe.work_center_id', $workCenterId)
                    ->where('pe.evaluation_type', 'referencia_iii')
                    ->where('ea.instrument', 'referencia_iii')
                    ->whereIn('pe.source', ['paper', 'online'])
                    ->where('pe.processing_status', 'completed')
                    ->whereNull('pe.deleted_at')
                    ->select(
                        'pe.id as evaluation_id',
                        'pe.source',
                        'ea.question_key',
                        'ea.answer_value'
                    )
                    ->orderBy('pe.id')
                    ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                    ->get();

                $evaluations = $rows
                    ->groupBy('evaluation_id')
                    ->map(function ($items, $evaluationId) {
                        $globalScore = 0;
                        $questionScores = [];
                        $source = 'paper';

                        foreach ($items as $row) {
                            $source = (string) ($row->source ?? $source);
                            $questionKey = (int) ($row->question_key ?? 0);

                            if ($questionKey < 1 || $questionKey > 72) {
                                continue;
                            }

                            $score = $this->getReferenceThreeScore(
                                (string) $questionKey,
                                (string) ($row->answer_value ?? '')
                            );

                            if ($score === null) {
                                continue;
                            }

                            $globalScore += (int) $score;
                            $questionScores[$questionKey] = (int) $score;
                        }

                        if ($questionScores === []) {
                            return null;
                        }

                        $globalLevel = $this->classifyNom035Score('global', null, $globalScore);

                        return [
                            'evaluation_id' => (string) $evaluationId,
                            'source' => $source,
                            'global_score' => $globalScore,
                            'global_level_key' => $globalLevel['key'],
                            'global_level_label' => $globalLevel['label'],
                            'question_scores' => $questionScores,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                $distribution = $this->initializeRiskLevelCounts();

                foreach ($evaluations as $evaluation) {
                    $levelKey = (string) ($evaluation['global_level_key'] ?? 'nulo');

                    if (array_key_exists($levelKey, $distribution)) {
                        $distribution[$levelKey]++;
                    }
                }

                $totalEvaluations = count($evaluations);
                $paperEvaluations = count(array_filter($evaluations, fn ($row) => ($row['source'] ?? null) === 'paper'));
                $onlineEvaluations = count(array_filter($evaluations, fn ($row) => ($row['source'] ?? null) === 'online'));

                $maxGlobalScore = (int) config('nom035_risk_levels.global.max_score', 288);
                $averageGlobalScore = $this->calculateReferenceThreeAverageScoreFromQuestionScores($evaluations);

                $averageGlobalPercentage = $maxGlobalScore > 0
                    ? round(($averageGlobalScore / $maxGlobalScore) * 100, 2)
                    : 0;

                $dominantLevelKey = 'nulo';
                $dominantCount = -1;

                foreach ($distribution as $levelKey => $count) {
                    if ((int) $count > $dominantCount) {
                        $dominantCount = (int) $count;
                        $dominantLevelKey = (string) $levelKey;
                    }
                }

                return [
                    'total_evaluations' => $totalEvaluations,
                    'paper_evaluations' => $paperEvaluations,
                    'online_evaluations' => $onlineEvaluations,
                    'max_global_score' => $maxGlobalScore,
                    'average_global_score' => $averageGlobalScore,
                    'average_global_percentage' => $averageGlobalPercentage,
                    'distribution' => $distribution,
                    'dominant_level_key' => $dominantLevelKey,
                    'dominant_level_label' => config("nom035_risk_levels.labels.$dominantLevelKey", ucfirst($dominantLevelKey)),
                    'evaluations' => $evaluations,
                ];
            }

        private function calculateReferenceThreeAverageScoreFromQuestionScores(array $evaluations): float
            {
                $questionTotals = [];

                foreach ($evaluations as $evaluation) {
                    $questionScores = $evaluation['question_scores'] ?? [];

                    if (! is_array($questionScores)) {
                        continue;
                    }

                    foreach ($questionScores as $questionKey => $score) {
                        $questionKey = (int) $questionKey;

                        if ($questionKey < 1 || $questionKey > 72) {
                            continue;
                        }

                        if (! isset($questionTotals[$questionKey])) {
                            $questionTotals[$questionKey] = [
                                'sum' => 0,
                                'count' => 0,
                            ];
                        }

                        $questionTotals[$questionKey]['sum'] += (int) $score;
                        $questionTotals[$questionKey]['count']++;
                    }
                }

                $finalTotal = 0;

                for ($questionKey = 1; $questionKey <= 72; $questionKey++) {
                    $avgRaw = 0.0;

                    if (! empty($questionTotals[$questionKey]['count'])) {
                        $avgRaw = $questionTotals[$questionKey]['sum'] / $questionTotals[$questionKey]['count'];
                    }

                    $finalTotal += max(0, min(4, (int) round($avgRaw, 0, PHP_ROUND_HALF_UP)));
                }

                return (float) $finalTotal;
            }

        private function getEnabledReferenceThreeConditionalQuestionKeys(array $conditionalAnswers): array
            {
                $enabledQuestions = [];

                foreach ($conditionalAnswers as $section) {
                    if (! is_array($section)) {
                        continue;
                    }

                    $condition = mb_strtoupper(trim((string) ($section['condition'] ?? '')));
                    $isEnabled = in_array($condition, ['SI', 'SÍ', 'YES', 'TRUE', '1'], true);

                    if (! $isEnabled) {
                        continue;
                    }

                    $questions = $section['questions'] ?? null;

                    if (! is_array($questions)) {
                        continue;
                    }

                    foreach ($questions as $questionNumber => $answer) {
                        $numericQuestion = (int) $questionNumber;

                        if ($numericQuestion >= 65 && $numericQuestion <= 72) {
                            $enabledQuestions[] = $numericQuestion;
                        }
                    }
                }

                return array_values(array_unique($enabledQuestions));
            }

    private function getEnabledReferenceThreeConditionalQuestionScores(array $conditionalAnswers): array
        {
            $scores = [];

            foreach ($conditionalAnswers as $section) {
                if (! is_array($section)) {
                    continue;
                }

                $condition = mb_strtoupper(trim((string) ($section['condition'] ?? '')));
                $isEnabled = in_array($condition, ['SI', 'SÍ', 'YES', 'TRUE', '1'], true);

                if (! $isEnabled) {
                    continue;
                }

                $questions = $section['questions'] ?? null;

                if (! is_array($questions)) {
                    continue;
                }

                foreach ($questions as $questionNumber => $answer) {
                    $numericQuestion = (int) $questionNumber;

                    if ($numericQuestion < 65 || $numericQuestion > 72) {
                        continue;
                    }

                    if ($answer === null || is_array($answer)) {
                        continue;
                    }

                    $score = $this->getReferenceThreeScore((string) $numericQuestion, (string) $answer);

                    if ($score === null) {
                        continue;
                    }

                    $scores[$numericQuestion] = (int) $score;
                }
            }

            return $scores;
        }

    private function emptyReferenceThreeConditionalState(): array
        {
            return [
                'customer_service' => null, // null = desconocido, true = aplica, false = no aplica
                'management' => null,       // null = desconocido, true = aplica, false = no aplica
                'enabled_question_keys' => [],
                'question_scores' => [],
            ];
        }

        private function resolveReferenceThreeConditionalState(array $conditionalAnswers): array
            {
                $state = $this->emptyReferenceThreeConditionalState();

                foreach ($conditionalAnswers as $sectionKey => $section) {
                    if (! is_array($section)) {
                        continue;
                    }

                    $status = $this->resolveReferenceThreeConditionalSectionStatusForStatistics($section);

                    if ($status === null) {
                        continue;
                    }

                    $normalizedSectionKey = $this->normalizeReferenceThreeConditionValueKey((string) $sectionKey);

                    if (in_array($normalizedSectionKey, ['customer_service', 'servicio_clientes', 'atencion_clientes', 'atiende_clientes'], true)) {
                        $state['customer_service'] = $status;
                    } elseif (in_array($normalizedSectionKey, ['management', 'jefatura', 'jefe', 'supervision', 'supervisa_personal'], true)) {
                        $state['management'] = $status;
                    }

                    $questions = $section['questions'] ?? null;

                    if (! is_array($questions)) {
                        continue;
                    }

                    foreach ($questions as $questionNumber => $answer) {
                        if (! is_numeric((string) $questionNumber)) {
                            continue;
                        }

                        $numericQuestion = (int) $questionNumber;

                        if ($numericQuestion >= 65 && $numericQuestion <= 68) {
                            $state['customer_service'] = $status;
                        } elseif ($numericQuestion >= 69 && $numericQuestion <= 72) {
                            $state['management'] = $status;
                        } else {
                            continue;
                        }

                        if (! $status) {
                            continue;
                        }

                        $state['enabled_question_keys'][] = $numericQuestion;

                        if ($answer === null || is_array($answer)) {
                            continue;
                        }

                        $score = $this->getReferenceThreeScore(
                            (string) $numericQuestion,
                            (string) $answer
                        );

                        if ($score === null) {
                            continue;
                        }

                        $state['question_scores'][$numericQuestion] = (int) $score;
                    }
                }

                $state['enabled_question_keys'] = array_values(array_unique($state['enabled_question_keys']));
                ksort($state['question_scores']);

                return $state;
            }

        private function resolveReferenceThreeConditionalSectionStatusForStatistics(array $section): ?bool
        {
            if (! array_key_exists('condition', $section)) {
                return null;
            }

            $condition = $section['condition'];

            if (is_bool($condition)) {
                return $condition;
            }

            if (is_numeric($condition)) {
                return (int) $condition === 1;
            }

            if (! is_string($condition)) {
                return null;
            }

            return $this->normalizeReferenceThreeConditionValueForStatistics($condition) === 'SI';
        }

        private function shouldUseReferenceThreeConditionalQuestion(
            int $questionKey,
            ?array $conditionalState,
            ?array $enabledConditionalQuestionKeys,
            bool $attendsPublic,
            bool $isBoss,
            bool $hasConditionalDataset = false
        ): bool {
            if ($questionKey < 65 || $questionKey > 72) {
                return true;
            }

            if ($conditionalState !== null) {
            $rangeKey = $questionKey <= 68
                ? 'customer_service'
                : 'management';

            $rangeState = $conditionalState[$rangeKey] ?? null;

            if ($rangeState === false) {
                return false;
            }

            if ($rangeState === true) {
                $enabledKeys = $conditionalState['enabled_question_keys'] ?? [];

                if ($enabledKeys === []) {
                    return true;
                }

                return in_array($questionKey, $enabledKeys, true);
            }

            /*
            * Si el centro de trabajo sí maneja condicionales,
            * una evaluación sin condicional explícito NO debe contar 65–72.
            *
            * Si el centro de trabajo NO trae condicionales en ninguna evaluación,
            * se respeta evaluation_answers como viene.
            */
            return ! $hasConditionalDataset;
        }

            // Compatibilidad con llamadas viejas del mismo controller.
            if (is_array($enabledConditionalQuestionKeys)) {
                return in_array($questionKey, $enabledConditionalQuestionKeys, true);
            }

            if ($questionKey >= 65 && $questionKey <= 68) {
                return $attendsPublic;
            }

            return $isBoss;
        }

    private function getReferenceThreeEvaluations($rows): array
        {
            $hasConditionalDataset = $rows->contains(function ($row): bool {
                return $this->hasExplicitReferenceThreeConditionalDataset(
                    $row->referencia_iii_conditional ?? null
                );
            });
            return $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) use ($hasConditionalDataset) {
                    $first = $items->first();

                    $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);

                    if (! is_array($extra)) {
                        $extra = [];
                    }

                    $conditionalRaw = $first->referencia_iii_conditional ?? null;
                    $conditionalData = is_string($conditionalRaw)
                        ? json_decode($conditionalRaw, true)
                        : $conditionalRaw;

                    $conditionalState = is_array($conditionalData) && $conditionalData !== []
                    ? $this->resolveReferenceThreeConditionalState($conditionalData)
                    : $this->emptyReferenceThreeConditionalState();

                $enabledConditionalQuestionKeys = $conditionalState['enabled_question_keys'] ?? [];
                $conditionalQuestionScores = $conditionalState['question_scores'] ?? [];

                                        $isBoss = $this->extractWorkerFlag($extra, [
                        'jefe',
                        'soy_jefe',
                        'is_boss',
                        'is_manager',
                        'manager',
                        'supervises_people',
                        'supervisa_personal',
                        'supervisa_personas',
                        'supervisa_gente',
                        'jefe_trabajadores',
                        'tiene_personal_a_cargo',
                        'personal_a_cargo',
                        'personas_a_cargo',
                        'personal_bajo_su_cargo',
                        'personal_bajo_su_mando',
                        'mando_personal',
                        'people_manager',
                    ]);

                    $attendsPublic = $this->extractWorkerFlag($extra, [
                        'atiende',
                        'atiende_clientes',
                        'atencion_clientes',
                        'atencion_al_cliente',
                        'atencion_al_publico',
                        'atiende_publico',
                        'trato_con_clientes',
                        'trato_directo_con_clientes',
                        'trato_con_publico',
                        'contacto_con_clientes',
                        'contacto_con_publico',
                        'servicio_clientes',
                        'servicio_al_cliente',
                        'servicio_usuarios',
                        'client_service',
                        'customer_service',
                        'attends_public',
                    ]);

                    return $this->buildReferenceThreeEvaluationResult(
                    (string) $evaluationId,
                    (string) ($first->source ?? 'paper'),
                    $items,
                    $attendsPublic,
                    $isBoss,
                    $enabledConditionalQuestionKeys,
                    $conditionalQuestionScores,
                    $conditionalState,
                    $hasConditionalDataset
                );
                })
                ->values()
                ->all();
        }

        private function buildReferenceThreeEvaluationResult(
                string $evaluationId,
                string $source,
                $answers,
                bool $attendsPublic = false,
                bool $isBoss = false,
                ?array $enabledConditionalQuestionKeys = null,
                array $conditionalQuestionScores = [],
                ?array $conditionalState = null,
                bool $hasConditionalDataset = false
            ): array {
            $globalScore = 0;
            $dimensionScores = [];
            $domainScores = [];
            $categoryScores = [];
            $questionScores = [];

            foreach ($answers as $answer) {
            $questionKey = (int) $answer->question_key;

            if ($questionKey < 1 || $questionKey > 72) {
                continue;
            }

            if (! $this->shouldUseReferenceThreeConditionalQuestion(
            $questionKey,
            $conditionalState,
            $enabledConditionalQuestionKeys,
            $attendsPublic,
            $isBoss,
            $hasConditionalDataset
        )) {
                continue;
            }

            $score = $this->getReferenceThreeScore($answer->question_key, $answer->answer_value);
            $meta = $this->getReferenceThreeQuestionMeta($answer->question_key);

            if ($score === null || $meta === null) {
                continue;
            }

            $globalScore += $score;
            $questionScores[$questionKey] = (int) $score;

            $dimensionScores[$meta['dimension']] = ($dimensionScores[$meta['dimension']] ?? 0) + $score;
            $domainScores[$meta['domain']] = ($domainScores[$meta['domain']] ?? 0) + $score;
            $categoryScores[$meta['category']] = ($categoryScores[$meta['category']] ?? 0) + $score;
        }

        foreach ($conditionalQuestionScores as $conditionalQuestionKey => $conditionalScore) {
            $conditionalQuestionKey = (int) $conditionalQuestionKey;

            if ($conditionalQuestionKey < 65 || $conditionalQuestionKey > 72) {
                continue;
            }

            if (array_key_exists($conditionalQuestionKey, $questionScores)) {
                continue;
            }

            if (! $this->shouldUseReferenceThreeConditionalQuestion(
            $conditionalQuestionKey,
            $conditionalState,
            $enabledConditionalQuestionKeys,
            $attendsPublic,
            $isBoss,
            $hasConditionalDataset
        )) {
                continue;
            }

            $meta = $this->getReferenceThreeQuestionMeta($conditionalQuestionKey);

            if ($meta === null) {
                continue;
            }

            $conditionalScore = (int) $conditionalScore;

            $globalScore += $conditionalScore;
            $questionScores[$conditionalQuestionKey] = $conditionalScore;

            $dimensionScores[$meta['dimension']] = ($dimensionScores[$meta['dimension']] ?? 0) + $conditionalScore;
            $domainScores[$meta['domain']] = ($domainScores[$meta['domain']] ?? 0) + $conditionalScore;
            $categoryScores[$meta['category']] = ($categoryScores[$meta['category']] ?? 0) + $conditionalScore;
        }

            $dimensionLevels = [];
            foreach ($dimensionScores as $name => $score) {
                $dimensionLevels[$name] = $this->classifyNom035Score('dimensions', $name, $score);
            }

            $domainLevels = [];
            foreach ($domainScores as $name => $score) {
                $domainLevels[$name] = $this->classifyNom035Score('domains', $name, $score);
            }

            $categoryLevels = [];
            foreach ($categoryScores as $name => $score) {
                $categoryLevels[$name] = $this->classifyNom035Score('categories', $name, $score);
            }

            $globalLevel = $this->classifyNom035Score('global', null, $globalScore);

            return [
                'evaluation_id' => $evaluationId,
                'source' => $source,
                'has_conditional_dataset' => $hasConditionalDataset,
                'global_score' => $globalScore,
                'global_level_key' => $globalLevel['key'],
                'global_level_label' => $globalLevel['label'],
                'question_scores' => $questionScores,
                'conditional_question_scores' => $conditionalQuestionScores,
                'dimension_scores' => $dimensionScores,
                'dimension_levels' => $dimensionLevels,
                'domain_scores' => $domainScores,
                'domain_levels' => $domainLevels,
                'category_scores' => $categoryScores,
                'category_levels' => $categoryLevels,
            ];
        }

    private function classifyNom035Score(string $scope, ?string $name, int $score): array
    {
        $levels = $scope === 'global'
            ? config('nom035_risk_levels.global.levels', [])
            : config("nom035_risk_levels.$scope.$name.levels", []);

        foreach ($levels as $levelKey => $range) {
            $min = (int) ($range['min'] ?? 0);
            $max = (int) ($range['max'] ?? 0);

            if ($score >= $min && $score <= $max) {
                return [
                    'key' => $levelKey,
                    'label' => config("nom035_risk_levels.labels.$levelKey", ucfirst($levelKey)),
                ];
            }
        }

        return [
            'key' => 'nulo',
            'label' => config('nom035_risk_levels.labels.nulo', 'Nulo'),
        ];
    }

        private function initializeRiskLevelCounts(): array
        {
            return [
                'nulo' => 0,
                'bajo' => 0,
                'medio' => 0,
                'alto' => 0,
                'muy_alto' => 0,
            ];
        }

        private function getReferenceThreeCategorySummaryFromService(WorkCenter $workCenter): array
            {
                /** @var WorkCenterNom035CalculationService $calculationService */
                $calculationService = app(WorkCenterNom035CalculationService::class);

                $stats = $calculationService->calculateCategoryStatistics($workCenter);

                $categories = [];

                foreach (($stats['categories'] ?? []) as $categoryName => $row) {
                    $distribution = $row['distribution'] ?? $this->initializeRiskLevelCounts();

                    foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                        $distribution[$levelKey] = (int) ($distribution[$levelKey] ?? 0);
                    }

                    $riskLevel = (string) ($row['risk_level'] ?? 'nulo');

                    $categories[] = [
                        'name' => $categoryName,
                        'max_score' => (int) ($row['max_score'] ?? 0),
                        'average_score' => (float) ($row['average_score'] ?? 0),
                        'average_percentage' => (float) ($row['percentage'] ?? 0),
                        'distribution' => $distribution,
                        'applicable_evaluations' => (int) ($row['total_evaluations'] ?? array_sum($distribution)),
                        'dominant_level_key' => $riskLevel,
                        'dominant_level_label' => (string) ($row['risk_level_label'] ?? config("nom035_risk_levels.labels.$riskLevel", ucfirst($riskLevel))),
                    ];
                }

                return [
                    'total_evaluations' => (int) ($stats['total_evaluations'] ?? 0),
                    'categories' => $categories,
                ];
            }

            private function getReferenceThreeDomainSummaryFromService(WorkCenter $workCenter): array
            {
                /** @var WorkCenterNom035CalculationService $calculationService */
                $calculationService = app(WorkCenterNom035CalculationService::class);

                $stats = $calculationService->calculateDomainStatistics($workCenter);

                $domains = [];

                foreach (($stats['domains'] ?? []) as $domainName => $row) {
                    $distribution = $row['distribution'] ?? $this->initializeRiskLevelCounts();

                    foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                        $distribution[$levelKey] = (int) ($distribution[$levelKey] ?? 0);
                    }

                    $riskLevel = (string) ($row['risk_level'] ?? 'nulo');

                    $domains[] = [
                        'name' => $domainName,
                        'max_score' => (int) ($row['max_score'] ?? 0),
                        'average_score' => (float) ($row['average_score'] ?? 0),
                        'average_percentage' => (float) ($row['percentage'] ?? 0),
                        'distribution' => $distribution,
                        'applicable_evaluations' => (int) ($row['total_evaluations'] ?? array_sum($distribution)),
                        'dominant_level_key' => $riskLevel,
                        'dominant_level_label' => (string) ($row['risk_level_label'] ?? config("nom035_risk_levels.labels.$riskLevel", ucfirst($riskLevel))),
                    ];
                }

                return [
                    'total_evaluations' => (int) ($stats['total_evaluations'] ?? 0),
                    'domains' => $domains,
                ];
            }

            private function getReferenceThreeDimensionSummaryFromService(WorkCenter $workCenter): array
            {
                /** @var WorkCenterNom035CalculationService $calculationService */
                $calculationService = app(WorkCenterNom035CalculationService::class);

                $stats = $calculationService->calculateDimensionStatistics($workCenter);

                $dimensions = [];

                foreach (($stats['dimensions'] ?? []) as $dimensionName => $row) {
                    $distribution = $row['distribution'] ?? $this->initializeRiskLevelCounts();

                    foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
                        $distribution[$levelKey] = (int) ($distribution[$levelKey] ?? 0);
                    }

                    $riskLevel = (string) ($row['risk_level'] ?? 'nulo');

                    $dimensions[] = [
                        'name' => $dimensionName,
                        'max_score' => (int) ($row['max_score'] ?? 0),
                        'average_score' => (float) ($row['average_score'] ?? 0),
                        'average_percentage' => (float) ($row['percentage'] ?? 0),
                        'distribution' => $distribution,
                        'applicable_evaluations' => (int) ($row['total_evaluations'] ?? array_sum($distribution)),
                        'dominant_level_key' => $riskLevel,
                        'dominant_level_label' => (string) ($row['risk_level_label'] ?? config("nom035_risk_levels.labels.$riskLevel", ucfirst($riskLevel))),
                        'domain' => (string) ($row['domain'] ?? ''),
                        'category' => (string) ($row['category'] ?? ''),
                    ];
                }

                return [
                    'total_evaluations' => (int) ($stats['total_evaluations'] ?? 0),
                    'dimensions' => $dimensions,
                ];
            }

        private function getReferenceThreeCategorySummary(string $organizationId, string $workCenterId): array
            {
                $globalSummary = $this->getReferenceThreeGlobalSummary($organizationId, $workCenterId);
                $evaluations = $globalSummary['evaluations'] ?? [];

                $totalEvaluations = count($evaluations);
                $domainConfig = config('question_dimensions', []);
                $categories = [];

                if ($totalEvaluations === 0 || ! is_array($domainConfig) || $domainConfig === []) {
                    return [
                        'total_evaluations' => 0,
                        'categories' => [],
                    ];
                }

                foreach ($domainConfig as $categoryName => $domains) {
                    if (! is_array($domains)) {
                        continue;
                    }

                    $distribution = $this->initializeRiskLevelCounts();
                    $scoreSum = 0;

                    foreach ($evaluations as $evaluation) {
                        $categoryScores = $evaluation['category_scores'] ?? [];
                        $categoryLevels = $evaluation['category_levels'] ?? [];

                        $categoryScore = (int) ($categoryScores[$categoryName] ?? 0);
                        $scoreSum += $categoryScore;

                        $levelKey = (string) (
                            $categoryLevels[$categoryName]['key']
                            ?? $this->classifyReferenceThreeCategoryRiskLevelFromConfig($categoryName, $categoryScore)
                        );

                        if (array_key_exists($levelKey, $distribution)) {
                            $distribution[$levelKey]++;
                        }
                    }

                    $averageRaw = $scoreSum / $totalEvaluations;
                    $averageScore = round($averageRaw, 2);
                    $maxScore = $this->getReferenceThreeCategoryMaxScoreFromQuestionDimensions($categoryName, $domainConfig);

                    $averagePercentage = $maxScore > 0
                        ? round(($averageRaw / $maxScore) * 100, 2)
                        : 0;

                    $averageLevelKey = $this->classifyReferenceThreeCategoryRiskLevelFromConfig($categoryName, $averageRaw);

                    $categories[] = [
                        'name' => $categoryName,
                        'max_score' => $maxScore,
                        'average_score' => $averageScore,
                        'average_percentage' => $averagePercentage,
                        'distribution' => $distribution,
                        'applicable_evaluations' => $totalEvaluations,
                        'dominant_level_key' => $averageLevelKey,
                        'dominant_level_label' => config("nom035_risk_levels.labels.$averageLevelKey", ucfirst($averageLevelKey)),
                    ];
                }

                return [
                    'total_evaluations' => $totalEvaluations,
                    'categories' => $categories,
                ];
            }

        private function getReferenceThreeCategoryMaxScores(): array
            {
                return [
                    'Ambiente de trabajo' => 20,
                    'Factores propios de la actividad' => 100,
                    'Organización del tiempo de trabajo' => 24,
                    'Liderazgo y relaciones en el trabajo' => 104,
                    'Entorno organizacional' => 40,
                ];
            }

        private function getReferenceThreeCategoryDomainNames(): array
            {
                return [
                    'Ambiente de trabajo' => [
                        'Condiciones en el ambiente de trabajo',
                    ],
                    'Factores propios de la actividad' => [
                        'Carga de trabajo',
                        'Falta de control sobre el trabajo',
                    ],
                    'Organización del tiempo de trabajo' => [
                        'Jornada de trabajo',
                        'Interferencia en la relación trabajo-familia',
                    ],
                    'Liderazgo y relaciones en el trabajo' => [
                        'Liderazgo',
                        'Relaciones en el trabajo',
                        'Violencia',
                    ],
                    'Entorno organizacional' => [
                        'Reconocimiento del desempeño',
                        'Insuficiente sentido de pertenencia e inestabilidad',
                    ],
                ];
            }

        private function getAttentionCount(array $distribution): int
    {
        return (int) ($distribution['alto'] ?? 0) + (int) ($distribution['muy_alto'] ?? 0);
    }

    private function getQuestionGlobalAttentionCount(array $distribution): int
        {
            return (int) ($distribution['medio'] ?? 0)
                + (int) ($distribution['alto'] ?? 0)
                + (int) ($distribution['muy_alto'] ?? 0);
        }

    private function getWordRiskCellStyle(string $levelKey): array
        {
            return match ($levelKey) {
                'muy_alto' => ['bg' => 'EF4444', 'text' => 'FFFFFF'],
                'alto' => ['bg' => 'F59E0B', 'text' => 'FFFFFF'],
                'medio' => ['bg' => 'F8FF03', 'text' => '111111'],
                'bajo' => ['bg' => '16A34A', 'text' => 'FFFFFF'],
                default => ['bg' => '3B82F6', 'text' => 'FFFFFF'],
            };
        }

    private function riskLevelWeight(string $levelKey): int
        {
            return match ($levelKey) {
                'muy_alto' => 5,
                'alto' => 4,
                'medio' => 3,
                'bajo' => 2,
                default => 1,
            };
        }

        private function compareRiskRows(
            array $a,
            array $b,
            string $levelField = 'global_level_key',
            string $scoreField = 'global_score'
        ): int {
            $riskCompare = $this->riskLevelWeight((string) ($b[$levelField] ?? 'nulo'))
                <=> $this->riskLevelWeight((string) ($a[$levelField] ?? 'nulo'));

            if ($riskCompare !== 0) {
                return $riskCompare;
            }

            $scoreCompare = ((int) ($b[$scoreField] ?? 0))
                <=> ((int) ($a[$scoreField] ?? 0));

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strnatcasecmp(
                (string) ($a['name'] ?? $a['folio'] ?? ''),
                (string) ($b['name'] ?? $b['folio'] ?? '')
            );
        }

        private function compareGroupedTablesByRisk(array $a, array $b): int
        {
            $aFirst = $a['rows'][0] ?? [];
            $bFirst = $b['rows'][0] ?? [];

            $aLevel = (string) ($aFirst['dimension_level_key'] ?? $aFirst['global_level_key'] ?? 'nulo');
            $bLevel = (string) ($bFirst['dimension_level_key'] ?? $bFirst['global_level_key'] ?? 'nulo');

            $riskCompare = $this->riskLevelWeight($bLevel) <=> $this->riskLevelWeight($aLevel);

            if ($riskCompare !== 0) {
                return $riskCompare;
            }

            $aScore = (int) ($aFirst['dimension_score'] ?? $aFirst['global_score'] ?? 0);
            $bScore = (int) ($bFirst['dimension_score'] ?? $bFirst['global_score'] ?? 0);

            $scoreCompare = $bScore <=> $aScore;

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        }

        private function compareQuestionSummaryBlocks(array $a, array $b): int
        {
            $aLevel = $this->classifyNom035Score('global', null, (int) ($a['summary']['final_total'] ?? 0))['key'] ?? 'nulo';
            $bLevel = $this->classifyNom035Score('global', null, (int) ($b['summary']['final_total'] ?? 0))['key'] ?? 'nulo';

            $riskCompare = $this->riskLevelWeight($bLevel) <=> $this->riskLevelWeight($aLevel);

            if ($riskCompare !== 0) {
                return $riskCompare;
            }

            $scoreCompare = ((int) ($b['summary']['final_total'] ?? 0))
                <=> ((int) ($a['summary']['final_total'] ?? 0));

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strnatcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        }

        private function sortDimensionSummariesByRisk(array $dimensions): array
        {
            usort($dimensions, function ($a, $b) {
                $riskCompare = $this->riskLevelWeight((string) ($b['dominant_level_key'] ?? 'nulo'))
                    <=> $this->riskLevelWeight((string) ($a['dominant_level_key'] ?? 'nulo'));

                if ($riskCompare !== 0) {
                    return $riskCompare;
                }

                $bDominant = (int) (($b['distribution'][$b['dominant_level_key'] ?? 'nulo'] ?? 0));
                $aDominant = (int) (($a['distribution'][$a['dominant_level_key'] ?? 'nulo'] ?? 0));

                if ($bDominant !== $aDominant) {
                    return $bDominant <=> $aDominant;
                }

                $bAttention = $this->getAttentionCount($b['distribution'] ?? []);
                $aAttention = $this->getAttentionCount($a['distribution'] ?? []);

                if ($bAttention !== $aAttention) {
                    return $bAttention <=> $aAttention;
                }

                return strnatcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            });

            return $dimensions;
        }

        private function addRiskLevelDistributionTable(Section $section, string $title, array $distribution, int $totalEvaluations): void
    {
        $section->addText(
            $title,
            ['bold' => true, 'size' => 12, 'color' => '1D4ED8'],
            ['spaceBefore' => 120, 'spaceAfter' => 100]
        );

        $table = $section->addTable('StatsTable');

        $table->addRow();
        $table->addCell(3200, $this->centeredCellStyle(['bgColor' => 'EAF2FF']))->addText(
            'Nivel',
            ['bold' => true, 'size' => 10],
            $this->centeredTextStyle()
        );
        $table->addCell(1600, $this->centeredCellStyle(['bgColor' => 'EAF2FF']))->addText(
            'Total',
            ['bold' => true, 'size' => 10],
            $this->centeredTextStyle()
        );
        $table->addCell(1600, $this->centeredCellStyle(['bgColor' => 'EAF2FF']))->addText(
            '%',
            ['bold' => true, 'size' => 10],
            $this->centeredTextStyle()
        );

        foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
            $total = (int) ($distribution[$levelKey] ?? 0);
            $percentage = $totalEvaluations > 0
                ? round(($total / $totalEvaluations) * 100, 2)
                : 0;

            $table->addRow();
            $table->addCell(3200, $this->centeredCellStyle())->addText(
                config("nom035_risk_levels.labels.$levelKey", ucfirst($levelKey)),
                ['size' => 10, 'color' => '374151'],
                $this->centeredTextStyle()
            );
            $table->addCell(1600, $this->centeredCellStyle())->addText(
                (string) $total,
                ['size' => 10, 'color' => '374151'],
                $this->centeredTextStyle()
            );
            $table->addCell(1600, $this->centeredCellStyle())->addText(
                $percentage . '%',
                ['size' => 10, 'color' => '374151'],
                $this->centeredTextStyle()
            );
        }
    }

    private function getReferenceThreeDomainSummary(string $organizationId, string $workCenterId): array
        {
            $globalSummary = $this->getReferenceThreeGlobalSummary($organizationId, $workCenterId);
            $evaluations = $globalSummary['evaluations'] ?? [];

            $totalEvaluations = count($evaluations);

            $hasConditionalDataset = collect($evaluations)->contains(
                fn (array $evaluation): bool => (bool) ($evaluation['has_conditional_dataset'] ?? false)
            );

            $domainConfig = config('question_dimensions', []);
            $riskLevels = config('nom035_risk_levels', []);
            $domains = [];

            if ($totalEvaluations === 0 || ! is_array($domainConfig) || $domainConfig === []) {
                return [
                    'total_evaluations' => 0,
                    'domains' => [],
                ];
            }

            foreach ($domainConfig as $categoryName => $domainRows) {
                if (! is_array($domainRows)) {
                    continue;
                }

                foreach ($domainRows as $domainName => $dimensionRows) {
                    if (! is_array($dimensionRows)) {
                        continue;
                    }

                    $distribution = $this->initializeRiskLevelCounts();
                    $scoreSum = 0;

                    $useCorrectedDomainLevel = $hasConditionalDataset && in_array($domainName, [
                        'Reconocimiento del desempeño',
                        'Insuficiente sentido de pertenencia e inestabilidad',
                    ], true);

                    foreach ($evaluations as $evaluation) {
                        $domainScores = $evaluation['domain_scores'] ?? [];
                        $domainLevels = $evaluation['domain_levels'] ?? [];

                        $domainScore = (int) ($domainScores[$domainName] ?? 0);
                        $scoreSum += $domainScore;

                        $levelKey = $useCorrectedDomainLevel
                            ? $this->classifyReferenceThreeDomainRiskLevelFromConfig($domainName, $domainScore)
                            : (string) (
                                $domainLevels[$domainName]['key']
                                ?? $this->classifyNom035Score('domains', $domainName, $domainScore)['key']
                            );

                        if (array_key_exists($levelKey, $distribution)) {
                            $distribution[$levelKey]++;
                        }
                    }

                    $averageRaw = $scoreSum / $totalEvaluations;
                    $averageScore = round($averageRaw, 2);

                    $maxScore = (int) ($riskLevels['domains'][$domainName]['max_score'] ?? 0);

                    if ($maxScore <= 0) {
                        $maxScore = $this->getReferenceThreeQuestionsMaxScoreFromDimensions($dimensionRows);
                    }

                    $averagePercentage = $maxScore > 0
                        ? round(($averageRaw / $maxScore) * 100, 2)
                        : 0;

                    $averageLevelKey = $useCorrectedDomainLevel
                    ? $this->classifyReferenceThreeDomainRiskLevelFromConfig($domainName, $averageRaw)
                    : $this->classifyNom035Score('domains', $domainName, (int) round($averageRaw))['key'];

                    $domains[] = [
                        'name' => $domainName,
                        'max_score' => $maxScore,
                        'average_score' => $averageScore,
                        'average_percentage' => $averagePercentage,
                        'distribution' => $distribution,
                        'applicable_evaluations' => $totalEvaluations,
                        'dominant_level_key' => $averageLevelKey,
                        'dominant_level_label' => config("nom035_risk_levels.labels.$averageLevelKey", ucfirst($averageLevelKey)),
                    ];
                }
            }

            return [
                'total_evaluations' => $totalEvaluations,
                'domains' => $domains,
            ];
        }


        private function getReferenceThreeDomainSummaryV2(string $organizationId, string $workCenterId): array
    {
        try {
            return $this->buildReferenceThreeDomainSummaryV2($organizationId, $workCenterId);
        } catch (\Throwable $e) {
            report($e);

            return $this->getReferenceThreeDomainSummary($organizationId, $workCenterId);
        }
    }

    private function buildReferenceThreeDomainSummaryV2(string $organizationId, string $workCenterId): array
    {
        $legacySummary = $this->getReferenceThreeDomainSummary($organizationId, $workCenterId);
        $legacyDomains = $legacySummary['domains'] ?? [];

        return [
            'total_evaluations' => (int) ($legacySummary['total_evaluations'] ?? 0),
            'domains' => $this->buildReferenceThreeDomainRowsV2($legacyDomains),
        ];
    }

             private function buildReferenceThreeDomainRowsV2(array $legacyDomains): array
    {
        $domains = [];

        foreach ($legacyDomains as $row) {
            $distribution = $row['distribution'] ?? $this->initializeRiskLevelCounts();
            $dominantLevelKey = (string) ($row['dominant_level_key'] ?? $this->resolveDominantRiskLevelKeyV2($distribution));

            $domains[] = [
                'name' => (string) ($row['name'] ?? ''),
                'max_score' => (int) ($row['max_score'] ?? 0),
                'average_score' => (float) ($row['average_score'] ?? 0),
                'average_percentage' => (float) ($row['average_percentage'] ?? 0),
                'distribution' => $distribution,
                'applicable_evaluations' => (int) ($row['applicable_evaluations'] ?? 0),
                'dominant_level_key' => $dominantLevelKey,
                'dominant_level_label' => (string) (
                    $row['dominant_level_label']
                    ?? config("nom035_risk_levels.labels.$dominantLevelKey", ucfirst($dominantLevelKey))
                ),
            ];
        }

        return $domains;
    }

    private function resolveDominantRiskLevelKeyV2(array $distribution): string
    {
        $dominantLevelKey = 'nulo';
        $dominantCount = -1;

        foreach ($distribution as $levelKey => $count) {
            if ((int) $count > $dominantCount) {
                $dominantCount = (int) $count;
                $dominantLevelKey = (string) $levelKey;
            }
        }

        return $dominantLevelKey;
    }

    private function classifyReferenceThreeDomainRiskLevelFromConfig(string $domainName, int|float $score): string
        {
            $roundedScore = (int) round((float) $score);

            /*
            * Ajuste específico para dominios de Entorno organizacional.
            * El promedio ya coincide; aquí solo corregimos la distribución
            * por nivel de riesgo de estos dos dominios.
            */
            if ($domainName === 'Reconocimiento del desempeño') {
                return match (true) {
                    $roundedScore <= 5 => 'nulo',
                    $roundedScore <= 8 => 'bajo',
                    $roundedScore <= 13 => 'medio',
                    $roundedScore <= 17 => 'alto',
                    default => 'muy_alto',
                };
            }

            if ($domainName === 'Insuficiente sentido de pertenencia e inestabilidad') {
                return match (true) {
                    $roundedScore <= 3 => 'nulo',
                    $roundedScore <= 6 => 'bajo',
                    $roundedScore <= 9 => 'medio',
                    default => 'alto',
                };
            }

            $levels = config("nom035_risk_levels.domains.$domainName.levels", []);

            if (! is_array($levels) || $levels === []) {
                return 'nulo';
            }

            foreach ($levels as $levelKey => $range) {
                $min = (int) ($range['min'] ?? 0);
                $max = (int) ($range['max'] ?? 0);

                if ($roundedScore >= $min && $roundedScore <= $max) {
                    return (string) $levelKey;
                }
            }

            return 'nulo';
        }

    private function getReferenceThreeDomainMaxScores(): array
    {
        return [
            'Condiciones en el ambiente de trabajo' => 20,
            'Carga de trabajo' => 60,
            'Falta de control sobre el trabajo' => 40,
            'Jornada de trabajo' => 8,
            'Interferencia en la relación trabajo-familia' => 16,
            'Liderazgo' => 36,
            'Relaciones en el trabajo' => 36,
            'Violencia' => 32,
            'Reconocimiento del desempeño' => 24,
            'Insuficiente sentido de pertenencia e inestabilidad' => 16,
        ];
    }

    private function getReferenceThreeDomainQuestionKeys(): array
        {
            return [
                'Condiciones en el ambiente de trabajo' => [1, 2, 3, 4, 5],
                'Carga de trabajo' => [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 65, 66, 67, 68],
                'Falta de control sobre el trabajo' => [23, 24, 25, 26, 27, 28, 29, 30, 35, 36],
                'Jornada de trabajo' => [17, 18],
                'Interferencia en la relación trabajo-familia' => [19, 20, 21, 22],
                'Liderazgo' => [31, 32, 33, 34, 37, 38, 39, 40, 41],
                'Relaciones en el trabajo' => [42, 43, 44, 45, 46, 69, 70, 71, 72],
                'Violencia' => [57, 58, 59, 60, 61, 62, 63, 64],
                'Reconocimiento del desempeño' => [47, 48, 49, 50, 51, 52],
                'Insuficiente sentido de pertenencia e inestabilidad' => [53, 54, 55, 56],
            ];
        }

    private function getReferenceThreeDimensionSummary(string $organizationId, string $workCenterId): array
        {
            $globalSummary = $this->getReferenceThreeGlobalSummary($organizationId, $workCenterId);
            $evaluations = $globalSummary['evaluations'] ?? [];

            $totalEvaluations = count($evaluations);
            $domainConfig = config('question_dimensions', []);
            $dimensions = [];

            if ($totalEvaluations === 0 || ! is_array($domainConfig) || $domainConfig === []) {
                return [
                    'total_evaluations' => 0,
                    'dimensions' => [],
                ];
            }

            foreach ($domainConfig as $categoryName => $domainRows) {
                if (! is_array($domainRows)) {
                    continue;
                }

                foreach ($domainRows as $domainName => $dimensionRows) {
                    if (! is_array($dimensionRows)) {
                        continue;
                    }

                    foreach ($dimensionRows as $dimensionName => $questions) {
                        if (! is_array($questions)) {
                            continue;
                        }

                        $distribution = $this->initializeRiskLevelCounts();
                        $scoreSum = 0;
                        $maxScore = count($questions) * 4;

                        foreach ($evaluations as $evaluation) {
                            $dimensionScores = $evaluation['dimension_scores'] ?? [];
                            $dimensionLevels = $evaluation['dimension_levels'] ?? [];

                            $dimensionScore = (int) ($dimensionScores[$dimensionName] ?? 0);
                            $scoreSum += $dimensionScore;

                            $levelKey = (string) (
                                $dimensionLevels[$dimensionName]['key']
                                ?? $this->classifyReferenceThreeDimensionRiskLevelFromConfig($dimensionName, $dimensionScore)
                            );

                            if (array_key_exists($levelKey, $distribution)) {
                                $distribution[$levelKey]++;
                            }
                        }

                        $averageRaw = $scoreSum / $totalEvaluations;
                        $averageScore = round($averageRaw, 2);

                        $averagePercentage = $maxScore > 0
                            ? round(($averageRaw / $maxScore) * 100, 2)
                            : 0;

                        $averageLevelKey = $this->classifyReferenceThreeDimensionRiskLevelFromConfig($dimensionName, $averageRaw);

                        $dimensions[] = [
                            'name' => $dimensionName,
                            'max_score' => $maxScore,
                            'average_score' => $averageScore,
                            'average_percentage' => $averagePercentage,
                            'distribution' => $distribution,
                            'applicable_evaluations' => $totalEvaluations,
                            'dominant_level_key' => $averageLevelKey,
                            'dominant_level_label' => config("nom035_risk_levels.labels.$averageLevelKey", ucfirst($averageLevelKey)),
                            'domain' => $domainName,
                            'category' => $categoryName,
                        ];
                    }
                }
            }

            return [
                'total_evaluations' => $totalEvaluations,
                'dimensions' => $dimensions,
            ];
        }

    private function getReferenceThreeAnswerSetsForStatistics(string $organizationId, string $workCenterId): array
        {
            $evaluations = DB::table('paper_evaluations as pe')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->where(function ($query) {
                    $query
                        ->whereNotNull('pe.referencia_iii_answers')
                        ->orWhereNotNull('pe.referencia_iii_conditional')
                        ->orWhereNotNull('pe.raw_data');
                })
                ->select(
                    'pe.id as evaluation_id',
                    'pe.referencia_iii_answers',
                    'pe.referencia_iii_conditional',
                    'pe.raw_data'
                )
                ->orderBy('pe.id')
                ->get();

            if ($evaluations->isEmpty()) {
                return [];
            }

            $evaluationIds = $evaluations
                ->pluck('evaluation_id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all();

            $answerRows = DB::table('evaluation_answers as ea')
                ->whereIn('ea.paper_evaluation_id', $evaluationIds)
                ->where('ea.instrument', 'referencia_iii')
                ->select(
                    'ea.paper_evaluation_id as evaluation_id',
                    'ea.question_key',
                    'ea.answer_value'
                )
                ->orderBy('ea.paper_evaluation_id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get()
                ->groupBy(fn ($row) => (string) $row->evaluation_id);

            return $evaluations
                ->map(function ($evaluation) use ($answerRows): array {
                    $evaluationId = (string) $evaluation->evaluation_id;

                    $conditionalAnswers = $this->decodeReferenceThreeJsonForStatistics(
                        $evaluation->referencia_iii_conditional ?? null
                    );

                    $answers = $this->normalizeReferenceThreeAnswersArrayForStatistics(
                        $this->decodeReferenceThreeJsonForStatistics($evaluation->referencia_iii_answers ?? null)
                    );

                    $rawAnswers = $this->extractRawReferenceThreeAnswersForStatistics(
                        $evaluation->raw_data ?? null
                    );

                    if ($answers === [] && $rawAnswers !== []) {
                        $answers = $rawAnswers;
                    } elseif ($rawAnswers !== []) {
                        foreach ($rawAnswers as $questionNumber => $answer) {
                            $questionKey = $this->normalizeReferenceThreeQuestionKeyForStatistics($questionNumber);

                            if (! array_key_exists($questionKey, $answers)) {
                                $answers[$questionKey] = $answer;
                            }
                        }
                    }

                    $dbAnswers = [];

                    foreach (($answerRows[$evaluationId] ?? collect()) as $row) {
                        if (! is_numeric($row->question_key) || $row->answer_value === null) {
                            continue;
                        }

                        $answer = strtoupper(trim((string) $row->answer_value));

                        if (! in_array($answer, ['A', 'B', 'C', 'D', 'E'], true)) {
                            continue;
                        }

                        $dbAnswers[$this->normalizeReferenceThreeQuestionKeyForStatistics($row->question_key)] = $answer;
                    }

                    if ($dbAnswers !== []) {
                    $answers = $dbAnswers;

                    $this->removeDisabledConditionalQuestionsForStatistics($answers, $conditionalAnswers);

                    ksort($answers);

                    return $answers;
                }

                $this->removeDisabledConditionalQuestionsForStatistics($answers, $conditionalAnswers);

                foreach ($this->getEnabledConditionalQuestionAnswersForStatistics($conditionalAnswers) as $questionNumber => $answer) {
                    $questionKey = $this->normalizeReferenceThreeQuestionKeyForStatistics($questionNumber);

                    if (! array_key_exists($questionKey, $answers)) {
                        $answers[$questionKey] = $answer;
                    }
                }

                $this->removeDisabledConditionalQuestionsForStatistics($answers, $conditionalAnswers);

                $this->removeDisabledConditionalQuestionsForStatistics($answers, $conditionalAnswers);

                ksort($answers);

                return $answers;
                })
                ->filter(fn (array $answers): bool => $answers !== [])
                ->values()
                ->all();
        }

        private function decodeReferenceThreeJsonForStatistics(mixed $value): array
            {
                if (is_array($value)) {
                    return $value;
                }

                if (is_object($value)) {
                    $encoded = json_encode($value);
                    $decoded = is_string($encoded) ? json_decode($encoded, true) : null;

                    return is_array($decoded) ? $decoded : [];
                }

                if (! is_string($value) || trim($value) === '') {
                    return [];
                }

                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : [];
            }

            private function normalizeReferenceThreeAnswersArrayForStatistics(array $source): array
            {
                $answers = [];

                foreach ($source as $questionNumber => $answer) {
                    if (! is_numeric((string) $questionNumber)) {
                        continue;
                    }

                    $numericQuestion = (int) $questionNumber;

                    if ($numericQuestion < 1 || $numericQuestion > 72) {
                        continue;
                    }

                    $answerValue = null;

                    if (is_string($answer)) {
                        $answerValue = $answer;
                    } elseif (is_array($answer) && is_string($answer['value'] ?? null)) {
                        $answerValue = $answer['value'];
                    }

                    if (! is_string($answerValue)) {
                        continue;
                    }

                    $normalizedAnswer = strtoupper(trim($answerValue));

                    if (! in_array($normalizedAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
                        continue;
                    }

                    $answers[$this->normalizeReferenceThreeQuestionKeyForStatistics($numericQuestion)] = $normalizedAnswer;
                }

                return $answers;
            }

        private function extractRawReferenceThreeAnswersForStatistics(mixed $rawData): array
            {
                $rawData = $this->decodeReferenceThreeJsonForStatistics($rawData);

                if ($rawData === []) {
                    return [];
                }

                $sources = [$rawData];

                if (is_array($rawData['referencia_iii'] ?? null)) {
                    $sources[] = $rawData['referencia_iii'];
                }

                if (is_array($rawData['answers'] ?? null)) {
                    $sources[] = $rawData['answers'];
                }

                if (is_array($rawData['referencia_iii_answers'] ?? null)) {
                    $sources[] = $rawData['referencia_iii_answers'];
                }

                $answers = [];

                foreach ($sources as $source) {
                    if (! is_array($source)) {
                        continue;
                    }

                    foreach ($source as $questionNumber => $answer) {
                        if (! is_numeric((string) $questionNumber)) {
                            continue;
                        }

                        $numericQuestion = (int) $questionNumber;

                        if ($numericQuestion < 1 || $numericQuestion > 72) {
                            continue;
                        }

                        $answerValue = null;

                        if (is_string($answer)) {
                            $answerValue = $answer;
                        } elseif (is_array($answer) && is_string($answer['value'] ?? null)) {
                            $answerValue = $answer['value'];
                        }

                        if (! is_string($answerValue)) {
                            continue;
                        }

                        $normalizedAnswer = strtoupper(trim($answerValue));

                        if (! in_array($normalizedAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
                            continue;
                        }

                        $answers[$this->normalizeReferenceThreeQuestionKeyForStatistics($numericQuestion)] = $normalizedAnswer;
                    }
                }

                return $answers;
            }

        private function getEnabledConditionalQuestionAnswersForStatistics(array $conditionalAnswers): array
            {
                $enabledQuestions = [];

                foreach ($conditionalAnswers as $section) {
                    if (! is_array($section)) {
                        continue;
                    }

                    if (! $this->isReferenceThreeConditionalSectionEnabledForStatistics($section)) {
                        continue;
                    }

                    $questions = $section['questions'] ?? null;

                    if (! is_array($questions)) {
                        continue;
                    }

                    foreach ($questions as $questionNumber => $answer) {
                        if (! is_numeric((string) $questionNumber)) {
                            continue;
                        }

                        $numericQuestion = (int) $questionNumber;

                        if ($numericQuestion < 65 || $numericQuestion > 72) {
                            continue;
                        }

                        if (! is_string($answer)) {
                            continue;
                        }

                        $normalizedAnswer = strtoupper(trim($answer));

                        if (! in_array($normalizedAnswer, ['A', 'B', 'C', 'D', 'E'], true)) {
                            continue;
                        }

                        $enabledQuestions[$numericQuestion] = $normalizedAnswer;
                    }
                }

                return $enabledQuestions;
            }

        private function normalizeReferenceThreeQuestionKeyForStatistics(int|string $questionNumber): string
        {
            return str_pad((string) ((int) $questionNumber), 2, '0', STR_PAD_LEFT);
        }

        private function removeDisabledConditionalQuestionsForStatistics(array &$answers, array $conditionalAnswers): void
        {
            $customerServiceEnabled = $this->hasEnabledConditionalQuestionRangeForStatistics(
                $conditionalAnswers,
                65,
                68
            );

            $managementEnabled = $this->hasEnabledConditionalQuestionRangeForStatistics(
                $conditionalAnswers,
                69,
                72
            );

            if (! $customerServiceEnabled) {
                for ($questionNumber = 65; $questionNumber <= 68; $questionNumber++) {
                    unset(
                        $answers[$this->normalizeReferenceThreeQuestionKeyForStatistics($questionNumber)],
                        $answers[(string) $questionNumber],
                        $answers[$questionNumber]
                    );
                }
            }

            if (! $managementEnabled) {
                for ($questionNumber = 69; $questionNumber <= 72; $questionNumber++) {
                    unset(
                        $answers[$this->normalizeReferenceThreeQuestionKeyForStatistics($questionNumber)],
                        $answers[(string) $questionNumber],
                        $answers[$questionNumber]
                    );
                }
            }
        }

        private function hasEnabledConditionalQuestionRangeForStatistics(
            array $conditionalAnswers,
            int $startQuestion,
            int $endQuestion
        ): bool {
            foreach ($conditionalAnswers as $section) {
                if (! is_array($section)) {
                    continue;
                }

                if (! $this->isReferenceThreeConditionalSectionEnabledForStatistics($section)) {
                    continue;
                }

                $questions = $section['questions'] ?? null;

                if (! is_array($questions)) {
                    continue;
                }

                foreach ($questions as $questionNumber => $answer) {
                    if (! is_numeric((string) $questionNumber)) {
                        continue;
                    }

                    $numericQuestion = (int) $questionNumber;

                    if ($numericQuestion >= $startQuestion && $numericQuestion <= $endQuestion) {
                        return true;
                    }
                }
            }

            return false;
        }

        private function isReferenceThreeConditionalSectionEnabledForStatistics(array $section): bool
        {
            $condition = $section['condition'] ?? null;

            if (is_bool($condition)) {
                return $condition;
            }

            if (is_numeric($condition)) {
                return (int) $condition === 1;
            }

            if (! is_string($condition)) {
                return false;
            }

            return $this->normalizeReferenceThreeConditionValueForStatistics($condition) === 'SI';
        }

        private function hasExplicitReferenceThreeConditionalDataset($raw): bool
            {
                if (is_string($raw)) {
                    $raw = json_decode($raw, true);
                }

                if (! is_array($raw) || $raw === []) {
                    return false;
                }

                foreach ($raw as $section) {
                    if (! is_array($section)) {
                        continue;
                    }

                    if (! array_key_exists('condition', $section)) {
                        continue;
                    }

                    $condition = $section['condition'];

                    if (is_bool($condition) || is_numeric($condition)) {
                        return true;
                    }

                    if (! is_string($condition)) {
                        continue;
                    }

                    $normalized = $this->normalizeReferenceThreeConditionValueKey($condition);

                    if (in_array($normalized, ['si', 'sí', 'yes', 'true', '1', 'no', 'false', '0'], true)) {
                        return true;
                    }
                }

                return false;
            }

            private function normalizeReferenceThreeConditionValueKey(string $value): string
            {
                return Str::of($value)
                    ->ascii()
                    ->lower()
                    ->replace(['-', ' '], '_')
                    ->replaceMatches('/_+/', '_')
                    ->trim('_')
                    ->toString();
            }

        private function normalizeReferenceThreeConditionValueForStatistics(string $value): string
        {
            $value = mb_strtoupper(trim($value));

            $value = strtr($value, [
                'Á' => 'A',
                'É' => 'E',
                'Í' => 'I',
                'Ó' => 'O',
                'Ú' => 'U',
            ]);

            return in_array($value, ['SI', 'YES', 'TRUE', '1'], true) ? 'SI' : 'NO';
        }

        private function calculateReferenceThreeQuestionsScoreForStatistics(array $answers, array $questions): int
        {
            $score = 0;
            $answerValues = config('answer_values', []);
            $groupOneQuestions = $answerValues['group1']['questions'] ?? [];

            foreach ($questions as $questionNumber) {
                $questionKey = $this->normalizeReferenceThreeQuestionKeyForStatistics($questionNumber);

                $answer = $answers[$questionKey]
                    ?? $answers[(string) ((int) $questionNumber)]
                    ?? $answers[(int) $questionNumber]
                    ?? null;

                if ($answer === null || is_array($answer)) {
                    continue;
                }

                $answer = strtoupper(trim((string) $answer));

                if (! in_array($answer, ['A', 'B', 'C', 'D', 'E'], true)) {
                    continue;
                }

                $group = in_array($questionKey, $groupOneQuestions, true)
                    ? 'group1'
                    : 'group2';

                $score += (int) ($answerValues[$group]['values'][$answer] ?? 0);
            }

            return $score;
        }

        private function getReferenceThreeCategoryMaxScoreFromQuestionDimensions(string $categoryName, array $domainConfig): int
        {
            $totalQuestions = 0;

            foreach (($domainConfig[$categoryName] ?? []) as $domainName => $dimensions) {
                if (! is_array($dimensions)) {
                    continue;
                }

                foreach ($dimensions as $dimensionName => $questions) {
                    if (is_array($questions)) {
                        $totalQuestions += count($questions);
                    }
                }
            }

            return $totalQuestions * 4;
        }

        private function getReferenceThreeQuestionsMaxScoreFromDimensions(array $dimensions): int
        {
            $totalQuestions = 0;

            foreach ($dimensions as $dimensionName => $questions) {
                if (is_array($questions)) {
                    $totalQuestions += count($questions);
                }
            }

            return $totalQuestions * 4;
        }

        private function classifyReferenceThreeCategoryRiskLevelFromConfig(string $categoryName, int|float $score): string
        {
            $levels = config("nom035_risk_levels.categories.$categoryName.levels", []);

            if (! is_array($levels) || $levels === []) {
                return 'nulo';
            }

            $roundedScore = (int) round((float) $score);

            foreach ($levels as $levelKey => $range) {
                $min = (int) ($range['min'] ?? 0);
                $max = (int) ($range['max'] ?? 0);

                if ($roundedScore >= $min && $roundedScore <= $max) {
                    return (string) $levelKey;
                }
            }

            return 'nulo';
        }

        private function classifyReferenceThreeDimensionRiskLevelFromConfig(string $dimensionName, int|float $score): string
        {
            $riskLevels = config('nom035_risk_levels.dimensions', []);
            $normalizedDimensionName = $this->normalizeReferenceThreeRiskNameForStatistics($dimensionName);

            $levels = [];

            foreach ($riskLevels as $configuredName => $configuredData) {
                if ($this->normalizeReferenceThreeRiskNameForStatistics((string) $configuredName) !== $normalizedDimensionName) {
                    continue;
                }

                $levels = $configuredData['levels'] ?? [];
                break;
            }

            if (! is_array($levels) || $levels === []) {
                return 'nulo';
            }

            $roundedScore = (int) round((float) $score);

            foreach ($levels as $levelKey => $range) {
                $min = (int) ($range['min'] ?? 0);
                $max = (int) ($range['max'] ?? 0);

                if ($roundedScore >= $min && $roundedScore <= $max) {
                    return (string) $levelKey;
                }
            }

            return 'nulo';
        }

        private function normalizeReferenceThreeRiskNameForStatistics(string $value): string
        {
            $value = str_replace("\xC2\xA0", ' ', $value);
            $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

            return mb_strtolower($value);
        }

    private function getReferenceThreeDimensionMaxScores(): array
    {
        return [
            'Condiciones peligrosas e inseguras' => 8,
            'Condiciones deficientes e insalubres' => 8,
            'Trabajos peligrosos' => 4,
            'Cargas cuantitativas' => 8,
            'Ritmos de trabajo acelerado' => 8,
            'Carga mental' => 12,
            'Cargas psicológicas emocionales' => 16,
            'Cargas de alta responsabilidad' => 8,
            'Cargas contradictorias o inconsistentes' => 8,
            'Falta de control y autonomía sobre el trabajo' => 16,
            'Limitada o nula posibilidad de desarrollo' => 8,
            'Insuficiente participación y manejo del cambio' => 8,
            'Limitada o inexistente capacitación' => 8,
            'Jornadas de trabajo extensas' => 8,
            'Influencia del trabajo fuera del centro laboral' => 8,
            'Influencia de las responsabilidades familiares' => 8,
            'Escasa claridad de funciones' => 16,
            'Características del liderazgo' => 20,
            'Relaciones sociales en el trabajo' => 20,
            'Deficiente relación con los colaboradores que supervisa' => 16,
            'Violencia laboral' => 32,
            'Escasa o nula retroalimentación del desempeño' => 8,
            'Escaso o nulo reconocimiento y compensación' => 16,
            'Limitado sentido de pertenencia' => 8,
            'Inestabilidad laboral' => 8,
        ];
    }

    private function getWorkerIdentificationByDimensionSummary(string $organizationId, string $workCenterId): array
        {
            $rows = DB::table('evaluation_answers as ea')
                ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.source',
                    'pe.personal_folio',
                    'pe.evaluee_name',
                    'ea.question_key',
                    'ea.answer_value',
                    'dd.department',
                    'dd.position',
                    'dd.extra_fields'
                )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            $evaluations = $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) {
                $first = $items->first();

                $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);
                if (! is_array($extra)) {
                    $extra = [];
                }

                $isBoss = $this->extractWorkerFlag($extra, [
                    'jefe',
                    'soy_jefe',
                    'is_boss',
                    'is_manager',
                    'supervises_people',
                    'supervisa_personal',
                    'jefe_trabajadores',
                ]);

                $attendsPublic = $this->extractWorkerFlag($extra, [
                    'atiende',
                    'atiende_clientes',
                    'atencion_clientes',
                    'servicio_clientes',
                    'servicio_usuarios',
                    'client_service',
                    'attends_public',
                ]);

                $result = $this->buildReferenceThreeEvaluationResult(
                    (string) $evaluationId,
                    (string) ($first->source ?? 'paper'),
                    $items,
                    $attendsPublic,
                    $isBoss
                );

                $result['folio'] = $this->safeValue($first->personal_folio);
                $result['name'] = $this->safeValue($first->evaluee_name);
                $result['area'] = $this->safeValue($first->department);
                $result['position'] = $this->safeValue($first->position);
                $result['is_boss'] = $isBoss;
                $result['attends_public'] = $attendsPublic;

                return $result;
            })
                ->values()
                ->all();

            $groups = [];
            $dimensionNames = array_keys($this->getReferenceThreeDimensionMaxScores());

            foreach ($dimensionNames as $index => $dimensionName) {
                $dimensionRows = [];
                $totals = [
                    'muy_alto' => 0,
                    'alto' => 0,
                    'medio' => 0,
                ];

                foreach ($evaluations as $evaluation) {
                    $dimensionLevel = $evaluation['dimension_levels'][$dimensionName]['key'] ?? 'nulo';

                    if (! in_array($dimensionLevel, ['medio', 'alto', 'muy_alto'], true)) {
                        continue;
                    }

                    $totals[$dimensionLevel]++;

                    $dimensionRows[] = [
                        'folio' => $evaluation['folio'] ?? 'N/D',
                        'source' => $evaluation['source'] ?? null,
                        'dimension_score' => (int) ($evaluation['dimension_scores'][$dimensionName] ?? 0),
                        'dimension_level_key' => $dimensionLevel,
                        'global_score' => (int) ($evaluation['global_score'] ?? 0),
                        'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                        'name' => $evaluation['name'] ?? 'N/D',
                        'area' => $evaluation['area'] ?? 'N/D',
                        'position' => $evaluation['position'] ?? 'N/D',
                        'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                        'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                    ];
                }

                usort($dimensionRows, function ($a, $b) {
                return $this->compareRiskRows($a, $b, 'dimension_level_key', 'dimension_score');
            });

                if (! empty($dimensionRows)) {
                    $groups[] = [
                        'number' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'code' => 'D' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                        'name' => $dimensionName,
                        'rows' => $dimensionRows,
                        'totals' => $totals,
                    ];
                }
            }
            usort($groups, function ($a, $b) {
            return $this->compareGroupedTablesByRisk($a, $b);
        });
            return $groups;
        }

        private function getWorkerIdentificationByPositionSummary(string $organizationId, string $workCenterId): array
        {
            $rows = DB::table('evaluation_answers as ea')
                ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.source',
                    'pe.personal_folio',
                    'pe.evaluee_name',
                    'ea.question_key',
                    'ea.answer_value',
                    'dd.department',
                    'dd.position',
                    'dd.work_schedule',
                    'dd.extra_fields'
                )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            $evaluations = $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) {
                $first = $items->first();

                $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);
                if (! is_array($extra)) {
                    $extra = [];
                }

                $isBoss = $this->extractWorkerFlag($extra, [
                    'jefe',
                    'soy_jefe',
                    'is_boss',
                    'is_manager',
                    'supervises_people',
                    'supervisa_personal',
                    'jefe_trabajadores',
                ]);

                $attendsPublic = $this->extractWorkerFlag($extra, [
                    'atiende',
                    'atiende_clientes',
                    'atencion_clientes',
                    'servicio_clientes',
                    'servicio_usuarios',
                    'client_service',
                    'attends_public',
                ]);

                $result = $this->buildReferenceThreeEvaluationResult(
                    (string) $evaluationId,
                    (string) ($first->source ?? 'paper'),
                    $items,
                    $attendsPublic,
                    $isBoss
                );

                $result['folio'] = $this->safeValue($first->personal_folio);
                $result['name'] = $this->safeValue($first->evaluee_name);
                $result['area'] = $this->safeValue($first->department);
                $result['position'] = $this->safeValue($first->position);
                $result['work_schedule'] = $this->safeValue($first->work_schedule);
                $result['is_boss'] = $isBoss;
                $result['attends_public'] = $attendsPublic;

                return $result;
            })
                ->values()
                ->all();

            $grouped = collect($evaluations)
                ->groupBy(function ($row) {
                    $label = trim((string) ($row['position'] ?? ''));
                    return $label !== '' ? $label : 'N/D';
                })
                ->map(function ($items, $positionName) {
                    $rows = collect($items)
                        ->map(function ($evaluation) {
                            return [
                                'folio' => $evaluation['folio'] ?? 'N/D',
                                'source' => $evaluation['source'] ?? null,
                                'global_score' => (int) ($evaluation['global_score'] ?? 0),
                                'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                                'name' => $evaluation['name'] ?? 'N/D',
                                'area' => $evaluation['area'] ?? 'N/D',
                                'work_schedule' => $evaluation['work_schedule'] ?? 'N/D',
                                'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                                'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                            ];
                        })
                        ->sort(function ($a, $b) {
                        return $this->compareRiskRows($a, $b, 'global_level_key', 'global_score');
                    })
                    ->values()
                    ->all();

                    return [
                        'name' => $positionName,
                        'rows' => $rows,
                    ];
                    })
                    ->sort(function ($a, $b) {
                        return $this->compareGroupedTablesByRisk($a, $b);
                    })
                ->values()
                ->all();

            return $grouped;
        }

        private function getWorkerIdentificationByDepartmentSummary(string $organizationId, string $workCenterId): array
        {
            $rows = DB::table('evaluation_answers as ea')
                ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.source',
                    'pe.personal_folio',
                    'pe.evaluee_name',
                    'ea.question_key',
                    'ea.answer_value',
                    'dd.department',
                    'dd.position',
                    'dd.work_schedule',
                    'dd.extra_fields'
                )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            $evaluations = $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) {
                $first = $items->first();

                $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);
                if (! is_array($extra)) {
                    $extra = [];
                }

                $isBoss = $this->extractWorkerFlag($extra, [
                    'jefe',
                    'soy_jefe',
                    'is_boss',
                    'is_manager',
                    'supervises_people',
                    'supervisa_personal',
                    'jefe_trabajadores',
                ]);

                $attendsPublic = $this->extractWorkerFlag($extra, [
                    'atiende',
                    'atiende_clientes',
                    'atencion_clientes',
                    'servicio_clientes',
                    'servicio_usuarios',
                    'client_service',
                    'attends_public',
                ]);

                $result = $this->buildReferenceThreeEvaluationResult(
                    (string) $evaluationId,
                    (string) ($first->source ?? 'paper'),
                    $items,
                    $attendsPublic,
                    $isBoss
                );

                $result['folio'] = $this->safeValue($first->personal_folio);
                $result['name'] = $this->safeValue($first->evaluee_name);
                $result['area'] = $this->safeValue($first->department);
                $result['position'] = $this->safeValue($first->position);
                $result['work_schedule'] = $this->safeValue($first->work_schedule);
                $result['is_boss'] = $isBoss;
                $result['attends_public'] = $attendsPublic;

                return $result;
            })
                ->values()
                ->all();

            $grouped = collect($evaluations)
                ->groupBy(function ($row) {
                    $label = trim((string) ($row['area'] ?? ''));
                    return $label !== '' ? $label : 'N/D';
                })
                ->map(function ($items, $departmentName) {
                    $rows = collect($items)
                        ->map(function ($evaluation) {
                            return [
                                'folio' => $evaluation['folio'] ?? 'N/D',
                                'source' => $evaluation['source'] ?? null,
                                'global_score' => (int) ($evaluation['global_score'] ?? 0),
                                'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                                'name' => $evaluation['name'] ?? 'N/D',
                                'position' => $evaluation['position'] ?? 'N/D',
                                'work_schedule' => $evaluation['work_schedule'] ?? 'N/D',
                                'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                                'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                            ];
                        })
                        ->sort(function ($a, $b) {
                    return $this->compareRiskRows($a, $b, 'global_level_key', 'global_score');
                })
                ->values()
                ->all();

                return [
                    'name' => $departmentName,
                    'rows' => $rows,
                ];
                })
                ->sort(function ($a, $b) {
                    return $this->compareGroupedTablesByRisk($a, $b);
                })
                ->values()
                ->all();

            return $grouped;
        }

        private function getWorkerIdentificationByWorkScheduleSummary(string $organizationId, string $workCenterId): array
        {
            $rows = DB::table('evaluation_answers as ea')
                ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.source',
                    'pe.personal_folio',
                    'pe.evaluee_name',
                    'ea.question_key',
                    'ea.answer_value',
                    'dd.department',
                    'dd.position',
                    'dd.work_schedule',
                    'dd.extra_fields'
                )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            $evaluations = $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) {
                $first = $items->first();

                $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);
                if (! is_array($extra)) {
                    $extra = [];
                }

                $isBoss = $this->extractWorkerFlag($extra, [
                    'jefe',
                    'soy_jefe',
                    'is_boss',
                    'is_manager',
                    'supervises_people',
                    'supervisa_personal',
                    'jefe_trabajadores',
                ]);

                $attendsPublic = $this->extractWorkerFlag($extra, [
                    'atiende',
                    'atiende_clientes',
                    'atencion_clientes',
                    'servicio_clientes',
                    'servicio_usuarios',
                    'client_service',
                    'attends_public',
                ]);

                $result = $this->buildReferenceThreeEvaluationResult(
                    (string) $evaluationId,
                    (string) ($first->source ?? 'paper'),
                    $items,
                    $attendsPublic,
                    $isBoss
                );

                $result['folio'] = $this->safeValue($first->personal_folio);
                $result['name'] = $this->safeValue($first->evaluee_name);
                $result['area'] = $this->safeValue($first->department);
                $result['position'] = $this->safeValue($first->position);
                $result['work_schedule'] = $this->safeValue($first->work_schedule);
                $result['is_boss'] = $isBoss;
                $result['attends_public'] = $attendsPublic;

                return $result;
            })
                ->values()
                ->all();

            $grouped = collect($evaluations)
                ->groupBy(function ($row) {
                    $label = trim((string) ($row['work_schedule'] ?? ''));
                    return $label !== '' ? $label : 'N/D';
                })
                ->map(function ($items, $workScheduleName) {
                    $rows = collect($items)
                        ->map(function ($evaluation) {
                            return [
                                'folio' => $evaluation['folio'] ?? 'N/D',
                                'source' => $evaluation['source'] ?? null,
                                'global_score' => (int) ($evaluation['global_score'] ?? 0),
                                'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                                'name' => $evaluation['name'] ?? 'N/D',
                                'area' => $evaluation['area'] ?? 'N/D',
                                'position' => $evaluation['position'] ?? 'N/D',
                                'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                                'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                            ];
                        })
                        ->sort(function ($a, $b) {
                    return $this->compareRiskRows($a, $b, 'global_level_key', 'global_score');
                })
                ->values()
                ->all();

                return [
                    'name' => $workScheduleName,
                    'rows' => $rows,
                ];
                })
                ->sort(function ($a, $b) {
                    return $this->compareGroupedTablesByRisk($a, $b);
                })
                ->values()
                ->all();

            return $grouped;
        }

        private function getSevereTraumaticEventsSummary(string $organizationId, string $workCenterId): array
            {
                $workCenter = WorkCenter::query()
                    ->where('organization_id', $organizationId)
                    ->findOrFail($workCenterId);

                /** @var \App\Services\WorkCenter\WorkCenterNom035RefIStatisticsService $service */
                $service = app(\App\Services\WorkCenter\WorkCenterNom035RefIStatisticsService::class);

                $clinicalPayload = $service->getClinicalAssessmentParticipants($workCenter);
                $acontecimientoPayload = $service->getAcontecimientoParticipants($workCenter);

                $s1ByEvaluationId = collect($acontecimientoPayload['participants'] ?? [])
                    ->mapWithKeys(function (array $participant) {
                        $events = $participant['events'] ?? [];

                        return [
                            (string) ($participant['id'] ?? '') => count(array_filter($events)),
                        ];
                    })
                    ->all();

                $resultRows = [];
                $requiresMen = 0;
                $requiresWomen = 0;

                foreach (($clinicalPayload['participants'] ?? []) as $participant) {
                    if (! ($participant['requires_clinical_assessment'] ?? false)) {
                        continue;
                    }

                    $demographics = $participant['demographics'] ?? [];
                    $sections = $participant['sections'] ?? [];
                    $evaluationId = (string) ($participant['id'] ?? '');

                    $gender = trim((string) ($demographics['genero'] ?? ''));
                    $genderNormalized = Str::lower(Str::ascii($gender));
                    $genderLabel = $gender !== '' && $gender !== 'No especificado'
                        ? ucfirst(mb_strtolower($gender))
                        : 'N/D';

                    if (in_array($genderNormalized, ['hombre', 'hombres', 'masculino', 'masculina', 'm'], true)) {
                        $requiresMen++;
                    } elseif (in_array($genderNormalized, ['mujer', 'mujeres', 'femenino', 'femenina', 'f'], true)) {
                        $requiresWomen++;
                    }

                    $resultRows[] = [
                        'evaluation_id' => $evaluationId,
                        'folio' => $this->safeValue($participant['personal_folio'] ?? null),
                        'source' => (string) ($participant['source'] ?? ''),
                        'name' => $this->safeValue($participant['name'] ?? null),
                        'gender' => $genderLabel,
                        'position' => $this->safeValue($demographics['puesto'] ?? null),
                        's1' => (int) ($s1ByEvaluationId[$evaluationId] ?? 0),
                        's2' => (int) ($sections['ii']['yes_count'] ?? 0),
                        's3' => (int) ($sections['iii']['yes_count'] ?? 0),
                        's4' => (int) ($sections['iv']['yes_count'] ?? 0),
                        'requires_valuation' => true,
                    ];
                }

                return [
                    'rows' => collect($resultRows)->sortBy('folio', SORT_NATURAL)->values()->all(),
                    'requires_valuation_total' => (int) ($clinicalPayload['requires_clinical_count'] ?? count($resultRows)),
                    'requires_valuation_men' => $requiresMen,
                    'requires_valuation_women' => $requiresWomen,
                ];
            }

        private function getAtsPanoramaSummary(string $organizationId, string $workCenterId): array
            {
                $workCenter = WorkCenter::query()
                    ->where('organization_id', $organizationId)
                    ->findOrFail($workCenterId);

                /** @var \App\Services\WorkCenter\WorkCenterNom035RefIStatisticsService $service */
                $service = app(\App\Services\WorkCenter\WorkCenterNom035RefIStatisticsService::class);

                $panorama = $service->getAtsPanoramaStatistics($workCenter);
                $participantsPayload = $service->getAcontecimientoParticipants($workCenter);

                $participants = collect($participantsPayload['participants'] ?? []);

                $yesRows = $participants
                    ->filter(fn (array $participant) => (bool) ($participant['has_any_event'] ?? false))
                    ->map(function (array $participant) {
                        $demographics = $participant['demographics'] ?? [];

                        $gender = trim((string) ($demographics['genero'] ?? ''));
                        $genderLabel = $gender !== '' && $gender !== 'No especificado'
                            ? ucfirst(mb_strtolower($gender))
                            : 'N/D';

                        $presentedAt = 'N/D';
                        try {
                            if (! empty($participant['created_at'])) {
                                $presentedAt = Carbon::parse($participant['created_at'])->format('d/m/Y, H:i');
                            }
                        } catch (\Throwable $e) {
                            $presentedAt = 'N/D';
                        }

                        $ageValue = $demographics['edad'] ?? null;
                        $ageLabel = 'N/D';
                        if ($ageValue !== null && $ageValue !== '' && $ageValue !== 'No especificado') {
                            $ageLabel = is_numeric($ageValue)
                                ? rtrim(rtrim(number_format((float) $ageValue, 2, '.', ''), '0'), '.')
                                : (string) $ageValue;
                        }

                        $events = $participant['events'] ?? [];

                        return [
                            'evaluation_id' => (string) ($participant['id'] ?? ''),
                            'folio' => $this->safeValue($participant['personal_folio'] ?? null),
                            'source' => (string) ($participant['source'] ?? ''),
                            'name' => $this->safeValue($participant['name'] ?? null),
                            'presented_at' => $presentedAt,
                            'gender' => $genderLabel,
                            'age' => $ageLabel,
                            'position' => $this->safeValue($demographics['puesto'] ?? null),
                            'area' => $this->safeValue($demographics['area'] ?? null),
                            'flags' => [
                                'accidente' => (bool) ($events['1'] ?? false),
                                'asaltos' => (bool) ($events['2'] ?? false),
                                'actos_violentos' => (bool) ($events['3'] ?? false),
                                'secuestro' => (bool) ($events['4'] ?? false),
                                'amenazas' => (bool) ($events['5'] ?? false),
                                'situacion_riesgo' => (bool) ($events['6'] ?? false),
                            ],
                        ];
                    })
                    ->sortBy('folio', SORT_NATURAL)
                    ->values()
                    ->all();

                $items = collect($panorama['items'] ?? [])->keyBy(fn ($item) => (int) ($item['index'] ?? 0));

                return [
                    'participants_considered' => (int) ($panorama['total_evaluations'] ?? 0),
                    'without_events' => (int) ($panorama['without_traumatic_event_count'] ?? 0),
                    'responded_yes_rows' => $yesRows,
                    'event_counts' => [
                        ['label' => 'Accidente', 'count' => (int) (($items[1]['yes_count'] ?? 0)), 'hex' => 'F43F5E'],
                        ['label' => 'Asaltos', 'count' => (int) (($items[2]['yes_count'] ?? 0)), 'hex' => 'F59E0B'],
                        ['label' => 'Actos violentos', 'count' => (int) (($items[3]['yes_count'] ?? 0)), 'hex' => '10B981'],
                        ['label' => 'Secuestro', 'count' => (int) (($items[4]['yes_count'] ?? 0)), 'hex' => '0EA5E9'],
                        ['label' => 'Amenazas', 'count' => (int) (($items[5]['yes_count'] ?? 0)), 'hex' => '8B5CF6'],
                        ['label' => 'Situación de riesgo', 'count' => (int) (($items[6]['yes_count'] ?? 0)), 'hex' => 'D946EF'],
                    ],
                ];
            }

        private function getWorkplaceViolenceWorkersSummary(string $organizationId, string $workCenterId): array
        {
            $atsFlags = collect($this->getSevereTraumaticEventsSummary($organizationId, $workCenterId)['rows'] ?? [])
                ->mapWithKeys(fn ($row) => [$row['evaluation_id'] => (bool) ($row['requires_valuation'] ?? false)])
                ->all();

            $rows = DB::table('evaluation_answers as ea')
                ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->where('pe.evaluation_type', 'referencia_iii')
                ->where('ea.instrument', 'referencia_iii')
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->whereIn('ea.question_key', ['57', '58', '59', '60', '61', '62', '63', '64'])
                ->select(
                    'pe.id as evaluation_id',
                    'pe.personal_folio',
                    'pe.evaluee_name',
                    'dd.gender',
                    'ea.question_key',
                    'ea.answer_value'
                )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            return $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) use ($atsFlags) {
                    $itemScores = [
                        57 => 0, 58 => 0, 59 => 0, 60 => 0,
                        61 => 0, 62 => 0, 63 => 0, 64 => 0,
                    ];

                    foreach ($items as $answer) {
                        $score = $this->getReferenceThreeScore($answer->question_key, $answer->answer_value);
                        if ($score !== null) {
                            $itemScores[(int) $answer->question_key] = (int) $score;
                        }
                    }

                    $points = array_sum($itemScores);

                    $first = $items->first();
                    $gender = trim((string) ($first->gender ?? ''));
                    $genderLabel = $gender !== '' ? ucfirst(mb_strtolower($gender)) : 'N/D';

                    return [
                        'evaluation_id' => (string) $evaluationId,
                        'folio' => $this->safeValue($first->personal_folio),
                        'name' => $this->safeValue($first->evaluee_name),
                        'gender' => $genderLabel,
                        'ats' => (bool) ($atsFlags[(string) $evaluationId] ?? false),
                        'points' => $points,
                        'items' => $itemScores,
                    ];
                })
                ->sortByDesc('points')
                ->values()
                ->all();
        }

        private function getWorkplaceViolenceQuantitativeSummary(string $organizationId, string $workCenterId): array
        {
            $rows = $this->getWorkplaceViolenceWorkersSummary($organizationId, $workCenterId);

            $distribution = $this->initializeRiskLevelCounts();
            $questionLabels = $this->getWorkplaceViolenceQuestionLabels();

            $questions = [];
            foreach ($questionLabels as $item => $label) {
                $questions[$item] = [
                    'item' => $item,
                    'label' => $label,
                    'distribution' => $this->initializeRiskLevelCounts(),
                ];
            }

            foreach ($rows as $row) {
                $level = $this->classifyNom035Score(
                    'dimensions',
                    'Violencia laboral',
                    (int) ($row['points'] ?? 0)
                );

                $levelKey = $level['key'] ?? 'nulo';

                if (array_key_exists($levelKey, $distribution)) {
                    $distribution[$levelKey]++;
                }

                foreach (array_keys($questionLabels) as $item) {
                    $score = (int) ($row['items'][$item] ?? 0);
                    $scoreLevelKey = $this->mapQuestionScoreToRiskLevelKey($score);

                    if (isset($questions[$item]['distribution'][$scoreLevelKey])) {
                        $questions[$item]['distribution'][$scoreLevelKey]++;
                    }
                }
            }

            return [
                'total_participants' => count($rows),
                'distribution' => $distribution,
                'questions' => array_values($questions),
            ];
        }

        private function getWorkplaceViolenceQuantitativeSummaryFromService(WorkCenter $workCenter): array
            {
                /** @var WorkCenterNom035CalculationService $calculationService */
                $calculationService = app(WorkCenterNom035CalculationService::class);

                $stats = $calculationService->calculateViolenceLaborStatistics($workCenter);

                $questions = [];

                foreach (($stats['questions'] ?? []) as $question) {
                    $questions[] = [
                        'item' => (int) ($question['number'] ?? 0),
                        'label' => (string) ($question['text'] ?? ''),
                        'distribution' => [
                            'nulo' => (int) ($question['distribution']['nulo'] ?? 0),
                            'bajo' => (int) ($question['distribution']['bajo'] ?? 0),
                            'medio' => (int) ($question['distribution']['medio'] ?? 0),
                            'alto' => (int) ($question['distribution']['alto'] ?? 0),
                            'muy_alto' => (int) ($question['distribution']['muy_alto'] ?? 0),
                        ],
                    ];
                }

                return [
                    'total_participants' => (int) ($stats['total_evaluated'] ?? 0),
                    'distribution' => [
                        'nulo' => (int) ($stats['total_by_level']['nulo'] ?? 0),
                        'bajo' => (int) ($stats['total_by_level']['bajo'] ?? 0),
                        'medio' => (int) ($stats['total_by_level']['medio'] ?? 0),
                        'alto' => (int) ($stats['total_by_level']['alto'] ?? 0),
                        'muy_alto' => (int) ($stats['total_by_level']['muy_alto'] ?? 0),
                    ],
                    'questions' => $questions,
                    'high_risk_total' => (int) ($stats['high_risk_total'] ?? 0),
                ];
            }

            private function getWorkplaceViolenceWorkersSummaryFromService(WorkCenter $workCenter): array
            {
                /** @var WorkCenterNom035CalculationService $calculationService */
                $calculationService = app(WorkCenterNom035CalculationService::class);

                $stats = $calculationService->calculateViolenceLaborStatistics($workCenter);

                $levelToScore = [
                    'nulo' => 0,
                    'bajo' => 1,
                    'medio' => 2,
                    'alto' => 3,
                    'muy_alto' => 4,
                ];

                /*
                * El servicio trae el cálculo correcto de violencia,
                * pero no siempre trae nombre/género como lo ocupamos en Word.
                * Por eso armamos un mapa desde paper_evaluations + demographic_data
                * solo para completar datos visuales, sin tocar el cálculo.
                */
                $workerMetaRows = DB::table('paper_evaluations as pe')
                    ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                    ->where('pe.organization_id', $workCenter->organization_id)
                    ->where('pe.work_center_id', $workCenter->id)
                    ->where('pe.evaluation_type', 'referencia_iii')
                    ->whereIn('pe.source', ['paper', 'online'])
                    ->where('pe.processing_status', 'completed')
                    ->whereNull('pe.deleted_at')
                    ->select(
                        'pe.id as evaluation_id',
                        'pe.source',
                        'pe.personal_folio',
                        'pe.evaluee_name',
                        'dd.gender'
                    )
                    ->get();

                $workerMetaByEvaluationId = [];
                $workerMetaBySourceAndFolio = [];
                $workerMetaByFolio = [];

                foreach ($workerMetaRows as $metaRow) {
                    $evaluationId = (string) ($metaRow->evaluation_id ?? '');
                    $source = (string) ($metaRow->source ?? '');
                    $folio = trim((string) ($metaRow->personal_folio ?? ''));

                    $gender = trim((string) ($metaRow->gender ?? ''));
                    $genderLabel = $gender !== ''
                        ? ucfirst(mb_strtolower($gender))
                        : 'N/D';

                    $meta = [
                        'name' => $this->safeValue($metaRow->evaluee_name),
                        'gender' => $genderLabel,
                    ];

                    if ($evaluationId !== '') {
                        $workerMetaByEvaluationId[$evaluationId] = $meta;
                    }

                    if ($folio !== '') {
                        $workerMetaByFolio[$folio] = $meta;
                        $workerMetaBySourceAndFolio[$source . '|' . $folio] = $meta;
                    }
                }

                $rows = [];

                foreach (($stats['participants'] ?? []) as $participant) {
                    $items = [];

                    foreach ([57, 58, 59, 60, 61, 62, 63, 64] as $questionNumber) {
                        $level = (string) ($participant['question_levels'][$questionNumber] ?? 'nulo');
                        $items[$questionNumber] = (int) ($levelToScore[$level] ?? 0);
                    }

                    $evaluationId = (string) ($participant['evaluation_id'] ?? $participant['id'] ?? '');
                    $folio = trim((string) ($participant['personal_folio'] ?? ''));
                    $source = (string) ($participant['source'] ?? '');

                    $meta = null;

                    if ($evaluationId !== '' && isset($workerMetaByEvaluationId[$evaluationId])) {
                        $meta = $workerMetaByEvaluationId[$evaluationId];
                    } elseif ($folio !== '' && isset($workerMetaBySourceAndFolio[$source . '|' . $folio])) {
                        $meta = $workerMetaBySourceAndFolio[$source . '|' . $folio];
                    } elseif ($folio !== '' && isset($workerMetaByFolio[$folio])) {
                        $meta = $workerMetaByFolio[$folio];
                    }

                    $genderFromService = trim((string) ($participant['demographics']['genero'] ?? ''));

                    $rows[] = [
                        'folio' => $folio,
                        'source' => $source,
                        'name' => $meta['name'] ?? 'N/D',
                        'gender' => $meta['gender'] ?? ($genderFromService !== '' ? ucfirst(mb_strtolower($genderFromService)) : 'N/D'),
                        'ats' => false,
                        'points' => (int) ($participant['violence_score'] ?? 0),
                        'risk_level' => (string) ($participant['risk_level'] ?? 'nulo'),
                        'items' => $items,
                    ];
                }

                return collect($rows)
                    ->sort(function (array $a, array $b): int {
                        $pointsCompare = ((int) ($b['points'] ?? 0)) <=> ((int) ($a['points'] ?? 0));

                        if ($pointsCompare !== 0) {
                            return $pointsCompare;
                        }

                        return strnatcasecmp((string) ($a['folio'] ?? ''), (string) ($b['folio'] ?? ''));
                    })
                    ->values()
                    ->all();
            }

        private function getWorkplaceViolenceQuestionLabels(): array
        {
            return [
                57 => '57. ¿En mi trabajo puedo expresarme libremente sin interrupciones constantes?',
                58 => '58. ¿Recibo críticas constantes a mi persona y/o trabajo?',
                59 => '59. ¿Recibo burlas, calumnias, difamaciones, humillaciones o ridiculizaciones?',
                60 => '60. ¿Se ignora mi presencia o se me excluye de las reuniones de trabajo y en la toma de decisiones?',
                61 => '61. ¿Se manipulan las situaciones de trabajo para hacerme parecer un mal trabajador?',
                62 => '62. ¿Se ignoran mis éxitos laborales y se atribuyen a otros trabajadores?',
                63 => '63. ¿Me bloquean o impiden las oportunidades que tengo para obtener ascenso o mejora en mi trabajo?',
                64 => '64. ¿He presenciado actos de violencia en mi centro de trabajo?',
            ];
        }

        private function mapQuestionScoreToRiskLevelKey(int $score): string
        {
            return match ($score) {
                4 => 'muy_alto',
                3 => 'alto',
                2 => 'medio',
                1 => 'bajo',
                default => 'nulo',
            };
        }

        private function getFinalRiskWorkersSummary(WorkCenter $workCenter): array
            {
                /** @var WorkCenterNom035CalculationService $calculationService */
                $calculationService = app(WorkCenterNom035CalculationService::class);

                /*
                * Este es el mismo origen que usa el sistema para el listado:
                * folio, nombre, área, puesto y total_score.
                */
                $serviceSummary = $calculationService->getEvaluationsWithDemographicsAndScores($workCenter);

                $rows = [];

                foreach (($serviceSummary['evaluations'] ?? []) as $evaluation) {
                    $demographics = is_array($evaluation['demographics'] ?? null)
                        ? $evaluation['demographics']
                        : [];

                    $globalScore = (int) ($evaluation['total_score'] ?? 0);
                    $globalLevel = $this->classifyNom035Score('global', null, $globalScore);

                    $rows[] = [
                        'folio' => $this->safeValue(
                            $evaluation['personal_folio']
                            ?? $evaluation['folio']
                            ?? 'N/D'
                        ),
                        'source' => $evaluation['source'] ?? null,
                        'global_score' => $globalScore,
                        'global_level_key' => (string) ($globalLevel['key'] ?? 'nulo'),
                        'global_level_label' => (string) ($globalLevel['label'] ?? 'Nulo'),
                        'name' => $this->safeValue($evaluation['evaluee_name'] ?? 'N/D'),
                        'area' => $this->safeValue($demographics['area'] ?? 'N/D'),
                        'position' => $this->safeValue($demographics['puesto'] ?? 'N/D'),
                    ];
                }

                return collect($rows)
                    ->sort(function (array $a, array $b): int {
                        /*
                        * Igual que el sistema cuando ordenas por nivel de riesgo:
                        * 1) Nivel de riesgo descendente
                        * 2) Puntos descendente
                        * 3) Folio ascendente
                        */
                        $riskCompare = $this->riskLevelWeight((string) ($b['global_level_key'] ?? 'nulo'))
                            <=> $this->riskLevelWeight((string) ($a['global_level_key'] ?? 'nulo'));

                        if ($riskCompare !== 0) {
                            return $riskCompare;
                        }

                        $scoreCompare = ((int) ($b['global_score'] ?? 0))
                            <=> ((int) ($a['global_score'] ?? 0));

                        if ($scoreCompare !== 0) {
                            return $scoreCompare;
                        }

                        $folioA = $this->stripFirstTwoLeadingZeros((string) ($a['folio'] ?? ''));
                        $folioB = $this->stripFirstTwoLeadingZeros((string) ($b['folio'] ?? ''));

                        $folioCompare = strnatcasecmp($folioA, $folioB);

                        if ($folioCompare !== 0) {
                            return $folioCompare;
                        }

                        return strnatcasecmp(
                            (string) ($a['name'] ?? ''),
                            (string) ($b['name'] ?? '')
                        );
                    })
                    ->values()
                    ->all();
            }

        private function getDomainQuantitativeAnalysisSummary(string $organizationId, string $workCenterId): array
            {
                $rows = DB::table('evaluation_answers as ea')
                    ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                    ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                    ->where('pe.organization_id', $organizationId)
                    ->where('pe.work_center_id', $workCenterId)
                    ->where('pe.evaluation_type', 'referencia_iii')
                    ->where('ea.instrument', 'referencia_iii')
                    ->whereIn('pe.source', ['paper', 'online'])
                    ->where('pe.processing_status', 'completed')
                    ->whereNull('pe.deleted_at')
                    ->select(
                        'pe.id as evaluation_id',
                        'pe.source',
                        'ea.question_key',
                        'ea.answer_value',
                        'dd.position',
                        'dd.extra_fields'
                    )
                    ->orderBy('pe.id')
                    ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                    ->get();

                $evaluations = $rows
                    ->groupBy('evaluation_id')
                    ->map(function ($items, $evaluationId) {
                        $first = $items->first();

                        $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);

                        if (! is_array($extra)) {
                            $extra = [];
                        }

                        $isBoss = $this->extractWorkerFlag($extra, [
                            'jefe',
                            'soy_jefe',
                            'is_boss',
                            'is_manager',
                            'supervises_people',
                            'supervisa_personal',
                            'jefe_trabajadores',
                        ]);

                        $attendsPublic = $this->extractWorkerFlag($extra, [
                            'atiende',
                            'atiende_clientes',
                            'atencion_clientes',
                            'servicio_clientes',
                            'servicio_usuarios',
                            'client_service',
                            'attends_public',
                        ]);

                        $result = $this->buildReferenceThreeEvaluationResult(
                            (string) $evaluationId,
                            (string) ($first->source ?? 'paper'),
                            $items,
                            $attendsPublic,
                            $isBoss
                        );

                        $result['position'] = $this->safeValue($first->position);

                        return $result;
                    })
                    ->values()
                    ->all();

                $domainNames = array_keys($this->getReferenceThreeDomainMaxScores());

                return collect($evaluations)
                    ->groupBy(function ($evaluation) {
                        $label = trim((string) ($evaluation['position'] ?? ''));

                        return $label !== '' ? $label : 'N/D';
                    })
                    ->map(function ($items, $positionName) use ($domainNames) {
                        $participants = count($items);
                        $rows = [];

                        foreach ($domainNames as $domainName) {
                            $distribution = $this->initializeRiskLevelCounts();
                            $applicableEvaluations = 0;

                            foreach ($items as $evaluation) {
                                $domainScores = $evaluation['domain_scores'] ?? [];

                                if (! is_array($domainScores) || ! array_key_exists($domainName, $domainScores)) {
                                    continue;
                                }

                                $score = (int) $domainScores[$domainName];
                                $applicableEvaluations++;

                                $levelKey = $evaluation['domain_levels'][$domainName]['key']
                                    ?? $this->classifyNom035Score('domains', $domainName, $score)['key'];

                                if (array_key_exists($levelKey, $distribution)) {
                                    $distribution[$levelKey]++;
                                }
                            }

                            if ($applicableEvaluations === 0) {
                                continue;
                            }

                            $rows[] = [
                                'label' => str_pad((string) count($rows) + 1, 2, '0', STR_PAD_LEFT) . '.- ' . $domainName,
                                'distribution' => $distribution,
                                'attention' => $this->getQuestionGlobalAttentionCount($distribution),
                                'applicable_evaluations' => $applicableEvaluations,
                            ];
                        }

                        if (empty($rows)) {
                            return null;
                        }

                        return [
                            'name' => $positionName,
                            'participants' => $participants,
                            'rows' => $rows,
                        ];
                    })
                    ->filter()
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all();
            }

        private function getWorkerIdentificationByCategorySummary(string $organizationId, string $workCenterId): array
            {
                $categorySummary = $this->getReferenceThreeCategorySummary($organizationId, $workCenterId);

                $categoryNames = collect($categorySummary['categories'] ?? [])
                    ->pluck('name')
                    ->filter(fn ($name): bool => trim((string) $name) !== '')
                    ->values()
                    ->all();

                if (empty($categoryNames)) {
                    return [
                        'categories' => [],
                        'rows' => [],
                    ];
                }

                $answerRows = DB::table('evaluation_answers as ea')
                    ->join('paper_evaluations as pe', 'pe.id', '=', 'ea.paper_evaluation_id')
                    ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                    ->where('pe.organization_id', $organizationId)
                    ->where('pe.work_center_id', $workCenterId)
                    ->where('pe.evaluation_type', 'referencia_iii')
                    ->where('ea.instrument', 'referencia_iii')
                    ->whereIn('pe.source', ['paper', 'online'])
                    ->where('pe.processing_status', 'completed')
                    ->whereNull('pe.deleted_at')
                    ->select(
                        'pe.id as evaluation_id',
                        'pe.source',
                        'pe.personal_folio',
                        'pe.evaluee_name',
                        'ea.question_key',
                        'ea.answer_value',
                        'dd.extra_fields'
                    )
                    ->orderBy('pe.id')
                    ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                    ->get();

                $workerRows = $answerRows
                    ->groupBy('evaluation_id')
                    ->map(function ($items, $evaluationId) use ($categoryNames) {
                        $first = $items->first();

                        $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);

                        if (! is_array($extra)) {
                            $extra = [];
                        }

                        $isBoss = $this->extractWorkerFlag($extra, [
                            'jefe',
                            'soy_jefe',
                            'is_boss',
                            'is_manager',
                            'supervises_people',
                            'supervisa_personal',
                            'jefe_trabajadores',
                        ]);

                        $attendsPublic = $this->extractWorkerFlag($extra, [
                            'atiende',
                            'atiende_clientes',
                            'atencion_clientes',
                            'servicio_clientes',
                            'servicio_usuarios',
                            'client_service',
                            'attends_public',
                        ]);

                        $result = $this->buildReferenceThreeEvaluationResult(
                            (string) $evaluationId,
                            (string) ($first->source ?? 'paper'),
                            $items,
                            $attendsPublic,
                            $isBoss
                        );

                        $categoryScores = $result['category_scores'] ?? [];
                        $categoryLevels = $result['category_levels'] ?? [];

                        if (! is_array($categoryScores)) {
                            $categoryScores = [];
                        }

                        if (! is_array($categoryLevels)) {
                            $categoryLevels = [];
                        }

                        $categories = [];

                        foreach ($categoryNames as $categoryName) {
                            if (! array_key_exists($categoryName, $categoryScores)) {
                                continue;
                            }

                            $score = (int) $categoryScores[$categoryName];

                            $levelMeta = is_array($categoryLevels[$categoryName] ?? null)
                                ? $categoryLevels[$categoryName]
                                : [];

                            $levelKey = $levelMeta['key']
                                ?? $this->classifyNom035Score('categories', $categoryName, $score)['key'];

                            $categories[$categoryName] = [
                                'name' => $categoryName,
                                'score' => $score,
                                'level_key' => $levelKey,
                            ];
                        }

                        return [
                            'folio' => $this->safeValue($first->personal_folio),
                            'source' => $result['source'] ?? null,
                            'name' => $this->safeValue($first->evaluee_name),
                            'global_score' => (int) ($result['global_score'] ?? 0),
                            'global_level_key' => $result['global_level_key'] ?? 'nulo',
                            'categories' => $categories,
                        ];
                    })
                    ->filter(function ($row) {
                        return ! empty($row['categories']);
                    })
                    ->sort(function ($a, $b) {
                        return $this->compareRiskRows($a, $b, 'global_level_key', 'global_score');
                    })
                    ->values()
                    ->all();

                return [
                    'categories' => $categoryNames,
                    'rows' => $workerRows,
                ];
            }

        private function extractWorkerFlag(array $payload, array $keys): bool
        {
            $normalize = function ($value): string {
                return str_replace(
                    ['-', ' '],
                    '_',
                    Str::lower(Str::ascii(trim((string) $value)))
                );
            };

            $normalizedKeys = array_map($normalize, $keys);

            if (array_is_list($payload)) {
                foreach ($payload as $item) {
                    if (is_array($item)) {
                        if ($this->extractWorkerFlag($item, $keys)) {
                            return true;
                        }
                        continue;
                    }

                    $itemNormalized = $normalize($item);

                    foreach ($normalizedKeys as $expectedKey) {
                        if (
                            $itemNormalized !== '' &&
                            (
                                $itemNormalized === $expectedKey ||
                                str_contains($itemNormalized, $expectedKey) ||
                                str_contains($expectedKey, $itemNormalized)
                            )
                        ) {
                            return true;
                        }
                    }
                }

                return false;
            }

            foreach ($payload as $payloadKey => $value) {
                $payloadKeyNormalized = $normalize($payloadKey);

                foreach ($normalizedKeys as $expectedKey) {
                    if (
                        $payloadKeyNormalized === $expectedKey ||
                        str_contains($payloadKeyNormalized, $expectedKey)
                    ) {
                        if (! is_array($value) && $this->isTruthyWorkerValue($value)) {
                            return true;
                        }

                        if (is_array($value)) {
                            foreach ([
                                'checked', 'selected', 'seleccionado', 'activo',
                                'aplica', 'value', 'valor', 'respuesta', 'answer', 'si'
                            ] as $selectedKey) {
                                if (
                                    array_key_exists($selectedKey, $value) &&
                                    $this->isTruthyWorkerValue($value[$selectedKey])
                                ) {
                                    return true;
                                }
                            }
                        }
                    }
                }

                if (is_array($value)) {
                    $labelValue = null;

                    foreach ([
                        'label', 'texto', 'text', 'nombre', 'name',
                        'titulo', 'title', 'opcion', 'option',
                        'valor_texto', 'display'
                    ] as $labelKey) {
                        if (array_key_exists($labelKey, $value) && ! is_array($value[$labelKey])) {
                            $labelValue = $normalize($value[$labelKey]);
                            break;
                        }
                    }

                    if ($labelValue !== null) {
                        foreach ($normalizedKeys as $expectedKey) {
                            if (
                                $labelValue === $expectedKey ||
                                str_contains($labelValue, $expectedKey) ||
                                str_contains($expectedKey, $labelValue)
                            ) {
                                foreach ([
                                    'checked', 'selected', 'seleccionado', 'activo',
                                    'aplica', 'value', 'valor', 'respuesta', 'answer', 'si'
                                ] as $selectedKey) {
                                    if (
                                        array_key_exists($selectedKey, $value) &&
                                        $this->isTruthyWorkerValue($value[$selectedKey])
                                    ) {
                                        return true;
                                    }
                                }
                            }
                        }
                    }

                    if ($this->extractWorkerFlag($value, $keys)) {
                        return true;
                    }
                }
            }

            return false;
        }

        private function isTruthyWorkerValue($value): bool
        {
            if (is_bool($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int) $value === 1;
            }

            if ($value === null) {
                return false;
            }

            $value = trim((string) $value);

            if ($value === '') {
                return false;
            }

            $value = mb_strtolower($value);
            $value = strtr($value, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'Á' => 'a',
                'É' => 'e',
                'Í' => 'i',
                'Ó' => 'o',
                'Ú' => 'u',
            ]);

            static $truthy = [
                '1' => true,
                'si' => true,
                's' => true,
                'true' => true,
                'yes' => true,
                'y' => true,
                'x' => true,
                'on' => true,
                'ok' => true,
                'activo' => true,
                'activa' => true,
                'checked' => true,
                'selected' => true,
                'seleccionado' => true,
                'seleccionada' => true,
                'aplica' => true,
                'aplica_si' => true,
                'verdadero' => true,
                'afirmativo' => true,
            ];

            return isset($truthy[$value]);
        }

    private function addChartImageIfExists(Section $section, ?string $chartPath, int $width = 560): void
        {
            if (! $chartPath || ! file_exists($chartPath)) {
                return;
            }

            $section->addImage($chartPath, [
                'width' => min($width, 560),
                'alignment' => Jc::CENTER,
                'spaceBefore' => 80,
                'spaceAfter' => 80,
            ]);
        }

        private function generateAtsPanoramaChart(array $events, string $outputPath): ?string
            {
                if (! function_exists('imagecreatetruecolor')) {
                    return null;
                }

                if (empty($events)) {
                    return null;
                }

                $chartDir = dirname($outputPath);

                if (! is_dir($chartDir)) {
                    mkdir($chartDir, 0755, true);
                }

                $width = 1220;
                $height = 560;

                $image = imagecreatetruecolor($width, $height);

                if (function_exists('imageantialias')) {
                    imageantialias($image, true);
                }

                $bg = imagecolorallocate($image, 255, 255, 255);
                $border = imagecolorallocate($image, 229, 231, 235);
                $text = imagecolorallocate($image, 55, 65, 81);
                $axis = imagecolorallocate($image, 209, 213, 219);

                imagefill($image, 0, 0, $bg);

                imagefilledrectangle($image, 18, 18, $width - 18, $height - 18, $bg);
                imagerectangle($image, 18, 18, $width - 18, $height - 18, $border);

                $left = 70;
                $right = $width - 70;
                $baseline = 420;
                $maxBarHeight = 240;

                imageline($image, $left, $baseline, $right, $baseline, $axis);

                $count = count($events);
                $barWidth = 90;
                $available = ($right - $left);
                $gap = (int) floor(($available - ($count * $barWidth)) / max(1, ($count - 1)));
                $maxValue = max(1, max(array_map(fn ($e) => (int) ($e['count'] ?? 0), $events)));

                foreach ($events as $index => $event) {
                    [$r, $g, $b] = $this->hexToRgb($event['hex'] ?? '3B82F6');
                    $fill = imagecolorallocate($image, $r, $g, $b);

                    $x1 = $left + ($index * ($barWidth + $gap));
                    $x2 = $x1 + $barWidth;

                    $value = (int) ($event['count'] ?? 0);
                    $barHeight = (int) round(($value / $maxValue) * $maxBarHeight);
                    $y1 = $baseline - $barHeight;

                    imagefilledrectangle($image, $x1, $y1, $x2, $baseline, $fill);

                    $this->drawChartTextCenteredBold(
                        $image,
                        12,
                        $x1 - 15,
                        $y1 - 34,
                        $x2 + 15,
                        $y1 - 4,
                        $text,
                        (string) $value
                    );

                    $label = (string) ($event['label'] ?? '');

                    if ($label === 'Situación de riesgo') {
                        $this->drawChartTextCentered($image, 11, $x1 - 25, $baseline + 12, $x2 + 25, $baseline + 32, $text, 'Situación');
                        $this->drawChartTextCentered($image, 11, $x1 - 25, $baseline + 30, $x2 + 25, $baseline + 50, $text, 'de riesgo');
                    } elseif ($label === 'Actos violentos') {
                        $this->drawChartTextCentered($image, 11, $x1 - 25, $baseline + 12, $x2 + 25, $baseline + 32, $text, 'Actos');
                        $this->drawChartTextCentered($image, 11, $x1 - 25, $baseline + 30, $x2 + 25, $baseline + 50, $text, 'violentos');
                    } else {
                        $this->drawChartTextCentered($image, 11, $x1 - 25, $baseline + 14, $x2 + 25, $baseline + 44, $text, $label);
                    }
                }

                imagepng($image, $outputPath);
                imagedestroy($image);

                return file_exists($outputPath) ? $outputPath : null;
            }

    private function generateRiskDistributionChart(string $title, array $distribution, string $outputPath): ?string
        {
            if (! function_exists('imagecreatetruecolor')) {
                return null;
            }

            $levels = [
                ['key' => 'nulo', 'label' => 'Nulo', 'count' => (int) ($distribution['nulo'] ?? 0), 'hex' => '3B82F6'],
                ['key' => 'bajo', 'label' => 'Bajo', 'count' => (int) ($distribution['bajo'] ?? 0), 'hex' => '16A34A'],
                ['key' => 'medio', 'label' => 'Medio', 'count' => (int) ($distribution['medio'] ?? 0), 'hex' => 'F8FF03'],
                ['key' => 'alto', 'label' => 'Alto', 'count' => (int) ($distribution['alto'] ?? 0), 'hex' => 'F59E0B'],
                ['key' => 'muy_alto', 'label' => 'Muy Alto', 'count' => (int) ($distribution['muy_alto'] ?? 0), 'hex' => 'EF4444'],
            ];

            $total = array_sum(array_column($levels, 'count'));

            if ($total <= 0) {
                return null;
            }

            $chartDir = dirname($outputPath);

            if (! is_dir($chartDir)) {
                mkdir($chartDir, 0755, true);
            }

            $width = 1220;
            $height = 920;

            $image = imagecreatetruecolor($width, $height);

            if (function_exists('imageantialias')) {
                imageantialias($image, true);
            }

            $bg = imagecolorallocate($image, 248, 250, 252);
            $white = imagecolorallocate($image, 255, 255, 255);
            $border = imagecolorallocate($image, 203, 213, 225);
            $titleColor = imagecolorallocate($image, 15, 23, 42);
            $headerBg = imagecolorallocate($image, 234, 242, 255);
            $darkRed = imagecolorallocate($image, 153, 27, 27);

            imagefill($image, 0, 0, $bg);

            imagefilledrectangle($image, 18, 18, $width - 18, $height - 18, $white);
            imagerectangle($image, 18, 18, $width - 18, $height - 18, $border);

            $this->drawChartText($image, 18, 36, 48, $titleColor, $title);

            imagefilledrectangle($image, 30, 78, 320, 122, $white);
            imagerectangle($image, 30, 78, 320, 122, $border);
            $this->drawChartTextCentered($image, 11, 30, 78, 320, 122, $titleColor, 'Incidencias por rangos');

            $boxStartX = 335;
            $boxWidth = 95;
            $boxGap = 8;

            foreach ($levels as $index => $level) {
                [$r, $g, $b] = $this->hexToRgb($level['hex']);
                $fill = imagecolorallocate($image, $r, $g, $b);

                $x1 = $boxStartX + (($boxWidth + $boxGap) * $index);
                $x2 = $x1 + $boxWidth;

                imagefilledrectangle($image, $x1, 78, $x2, 122, $fill);
                imagerectangle($image, $x1, 78, $x2, 122, $border);
                $this->drawChartTextCenteredBold($image, 12, $x1, 78, $x2, 122, $white, (string) $level['count']);
            }

                        $percentages = [];

            foreach ($levels as $level) {
                $percentages[$level['key']] = $total > 0
                    ? round(($level['count'] / $total) * 100, 1)
                    : 0.0;
            }

            $attention = (int) ($distribution['alto'] ?? 0) + (int) ($distribution['muy_alto'] ?? 0);
            $attentionPct = $total > 0 ? round(($attention / $total) * 100, 1) : 0.0;

            imagefilledrectangle($image, 1000, 78, 1135, 122, $darkRed);
            imagerectangle($image, 1000, 78, 1135, 122, $border);
            $this->drawChartTextCenteredBold($image, 12, 1000, 78, 1135, 122, $white, (string) $attention);

            imagefilledrectangle($image, 30, 140, $width - 30, 560, $white);
            imagerectangle($image, 30, 140, $width - 30, 560, $border);

            $this->drawChartText($image, 15, 70, 200, $titleColor, 'Total de');
            $this->drawChartText($image, 15, 70, 228, $titleColor, 'Participantes');
            $this->drawChartTextBold($image, 18, 115, 275, $titleColor, (string) $total);

            $cx = 390;
            $cy = 350;
            $radius = 180;

            $start = 0.0;

            foreach ($levels as $level) {
                if ($level['count'] <= 0) {
                    continue;
                }

                [$r, $g, $b] = $this->hexToRgb($level['hex']);
                $sliceColor = imagecolorallocate($image, $r, $g, $b);

                $angle = ($level['count'] / $total) * 360;
                $end = $start + $angle;

                imagefilledarc(
                    $image,
                    $cx,
                    $cy,
                    $radius * 2,
                    $radius * 2,
                    $start,
                    $end,
                    $sliceColor,
                    IMG_ARC_PIE
                );

                if ($angle >= 18) {
                    $mid = deg2rad($start + ($angle / 2));
                    $labelX = (int) round($cx + cos($mid) * ($radius * 0.58));
                    $labelY = (int) round($cy + sin($mid) * ($radius * 0.58));
                    $pct = number_format($percentages[$level['key']] ?? 0, 1);

                    $labelColor = in_array($level['key'], ['medio'], true) ? $titleColor : $white;

                    $this->drawChartTextBold(
                        $image,
                        9,
                        $labelX - 28,
                        $labelY,
                        $labelColor,
                        $pct . '% ' . $level['label']
                    );
                }

                $start = $end;
            }

            imagearc($image, $cx, $cy, $radius * 2, $radius * 2, 0, 360, $border);

            $legendX = 760;
            $legendY = 190;
            $legendStep = 38;

            foreach ($levels as $index => $level) {
                [$r, $g, $b] = $this->hexToRgb($level['hex']);
                $legendColor = imagecolorallocate($image, $r, $g, $b);
                $pct = number_format($percentages[$level['key']] ?? 0, 1);

                imagefilledrectangle($image, $legendX, $legendY + ($index * $legendStep), $legendX + 22, $legendY + 16 + ($index * $legendStep), $legendColor);
                imagerectangle($image, $legendX, $legendY + ($index * $legendStep), $legendX + 22, $legendY + 16 + ($index * $legendStep), $border);

                $this->drawChartText(
                    $image,
                    10,
                    $legendX + 35,
                    $legendY + 13 + ($index * $legendStep),
                    $titleColor,
                    $pct . '% ' . $level['label']
                );
            }

            $this->drawChartText($image, 14, 845, 420, $titleColor, 'Atención');

            imagefilledrectangle($image, 860, 455, 970, 472, imagecolorallocate($image, 250, 204, 21));
            imagefilledrectangle($image, 971, 455, 1035, 472, imagecolorallocate($image, 245, 158, 11));
            imagefilledrectangle($image, 1036, 455, 1100, 472, imagecolorallocate($image, 239, 68, 68));
            imagerectangle($image, 860, 455, 1100, 472, $border);

            imagefilledrectangle($image, 860, 490, 1100, 548, $darkRed);
            imagerectangle($image, 860, 490, 1100, 548, $border);
            $this->drawChartTextCenteredBold($image, 14, 860, 490, 1100, 548, $white, number_format($attentionPct, 1) . '%');

            // ===== tabla inferior integrada =====
                $tableX = 80;
                $tableY = 615;
                $tableRowH = 48;
                $col1 = 300;
                $col2 = 170;
                $col3 = 220;

                $headerBg = imagecolorallocate($image, 234, 242, 255);

                $this->drawChartTextBold($image, 16, $tableX, $tableY - 18, $titleColor, 'Distribución por nivel global');

                imagefilledrectangle(
                    $image,
                    $tableX,
                    $tableY,
                    $tableX + $col1 + $col2 + $col3,
                    $tableY + ($tableRowH * 6),
                    $white
                );
                imagerectangle(
                    $image,
                    $tableX,
                    $tableY,
                    $tableX + $col1 + $col2 + $col3,
                    $tableY + ($tableRowH * 6),
                    $border
                );

                // encabezado
                imagefilledrectangle($image, $tableX, $tableY, $tableX + $col1, $tableY + $tableRowH, $headerBg);
                imagefilledrectangle($image, $tableX + $col1, $tableY, $tableX + $col1 + $col2, $tableY + $tableRowH, $headerBg);
                imagefilledrectangle($image, $tableX + $col1 + $col2, $tableY, $tableX + $col1 + $col2 + $col3, $tableY + $tableRowH, $headerBg);

                imagerectangle($image, $tableX, $tableY, $tableX + $col1, $tableY + $tableRowH, $border);
                imagerectangle($image, $tableX + $col1, $tableY, $tableX + $col1 + $col2, $tableY + $tableRowH, $border);
                imagerectangle($image, $tableX + $col1 + $col2, $tableY, $tableX + $col1 + $col2 + $col3, $tableY + $tableRowH, $border);

                $this->drawChartTextBold($image, 13, $tableX + 14, $tableY + 31, $titleColor, 'Nivel');
                $this->drawChartTextBold($image, 13, $tableX + $col1 + 14, $tableY + 31, $titleColor, 'Total');
                $this->drawChartTextBold($image, 13, $tableX + $col1 + $col2 + 14, $tableY + 31, $titleColor, 'Porcentaje');

                foreach ($levels as $i => $level) {
                    $y1 = $tableY + $tableRowH + ($i * $tableRowH);
                    $y2 = $y1 + $tableRowH;

                    [$r, $g, $b] = $this->hexToRgb($level['hex']);
                    $rowBg = imagecolorallocate($image, $r, $g, $b);

                    $rowTextColor = in_array($level['key'], ['medio', 'alto'], true)
                        ? $titleColor
                        : $white;

                    imagefilledrectangle($image, $tableX, $y1, $tableX + $col1, $y2, $rowBg);
                    imagefilledrectangle($image, $tableX + $col1, $y1, $tableX + $col1 + $col2, $y2, $rowBg);
                    imagefilledrectangle($image, $tableX + $col1 + $col2, $y1, $tableX + $col1 + $col2 + $col3, $y2, $rowBg);

                    imagerectangle($image, $tableX, $y1, $tableX + $col1, $y2, $border);
                    imagerectangle($image, $tableX + $col1, $y1, $tableX + $col1 + $col2, $y2, $border);
                    imagerectangle($image, $tableX + $col1 + $col2, $y1, $tableX + $col1 + $col2 + $col3, $y2, $border);

                    $pct = number_format(($level['count'] / $total) * 100, 2) . '%';

                    $this->drawChartTextBold($image, 13, $tableX + 14, $y1 + 31, $rowTextColor, $level['label']);
                    $this->drawChartTextCenteredBold($image, 13, $tableX + $col1, $y1, $tableX + $col1 + $col2, $y2, $rowTextColor, (string) $level['count']);
                    $this->drawChartTextCenteredBold($image, 13, $tableX + $col1 + $col2, $y1, $tableX + $col1 + $col2 + $col3, $y2, $rowTextColor, $pct);
                }

            imagepng($image, $outputPath);
            imagedestroy($image);

            return $outputPath;
        }

    private function generateCategoryDashboardChart(
            array $categories,
            int $totalEvaluations,
            string $outputPath,
            int $partNumber = 1,
            int $totalParts = 1
        ): ?string
        {
            if (! function_exists('imagecreatetruecolor')) {
                return null;
            }

            if (empty($categories)) {
                return null;
            }

            $chartDir = dirname($outputPath);

            if (! is_dir($chartDir)) {
                mkdir($chartDir, 0755, true);
            }

            $columns = 2;
            $rows = (int) ceil(count($categories) / $columns);

            $width = 1780;

            $tableX = 35;
            $tableY = 90;
            $tableW = $width - 70;
            $rowH = 62;

            $descW = 760;
            $cellW = 135;

            $tableHeight = $rowH * (count($categories) + 1);
            $panelY = $tableY + $tableHeight + 24;
            $chartBlockHeight = $rows * 430;
            $height = $panelY + $chartBlockHeight + 130;

            $image = imagecreatetruecolor($width, $height);

            if (function_exists('imageantialias')) {
                imageantialias($image, true);
            }

            $white = imagecolorallocate($image, 255, 255, 255);
            $panel = imagecolorallocate($image, 248, 250, 252);
            $border = imagecolorallocate($image, 203, 213, 225);
            $text = imagecolorallocate($image, 31, 41, 55);
            $muted = imagecolorallocate($image, 100, 116, 139);
            $redDark = imagecolorallocate($image, 153, 27, 27);

            $blue = imagecolorallocate($image, 59, 130, 246);   // nulo
            $green = imagecolorallocate($image, 22, 163, 74);   // bajo
            $yellow = imagecolorallocate($image, 250, 204, 21); // medio
            $orange = imagecolorallocate($image, 245, 158, 11); // alto
            $red = imagecolorallocate($image, 239, 68, 68);     // muy alto

            imagefill($image, 0, 0, $white);

            imagefilledrectangle($image, 20, 20, $width - 20, $height - 20, $panel);
            imagerectangle($image, 20, 20, $width - 20, $height - 20, $border);

            $title = 'Categorías - Atención (%)';
            if ($totalParts > 1) {
                $title .= ' - Parte ' . $partNumber . ' de ' . $totalParts;
            }

            $this->drawChartTextBold($image, 24, 38, 42, $text, $title);

            // ===== Tabla superior =====

            imagefilledrectangle($image, $tableX, $tableY, $tableX + $tableW, $tableY + ($rowH * (count($categories) + 1)), $white);
            imagerectangle($image, $tableX, $tableY, $tableX + $tableW, $tableY + ($rowH * (count($categories) + 1)), $border);

            $this->drawChartTextBold($image, 18, $tableX + 18, $tableY + 40, $text, 'Descripción');

            $headers = [
                ['Nulo', $blue],
                ['Bajo', $green],
                ['Medio', $yellow],
                ['Alto', $orange],
                ['Muy Alto', $red],
                ['Atención', $redDark],
            ];

            foreach ($headers as $i => [$label, $fill]) {
                $x1 = $tableX + $descW + ($cellW * $i);
                $x2 = $x1 + $cellW;

                imagefilledrectangle($image, $x1, $tableY, $x2, $tableY + $rowH, $fill);
                imagerectangle($image, $x1, $tableY, $x2, $tableY + $rowH, $border);

                $this->drawChartTextCenteredBold($image, 16, $x1, $tableY, $x2, $tableY + $rowH, $white, $label);
            }

            foreach ($categories as $index => $category) {
                $y1 = $tableY + $rowH + ($index * $rowH);
                $y2 = $y1 + $rowH;

                imagerectangle($image, $tableX, $y1, $tableX + $descW, $y2, $border);

                $name = mb_substr((string) $category['name'], 0, 58);
                $this->drawChartTextBold($image, 16, $tableX + 18, $y1 + 40, $text, $name);

                $dist = $category['distribution'] ?? [];

                $rowValues = [
                    ['nulo', $blue],
                    ['bajo', $green],
                    ['medio', $yellow],
                    ['alto', $orange],
                    ['muy_alto', $red],
                ];

                foreach ($rowValues as $cellIndex => [$key, $fill]) {
                    $x1 = $tableX + $descW + ($cellW * $cellIndex);
                    $x2 = $x1 + $cellW;

                    imagefilledrectangle($image, $x1, $y1, $x2, $y2, $fill);
                    imagerectangle($image, $x1, $y1, $x2, $y2, $border);

                    $value = (string) ((int) ($dist[$key] ?? 0));
                    $this->drawChartTextCenteredBold($image, 16, $x1, $y1, $x2, $y2, $white, $value);
                }

                $attention = (string) $this->getAttentionCount($dist);
                $attX1 = $tableX + $descW + ($cellW * 5);
                $attX2 = $attX1 + $cellW;

                imagefilledrectangle($image, $attX1, $y1, $attX2, $y2, $redDark);
                imagerectangle($image, $attX1, $y1, $attX2, $y2, $border);

                $this->drawChartTextCenteredBold($image, 16, $attX1, $y1, $attX2, $y2, $white, $attention);
            }

            // panel inferior
            imagefilledrectangle($image, 35, $panelY, $width - 35, $height - 35, $white);
            imagerectangle($image, 35, $panelY, $width - 35, $height - 35, $border);
            $this->drawChartTextBold($image, 22, 50, $panelY + 32, $text, 'Atención (%)');

            $slots = [];
            $baseX = 75;
            $baseY = $panelY + 95;
            $colGap = 860;
            $rowGap = 420;

            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $columns; $c++) {
                    $slots[] = [
                        $baseX + ($c * $colGap),
                        $baseY + ($r * $rowGap),
                    ];
                }
            }

            foreach ($categories as $i => $category) {
                if (! isset($slots[$i])) {
                    break;
                }

                [$baseX, $baseY] = $slots[$i];

                $this->drawCategoryMiniChart(
                    $image,
                    $baseX,
                    $baseY,
                    700,
                    340,
                    (string) $category['name'],
                    (float) ($category['average_score'] ?? 0),
                    (int) ($category['max_score'] ?? 0),
                    (float) ($category['average_percentage'] ?? 0),
                    $category['distribution'] ?? [],
                    (int) ($category['applicable_evaluations'] ?? $totalEvaluations),
                    $blue,
                    $green,
                    $yellow,
                    $orange,
                    $red,
                    $text,
                    $muted,
                    $border
                );
            }

            imagepng($image, $outputPath);
            imagedestroy($image);

            return $outputPath;
        }

    private function drawCategoryMiniChart(
            $image,
            int $x,
            int $y,
            int $w,
            int $h,
            string $title,
            float $averageScore,
            int $maxScore,
            float $averagePercentage,
            array $distribution,
            int $totalEvaluations,
            $blue,
            $green,
            $yellow,
            $orange,
            $red,
            $textColor,
            $mutedColor,
            $borderColor
        ): void {
            $title = mb_substr($title, 0, 40);

            $this->drawChartTextBold($image, 18, $x + 8, $y - 28, $textColor, $title);

            $averageText = 'Promedio: ' .
            number_format($averageScore, 2) .
            ' / ' .
            $maxScore .
            ' (' .
            number_format($averagePercentage, 2) .
            '%)';

        $this->drawChartText($image, 12, $x + 8, $y + 8, $mutedColor, $averageText);

        $chartX = $x + 10;
        $chartY = $y + 48;
        $chartW = $w;
        $chartH = max(185, $h - 40);

            imagerectangle($image, $chartX, $chartY, $chartX + $chartW, $chartY + $chartH, $borderColor);

            for ($i = 0; $i <= 5; $i++) {
                $gy = $chartY + $chartH - (int)(($chartH / 5) * $i);
                imageline($image, $chartX, $gy, $chartX + $chartW, $gy, $borderColor);

                $axisLabel = (string) ($i * 20);
                $this->drawChartText($image, 10, $chartX - 28, $gy + 4, $mutedColor, $axisLabel);
            }

            $colors = [$blue, $green, $yellow, $orange, $red];
            $keys = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

            $barW = 92;
            $gap = 24;
            $startX = $chartX + 42;
            $maxH = $chartH - 35;

            foreach ($keys as $i => $key) {
                $pct = $totalEvaluations > 0
                    ? round(((int) ($distribution[$key] ?? 0) / $totalEvaluations) * 100, 2)
                    : 0;

                $barHeight = (int) round(($pct / 100) * $maxH);

                $bx1 = $startX + (($barW + $gap) * $i);
                $bx2 = $bx1 + $barW;
                $by1 = $chartY + $chartH - $barHeight;
                $by2 = $chartY + $chartH;

                imagefilledrectangle($image, $bx1, $by1, $bx2, $by2, $colors[$i]);
                imagerectangle($image, $bx1, $by1, $bx2, $by2, $borderColor);

                $label = number_format($pct, 2);
                $this->drawChartTextBold($image, 11, $bx1 - 2, $by1 - 10, $textColor, $label);
            }
        }

    private function generateDomainDashboardChart(
            array $domains,
            int $totalEvaluations,
            string $outputPath,
            int $partNumber = 1,
            int $totalParts = 1
        ): ?string
        {
            if (! function_exists('imagecreatetruecolor')) {
                return null;
            }

            if (empty($domains)) {
                return null;
            }

            $chartDir = dirname($outputPath);

            if (! is_dir($chartDir)) {
                mkdir($chartDir, 0755, true);
            }

            $columns = 2;
            $rows = (int) ceil(count($domains) / $columns);

            $width = 1780;

            $tableX = 35;
            $tableY = 90;
            $tableW = $width - 70;
            $rowH = 60;

            $descW = 620;
            $cellW = 115;

            $tableHeight = $rowH * (count($domains) + 1);
            $panelY = $tableY + $tableHeight + 24;
            $chartBlockHeight = $rows * 430;
            $height = $panelY + $chartBlockHeight + 130;

            $image = imagecreatetruecolor($width, $height);

            if (function_exists('imageantialias')) {
                imageantialias($image, true);
            }

            $white = imagecolorallocate($image, 255, 255, 255);
            $panel = imagecolorallocate($image, 248, 250, 252);
            $border = imagecolorallocate($image, 203, 213, 225);
            $text = imagecolorallocate($image, 31, 41, 55);
            $muted = imagecolorallocate($image, 100, 116, 139);
            $redDark = imagecolorallocate($image, 153, 27, 27);

            $blue = imagecolorallocate($image, 59, 130, 246);   // nulo
            $green = imagecolorallocate($image, 22, 163, 74);   // bajo
            $yellow = imagecolorallocate($image, 250, 204, 21); // medio
            $orange = imagecolorallocate($image, 245, 158, 11); // alto
            $red = imagecolorallocate($image, 239, 68, 68);     // muy alto

            imagefill($image, 0, 0, $white);

            imagefilledrectangle($image, 20, 20, $width - 20, $height - 20, $panel);
            imagerectangle($image, 20, 20, $width - 20, $height - 20, $border);

            $title = 'Dominios - Atención (%)';
if ($totalParts > 1) {
    $title .= ' - Parte ' . $partNumber . ' de ' . $totalParts;
}

$this->drawChartTextBold($image, 24, 38, 42, $text, $title);

            // ===== Tabla superior =====
            $tableX = 35;
            $tableY = 90;
            $tableW = $width - 70;
            $rowH = 60;

            $descW = 620;
            $cellW = 115;

            imagefilledrectangle($image, $tableX, $tableY, $tableX + $tableW, $tableY + ($rowH * (count($domains) + 1)), $white);
            imagerectangle($image, $tableX, $tableY, $tableX + $tableW, $tableY + ($rowH * (count($domains) + 1)), $border);

            $this->drawChartTextBold($image, 18, $tableX + 18, $tableY + 38, $text, 'Descripción');

            $headers = [
                ['Nulo', $blue],
                ['Bajo', $green],
                ['Medio', $yellow],
                ['Alto', $orange],
                ['Muy Alto', $red],
                ['Atención', $redDark],
            ];

            foreach ($headers as $i => [$label, $fill]) {
                $x1 = $tableX + $descW + ($cellW * $i);
                $x2 = $x1 + $cellW;

                imagefilledrectangle($image, $x1, $tableY, $x2, $tableY + $rowH, $fill);
                imagerectangle($image, $x1, $tableY, $x2, $tableY + $rowH, $border);

                $this->drawChartTextCenteredBold($image, 16, $x1, $tableY, $x2, $tableY + $rowH, $white, $label);
            }

            foreach ($domains as $index => $domain) {
                $y1 = $tableY + $rowH + ($index * $rowH);
                $y2 = $y1 + $rowH;

                imagerectangle($image, $tableX, $y1, $tableX + $descW, $y2, $border);

                $name = mb_substr((string) $domain['name'], 0, 62);
                $this->drawChartTextBold($image, 15, $tableX + 18, $y1 + 38, $text, $name);

                $dist = $domain['distribution'] ?? [];

                $rowValues = [
                    ['nulo', $blue],
                    ['bajo', $green],
                    ['medio', $yellow],
                    ['alto', $orange],
                    ['muy_alto', $red],
                ];

                foreach ($rowValues as $cellIndex => [$key, $fill]) {
                    $x1 = $tableX + $descW + ($cellW * $cellIndex);
                    $x2 = $x1 + $cellW;

                    imagefilledrectangle($image, $x1, $y1, $x2, $y2, $fill);
                    imagerectangle($image, $x1, $y1, $x2, $y2, $border);

                    $value = (string) ((int) ($dist[$key] ?? 0));
                    $this->drawChartTextCenteredBold($image, 16, $x1, $y1, $x2, $y2, $white, $value);
                }

                $attention = (string) $this->getAttentionCount($dist);
                $attX1 = $tableX + $descW + ($cellW * 5);
                $attX2 = $attX1 + $cellW;

                imagefilledrectangle($image, $attX1, $y1, $attX2, $y2, $redDark);
                imagerectangle($image, $attX1, $y1, $attX2, $y2, $border);

                $this->drawChartTextCenteredBold($image, 16, $attX1, $y1, $attX2, $y2, $white, $attention);
            }

            // panel inferior
            imagefilledrectangle($image, 35, $panelY, $width - 35, $height - 35, $white);
            imagerectangle($image, 35, $panelY, $width - 35, $height - 35, $border);
            $this->drawChartTextBold($image, 22, 50, $panelY + 32, $text, 'Atención (%)');

            $slots = [];
            $baseX = 75;
            $baseY = $panelY + 95;
            $colGap = 860;
            $rowGap = 420;

            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $columns; $c++) {
                    $slots[] = [
                        $baseX + ($c * $colGap),
                        $baseY + ($r * $rowGap),
                    ];
                }
            }

            foreach ($domains as $i => $domain) {
                if (! isset($slots[$i])) {
                    break;
                }

                [$baseX, $baseY] = $slots[$i];

                $this->drawDomainMiniChart(
                $image,
                $baseX,
                $baseY,
                700,
                340,
                (string) $domain['name'],
                (float) ($domain['average_score'] ?? 0),
                (int) ($domain['max_score'] ?? 0),
                (float) ($domain['average_percentage'] ?? 0),
                $domain['distribution'] ?? [],
                (int) ($domain['applicable_evaluations'] ?? $totalEvaluations),
                $blue,
                $green,
                $yellow,
                $orange,
                $red,
                $text,
                $muted,
                $border
            );
            }

            imagepng($image, $outputPath);
            imagedestroy($image);
            gc_collect_cycles();

            return $outputPath;
        }

        private function drawDomainMiniChart(
            $image,
            int $x,
            int $y,
            int $w,
            int $h,
            string $title,
            float $averageScore,
            int $maxScore,
            float $averagePercentage,
            array $distribution,
            int $totalEvaluations,
            $blue,
            $green,
            $yellow,
            $orange,
            $red,
            $textColor,
            $mutedColor,
            $borderColor
        ): void {
            $wrapped = explode("\n", wordwrap($title, 34, "\n", true));
            $line1 = $wrapped[0] ?? '';
            $line2 = $wrapped[1] ?? '';

            $this->drawChartTextBold($image, 18, $x + 8, $y - 32, $textColor, $line1);

            if ($line2 !== '') {
                $this->drawChartTextBold($image, 18, $x + 8, $y - 8, $textColor, $line2);
            }

            $averageText = 'Promedio: ' .
            number_format($averageScore, 2) .
            ' / ' .
            $maxScore .
            ' (' .
            number_format($averagePercentage, 2) .
            '%)';

        $this->drawChartText($image, 12, $x + 8, $y + 14, $mutedColor, $averageText);

        $chartX = $x + 12;
        $chartY = $y + 48;
        $chartW = $w;
        $chartH = max(270, $h - 40);

            imagerectangle($image, $chartX, $chartY, $chartX + $chartW, $chartY + $chartH, $borderColor);

            for ($i = 0; $i <= 5; $i++) {
                $gy = $chartY + $chartH - (int)(($chartH / 5) * $i);
                imageline($image, $chartX, $gy, $chartX + $chartW, $gy, $borderColor);

                $axisLabel = (string) ($i * 20);
                $this->drawChartText($image, 10, $chartX - 28, $gy + 4, $mutedColor, $axisLabel);
            }

            $colors = [$blue, $green, $yellow, $orange, $red];
            $keys = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

            $barW = 92;
            $gap = 24;
            $startX = $chartX + 42;
            $maxH = $chartH - 35;

            foreach ($keys as $i => $key) {
                $pct = $totalEvaluations > 0
                    ? round(((int) ($distribution[$key] ?? 0) / $totalEvaluations) * 100, 2)
                    : 0;

                $barHeight = (int) round(($pct / 100) * $maxH);

                $bx1 = $startX + (($barW + $gap) * $i);
                $bx2 = $bx1 + $barW;
                $by1 = $chartY + $chartH - $barHeight;
                $by2 = $chartY + $chartH;

                imagefilledrectangle($image, $bx1, $by1, $bx2, $by2, $colors[$i]);
                imagerectangle($image, $bx1, $by1, $bx2, $by2, $borderColor);

                $label = number_format($pct, 2);
                $this->drawChartTextBold($image, 10, $bx1 - 2, $by1 - 10, $textColor, $label);
            }
        }    

    private function generateDimensionDashboardChart(
            array $dimensions,
            int $totalEvaluations,
            string $outputPath,
            int $partNumber = 1,
            int $totalParts = 1
        ): ?string {
            if (! function_exists('imagecreatetruecolor')) {
                return null;
            }

            if (empty($dimensions)) {
                return null;
            }

            $chartDir = dirname($outputPath);

            if (! is_dir($chartDir)) {
                mkdir($chartDir, 0755, true);
            }

            $columns = 2;
            $rows = (int) ceil(count($dimensions) / $columns);

            $width = 1560;
            $tableX = 30;
            $tableY = 82;
            $rowH = 44;
            $descW = 790;
            $cellW = 105;

            $tableHeight = $rowH * (count($dimensions) + 1);
            $panelY = $tableY + $tableHeight + 22;
            $chartBlockHeight = ($rows * 520);
            $height = $panelY + $chartBlockHeight + 160;

            $image = imagecreatetruecolor($width, $height);

            if (function_exists('imageantialias')) {
                imageantialias($image, true);
            }

            $white = imagecolorallocate($image, 255, 255, 255);
            $panel = imagecolorallocate($image, 248, 250, 252);
            $border = imagecolorallocate($image, 203, 213, 225);
            $text = imagecolorallocate($image, 31, 41, 55);
            $muted = imagecolorallocate($image, 100, 116, 139);
            $redDark = imagecolorallocate($image, 153, 27, 27);

            $blue = imagecolorallocate($image, 59, 130, 246);   // nulo
            $green = imagecolorallocate($image, 22, 163, 74);   // bajo
            $yellow = imagecolorallocate($image, 250, 204, 21); // medio
            $orange = imagecolorallocate($image, 245, 158, 11); // alto
            $red = imagecolorallocate($image, 239, 68, 68);     // muy alto
            $redDark = imagecolorallocate($image, 153, 0, 0);     // atención

            imagefill($image, 0, 0, $white);

            imagefilledrectangle($image, 20, 20, $width - 20, $height - 20, $panel);
            imagerectangle($image, 20, 20, $width - 20, $height - 20, $border);

            $title = 'Dimensiones - Atención (%)';
            if ($totalParts > 1) {
                $title .= ' - Parte ' . $partNumber . ' de ' . $totalParts;
            }

            $this->drawChartTextBold($image, 24, 38, 42, $text, $title);

            // ===== Tabla superior =====
            $tableW = $width - 70;

            imagefilledrectangle(
                $image,
                $tableX,
                $tableY,
                $tableX + $tableW,
                $tableY + $tableHeight,
                $white
            );
            imagerectangle(
                $image,
                $tableX,
                $tableY,
                $tableX + $tableW,
                $tableY + $tableHeight,
                $border
            );

            $this->drawChartTextBold($image, 18, $tableX + 18, $tableY + 36, $text, 'Descripción');

            $headers = [
                ['Nulo', $blue],
                ['Bajo', $green],
                ['Medio', $yellow],
                ['Alto', $orange],
                ['Muy Alto', $red],
                ['Atención', $redDark],
            ];

            foreach ($headers as $i => [$label, $fill]) {
                $x1 = $tableX + $descW + ($cellW * $i);
                $x2 = $x1 + $cellW;

                imagefilledrectangle($image, $x1, $tableY, $x2, $tableY + $rowH, $fill);
                imagerectangle($image, $x1, $tableY, $x2, $tableY + $rowH, $border);

                $this->drawChartTextCenteredBold($image, 15, $x1, $tableY, $x2, $tableY + $rowH, $white, $label);
            }

            foreach ($dimensions as $index => $dimension) {
                $y1 = $tableY + $rowH + ($index * $rowH);
                $y2 = $y1 + $rowH;

                imagerectangle($image, $tableX, $y1, $tableX + $descW, $y2, $border);

                $name = mb_substr((string) $dimension['name'], 0, 76);
                $this->drawChartTextBold($image, 14, $tableX + 16, $y1 + 36, $text, $name);

                $dist = $dimension['distribution'] ?? [];

                $rowValues = [
                    ['nulo', $blue],
                    ['bajo', $green],
                    ['medio', $yellow],
                    ['alto', $orange],
                    ['muy_alto', $red],
                ];

                foreach ($rowValues as $cellIndex => [$key, $fill]) {
                    $x1 = $tableX + $descW + ($cellW * $cellIndex);
                    $x2 = $x1 + $cellW;

                    imagefilledrectangle($image, $x1, $y1, $x2, $y2, $fill);
                    imagerectangle($image, $x1, $y1, $x2, $y2, $border);

                    $value = (string) ((int) ($dist[$key] ?? 0));
                    $this->drawChartTextCenteredBold($image, 15, $x1, $y1, $x2, $y2, $white, $value);
                }

                $attention = (string) $this->getAttentionCount($dist);
                $attX1 = $tableX + $descW + ($cellW * 5);
                $attX2 = $attX1 + $cellW;

                imagefilledrectangle($image, $attX1, $y1, $attX2, $y2, $redDark);
                imagerectangle($image, $attX1, $y1, $attX2, $y2, $border);

                $this->drawChartTextCenteredBold($image, 15, $attX1, $y1, $attX2, $y2, $white, $attention);
            }

            // ===== Panel inferior =====
            imagefilledrectangle($image, 35, $panelY, $width - 35, $height - 35, $white);
            imagerectangle($image, 35, $panelY, $width - 35, $height - 35, $border);

            $this->drawChartTextBold($image, 22, 50, $panelY + 32, $text, 'Atención (%)');

            $slots = [];
            $baseX = 50;
            $baseY = $panelY + 100;
            $colGap = 730;
            $rowGap = 500;

            for ($r = 0; $r < $rows; $r++) {
                for ($c = 0; $c < $columns; $c++) {
                    $slots[] = [
                        $baseX + ($c * $colGap),
                        $baseY + ($r * $rowGap),
                    ];
                }
            }

            foreach ($dimensions as $i => $dimension) {
                if (! isset($slots[$i])) {
                    break;
                }

                [$slotX, $slotY] = $slots[$i];

                $this->drawDimensionMiniChart(
                    $image,
                    $slotX,
                    $slotY,
                    700,
                    430,
                    (string) $dimension['name'],
                    (float) ($dimension['average_score'] ?? 0),
                    (int) ($dimension['max_score'] ?? 0),
                    (float) ($dimension['average_percentage'] ?? 0),
                    $dimension['distribution'] ?? [],
                    (int) ($dimension['applicable_evaluations'] ?? $totalEvaluations),
                    $blue,
                    $green,
                    $yellow,
                    $orange,
                    $red,
                    $text,
                    $muted,
                    $border
                );
            }

           imagepng($image, $outputPath);
            imagedestroy($image);
            gc_collect_cycles();

            return $outputPath;
        }

    private function drawDimensionMiniChart(
        $image,
        int $x,
        int $y,
        int $w,
        int $h,
        string $title,
        float $averageScore,
        int $maxScore,
        float $averagePercentage,
        array $distribution,
        int $totalEvaluations,
            $blue,
            $green,
            $yellow,
            $orange,
            $red,
            $textColor,
            $mutedColor,
            $borderColor
        ): void {
            $wrapped = explode("\n", wordwrap($title, 38, "\n", true));
            $line1 = $wrapped[0] ?? '';
            $line2 = $wrapped[1] ?? '';
            $line3 = $wrapped[2] ?? '';

            $titleLeft = $x + 6;
            $titleRight = $x + $w + 14;
            $titleTop = $y - 2;
            $lineHeight = 14;

            if ($line1 !== '') {
                $this->drawChartTextCenteredBold(
                    $image,
                    14,
                    $titleLeft,
                    $titleTop,
                    $titleRight,
                    $titleTop + $lineHeight,
                    $textColor,
                    $line1
                );
            }

            if ($line2 !== '') {
                $this->drawChartTextCenteredBold(
                    $image,
                    14,
                    $titleLeft,
                    $titleTop + 14,
                    $titleRight,
                    $titleTop + 28,
                    $textColor,
                    $line2
                );
            }

            if ($line3 !== '') {
                $this->drawChartTextCenteredBold(
                    $image,
                    14,
                    $titleLeft,
                    $titleTop + 28,
                    $titleRight,
                    $titleTop + 42,
                    $textColor,
                    $line3
                );
            }

            $averageText = 'Promedio: ' .
            number_format($averageScore, 2) .
            ' / ' .
            $maxScore .
            ' (' .
            number_format($averagePercentage, 2) .
            '%)';

        $this->drawChartText($image, 12, $x + 12, $y + 64, $mutedColor, $averageText);

        $chartX = $x + 10;
        $chartY = $y + 98;
        $chartW = $w;
        $chartH = max(270, $h - 55);

            imagerectangle($image, $chartX, $chartY, $chartX + $chartW, $chartY + $chartH, $borderColor);

            for ($i = 0; $i <= 5; $i++) {
                $gy = $chartY + $chartH - (int)(($chartH / 5) * $i);
                imageline($image, $chartX, $gy, $chartX + $chartW, $gy, $borderColor);

                $axisLabel = (string) ($i * 20);
                $this->drawChartText($image, 9, $chartX - 24, $gy + 4, $mutedColor, $axisLabel);
            }

            $colors = [$blue, $green, $yellow, $orange, $red];
            $keys = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

            $barW = 92;
            $gap = 24;
            $startX = $chartX + 42;
            $maxH = $chartH - 45;

            foreach ($keys as $i => $key) {
                $pct = $totalEvaluations > 0
                    ? round(((int) ($distribution[$key] ?? 0) / $totalEvaluations) * 100, 2)
                    : 0;

                $barHeight = (int) round(($pct / 100) * $maxH);

                $bx1 = $startX + (($barW + $gap) * $i);
                $bx2 = $bx1 + $barW;
                $by1 = $chartY + $chartH - $barHeight;
                $by2 = $chartY + $chartH;

                imagefilledrectangle($image, $bx1, $by1, $bx2, $by2, $colors[$i]);
                imagerectangle($image, $bx1, $by1, $bx2, $by2, $borderColor);

                $label = number_format($pct, 2);
                $this->drawChartTextBold($image, 9, $bx1 - 2, $by1 - 8, $textColor, $label);
            }
        }

    private function getChartFontPath(): ?string
        {
            $candidates = [
                resource_path('fonts/arial.ttf'),
                resource_path('fonts/Arial.ttf'),
                public_path('fonts/arial.ttf'),
                public_path('fonts/Arial.ttf'),
                'C:\\Windows\\Fonts\\arial.ttf',
                'C:\\Windows\\Fonts\\calibri.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf',
            ];

            foreach ($candidates as $path) {
                if (is_string($path) && file_exists($path)) {
                    return $path;
                }
            }

            return null;
        }

        private function drawChartText($image, int $size, int $x, int $y, $color, string $text): void
        {
            $text = trim($text);

            if ($text === '') {
                return;
            }

            $font = $this->getChartFontPath();

            if ($font && function_exists('imagettftext')) {
                imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
                return;
            }

            $fallback = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
            imagestring($image, 3, $x, max(0, $y - 12), $fallback, $color);
        }

        private function drawChartTextBold($image, int $size, int $x, int $y, $color, string $text): void
            {
                $text = trim($text);

                if ($text === '') {
                    return;
                }

                $font = $this->getChartFontPath();

                if ($font && function_exists('imagettftext')) {
                    imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
                    imagettftext($image, $size, 0, $x + 1, $y, $color, $font, $text);
                    return;
                }

                $fallback = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
                imagestring($image, 5, $x, max(0, $y - 12), $fallback, $color);
                imagestring($image, 5, $x + 1, max(0, $y - 12), $fallback, $color);
            }

            private function drawChartTextCenteredBold($image, int $size, int $x1, int $y1, int $x2, int $y2, $color, string $text): void
            {
                $text = trim($text);

                if ($text === '') {
                    return;
                }

                $font = $this->getChartFontPath();

                if ($font && function_exists('imagettfbbox')) {
                    $box = imagettfbbox($size, 0, $font, $text);
                    $textWidth = abs($box[2] - $box[0]);
                    $textHeight = abs($box[7] - $box[1]);

                    $x = (int) round((($x2 - $x1) - $textWidth) / 2) + $x1;
                    $y = (int) round((($y2 - $y1) + $textHeight) / 2) + $y1 - 2;

                    imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
                    imagettftext($image, $size, 0, $x + 1, $y, $color, $font, $text);
                    return;
                }

                $fallback = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
                $fontIndex = 5;
                $textWidth = imagefontwidth($fontIndex) * strlen($fallback);
                $textHeight = imagefontheight($fontIndex);

                $x = (int) round((($x2 - $x1) - $textWidth) / 2) + $x1;
                $y = (int) round((($y2 - $y1) - $textHeight) / 2) + $y1;

                imagestring($image, $fontIndex, $x, $y, $fallback, $color);
                imagestring($image, $fontIndex, $x + 1, $y, $fallback, $color);
            }

        private function drawChartTextCentered($image, int $size, int $x1, int $y1, int $x2, int $y2, $color, string $text): void
        {
            $text = trim($text);

            if ($text === '') {
                return;
            }

            $font = $this->getChartFontPath();

            if ($font && function_exists('imagettfbbox')) {
                $box = imagettfbbox($size, 0, $font, $text);
                $textWidth = abs($box[2] - $box[0]);
                $textHeight = abs($box[7] - $box[1]);

                $x = (int) round((($x2 - $x1) - $textWidth) / 2) + $x1;
                $y = (int) round((($y2 - $y1) + $textHeight) / 2) + $y1 - 2;

                imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
                return;
            }

            $fallback = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
            $fontIndex = 3;
            $textWidth = imagefontwidth($fontIndex) * strlen($fallback);
            $textHeight = imagefontheight($fontIndex);

            $x = (int) round((($x2 - $x1) - $textWidth) / 2) + $x1;
            $y = (int) round((($y2 - $y1) - $textHeight) / 2) + $y1;

            imagestring($image, $fontIndex, $x, $y, $fallback, $color);
        }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function addConsultantInformationSection(Section $section): void
        {
            $profiles = [
                [
                    'company' => 'Training and Manufacturing Services',
                    'name' => 'Lorena García López',
                    'position' => 'Evaluadora',
                    'email' => 'lorena.garcia@trainingyms.com',
                    'phone' => '(844) 44 88 138',
                    'lines' => [
                        'Secretaria del Trabajo y Previsión Social / STPS 25 años',
                        'Área de Vinculación / Inclusión / Capacitación / Seguridad y Salud',
                        'Registro de Agente Capacitador ante STPS: TMS060511623-0013 CENTRO EVALUADOR',
                    ],
                    'accreditation' => 'Training and Manufacturing Services, S.C.   Cédula de acreditación CE1488-OC006-06',
                    'certifications' => [
                        'EC0891, “Facilitación de la implementación del programa SOLVE: promoción de la salud en el trabajo (Factores de riesgos psicosociales en el trabajo)”',
                        'EC0217.01, “Impartición de Cursos de Formación del Capital Humano de Manera Presencial Grupal”',
                        'EC0581, “Integración y Funcionamiento de las Comisiones Mixtas de Capacitación, Adiestramiento y Productividad”',
                        'EC0076, “Evaluación de la Competencia de Candidatos con Base en Estándares de Competencia”',
                        'EC0301, “Diseño de cursos de formación del capital humano de manera presencial grupal, sus instrumentos de evaluación y manuales del curso”',
                        'EC0779, “Transversalización de la perspectiva de género en la administración pública municipal”',
                        'EC0308, “Capacitación presencial a servidoras y servidores públicos en y desde el enfoque de Igualdad entre mujeres y hombres. Nivel básico”',
                    ],
                                ],
                [
                    'company' => 'Training and Manufacturing Services',
                    'name' => 'Jaime Lozano Castro',
                    'position' => 'Proyectos',
                    'email' => 'jaime_lozano@trainingyms.com',
                    'phone' => '(844) 622 28 97',
                    'lines' => [
                        'Training and Manufacturing Services, S.C. 20 años',
                        'Capacitación / Seguridad y Salud / Evaluador',
                        'Registro de Agente Capacitador ante STPS: TMS060511623-0013 CENTRO EVALUADOR',
                    ],
                    'accreditation' => 'Training and Manufacturing Services, S.C.   Cédula de acreditación CE1488-OC006-06',
                    'certifications' => [
                        'EC0891, “Facilitación de la implementación del programa SOLVE: promoción de la salud en el trabajo (Factores de riesgos psicosociales en el trabajo)”',
                        'EC0217.01, “Impartición de Cursos de Formación del Capital Humano de Manera Presencial Grupal”',
                        'EC0581, “Integración y Funcionamiento de las Comisiones Mixtas de Capacitación, Adiestramiento y Productividad”',
                        'EC0076, “Evaluación de la Competencia de Candidatos con Base en Estándares de Competencia”',
                        'EC0301, “Diseño de cursos de formación del capital humano de manera presencial grupal, sus instrumentos de evaluación y manuales del curso”',
                        'EC0779, “Transversalización de la perspectiva de género en la administración pública municipal”',
                        'EC0308, “Capacitación presencial a servidoras y servidores públicos en y desde el enfoque de Igualdad entre mujeres y hombres. Nivel básico”',
                    ],
                ],
                [
                    'company' => 'Training and Manufacturing Services',
                    'name' => 'Alejandra Ayala Flores',
                    'position' => 'Validación',
                    'email' => 'alejandra.ayala@trainingyms.com',
                    'phone' => '(844) 455 02 68',
                    'lines' => [
                        'Asociación de Industriales y Empresarios de Ramos Arizpe 10 años',
                        'Área de Vinculación / Capacitación / Desarrollo de proveedores',
                        'Registro de Agente Capacitador ante STPS: TMS060511623-0013 CENTRO EVALUADOR',
                    ],
                    'accreditation' => 'Training and Manufacturing Services, S.C.   Cédula de acreditación CE1488-OC006-06',
                    'certifications' => [
                        'EC0217.01, “Impartición de Cursos de Formación del Capital Humano de Manera Presencial Grupal”',
                        'EC0581, “Integración y Funcionamiento de las Comisiones Mixtas de Capacitación, Adiestramiento y Productividad”',
                        'EC0076, “Evaluación de la Competencia de Candidatos con Base en Estándares de Competencia”',
                        'EC0301, “Diseño de cursos de formación del capital humano de manera presencial grupal, sus instrumentos de evaluación y manuales del curso”',
                    ],
                ],
            ];

                                $section->addTitle('XXV. Información del equipo consultor', 1);

                foreach ($profiles as $index => $profile) {
                    if ($index > 0) {
                        $section->addPageBreak();
                        $section->addText(
                            'XXV. Información del equipo consultor',
                            ['bold' => true, 'size' => 14],
                            ['spaceAfter' => 180]
                        );
                }

                $this->renderConsultantProfile($section, $profile);
            }
        }

        private function renderConsultantProfile(Section $section, array $profile): void
            {
                $blue = '2F5597';
                $textDark = '111111';

                $section->addText(
                    $this->safeValue($profile['company']),
                    ['bold' => true, 'size' => 11, 'color' => $textDark],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
                );

                $cardPath = $this->generateConsultantProfileCardImage(
                    $profile,
                    $this->makeUniqueChartPath('consultant_profile_card')
                );

                if ($cardPath && file_exists($cardPath)) {
                    $section->addImage($cardPath, [
                    'width' => 430,
                    'alignment' => Jc::CENTER,
                    'spaceBefore' => 30,
                    'spaceAfter' => 70,
                ]);
                } else {
                    // Fallback sin tabla, por si GD no está disponible en el servidor.
                    foreach ([
                        'Nombre' => $profile['name'],
                        'Puesto' => $profile['position'],
                        'E-mail' => $profile['email'],
                        'Móvil' => $profile['phone'],
                    ] as $label => $value) {
                        $section->addText(
                            $label . ': ' . $this->safeValue($value),
                            ['size' => 8, 'color' => $textDark],
                            ['spaceAfter' => 40]
                        );
                    }
                }

                $section->addTextBreak(2);

                foreach ($profile['lines'] as $line) {
                    $section->addText(
                        $line,
                        ['size' => 8, 'color' => $textDark],
                        ['spaceAfter' => 75]
                    );
                }

                $section->addText(
                    '•   ' . $profile['accreditation'],
                    ['size' => 8, 'color' => $textDark],
                    ['spaceAfter' => 120]
                );

                $section->addTextBreak(1);

                $section->addText(
                    'Certificaciones ante el Consejo Nacional de Normalización y Certificación de Competencias Laborales (CONOCER)',
                    ['size' => 8, 'color' => $textDark],
                    ['spaceAfter' => 55]
                );

                $section->addText(
                    'de la Secretaría de Educación Pública (SEP):',
                    ['size' => 8, 'color' => $textDark],
                    ['spaceAfter' => 110]
                );

                foreach ($profile['certifications'] as $certification) {
                    $section->addText(
                        $certification,
                        ['size' => 8, 'color' => $blue],
                        ['spaceAfter' => 55]
                    );
                }
            }

            private function generateConsultantProfileCardImage(array $profile, string $outputPath): ?string
                {
                    if (! function_exists('imagecreatetruecolor')) {
                        return null;
                    }

                    $chartDir = dirname($outputPath);

                    if (! is_dir($chartDir)) {
                        mkdir($chartDir, 0755, true);
                    }

                    $width = 980;
                    $height = 255;

                    $image = imagecreatetruecolor($width, $height);

                    if (function_exists('imageantialias')) {
                        imageantialias($image, true);
                    }

                    $white = imagecolorallocate($image, 255, 255, 255);
                    $cardBg = $this->allocateColor($image, 'F5F8FE');
                    $border = $this->allocateColor($image, 'C8D7F4');
                    $line = $this->allocateColor($image, 'E3EBF8');
                    $blue = $this->allocateColor($image, '2F5597');
                    $dark = $this->allocateColor($image, '111111');
                    $icon = $this->allocateColor($image, '9AA8BD');

                    imagefill($image, 0, 0, $white);

                    $x1 = 18;
                    $y1 = 18;
                    $x2 = $width - 18;
                    $y2 = $height - 18;
                    $radius = 14;

                    $this->drawRoundedFilledRectangle($image, $x1, $y1, $x2, $y2, $radius, $cardBg);
                    $this->drawRoundedRectangleBorder($image, $x1, $y1, $x2, $y2, $radius, $border);

                    $rows = [
                        ['×', 'Nombre', $profile['name'] ?? ''],
                        ['□', 'Puesto', $profile['position'] ?? ''],
                        ['✉', 'E-mail', $profile['email'] ?? ''],
                        [' ', 'Móvil', $profile['phone'] ?? ''],
                    ];

                    $leftX = 48;
                    $labelX = 88;
                    $valueRightX = 905;
                    $topY = 42;
                    $rowH = 48;

                    foreach ($rows as $index => [$rowIcon, $label, $value]) {
                        $rowTop = $topY + ($index * $rowH);
                        $baseline = $rowTop + 28;

                        if ($index < 3) {
                            imageline(
                                $image,
                                38,
                                $rowTop + $rowH - 2,
                                920,
                                $rowTop + $rowH - 2,
                                $line
                            );
                        }

                        $this->drawChartText(
                            $image,
                            11,
                            $leftX,
                            $baseline,
                            $icon,
                            $rowIcon
                        );

                        $this->drawChartText(
                            $image,
                            12,
                            $labelX,
                            $baseline,
                            $blue,
                            $label
                        );

                        $this->drawImageTextRightBold(
                            $image,
                            12,
                            $valueRightX,
                            $baseline,
                            $dark,
                            $this->safeValue($value)
                        );
                    }

                    imagepng($image, $outputPath);
                    imagedestroy($image);

                    return $outputPath;
                }

                private function drawRoundedFilledRectangle($image, int $x1, int $y1, int $x2, int $y2, int $radius, $color): void
                {
                    imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
                    imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

                    imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
                    imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
                    imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
                    imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
                }

                private function drawRoundedRectangleBorder($image, int $x1, int $y1, int $x2, int $y2, int $radius, $color): void
                {
                    imageline($image, $x1 + $radius, $y1, $x2 - $radius, $y1, $color);
                    imageline($image, $x1 + $radius, $y2, $x2 - $radius, $y2, $color);
                    imageline($image, $x1, $y1 + $radius, $x1, $y2 - $radius, $color);
                    imageline($image, $x2, $y1 + $radius, $x2, $y2 - $radius, $color);

                    imagearc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color);
                    imagearc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color);
                    imagearc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color);
                    imagearc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color);
                }

            private function allocateColor($image, string $hex)
            {
                [$r, $g, $b] = $this->hexToRgb($hex);

                return imagecolorallocate($image, $r, $g, $b);
            }

            private function drawImageTextRightBold($image, int $size, int $rightX, int $y, $color, string $text): void
            {
                $text = trim($text);

                if ($text === '') {
                    return;
                }

                $font = $this->getChartFontPath();

                if ($font && function_exists('imagettfbbox') && function_exists('imagettftext')) {
                    $box = imagettfbbox($size, 0, $font, $text);
                    $textWidth = abs($box[2] - $box[0]);
                    $x = $rightX - $textWidth;

                    imagettftext($image, $size, 0, $x, $y, $color, $font, $text);
                    imagettftext($image, $size, 0, $x + 1, $y, $color, $font, $text);

                    return;
                }

                $fallback = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
                $fontIndex = 5;
                $textWidth = imagefontwidth($fontIndex) * strlen($fallback);
                $x = $rightX - $textWidth;

                imagestring($image, $fontIndex, $x, max(0, $y - 12), $fallback, $color);
                imagestring($image, $fontIndex, $x + 1, max(0, $y - 12), $fallback, $color);
            }

        private function centeredCellStyle(array $style = []): array
            {
                return array_merge([
                    'valign' => 'center',
                ], $style);
            }

            private function centeredTextStyle(array $style = []): array
            {
                return array_merge([
                    'alignment' => Jc::CENTER,
                    'spaceAfter' => 0,
                ], $style);
            }
    
    private function addInfoRow($table, string $label, ?string $value): void
        {
            $table->addRow(520);

            $table->addCell(2600, [
                'bgColor' => 'D9D9D9',
                'valign' => 'center',
            ])->addText(
                $this->safeValue($label),
                ['bold' => true, 'size' => 9, 'color' => '111827'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $table->addCell(6800, [
                'bgColor' => 'F7F7F7',
                'valign' => 'center',
            ])->addText(
                $this->safeValue($value),
                ['size' => 9, 'color' => '111827'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }
    
        private function formatAddress(WorkCenter $workCenter, Organization $organization): string
        {
            $street = $this->firstFilled($workCenter->street_address, $organization->calle_numero);
            $neighborhood = $this->firstFilled($workCenter->neighborhood, $organization->colonia);
            $postalCode = $this->firstFilled($workCenter->postal_code, $organization->codigo_postal);
            $municipality = $this->firstFilled($workCenter->municipality, $organization->municipio);
            $state = $this->firstFilled($workCenter->state, $organization->estado);

            $parts = array_filter([
                $street,
                $neighborhood,
                $postalCode ? 'C.P. ' . $postalCode : null,
                $municipality,
                $state,
            ]);

            return empty($parts) ? 'N/D' : implode(', ', $parts);
        }

    private function firstFilled(...$values): ?string
        {
            foreach ($values as $value) {
                if ($value !== null && trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }

            return null;
        }

    private function safeValue($value): string
        {
            $value = is_null($value) ? '' : trim((string) $value);

            if ($value === '') {
                return 'N/D';
            }

            // limpia caracteres de control que también pueden romper el XML del DOCX
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $value) ?? $value;

            // evita doble codificación y luego escapa para XML
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

    private function formatDate($date): string
    {
        if (empty($date)) {
            return 'N/D';
        }

        try {
            if ($date instanceof Carbon) {
                return $date->format('d/m/Y');
            }

            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable $e) {
            return 'N/D';
        }
    }
}
