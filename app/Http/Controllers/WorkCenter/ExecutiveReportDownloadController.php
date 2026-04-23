<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\WorkCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExecutiveReportDownloadController extends Controller
{
    public function download(
        Request $request,
        string $workCenter,
        string $organization
    ): BinaryFileResponse {
        $organizationModel = Organization::query()->findOrFail($organization);

        $workCenterModel = WorkCenter::query()
            ->where('organization_id', $organization)
            ->where('id', $workCenter)
            ->firstOrFail();

        $phpWord = $this->buildReport($organizationModel, $workCenterModel);

        $outputDir = storage_path('app/tmp/nom035');

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

                $runId = now()->format('Ymd_His_u') . '_' . Str::random(8);

        $fileName = 'Informe_Ejecutivo_NOM035_' .
            Str::slug($organizationModel->name ?? 'empresa', '_') . '_' .
            Str::slug($workCenterModel->name ?? 'centro_trabajo', '_') . '_' .
            $runId .
            '.docx';

        $outputPath = $outputDir . DIRECTORY_SEPARATOR . $fileName;

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);

        return response()
            ->download($outputPath, $fileName)
            ->deleteFileAfterSend(true);
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

        $phpWord->addTitleStyle(
            1,
            ['bold' => true, 'size' => 16, 'color' => '1F2937'],
            ['spaceAfter' => 240]
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

        $footer = $section->addFooter();

        $footerTable = $footer->addTable([
            'alignment' => JcTable::CENTER,
            'borderSize' => 0,
            'cellMargin' => 0,
        ]);

        $footerTable->addRow();

        $footerTable->addCell(3200)->addText(
            $this->safeValue($workCenter->code ?? 'N/D'),
            ['bold' => true, 'size' => 9, 'color' => '6B7280'],
            ['spaceAfter' => 0]
        );

        $footerTable->addCell(3600)->addText(
            'Fecha elaboración: ' . $this->formatDate(now()),
            ['size' => 9, 'color' => '6B7280'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );

        $footerTable->addCell(2600)->addPreserveText(
            'Página {PAGE} de {NUMPAGES}',
            ['size' => 9, 'color' => '6B7280'],
            ['alignment' => Jc::END, 'spaceAfter' => 0]
        );

        $this->addCover($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addGeneralInformationSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addPaperDemographicSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeGlobalRiskSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addWorkplaceViolenceQuantitativeSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeCategorySection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeDimensionSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addWorkerIdentificationByDimensionSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeQuestionGlobalSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeQuestionGenderSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeQuestionPositionSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeQuestionDepartmentSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeQuestionWorkScheduleSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addReferenceThreeQuestionRiskFactorSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addWorkerIdentificationByPositionSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addWorkerIdentificationByDepartmentSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addWorkerIdentificationByWorkScheduleSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addSevereTraumaticEventsSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addWorkplaceViolenceWorkersSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addFinalRiskWorkersSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addDomainQuantitativeAnalysisSection($section, $organization, $workCenter);
        $section->addPageBreak();
        $this->addWorkerIdentificationByCategorySection($section, $organization, $workCenter);
        return $phpWord;
            }

    private function addCover(Section $section, Organization $organization, WorkCenter $workCenter): void
{
    $legalName = $this->firstFilled(
        $workCenter->legal_name,
        $organization->razon_social,
        $organization->name
    );

    $centerName = $this->firstFilled(
        $workCenter->name,
        $organization->name
    );

    $coverTopTable = $section->addTable([
        'alignment' => JcTable::CENTER,
        'borderSize' => 0,
        'cellMargin' => 0,
    ]);

    $coverTopTable->addRow(900);
    $coverTopTable->addCell(9400, ['bgColor' => '062A78'])->addText(
        ' ',
        ['size' => 1],
        ['spaceAfter' => 0]
    );

    $section->addTextBreak(3);

    $section->addText(
        'INFORME EJECUTIVO',
        ['bold' => true, 'size' => 28, 'color' => '062A78'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 500]
    );

    $section->addText(
        'ANÁLISIS',
        ['bold' => true, 'size' => 24, 'color' => '062A78'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 350]
    );

    $section->addText(
        'Factores de Riesgo Psicosocial',
        ['bold' => true, 'size' => 20, 'color' => '062A78'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
    );

    $section->addText(
        'en el Trabajo',
        ['bold' => true, 'size' => 20, 'color' => '062A78'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 450]
    );

    $section->addText(
        'NOM-035-STPS-2018',
        ['bold' => true, 'size' => 22, 'color' => 'C00000'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 500]
    );

    $section->addText(
        mb_strtoupper($this->safeValue($legalName)),
        ['bold' => true, 'size' => 15, 'color' => '1F2937'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 120]
    );

    $section->addText(
        $this->safeValue($centerName),
        ['bold' => false, 'size' => 13, 'color' => '374151'],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 250]
    );

    $metaTable = $section->addTable([
        'alignment' => JcTable::CENTER,
        'borderSize' => 0,
        'cellMargin' => 50,
    ]);

    $metaTable->addRow();
    $metaTable->addCell(2500)->addText(
        'Código: ' . $this->safeValue($workCenter->code),
        ['bold' => true, 'size' => 10, 'color' => '062A78'],
        ['alignment' => Jc::CENTER]
    );
    $metaTable->addCell(4200)->addText(
        'Fecha elaboración: ' . $this->formatDate(now()),
        ['bold' => true, 'size' => 10, 'color' => '062A78'],
        ['alignment' => Jc::CENTER]
    );

    $section->addTextBreak(7);

    $coverBottomTable = $section->addTable([
        'alignment' => JcTable::CENTER,
        'borderSize' => 0,
        'cellMargin' => 0,
    ]);

    $coverBottomTable->addRow(1100);
    $coverBottomTable->addCell(9400, ['bgColor' => '062A78'])->addText(
        ' ',
        ['size' => 1],
        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
    );
}

    private function addGeneralInformationSection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $section->addText(
                'Información General del Centro de Trabajo',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 140]
            );

            $cards = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 40,
            ]);

            $cards->addRow();

            $companyCell = $cards->addCell(3133, ['bgColor' => '062A78']);
            $companyCell->addText('Empresa', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $companyCell->addText($this->safeValue($organization->name), ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $centerCell = $cards->addCell(3133, ['bgColor' => '1F4E78']);
            $centerCell->addText('Centro de trabajo', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $centerCell->addText($this->safeValue($workCenter->name), ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $codeCell = $cards->addCell(3134, ['bgColor' => '374151']);
            $codeCell->addText('Código', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $codeCell->addText($this->safeValue($workCenter->code), ['bold' => true, 'size' => 11, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $section->addTextBreak(1);

            $bandOne = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $bandOne->addRow();
            $bandOne->addCell(9400, ['bgColor' => 'D9D9D9'])->addText(
                'Datos Generales',
                ['bold' => true, 'size' => 11, 'color' => '111111'],
                ['spaceAfter' => 0]
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

            $bandTwo = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $bandTwo->addRow();
            $bandTwo->addCell(9400, ['bgColor' => 'D9D9D9'])->addText(
                'Contacto y Responsable',
                ['bold' => true, 'size' => 11, 'color' => '111111'],
                ['spaceAfter' => 0]
            );

            $section->addTextBreak(1);

            $contactTable = $section->addTable('InfoTable');
            $this->addInfoRow($contactTable, 'Nombre contacto', $this->firstFilled($workCenter->contact_name, $organization->contacto_nombre));
            $this->addInfoRow($contactTable, 'Puesto contacto', $this->firstFilled($workCenter->contact_position, $organization->contacto_puesto));
            $this->addInfoRow($contactTable, 'Email contacto', $this->firstFilled($workCenter->contact_email, $organization->contacto_email));
            $this->addInfoRow($contactTable, 'Teléfono contacto', $this->firstFilled($workCenter->contact_phone, $organization->contacto_movil));
            $this->addInfoRow($contactTable, 'Responsable', $this->firstFilled($workCenter->responsible_name, $organization->responsable_nombre));
            $this->addInfoRow($contactTable, 'Puesto responsable', $this->firstFilled($workCenter->responsible_position, $organization->responsable_puesto));
            $this->addInfoRow($contactTable, 'Email responsable', $this->firstFilled($workCenter->responsible_email, $organization->responsable_email));
            $this->addInfoRow($contactTable, 'Teléfono responsable', $this->firstFilled($workCenter->responsible_phone, $organization->responsable_movil));
        }

        private function addPaperDemographicSection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $summary = $this->getParticipantSummary($organization->id, $workCenter->id);

            $section->addText(
                'Perfil Demográfico de Participantes',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 140]
            );

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

            $band = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);
            $band->addRow();
            $band->addCell(9400, ['bgColor' => 'D9D9D9'])->addText(
                'Resumen General',
                ['bold' => true, 'size' => 11, 'color' => '111111'],
                ['spaceAfter' => 0]
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

            $this->addDistributionTable($section, 'Sexo', $summary['gender']);
            $this->addDistributionTable($section, 'Edad', $summary['age']);
            $this->addDistributionTable($section, 'Estado civil', $summary['marital_status']);
            $this->addDistributionTable($section, 'Nivel de estudios', $summary['education_level']);
            $this->addDistributionTable($section, 'Puesto', $summary['position']);
            $this->addDistributionTable($section, 'Departamento / Área', $summary['department']);
            $this->addDistributionTable($section, 'Tipo de puesto', $summary['position_type']);
            $this->addDistributionTable($section, 'Tipo de contrato', $summary['contract_type']);
            $this->addDistributionTable($section, 'Tipo de personal', $summary['personnel_type']);
            $this->addDistributionTable($section, 'Jornada laboral', $summary['work_schedule']);
            $this->addDistributionTable($section, 'Rotación de turno', $summary['shift_rotation']);
            $this->addDistributionTable($section, 'Antigüedad en el puesto actual', $summary['time_in_current_position']);
            $this->addDistributionTable($section, 'Experiencia laboral', $summary['work_experience']);
        }

    private function addReferenceThreeGlobalRiskSection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeGlobalSummary($organization->id, $workCenter->id);

            $section->addText(
                '1. Análisis Cuantitativo de los Factores de Riesgo Psicosocial.',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 120]
            );

            $section->addText(
                'Referencia: Calificación Total',
                ['bold' => true, 'size' => 12],
                ['spaceAfter' => 180]
            );

            if (($summary['total_evaluations'] ?? 0) === 0) {
                $section->addText(
                    'No hay evaluaciones de Referencia III disponibles para este centro de trabajo.',
                    ['size' => 10, 'color' => '374151']
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
            $c1->addText('Evaluaciones', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $c1->addText((string) $summary['total_evaluations'], ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $c2 = $cards->addCell(2350, ['bgColor' => '1F4E78']);
            $c2->addText('Promedio', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $c2->addText($summary['average_global_score'] . ' / ' . $summary['max_global_score'], ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $c3 = $cards->addCell(2350, ['bgColor' => '374151']);
            $c3->addText('% Promedio', ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $c3->addText($summary['average_global_percentage'] . '%', ['bold' => true, 'size' => 15, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $levelStyle = $this->getWordRiskCellStyle($summary['dominant_level_key'] ?? 'nulo');
            $c4 = $cards->addCell(2350, ['bgColor' => $levelStyle['bg']]);
            $c4->addText('Nivel predominante', ['bold' => true, 'size' => 9, 'color' => $levelStyle['text']], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
            $c4->addText($summary['dominant_level_label'], ['bold' => true, 'size' => 13, 'color' => $levelStyle['text']], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

            $section->addTextBreak(1);

            $this->addRiskLevelDistributionTable(
                $section,
                'Nivel de riesgo',
                $summary['distribution'],
                (int) $summary['total_evaluations']
            );

            $section->addTextBreak(1);

            $globalChartPath = $this->generateRiskDistributionChart(
                'Distribución Global de Niveles de Riesgo',
                $summary['distribution'],
                $this->makeUniqueChartPath('global')
            );

            $this->addChartImageIfExists($section, $globalChartPath, 560);

            $section->addTextBreak(1);
            $section->addText(
                '*Referencia: NORMA Oficial Mexicana NOM-035-STPS-2018. Guía de Referencia III.3 inciso a) Tabla 5. Pág. 39. inciso c) Pág. 40',
                ['size' => 9, 'color' => '374151'],
                ['alignment' => Jc::CENTER]
            );
        }

    private function addWorkplaceViolenceQuantitativeSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $summary = $this->getWorkplaceViolenceQuantitativeSummary($organization->id, $workCenter->id);

            $section->addText(
                '2. Análisis Cuantitativo de Actos de Violencia Laboral',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

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
                $section->addText(
                    'No se encontraron evaluaciones con datos de violencia laboral.',
                    ['size' => 10, 'color' => '374151']
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

            $section->addTextBreak(1);
            $section->addText(
                '*Referencia: NORMA Oficial Mexicana NOM-035-STPS-2018. Índice de contenido 7.2. Inciso g). Página 6',
                ['size' => 9, 'color' => '374151'],
                ['alignment' => Jc::CENTER]
            );
        }

    private function addReferenceThreeCategorySection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeCategorySummary($organization->id, $workCenter->id);

            $section->addText(
                '3. Evaluación del Entorno Organizacional.',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 140]
            );

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

            if (($summary['total_evaluations'] ?? 0) === 0) {
                $section->addText(
                    'No hay evaluaciones de Referencia III disponibles para este centro de trabajo.',
                    ['size' => 10, 'color' => '374151']
                );
                return;
            }

            $chartPath = $this->generateCategoryDashboardChart(
                $summary['categories'],
                (int) $summary['total_evaluations'],
                $this->makeUniqueChartPath('category_dashboard')
            );

            $this->addChartImageIfExists($section, $chartPath, 500);

            $section->addTextBreak(1);
            $section->addText(
                '*Referencia: NORMA Oficial Mexicana NOM-035-STPS-2018. Índice de contenido 4.6. Página 3 NORMA Oficial Mexicana NOM-035-STPS-2018. Índice de contenido 7.3. Página 6',
                ['size' => 9, 'color' => '374151'],
                ['alignment' => Jc::CENTER]
            );
        }

    private function addReferenceThreeDomainSection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeDomainSummary($organization->id, $workCenter->id);

            $section->addText(
                '5. Análisis por Dominio',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

            $section->addText(
                'Distribución consolidada por dominio con conteos por nivel y gráficas de atención.',
                ['size' => 10, 'color' => '4B5563'],
                ['spaceAfter' => 220]
            );

            if (($summary['total_evaluations'] ?? 0) === 0) {
                $section->addText(
                    'No hay evaluaciones de Referencia III disponibles para este centro de trabajo.',
                    ['size' => 10, 'color' => '374151']
                );
                return;
            }

            $chartPath = $this->generateDomainDashboardChart(
                $summary['domains'],
                (int) $summary['total_evaluations'],
                $this->makeUniqueChartPath('domain_dashboard')
            );

            $this->addChartImageIfExists($section, $chartPath, 500);
        }

   private function addReferenceThreeDimensionSection(Section $section, Organization $organization, WorkCenter $workCenter): void
        {
            $summary = $this->getReferenceThreeDimensionSummary($organization->id, $workCenter->id);

            $section->addText(
                '4. Análisis Cuantitativo de los Factores de Riesgo Psicosocial, Referencia: Dimensión. Tabla 6',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

            $section->addText(
                'Distribución consolidada por dimensión con conteos por nivel y gráficas de atención.',
                ['size' => 10, 'color' => '4B5563'],
                ['spaceAfter' => 220]
            );

            if (($summary['total_evaluations'] ?? 0) === 0) {
                $section->addText(
                    'No hay evaluaciones de Referencia III disponibles para este centro de trabajo.',
                    ['size' => 10, 'color' => '374151']
                );
                return;
            }

            $dimensionChunks = collect($summary['dimensions'])->chunk(9)->values();

            foreach ($dimensionChunks as $index => $chunk) {
                if ($index > 0) {
                    $section->addPageBreak();
                    $section->addText(
                        '4. Análisis Cuantitativo de los Factores de Riesgo Psicosocial, Referencia: Dimensión. Tabla 6 (continuación)',
                        ['bold' => true, 'size' => 14],
                        ['spaceAfter' => 180]
                    );
                }

                $chartPath = $this->generateDimensionDashboardChart(
                    $chunk->values()->all(),
                    (int) $summary['total_evaluations'],
                    $this->makeUniqueChartPath('dimension_dashboard_' . ($index + 1)),
                    $index + 1,
                    $dimensionChunks->count()
                );

                $this->addChartImageIfExists($section, $chartPath, 560);
            }
        }

    private function addReferenceThreeQuestionGlobalSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $summary = $this->getQuestionAverageMatrixSummary($organization->id, $workCenter->id);

            $section->addText(
            '5. Análisis Cuantitativo de los Factores de Riesgo Psicosocial, Referencia: Calificación Final. Tabla 6',
            ['bold' => true, 'size' => 14],
            ['spaceAfter' => 140]
        );

        $section->addText(
            'a) Promedio General por Pregunta',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 180]
        );

            if (($summary['participants'] ?? 0) === 0) {
                $section->addText(
                    'No hay evaluaciones de Referencia III disponibles para este centro de trabajo.',
                    ['size' => 10, 'color' => '374151']
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

            $printed = false;

            if (($femaleSummary['participants'] ?? 0) > 0) {
                $section->addText(
                    'b) Por Género',
                    ['bold' => true, 'size' => 12],
                    ['spaceAfter' => 120]
                );

                $this->addQuestionAverageGenderBand($section, 'Femenino');
                $this->renderQuestionAverageMatrixTable($section, $femaleSummary);
                $printed = true;
            }

            if (($maleSummary['participants'] ?? 0) > 0) {
                if ($printed) {
                    $section->addPageBreak();
                }

                $section->addText(
                    'b) Por Género',
                    ['bold' => true, 'size' => 12],
                    ['spaceAfter' => 120]
                );

                $this->addQuestionAverageGenderBand($section, 'Masculino');
                $this->renderQuestionAverageMatrixTable($section, $maleSummary);
                $printed = true;
            }

            if (! $printed) {
                $section->addText(
                    'b) Por Género',
                    ['bold' => true, 'size' => 12],
                    ['spaceAfter' => 120]
                );

                $section->addText(
                    'No hay información de género disponible para generar las tablas de Femenino y Masculino.',
                    ['size' => 10, 'color' => '374151']
                );
            }
        }

        private function addReferenceThreeQuestionPositionSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $positions = $this->getQuestionAveragePositionLabels($organization->id, $workCenter->id);

            $printed = false;

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
                    continue;
                }

                if ($printed) {
                    $section->addPageBreak();
                }

                $section->addText(
            'c) Por Naturaleza de funciones. I. De los Puestos',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 120]
        );

                $this->addQuestionAverageGenderBand($section, $positionLabel);
                $this->renderQuestionAverageMatrixTable($section, $summary);

                $printed = true;
            }

            if (! $printed) {
                $section->addText(
            'c) Por Naturaleza de funciones. I. De los Puestos',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 120]
        );

                $section->addText(
                    'No hay información de puestos disponible para generar esta sección.',
                    ['size' => 10, 'color' => '374151']
                );
            }
        }

        private function addReferenceThreeQuestionDepartmentSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $departments = $this->getQuestionAverageDepartmentLabels($organization->id, $workCenter->id);

            $printed = false;

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

                if ($printed) {
                    $section->addPageBreak();
                }

                $section->addText(
            'c) Por Naturaleza de funciones. II. De las Áreas',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 120]
        );

                $this->addQuestionAverageGenderBand($section, $departmentLabel);
                $this->renderQuestionAverageMatrixTable($section, $summary);

                $printed = true;
            }

            if (! $printed) {
                $section->addText(
            'c) Por Naturaleza de funciones. II. De las Áreas',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 120]
        );

                $section->addText(
                    'No hay información de áreas disponible para generar esta sección.',
                    ['size' => 10, 'color' => '374151']
                );
            }
        }

        private function addReferenceThreeQuestionWorkScheduleSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $schedules = $this->getQuestionAverageWorkScheduleLabels($organization->id, $workCenter->id);

            $printed = false;

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

                if ($printed) {
                    $section->addPageBreak();
                }

                $section->addText(
            'c) Por Naturaleza de funciones. III. De la Jornada Laboral',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 120]
        );

                $this->addQuestionAverageGenderBand($section, $scheduleLabel);
                $this->renderQuestionAverageMatrixTable($section, $summary);

                $printed = true;
            }

            if (! $printed) {

                $section->addText(
                    'c) Por Naturaleza de funciones. III. De la Jornada Laboral',
                    ['bold' => true, 'size' => 12],
                    ['spaceAfter' => 120]
                );

                $section->addText(
                    'No hay información de jornada laboral disponible para generar esta sección.',
                    ['size' => 10, 'color' => '374151']
                );
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

            foreach ($levels as $index => $level) {
                if ($index > 0) {
                    $section->addPageBreak();
                }

                $summary = $this->getQuestionAverageMatrixSummaryByGlobalLevel(
                    $organization->id,
                    $workCenter->id,
                    $level['key']
                );

                $section->addText(
            'd) Por Factor de Riesgo',
            ['bold' => true, 'size' => 12],
            ['spaceAfter' => 120]
        );

                $this->addQuestionAverageGenderBand(
                    $section,
                    $level['roman'] . ' ' . $level['label']
                );

                $this->renderQuestionAverageMatrixTable($section, $summary);
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
                'borderColor' => '444444',
                'cellMargin' => 20,
            ]);

            $table->addRow(560, ['cantSplit' => true]);
            $table->addCell(2700, ['gridSpan' => 2, 'bgColor' => 'D9D9D9'])->addText(
                'Categorías',
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
            $table->addCell(3000, ['gridSpan' => 2, 'bgColor' => 'D9D9D9'])->addText(
                'Dominios',
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
            $table->addCell(5200, ['gridSpan' => 2, 'bgColor' => 'D9D9D9'])->addText(
                'Factores de Riesgo Psicosocial',
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
            $table->addCell(5600, ['bgColor' => 'D9D9D9'])->addText(
                'Preguntas (items)',
                ['bold' => true, 'size' => 10],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
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

                        $table->addRow(420, ['cantSplit' => true]);

                        if (! $categoryStarted) {
                            $categoryRows = collect($category['domains'])->sum(fn ($domainItem) => count($domainItem['dimensions']));

                            $table->addCell(2000, ['vMerge' => 'restart'])->addText(
                                $category['name'],
                                ['bold' => true, 'size' => 9],
                                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                            );
                            $table->addCell(700, ['vMerge' => 'restart', 'bgColor' => $categoryStyle['bg']])->addText(
                                (string) $category['score'],
                                ['bold' => true, 'size' => 9, 'color' => $categoryStyle['text']],
                                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                            );

                            $categoryStarted = true;
                        } else {
                            $table->addCell(2000, ['vMerge' => 'continue'])->addText('', ['size' => 1], ['spaceAfter' => 0]);
                            $table->addCell(700, ['vMerge' => 'continue'])->addText('', ['size' => 1], ['spaceAfter' => 0]);
                        }

                        if (! $domainStarted) {
                            $table->addCell(2300, ['vMerge' => 'restart'])->addText(
                                $domain['name'],
                                ['bold' => true, 'size' => 9],
                                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                            );
                            $table->addCell(700, ['vMerge' => 'restart', 'bgColor' => $domainStyle['bg']])->addText(
                                (string) $domain['score'],
                                ['bold' => true, 'size' => 9, 'color' => $domainStyle['text']],
                                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                            );

                            $domainStarted = true;
                        } else {
                            $table->addCell(2300, ['vMerge' => 'continue'])->addText('', ['size' => 1], ['spaceAfter' => 0]);
                            $table->addCell(700, ['vMerge' => 'continue'])->addText('', ['size' => 1], ['spaceAfter' => 0]);
                        }

                        $table->addCell(4500)->addText(
                            $dimension['name'],
                            ['size' => 9],
                            ['spaceAfter' => 0]
                        );

                        $table->addCell(700, ['bgColor' => $dimensionStyle['bg']])->addText(
                            (string) $dimension['score'],
                            ['bold' => true, 'size' => 9, 'color' => $dimensionStyle['text']],
                            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                        );

                        $itemsCell = $table->addCell(5600);
                        $this->addQuestionAverageItemsToCell($itemsCell, $dimension['items'], $dimension['note'] ?? null);
                    }
                }
            }

            $footer = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '444444',
                'cellMargin' => 20,
            ]);
            $footer->addRow(420, ['cantSplit' => true]);

            $globalLevel = $this->classifyNom035Score('global', null, (int) $summary['final_total']);
            $globalStyle = $this->getWordRiskCellStyle($globalLevel['key']);

            $footerCell = $footer->addCell(4200, ['bgColor' => $globalStyle['bg']]);
            $footerRun = $footerCell->addTextRun(['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $footerRun->addText(
                'Calificación Total Final',
                ['bold' => true, 'size' => 10, 'color' => $globalStyle['text']]
            );
            $footerRun->addTextBreak();
            $footerRun->addText(
                $summary['final_total'] . ' / 288 - ' . number_format($summary['final_percentage'], 2) . ' %',
                ['bold' => true, 'size' => 10, 'color' => $globalStyle['text']]
            );

            $footer->addCell(4200, ['bgColor' => 'D9D9D9'])->addText(
                $summary['participants'] . ' Participantes',
                ['bold' => true, 'size' => 10, 'color' => '111111'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

    private function addWorkerIdentificationByDimensionSection(
            Section $section,
            Organization $organization,
            WorkCenter $workCenter
        ): void {
            $groups = $this->getWorkerIdentificationByDimensionSummary($organization->id, $workCenter->id);

            $section->addText(
                '4.1 Identificación de los Trabajadores. Factores de Riesgo Psicosocial, Referencia: Dimensión. Tabla 6',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

            if (empty($groups)) {
                $section->addText(
                    'No se encontraron trabajadores con nivel Medio, Alto o Muy Alto por dimensión para el centro de trabajo seleccionado.',
                    ['size' => 10, 'color' => '374151']
                );
                return;
            }

            $this->addWorkerIdentificationRiskLegend($section);

            foreach ($groups as $groupIndex => $group) {
                if ($groupIndex > 0) {
                    $section->addTextBreak(1);
                }

                $section->addText(
                    $group['number'] . ' ' . $group['name'],
                    ['bold' => true, 'size' => 11],
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
                $this->addWorkerHeaderCell($table, 900, 'Folio');
                $this->addWorkerHeaderCell($table, 900, 'Calif.');
                $this->addWorkerHeaderCell($table, 5200, 'Nombre');
                $this->addWorkerHeaderCell($table, 2100, 'Area');
                $this->addWorkerHeaderCell($table, 2100, 'Puesto');
                $this->addWorkerHeaderCell($table, 850, 'Jefe');
                $this->addWorkerHeaderCell($table, 850, 'Atiende');

                foreach ($group['rows'] as $row) {
                $table->addRow();

                $dimensionStyle = $this->getWordRiskCellStyle($row['dimension_level_key'] ?? 'nulo');
                $globalStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                $table->addCell(900, ['bgColor' => $dimensionStyle['bg']])->addText(
                    (string) ($row['dimension_score'] ?? 0),
                    [
                        'bold' => true,
                        'size' => 10,
                        'color' => $dimensionStyle['text'],
                    ],
                    [
                        'alignment' => Jc::CENTER,
                        'spaceAfter' => 0,
                    ]
                );

                $table->addCell(900)->addText(
                    $this->safeValue($row['folio']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(900, ['bgColor' => $globalStyle['bg']])->addText(
                    (string) ($row['global_score'] ?? 0),
                    [
                        'bold' => true,
                        'size' => 10,
                        'color' => $globalStyle['text'],
                    ],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(5200)->addText(
                    $this->safeValue($row['name']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(2100)->addText(
                    $this->safeValue($row['area']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(2100)->addText(
                    $this->safeValue($row['position']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['is_boss'] ?? false)),
                    ['bold' => true, 'size' => 12],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['attends_public'] ?? false)),
                    ['bold' => true, 'size' => 12],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

                // fila total
                $table->addRow();

                $table->addCell(1200, ['gridSpan' => 1, 'bgColor' => 'D9D9D9'])->addText(
                    'T o t a l',
                    ['bold' => true, 'size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(7200, ['gridSpan' => 4, 'bgColor' => 'D9D9D9'])->addText(
                    $group['number'] . ' ' . $group['name'],
                    ['bold' => true, 'size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(950, ['bgColor' => 'FF1A1A'])->addText(
                    (string) $group['totals']['muy_alto'],
                    ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(950, ['bgColor' => 'F28C00'])->addText(
                    (string) $group['totals']['alto'],
                    ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(950, ['bgColor' => 'F6E600'])->addText(
                    (string) $group['totals']['medio'],
                    ['bold' => true, 'size' => 10, 'color' => '111111'],
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

            $section->addText(
                '6. Identificación de los Trabajadores con Factores de Riesgo Psicosocial',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 120]
            );

            $section->addText(
                'a) por la Naturaleza de sus Funciones I. De los Puestos',
                ['bold' => true, 'size' => 12],
                ['spaceAfter' => 180]
            );

            if (empty($groups)) {
                $section->addText(
                    'No se encontraron trabajadores para generar la identificación por puestos.',
                    ['size' => 10, 'color' => '374151']
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
                $this->addWorkerHeaderCell($table, 2200, 'Area');
                $this->addWorkerHeaderCell($table, 1700, 'Jornada');
                $this->addWorkerHeaderCell($table, 850, 'Jefe');
                $this->addWorkerHeaderCell($table, 850, 'Atiende');

                foreach ($group['rows'] as $row) {
                    $table->addRow();

                    $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                    $table->addCell(900)->addText(
                        $this->safeValue($row['folio']),
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
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(2200)->addText(
                        $this->safeValue($row['area']),
                        ['size' => 10],
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(1700)->addText(
                        $this->safeValue($row['work_schedule']),
                        ['size' => 10],
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['is_boss'] ?? false)),
                    ['bold' => true, 'size' => 12],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['attends_public'] ?? false)),
                    ['bold' => true, 'size' => 12],
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

            $section->addText(
                '6. Identificación de los Trabajadores con Factores de Riesgo Psicosocial',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 120]
            );

            $section->addText(
                'a) por la Naturaleza de sus Funciones II. De las Áreas',
                ['bold' => true, 'size' => 12],
                ['spaceAfter' => 180]
            );

            if (empty($groups)) {
                $section->addText(
                    'No se encontraron trabajadores para generar la identificación por áreas.',
                    ['size' => 10, 'color' => '374151']
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
                $this->addWorkerHeaderCell($table, 2200, 'Puesto');
                $this->addWorkerHeaderCell($table, 1700, 'Jornada');
                $this->addWorkerHeaderCell($table, 850, 'Jefe');
                $this->addWorkerHeaderCell($table, 850, 'Atiende');

                foreach ($group['rows'] as $row) {
                    $table->addRow();

                    $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                    $table->addCell(900)->addText(
                        $this->safeValue($row['folio']),
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
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(2200)->addText(
                        $this->safeValue($row['position']),
                        ['size' => 10],
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(1700)->addText(
                        $this->safeValue($row['work_schedule']),
                        ['size' => 10],
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['is_boss'] ?? false)),
                    ['bold' => true, 'size' => 12],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['attends_public'] ?? false)),
                    ['bold' => true, 'size' => 12],
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

            $section->addText(
                '6. Identificación de los Trabajadores con Factores de Riesgo Psicosocial',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 120]
            );

            $section->addText(
                'a) por la Naturaleza de sus Funciones III. De la Jornada Laboral',
                ['bold' => true, 'size' => 12],
                ['spaceAfter' => 180]
            );

            if (empty($groups)) {
                $section->addText(
                    'No se encontraron trabajadores para generar la identificación por jornada laboral.',
                    ['size' => 10, 'color' => '374151']
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
                $this->addWorkerHeaderCell($table, 2200, 'Area');
                $this->addWorkerHeaderCell($table, 2200, 'Puesto');
                $this->addWorkerHeaderCell($table, 850, 'Jefe');
                $this->addWorkerHeaderCell($table, 850, 'Atiende');

                foreach ($group['rows'] as $row) {
                    $table->addRow();

                    $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                    $table->addCell(900)->addText(
                        $this->safeValue($row['folio']),
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
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(2200)->addText(
                        $this->safeValue($row['area']),
                        ['size' => 10],
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(2200)->addText(
                        $this->safeValue($row['position']),
                        ['size' => 10],
                        ['spaceAfter' => 0]
                    );

                    $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['is_boss'] ?? false)),
                    ['bold' => true, 'size' => 12],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(850)->addText(
                    $this->workerFlagMark((bool) ($row['attends_public'] ?? false)),
                    ['bold' => true, 'size' => 12],
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

            $section->addText(
                "7. Identificación de los Trabajadores que fueron sujetos a\nAcontecimientos Traumáticos Severos durante o con motivo del trabajo",
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

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
                    ['spaceAfter' => 80]
                );
            }

            $section->addTextBreak(1);

            $section->addText(
                'Trabajadores que Fueron Sujetos a Acontecimientos Traumáticos Severos',
                ['bold' => true, 'size' => 12],
                ['spaceAfter' => 120]
            );

            if (empty($summary['rows'])) {
                $section->addText(
                    'No se encontraron trabajadores con registro de acontecimientos traumáticos severos.',
                    ['size' => 10, 'color' => '374151']
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

                $rowBg = ! empty($row['requires_valuation']) ? 'D9D9D9' : null;

                $table->addCell(700, $rowBg ? ['bgColor' => $rowBg] : [])->addText(
                    $this->safeValue($row['folio']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(4000, $rowBg ? ['bgColor' => $rowBg] : [])->addText(
                    $this->safeValue($row['name']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(1300, $rowBg ? ['bgColor' => $rowBg] : [])->addText(
                    $this->safeValue($row['gender']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(1800, $rowBg ? ['bgColor' => $rowBg] : [])->addText(
                    $this->safeValue($row['position']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                foreach (['s1', 's2', 's3', 's4'] as $key) {
                    $table->addCell(500, $rowBg ? ['bgColor' => $rowBg] : [])->addText(
                        (string) ($row[$key] ?? 0),
                        ['size' => 10],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }

                $table->addCell(1000, $rowBg ? ['bgColor' => $rowBg] : [])->addText(
                    ! empty($row['requires_valuation']) ? 'Sí' : 'No',
                    ['bold' => ! empty($row['requires_valuation']), 'size' => 10],
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
            $rows = $this->getWorkplaceViolenceWorkersSummary($organization->id, $workCenter->id);

            $section->addText(
                '8. Identificación de los Trabajadores Sujetos a Actos de Violencia Laboral',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

            if (empty($rows)) {
                $section->addText(
                    'No se encontraron trabajadores con respuestas asociadas a violencia laboral.',
                    ['size' => 10, 'color' => '374151']
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

                $pointsLevel = $this->classifyNom035Score('dimensions', 'Violencia laboral', (int) $row['points']);
                $pointsStyle = $this->getWordRiskCellStyle($pointsLevel['key']);

                $table->addCell(650)->addText(
                    $this->safeValue($row['folio']),
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
            $rows = $this->getFinalRiskWorkersSummary($organization->id, $workCenter->id);

            $section->addText(
                '9. Identificación de los Trabajadores con Factores de Riesgo Psicosocial. Referencia: Calificación Final.',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

            if (empty($rows)) {
                $section->addText(
                    'No se encontraron trabajadores con nivel Medio, Alto o Muy Alto en la calificación final.',
                    ['size' => 10, 'color' => '374151']
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
            $this->addWorkerHeaderCell($table, 2200, 'Area');
            $this->addWorkerHeaderCell($table, 1700, 'Puesto');
            $this->addWorkerHeaderCell($table, 850, 'Jefe');
            $this->addWorkerHeaderCell($table, 850, 'Atiende');

            foreach ($rows as $row) {
                $table->addRow();

                $riskStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                $table->addCell(900)->addText(
                    $this->safeValue($row['folio']),
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
                    ['spaceAfter' => 0]
                );

                $table->addCell(2200)->addText(
                    $this->safeValue($row['area']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(1700)->addText(
                    $this->safeValue($row['position']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                $table->addCell(850)->addText(
                $this->workerFlagMark((bool) ($row['is_boss'] ?? false)),
                ['bold' => true, 'size' => 12],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            $table->addCell(850)->addText(
                $this->workerFlagMark((bool) ($row['attends_public'] ?? false)),
                ['bold' => true, 'size' => 12],
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

            $section->addText(
                '10. Análisis Cuantitativo de los Dominios, Referencia: Calificación Final. Tabla 6',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 180]
            );

            $section->addText(
                'Promedio por Pregunta (Global)',
                ['bold' => true, 'size' => 12],
                ['spaceAfter' => 180]
            );

            if (empty($groups)) {
                $section->addText(
                    'No se encontraron datos para generar el análisis cuantitativo de dominios.',
                    ['size' => 10, 'color' => '374151']
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
            $rows = $this->getWorkerIdentificationByCategorySummary($organization->id, $workCenter->id);

            $section->addText(
                '11. Identificación de los Trabajadores con Factores de Riesgo Psicosocial. Referencia: Categoria',
                ['bold' => true, 'size' => 14],
                ['spaceAfter' => 140]
            );

            $section->addText(
                '1. Ambiente de trabajo   2. Factores propios de la actividad   3. Organización del tiempo de trabajo   4. Liderazgo y relaciones en el trabajo   5. Entorno organizacional',
                ['size' => 10],
                ['spaceAfter' => 140]
            );

            if (empty($rows)) {
                $section->addText(
                    'No se encontraron trabajadores con nivel Medio, Alto o Muy Alto para la identificación por categoría.',
                    ['size' => 10, 'color' => '374151']
                );
                return;
            }

            $this->addWorkerIdentificationRiskLegend($section);

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 45,
            ]);

            $table->addRow();
            $this->addWorkerHeaderCell($table, 800, 'Folio');
            $this->addWorkerHeaderCell($table, 900, 'Calif.');
            $this->addWorkerHeaderCell($table, 4700, 'Nombre');
            $this->addWorkerHeaderCell($table, 1200, 'Categoría 1');
            $this->addWorkerHeaderCell($table, 1200, 'Categoría 2');
            $this->addWorkerHeaderCell($table, 1200, 'Categoría 3');
            $this->addWorkerHeaderCell($table, 1200, 'Categoría 4');
            $this->addWorkerHeaderCell($table, 1200, 'Categoría 5');

            foreach ($rows as $row) {
                $table->addRow();

                $globalStyle = $this->getWordRiskCellStyle($row['global_level_key'] ?? 'nulo');

                $table->addCell(800)->addText(
                    $this->safeValue($row['folio']),
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(900, ['bgColor' => $globalStyle['bg']])->addText(
                    (string) ($row['global_score'] ?? 0),
                    ['bold' => true, 'size' => 10, 'color' => $globalStyle['text']],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );

                $table->addCell(4700)->addText(
                    $this->safeValue($row['name']),
                    ['size' => 10],
                    ['spaceAfter' => 0]
                );

                foreach ($row['categories'] as $category) {
                    $categoryStyle = $this->getWordRiskCellStyle($category['level_key'] ?? 'nulo');

                    $table->addCell(1200, ['bgColor' => $categoryStyle['bg']])->addText(
                        (string) ($category['score'] ?? 0),
                        ['bold' => true, 'size' => 10, 'color' => $categoryStyle['text']],
                        ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                    );
                }
            }
        }

        private function addWorkerIdentificationRiskLegend(Section $section): void
        {
            $section->addText(
                'Nivel de riesgo',
                ['size' => 11],
                ['spaceAfter' => 60]
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
                $legend->addCell(1700, ['bgColor' => $bg])->addText(
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

                            $dimensionScore += $avgRaw;
                        }

                        $dimensionDisplayScore = (int) round($dimensionScore, 0, PHP_ROUND_HALF_UP);

                        $dimensions[] = [
                            'name' => $dimension['name'],
                            'items' => $items,
                            'score' => $dimensionDisplayScore,
                            'note' => ! empty($dimension['note_key'])
                                ? ('*' . $dimension['note_key'] . ' / ' . ($noteCounts[$dimension['note_key']] ?? 0))
                                : null,
                        ];

                        $domainScore += $dimensionScore;
                    }

                    $domainDisplayScore = (int) round($domainScore, 0, PHP_ROUND_HALF_UP);

                    $domains[] = [
                        'name' => $domain['name'],
                        'score' => $domainDisplayScore,
                        'dimensions' => $dimensions,
                    ];

                    $categoryScore += $domainScore;
                }

                $categoryDisplayScore = (int) round($categoryScore, 0, PHP_ROUND_HALF_UP);

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

                            $dimensionScore += $avgRaw;
                        }

                        $dimensionDisplayScore = (int) round($dimensionScore, 0, PHP_ROUND_HALF_UP);

                        $dimensions[] = [
                            'name' => $dimension['name'],
                            'items' => $items,
                            'score' => $dimensionDisplayScore,
                            'note' => ! empty($dimension['note_key'])
                                ? ('*' . $dimension['note_key'] . ' / ' . ($noteCounts[$dimension['note_key']] ?? 0))
                                : null,
                        ];

                        $domainScore += $dimensionScore;
                    }

                    $domainDisplayScore = (int) round($domainScore, 0, PHP_ROUND_HALF_UP);

                    $domains[] = [
                        'name' => $domain['name'],
                        'score' => $domainDisplayScore,
                        'dimensions' => $dimensions,
                    ];

                    $categoryScore += $domainScore;
                }

                $categoryDisplayScore = (int) round($categoryScore, 0, PHP_ROUND_HALF_UP);

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

        private function getRiskHexByLevel(string $levelKey): string
        {
            return match ($levelKey) {
                'muy_alto' => 'EF4444',
                'alto' => 'F59E0B',
                'medio' => 'F8FF03',
                'bajo' => '16A34A',
                default => '3B82F6',
            };
        }

        private function addWorkerHeaderCell($table, int $width, string $label): void
        {
            $table->addCell($width, ['bgColor' => 'FFFFFF'])->addText(
                $label,
                ['size' => 10, 'color' => '111111'],
                ['spaceAfter' => 0]
            );
        }

        private function workerFlagMark(bool $value): string
        {
            return $value ? '✓' : '';
        }

    private function addQuestionGlobalHeaderCell($table, int $width, string $label, string $bgColor, string $textColor): void
        {
            $table->addCell($width, ['bgColor' => $bgColor])->addText(
                $label,
                ['bold' => true, 'size' => 10, 'color' => $textColor],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        private function addQuestionGlobalValueCell($table, int $width, string $value, string $bgColor, string $textColor): void
        {
            $table->addCell($width, ['bgColor' => $bgColor])->addText(
                $value,
                ['bold' => true, 'size' => 10, 'color' => $textColor],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );
        }

        private function addQuestionGlobalRiskLegend(Section $section): void
        {
            $section->addText(
                'Nivel de riesgo',
                ['size' => 10],
                ['spaceAfter' => 40]
            );

            $legend = $section->addTable([
                'alignment' => JcTable::START,
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
                $legend->addCell(1400, ['bgColor' => $bg])->addText(
                    $label,
                    ['bold' => true, 'size' => 10, 'color' => $text],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }
        }

    private function addQuestionAverageItemsToCell($cell, array $items, ?string $note = null): void
        {
            $itemsTable = $cell->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 8,
            ]);

            $itemsTable->addRow(320, ['cantSplit' => true]);

            foreach ($items as $item) {
                $hex = $this->getQuestionValueHex((int) $item['score']);
                $textColor = ((int) $item['score'] === 2) ? '111111' : 'FFFFFF';

                $itemsTable->addCell(360, [
                    'bgColor' => $hex,
                    'borderSize' => 6,
                    'borderColor' => '333333',
                ])->addText(
                    (string) $item['number'],
                    ['bold' => true, 'size' => 9, 'color' => $textColor],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            if ($note) {
                $itemsTable->addCell(900, [
                    'bgColor' => 'D9D9D9',
                    'borderSize' => 6,
                    'borderColor' => '333333',
                ])->addText(
                    $note,
                    ['size' => 9, 'color' => '111111'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }
        }

        private function addQuestionAverageMatrixLegends(Section $section): void
        {
            $section->addText(
                'Nivel de riesgo',
                ['size' => 9],
                ['spaceAfter' => 20]
            );

            $riskLegend = $section->addTable([
                'alignment' => JcTable::START,
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 15,
            ]);

            $riskLegend->addRow(300, ['cantSplit' => true]);

            foreach ([
                ['Nulo', '3B82F6', 'FFFFFF'],
                ['Bajo', '16A34A', 'FFFFFF'],
                ['Medio', 'F8FF03', '111111'],
                ['Alto', 'F59E0B', 'FFFFFF'],
                ['Muy Alto', 'EF4444', 'FFFFFF'],
            ] as [$label, $bg, $text]) {
                $riskLegend->addCell(1120, ['bgColor' => $bg])->addText(
                    $label,
                    ['bold' => true, 'size' => 9, 'color' => $text],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $section->addText(
                'Valor de la pregunta según el color',
                ['size' => 9],
                ['spaceBefore' => 40, 'spaceAfter' => 20]
            );

            $valueLegend = $section->addTable([
                'alignment' => JcTable::START,
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 15,
            ]);

            $valueLegend->addRow(300, ['cantSplit' => true]);

            foreach ([
                ['0', '3B82F6', 'FFFFFF'],
                ['1', '16A34A', 'FFFFFF'],
                ['2', 'F8FF03', '111111'],
                ['3', 'F59E0B', 'FFFFFF'],
                ['4', 'EF4444', 'FFFFFF'],
            ] as [$label, $bg, $text]) {
                $valueLegend->addCell(520, ['bgColor' => $bg])->addText(
                    $label,
                    ['bold' => true, 'size' => 9, 'color' => $text],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }

            $section->addText('*a  Servicio a clientes o usuarios', ['size' => 9], ['spaceBefore' => 30, 'spaceAfter' => 15]);
            $section->addText('*b  Soy jefe de otros trabajadores', ['size' => 9], ['spaceAfter' => 15]);
        }

    private function addDistributionTable(Section $section, string $title, array $rows): void
        {
            $band = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 0,
                'cellMargin' => 0,
            ]);

            $band->addRow();
            $band->addCell(9400, ['bgColor' => 'D9D9D9'])->addText(
                $title,
                ['bold' => true, 'size' => 11, 'color' => '111111'],
                ['spaceAfter' => 0]
            );

            $section->addTextBreak(1);

            $table = $section->addTable([
                'alignment' => JcTable::CENTER,
                'borderSize' => 6,
                'borderColor' => '808080',
                'cellMargin' => 45,
            ]);

            $table->addRow();
            $table->addCell(7000, ['bgColor' => '062A78'])->addText(
                'Categoría',
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['spaceAfter' => 0]
            );
            $table->addCell(1800, ['bgColor' => '062A78'])->addText(
                'Total',
                ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
            );

            if (empty($rows)) {
                $table->addRow();
                $table->addCell(7000)->addText('N/D', ['size' => 10, 'color' => '374151'], ['spaceAfter' => 0]);
                $table->addCell(1800)->addText('0', ['bold' => true, 'size' => 10, 'color' => '374151'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
                return;
            }

            foreach ($rows as $row) {
                $table->addRow();
                $table->addCell(7000)->addText(
                    $this->safeValue($row['label'] ?? 'N/D'),
                    ['size' => 10, 'color' => '111827'],
                    ['spaceAfter' => 0]
                );
                $table->addCell(1800)->addText(
                    (string) ($row['total'] ?? 0),
                    ['bold' => true, 'size' => 10, 'color' => '111827'],
                    ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
                );
            }
        }

    private function getParticipantSummary(string $organizationId, string $workCenterId): array
    {
        $evaluationsBase = DB::table('paper_evaluations as pe')
            ->where('pe.organization_id', $organizationId)
            ->where('pe.work_center_id', $workCenterId)
            ->whereIn('pe.source', ['paper', 'online'])
            ->where('pe.processing_status', 'completed')
            ->whereNull('pe.deleted_at');

        $paperParticipants = (clone $evaluationsBase)
            ->where('pe.source', 'paper')
            ->distinct()
            ->count('pe.personal_folio');

        $onlineParticipants = (clone $evaluationsBase)
            ->where('pe.source', 'online')
            ->distinct()
            ->count('pe.personal_folio');

        $totalParticipants = $paperParticipants + $onlineParticipants;

        $demographicBase = DB::table('paper_evaluations as pe')
            ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
            ->where('pe.organization_id', $organizationId)
            ->where('pe.work_center_id', $workCenterId)
            ->whereIn('pe.source', ['paper', 'online'])
            ->where('pe.processing_status', 'completed')
            ->whereNull('pe.deleted_at');

        $genderRows = $this->groupDemographicCounts($demographicBase, 'dd.gender');
        $genderTotals = $this->summarizeGenderRows($genderRows);

        return [
            'total_participants' => $totalParticipants,
            'paper_participants' => $paperParticipants,
            'online_participants' => $onlineParticipants,
            'men_total' => $genderTotals['men'],
            'women_total' => $genderTotals['women'],
            'unspecified_gender_total' => $genderTotals['unspecified'],
            'gender' => $genderRows,
            'age' => $this->groupAgeRanges($demographicBase, 'dd.age'),
            'marital_status' => $this->groupDemographicCounts($demographicBase, 'dd.marital_status'),
            'education_level' => $this->groupDemographicCounts($demographicBase, 'dd.education_level'),
            'position' => $this->groupDemographicCounts($demographicBase, 'dd.position'),
            'department' => $this->groupDemographicCounts($demographicBase, 'dd.department'),
            'position_type' => $this->groupDemographicCounts($demographicBase, 'dd.position_type'),
            'contract_type' => $this->groupDemographicCounts($demographicBase, 'dd.contract_type'),
            'personnel_type' => $this->groupDemographicCounts($demographicBase, 'dd.personnel_type'),
            'work_schedule' => $this->groupDemographicCounts($demographicBase, 'dd.work_schedule'),
            'shift_rotation' => $this->groupDemographicCounts($demographicBase, 'dd.shift_rotation'),
            'time_in_current_position' => $this->groupDemographicCounts($demographicBase, 'dd.time_in_current_position'),
            'work_experience' => $this->groupDemographicCounts($demographicBase, 'dd.work_experience'),
        ];
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
            if ($total > 0 || $label === 'N/D') {
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
        $averageGlobalScore = $totalEvaluations > 0
            ? round(array_sum(array_column($evaluations, 'global_score')) / $totalEvaluations, 2)
            : 0;

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

    private function getReferenceThreeEvaluations($rows): array
        {
            return $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) {
                    $first = $items->first();

                    $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);
                    if (! is_array($extra)) {
                        $extra = [];
                    }

                    $isBoss = $this->extractWorkerFlag($extra, [
                        'jefe', 'soy_jefe', 'is_boss', 'is_manager',
                        'supervises_people', 'supervisa_personal', 'jefe_trabajadores',
                    ]) || $items->contains(function ($answer) {
                        return in_array((int) $answer->question_key, [69, 70, 71, 72], true);
                    });

                    $attendsPublic = $this->extractWorkerFlag($extra, [
                        'atiende', 'atiende_clientes', 'atencion_clientes',
                        'servicio_clientes', 'servicio_usuarios', 'client_service', 'attends_public',
                    ]) || $items->contains(function ($answer) {
                        return in_array((int) $answer->question_key, [65, 66, 67, 68], true);
                    });

                    return $this->buildReferenceThreeEvaluationResult(
                        (string) $evaluationId,
                        (string) ($first->source ?? 'paper'),
                        $items,
                        $attendsPublic,
                        $isBoss
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
            bool $isBoss = false
        ): array {
            $globalScore = 0;
            $dimensionScores = [];
            $domainScores = [];
            $categoryScores = [];

            foreach ($answers as $answer) {
                $questionKey = (int) $answer->question_key;

                if (in_array($questionKey, [65, 66, 67, 68], true) && ! $attendsPublic) {
                    continue;
                }

                if (in_array($questionKey, [69, 70, 71, 72], true) && ! $isBoss) {
                    continue;
                }

                $score = $this->getReferenceThreeScore($answer->question_key, $answer->answer_value);
                $meta = $this->getReferenceThreeQuestionMeta($answer->question_key);

                if ($score === null || $meta === null) {
                    continue;
                }

                $globalScore += $score;

                $dimensionScores[$meta['dimension']] = ($dimensionScores[$meta['dimension']] ?? 0) + $score;
                $domainScores[$meta['domain']] = ($domainScores[$meta['domain']] ?? 0) + $score;
                $categoryScores[$meta['category']] = ($categoryScores[$meta['category']] ?? 0) + $score;
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
                'global_score' => $globalScore,
                'global_level_key' => $globalLevel['key'],
                'global_level_label' => $globalLevel['label'],
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

    private function getReferenceThreeCategorySummary(string $organizationId, string $workCenterId): array
    {
        $globalSummary = $this->getReferenceThreeGlobalSummary($organizationId, $workCenterId);
        $evaluations = $globalSummary['evaluations'] ?? [];
        $totalEvaluations = (int) ($globalSummary['total_evaluations'] ?? 0);

        $categories = [];

        foreach ($this->getReferenceThreeCategoryMaxScores() as $categoryName => $maxScore) {
            $distribution = $this->initializeRiskLevelCounts();
            $scoreSum = 0;

            foreach ($evaluations as $evaluation) {
                $score = (int) ($evaluation['category_scores'][$categoryName] ?? 0);
                $scoreSum += $score;

                $levelKey = $evaluation['category_levels'][$categoryName]['key']
                    ?? $this->classifyNom035Score('categories', $categoryName, $score)['key'];

                if (array_key_exists($levelKey, $distribution)) {
                    $distribution[$levelKey]++;
                }
            }

            $averageScore = $totalEvaluations > 0
                ? round($scoreSum / $totalEvaluations, 2)
                : 0;

            $averagePercentage = $maxScore > 0
                ? round(($averageScore / $maxScore) * 100, 2)
                : 0;

            $dominantLevelKey = 'nulo';
            $dominantCount = -1;

            foreach ($distribution as $levelKey => $count) {
                if ($count > $dominantCount) {
                    $dominantCount = $count;
                    $dominantLevelKey = $levelKey;
                }
            }

            $categories[] = [
                'name' => $categoryName,
                'max_score' => $maxScore,
                'average_score' => $averageScore,
                'average_percentage' => $averagePercentage,
                'distribution' => $distribution,
                'dominant_level_key' => $dominantLevelKey,
                'dominant_level_label' => config("nom035_risk_levels.labels.$dominantLevelKey", ucfirst($dominantLevelKey)),
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

        private function getAttentionCount(array $distribution): int
    {
        return (int) ($distribution['alto'] ?? 0) + (int) ($distribution['muy_alto'] ?? 0);
    }

    private function getAttentionPercentage(array $distribution, int $totalEvaluations): float
        {
            if ($totalEvaluations <= 0) {
                return 0;
            }

            return round(($this->getAttentionCount($distribution) / $totalEvaluations) * 100, 2);
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

    private function addRiskLevelDistributionTable(Section $section, string $title, array $distribution, int $totalEvaluations): void
    {
        $section->addText(
            $title,
            ['bold' => true, 'size' => 12, 'color' => '1D4ED8'],
            ['spaceBefore' => 120, 'spaceAfter' => 100]
        );

        $table = $section->addTable('StatsTable');

        $table->addRow();
        $table->addCell(3200, ['bgColor' => 'EAF2FF'])->addText('Nivel', ['bold' => true, 'size' => 10]);
        $table->addCell(1600, ['bgColor' => 'EAF2FF'])->addText('Total', ['bold' => true, 'size' => 10]);
        $table->addCell(1600, ['bgColor' => 'EAF2FF'])->addText('%', ['bold' => true, 'size' => 10]);

        foreach (['nulo', 'bajo', 'medio', 'alto', 'muy_alto'] as $levelKey) {
            $total = (int) ($distribution[$levelKey] ?? 0);
            $percentage = $totalEvaluations > 0
                ? round(($total / $totalEvaluations) * 100, 2)
                : 0;

            $table->addRow();
            $table->addCell(3200)->addText(
                config("nom035_risk_levels.labels.$levelKey", ucfirst($levelKey)),
                ['size' => 10, 'color' => '374151']
            );
            $table->addCell(1600)->addText((string) $total, ['size' => 10, 'color' => '374151']);
            $table->addCell(1600)->addText($percentage . '%', ['size' => 10, 'color' => '374151']);
        }
    }

    private function getReferenceThreeDomainSummary(string $organizationId, string $workCenterId): array
    {
        $globalSummary = $this->getReferenceThreeGlobalSummary($organizationId, $workCenterId);
        $evaluations = $globalSummary['evaluations'] ?? [];
        $totalEvaluations = (int) ($globalSummary['total_evaluations'] ?? 0);

        $domains = [];

        foreach ($this->getReferenceThreeDomainMaxScores() as $domainName => $maxScore) {
            $distribution = $this->initializeRiskLevelCounts();
            $scoreSum = 0;

            foreach ($evaluations as $evaluation) {
                $score = (int) ($evaluation['domain_scores'][$domainName] ?? 0);
                $scoreSum += $score;

                $levelKey = $evaluation['domain_levels'][$domainName]['key']
                    ?? $this->classifyNom035Score('domains', $domainName, $score)['key'];

                if (array_key_exists($levelKey, $distribution)) {
                    $distribution[$levelKey]++;
                }
            }

            $averageScore = $totalEvaluations > 0
                ? round($scoreSum / $totalEvaluations, 2)
                : 0;

            $averagePercentage = $maxScore > 0
                ? round(($averageScore / $maxScore) * 100, 2)
                : 0;

            $dominantLevelKey = 'nulo';
            $dominantCount = -1;

            foreach ($distribution as $levelKey => $count) {
                if ($count > $dominantCount) {
                    $dominantCount = $count;
                    $dominantLevelKey = $levelKey;
                }
            }

            $domains[] = [
                'name' => $domainName,
                'max_score' => $maxScore,
                'average_score' => $averageScore,
                'average_percentage' => $averagePercentage,
                'distribution' => $distribution,
                'dominant_level_key' => $dominantLevelKey,
                'dominant_level_label' => config("nom035_risk_levels.labels.$dominantLevelKey", ucfirst($dominantLevelKey)),
            ];
        }

        return [
            'total_evaluations' => $totalEvaluations,
            'domains' => $domains,
        ];
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

    private function getReferenceThreeDimensionSummary(string $organizationId, string $workCenterId): array
    {
        $globalSummary = $this->getReferenceThreeGlobalSummary($organizationId, $workCenterId);
        $evaluations = $globalSummary['evaluations'] ?? [];
        $totalEvaluations = (int) ($globalSummary['total_evaluations'] ?? 0);

        $dimensions = [];

        foreach ($this->getReferenceThreeDimensionMaxScores() as $dimensionName => $maxScore) {
            $distribution = $this->initializeRiskLevelCounts();
            $scoreSum = 0;

            foreach ($evaluations as $evaluation) {
                $score = (int) ($evaluation['dimension_scores'][$dimensionName] ?? 0);
                $scoreSum += $score;

                $levelKey = $evaluation['dimension_levels'][$dimensionName]['key']
                    ?? $this->classifyNom035Score('dimensions', $dimensionName, $score)['key'];

                if (array_key_exists($levelKey, $distribution)) {
                    $distribution[$levelKey]++;
                }
            }

            $averageScore = $totalEvaluations > 0
                ? round($scoreSum / $totalEvaluations, 2)
                : 0;

            $averagePercentage = $maxScore > 0
                ? round(($averageScore / $maxScore) * 100, 2)
                : 0;

            $dominantLevelKey = 'nulo';
            $dominantCount = -1;

            foreach ($distribution as $levelKey => $count) {
                if ($count > $dominantCount) {
                    $dominantCount = $count;
                    $dominantLevelKey = $levelKey;
                }
            }

            $dimensions[] = [
                'name' => $dimensionName,
                'max_score' => $maxScore,
                'average_score' => $averageScore,
                'average_percentage' => $averagePercentage,
                'distribution' => $distribution,
                'dominant_level_key' => $dominantLevelKey,
                'dominant_level_label' => config("nom035_risk_levels.labels.$dominantLevelKey", ucfirst($dominantLevelKey)),
            ];
        }

        return [
            'total_evaluations' => $totalEvaluations,
            'dimensions' => $dimensions,
        ];
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
                    return ($b['global_score'] ?? 0) <=> ($a['global_score'] ?? 0);
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
                                'global_score' => (int) ($evaluation['global_score'] ?? 0),
                                'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                                'name' => $evaluation['name'] ?? 'N/D',
                                'area' => $evaluation['area'] ?? 'N/D',
                                'work_schedule' => $evaluation['work_schedule'] ?? 'N/D',
                                'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                                'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                            ];
                        })
                        ->sortByDesc('global_score')
                        ->values()
                        ->all();

                    return [
                        'name' => $positionName,
                        'rows' => $rows,
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
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
                                'global_score' => (int) ($evaluation['global_score'] ?? 0),
                                'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                                'name' => $evaluation['name'] ?? 'N/D',
                                'position' => $evaluation['position'] ?? 'N/D',
                                'work_schedule' => $evaluation['work_schedule'] ?? 'N/D',
                                'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                                'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                            ];
                        })
                        ->sortByDesc('global_score')
                        ->values()
                        ->all();

                    return [
                        'name' => $departmentName,
                        'rows' => $rows,
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
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
                                'global_score' => (int) ($evaluation['global_score'] ?? 0),
                                'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                                'name' => $evaluation['name'] ?? 'N/D',
                                'area' => $evaluation['area'] ?? 'N/D',
                                'position' => $evaluation['position'] ?? 'N/D',
                                'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                                'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                            ];
                        })
                        ->sortByDesc('global_score')
                        ->values()
                        ->all();

                    return [
                        'name' => $workScheduleName,
                        'rows' => $rows,
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();

            return $grouped;
        }

        private function getSevereTraumaticEventsSummary(string $organizationId, string $workCenterId): array
        {
            $rows = DB::table('paper_evaluations as pe')
                ->leftJoin('demographic_data as dd', 'dd.paper_evaluation_id', '=', 'pe.id')
                ->where('pe.organization_id', $organizationId)
                ->where('pe.work_center_id', $workCenterId)
                ->whereIn('pe.source', ['paper', 'online'])
                ->where('pe.processing_status', 'completed')
                ->whereNull('pe.deleted_at')
                ->whereNotNull('pe.referencia_i_answers')
                ->select(
                    'pe.id as evaluation_id',
                    'pe.personal_folio',
                    'pe.evaluee_name',
                    'pe.referencia_i_answers',
                    'dd.gender',
                    'dd.position'
                )
                ->orderBy('pe.personal_folio')
                ->get();

            $resultRows = [];
            $requiresMen = 0;
            $requiresWomen = 0;

            foreach ($rows as $row) {
                $answers = json_decode((string) $row->referencia_i_answers, true);
                if (! is_array($answers)) {
                    continue;
                }

                $sections = $this->parseAtsSectionsFromAnswers($answers);

                $total = $sections['s1'] + $sections['s2'] + $sections['s3'] + $sections['s4'];
                if ($total <= 0) {
                    continue;
                }

                $requiresValuation = $this->requiresAtsValuation($sections);

                $gender = trim((string) ($row->gender ?? ''));
                $genderLabel = $gender !== '' ? ucfirst(mb_strtolower($gender)) : 'N/D';

                if ($requiresValuation) {
                    if (str_contains(mb_strtolower($genderLabel), 'masc') || $genderLabel === 'Hombre') {
                        $requiresMen++;
                    } elseif (str_contains(mb_strtolower($genderLabel), 'fem') || $genderLabel === 'Mujer') {
                        $requiresWomen++;
                    }
                }

                $resultRows[] = [
                    'evaluation_id' => (string) $row->evaluation_id,
                    'folio' => $this->safeValue($row->personal_folio),
                    'name' => $this->safeValue($row->evaluee_name),
                    'gender' => $genderLabel,
                    'position' => $this->safeValue($row->position),
                    's1' => $sections['s1'],
                    's2' => $sections['s2'],
                    's3' => $sections['s3'],
                    's4' => $sections['s4'],
                    'requires_valuation' => $requiresValuation,
                ];
            }

            return [
                'rows' => collect($resultRows)->sortBy('folio', SORT_NATURAL)->values()->all(),
                'requires_valuation_total' => $requiresMen + $requiresWomen,
                'requires_valuation_men' => $requiresMen,
                'requires_valuation_women' => $requiresWomen,
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
                    if ($points <= 0) {
                        return null;
                    }

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
                ->filter()
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

                foreach ($questionLabels as $item => $label) {
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

        private function getFinalRiskWorkersSummary(string $organizationId, string $workCenterId): array
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
                    'jefe', 'soy_jefe', 'is_boss', 'is_manager',
                    'supervises_people', 'supervisa_personal', 'jefe_trabajadores',
                ]);

                $attendsPublic = $this->extractWorkerFlag($extra, [
                    'atiende', 'atiende_clientes', 'atencion_clientes',
                    'servicio_clientes', 'servicio_usuarios', 'client_service', 'attends_public',
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

            return collect($evaluations)
                ->filter(function ($evaluation) {
                    return in_array($evaluation['global_level_key'] ?? 'nulo', ['medio', 'alto', 'muy_alto'], true);
                })
                ->map(function ($evaluation) {
                    return [
                        'folio' => $evaluation['folio'] ?? 'N/D',
                        'global_score' => (int) ($evaluation['global_score'] ?? 0),
                        'global_level_key' => $evaluation['global_level_key'] ?? 'nulo',
                        'name' => $evaluation['name'] ?? 'N/D',
                        'area' => $evaluation['area'] ?? 'N/D',
                        'position' => $evaluation['position'] ?? 'N/D',
                        'is_boss' => (bool) ($evaluation['is_boss'] ?? false),
                        'attends_public' => (bool) ($evaluation['attends_public'] ?? false),
                    ];
                })
                ->sortByDesc('global_score')
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
                'dd.department',
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
                    'jefe', 'soy_jefe', 'is_boss', 'is_manager',
                    'supervises_people', 'supervisa_personal', 'jefe_trabajadores',
                ]);

                $attendsPublic = $this->extractWorkerFlag($extra, [
                    'atiende', 'atiende_clientes', 'atencion_clientes',
                    'servicio_clientes', 'servicio_usuarios', 'client_service', 'attends_public',
                ]);

                $result = $this->buildReferenceThreeEvaluationResult(
                    (string) $evaluationId,
                    (string) ($first->source ?? 'paper'),
                    $items,
                    $attendsPublic,
                    $isBoss
                );

                $result['area'] = $this->safeValue($first->department);

                return $result;
            })
                ->values()
                ->all();

            $domainNames = array_keys($this->getReferenceThreeDomainMaxScores());

            return collect($evaluations)
                ->groupBy(function ($evaluation) {
                    $label = trim((string) ($evaluation['area'] ?? ''));
                    return $label !== '' ? $label : 'N/D';
                })
                ->map(function ($items, $areaName) use ($domainNames) {
                    $participants = count($items);
                    $rows = [];

                    foreach ($domainNames as $index => $domainName) {
                        $distribution = $this->initializeRiskLevelCounts();

                        foreach ($items as $evaluation) {
                            $score = (int) ($evaluation['domain_scores'][$domainName] ?? 0);
                            $levelKey = $evaluation['domain_levels'][$domainName]['key']
                                ?? $this->classifyNom035Score('domains', $domainName, $score)['key'];

                            if (array_key_exists($levelKey, $distribution)) {
                                $distribution[$levelKey]++;
                            }
                        }

                        $rows[] = [
                            'label' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '.- ' . $domainName,
                            'distribution' => $distribution,
                            'attention' => $this->getQuestionGlobalAttentionCount($distribution),
                        ];
                    }

                    return [
                        'name' => $areaName,
                        'participants' => $participants,
                        'rows' => $rows,
                    ];
                })
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        }

        private function getWorkerIdentificationByCategorySummary(string $organizationId, string $workCenterId): array
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
                'dd.extra_fields'
            )
                ->orderBy('pe.id')
                ->orderByRaw('CAST(ea.question_key AS UNSIGNED)')
                ->get();

            $categoryNames = array_keys($this->getReferenceThreeCategoryMaxScores());

            return $rows
                ->groupBy('evaluation_id')
                ->map(function ($items, $evaluationId) use ($categoryNames) {
                $first = $items->first();

                $extra = json_decode((string) ($first->extra_fields ?? '[]'), true);
                if (! is_array($extra)) {
                    $extra = [];
                }

                $isBoss = $this->extractWorkerFlag($extra, [
                    'jefe', 'soy_jefe', 'is_boss', 'is_manager',
                    'supervises_people', 'supervisa_personal', 'jefe_trabajadores',
                ]);

                $attendsPublic = $this->extractWorkerFlag($extra, [
                    'atiende', 'atiende_clientes', 'atencion_clientes',
                    'servicio_clientes', 'servicio_usuarios', 'client_service', 'attends_public',
                ]);

                $result = $this->buildReferenceThreeEvaluationResult(
                    (string) $evaluationId,
                    (string) ($first->source ?? 'paper'),
                    $items,
                    $attendsPublic,
                    $isBoss
                );
                    $categories = [];
                    foreach ($categoryNames as $categoryName) {
                        $score = (int) ($result['category_scores'][$categoryName] ?? 0);
                        $levelKey = $result['category_levels'][$categoryName]['key']
                            ?? $this->classifyNom035Score('categories', $categoryName, $score)['key'];

                        $categories[] = [
                            'name' => $categoryName,
                            'score' => $score,
                            'level_key' => $levelKey,
                        ];
                    }

                    return [
                        'folio' => $this->safeValue($first->personal_folio),
                        'name' => $this->safeValue($first->evaluee_name),
                        'global_score' => (int) ($result['global_score'] ?? 0),
                        'global_level_key' => $result['global_level_key'] ?? 'nulo',
                        'categories' => $categories,
                    ];
                })
                ->filter(function ($row) {
                    return in_array($row['global_level_key'] ?? 'nulo', ['medio', 'alto', 'muy_alto'], true);
                })
                ->sortByDesc('global_score')
                ->values()
                ->all();
        }

        private function parseAtsSectionsFromAnswers(array $answers): array
        {
            $normalized = $this->normalizeAtsKeysRecursive($answers);

            $s1 = $this->countTruthyRecursive($this->getAtsSectionPayload($normalized, [
                'seccion_i', 'section_i', 's_i', 'si',
            ]));

            $s2 = $this->countTruthyRecursive($this->getAtsSectionPayload($normalized, [
                'seccion_ii', 'section_ii', 's_ii', 'sii',
            ]));

            $s3 = $this->countTruthyRecursive($this->getAtsSectionPayload($normalized, [
                'seccion_iii', 'section_iii', 's_iii', 'siii',
            ]));

            $s4 = $this->countTruthyRecursive($this->getAtsSectionPayload($normalized, [
                'seccion_iv', 'section_iv', 's_iv', 'siv',
            ]));

            return compact('s1', 's2', 's3', 's4');
        }

        private function requiresAtsValuation(array $sections): bool
        {
            return (int) ($sections['s1'] ?? 0) > 0
                && (
                    (int) ($sections['s2'] ?? 0) > 0
                    || (int) ($sections['s3'] ?? 0) >= 3
                    || (int) ($sections['s4'] ?? 0) >= 2
                );
        }

        private function getAtsSectionPayload(array $payload, array $aliases)
        {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $payload)) {
                    return $payload[$alias];
                }
            }

            return [];
        }

        private function normalizeAtsKeysRecursive($value)
        {
            if (! is_array($value)) {
                return $value;
            }

            $normalized = [];

            foreach ($value as $key => $item) {
                $normalizedKey = is_string($key)
                    ? str_replace(['-', ' '], '_', Str::lower(Str::ascii($key)))
                    : $key;

                $normalized[$normalizedKey] = $this->normalizeAtsKeysRecursive($item);
            }

            return $normalized;
        }

        private function countTruthyRecursive($value): int
        {
            if (is_array($value)) {
                $count = 0;
                foreach ($value as $item) {
                    $count += $this->countTruthyRecursive($item);
                }
                return $count;
            }

            return $this->isTruthyWorkerValue($value) ? 1 : 0;
        }

        private function extractWorkerFlag(array $payload, array $keys): bool
        {
            $normalizedKeys = array_map(
                fn ($key) => mb_strtolower(trim((string) $key)),
                $keys
            );

            foreach ($payload as $payloadKey => $value) {
                $payloadKeyNormalized = mb_strtolower(trim((string) $payloadKey));

                foreach ($normalizedKeys as $expectedKey) {
                    if (
                        $payloadKeyNormalized === $expectedKey ||
                        str_contains($payloadKeyNormalized, $expectedKey)
                    ) {
                        if ($this->isTruthyWorkerValue($value)) {
                            return true;
                        }
                    }
                }

                if (is_array($value) && $this->extractWorkerFlag($value, $keys)) {
                    return true;
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

                $value = mb_strtolower(trim((string) $value));

                return in_array($value, [
                    '1', 'si', 'sí', 'true', 'x', 'yes',
                    'aplica', 'activo', 'checked', 'seleccionado', 'on'
                ], true);
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
            $textColor = imagecolorallocate($image, 51, 65, 85);
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

            $attention = (int) ($distribution['alto'] ?? 0) + (int) ($distribution['muy_alto'] ?? 0);
            $attentionPct = round(($attention / $total) * 100, 2);

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

                if ($angle >= 20) {
                    $mid = deg2rad($start + ($angle / 2));
                    $labelX = (int) round($cx + cos($mid) * ($radius * 0.58));
                    $labelY = (int) round($cy + sin($mid) * ($radius * 0.58));
                    $pct = number_format(($level['count'] / $total) * 100, 2);

                    $this->drawChartTextBold(
                        $image,
                        9,
                        $labelX - 28,
                        $labelY,
                        $titleColor,
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
                $pct = number_format(($level['count'] / $total) * 100, 2);

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
            $this->drawChartTextCenteredBold($image, 14, 860, 490, 1100, 548, $white, number_format($attentionPct, 2) . '%');

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

    private function generateAveragePercentageChart(string $title, array $items, string $outputPath): ?string
    {
        $labels = [];
        $values = [];

        foreach ($items as $item) {
            $labels[] = (string) ($item['label'] ?? 'N/D');
            $values[] = (float) ($item['value'] ?? 0);
        }

        return $this->renderHorizontalBarChart($title, $labels, $values, $outputPath, 100, '%');
    }

    private function generateCategoryDashboardChart(array $categories, int $totalEvaluations, string $outputPath): ?string
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

            $width = 1680;
            $height = 1320;

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

            $this->drawChartTextBold($image, 24, 38, 42, $text, 'Categorías - Atención (%)');

            // ===== Tabla superior =====
            $tableX = 35;
            $tableY = 90;
            $tableW = $width - 70;
            $rowH = 62;

            $descW = 760;
            $cellW = 135;

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

            // ===== Panel inferior =====
            $panelY = 500;
            imagefilledrectangle($image, 35, $panelY, $width - 35, $height - 35, $white);
            imagerectangle($image, 35, $panelY, $width - 35, $height - 35, $border);

            $this->drawChartTextBold($image, 22, 50, $panelY + 32, $text, 'Atención (%)');

            $slots = [
                [75, 615],
                [600, 615],
                [1125, 615],
                [75, 955],
                [600, 955],
                [1125, 955],
            ];

            foreach ($categories as $i => $category) {
                if (! isset($slots[$i])) {
                    break;
                }

                [$baseX, $baseY] = $slots[$i];

                $this->drawCategoryMiniChart(
                    $image,
                    $baseX,
                    $baseY,
                    390,
                    250,
                    (string) $category['name'],
                    $category['distribution'] ?? [],
                    $totalEvaluations,
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
            $title = mb_substr($title, 0, 34);

            $this->drawChartTextBold($image, 17, $x + 8, $y - 24, $textColor, $title);

            $chartX = $x + 10;
            $chartY = $y;
            $chartW = $w;
            $chartH = $h;

            imagerectangle($image, $chartX, $chartY, $chartX + $chartW, $chartY + $chartH, $borderColor);

            for ($i = 0; $i <= 5; $i++) {
                $gy = $chartY + $chartH - (int)(($chartH / 5) * $i);
                imageline($image, $chartX, $gy, $chartX + $chartW, $gy, $borderColor);

                $axisLabel = (string) ($i * 20);
                $this->drawChartText($image, 10, $chartX - 28, $gy + 4, $mutedColor, $axisLabel);
            }

            $colors = [$blue, $green, $yellow, $orange, $red];
            $keys = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

            $barW = 56;
            $gap = 16;
            $startX = $chartX + 28;
            $maxH = $chartH - 28;

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

    private function generateDomainDashboardChart(array $domains, int $totalEvaluations, string $outputPath): ?string
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

            $width = 1720;
            $height = 2280;

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

            $this->drawChartTextBold($image, 24, 38, 42, $text, 'Dominios - Atención (%)');

            // ===== Tabla superior =====
            $tableX = 35;
            $tableY = 90;
            $tableW = $width - 70;
            $rowH = 60;

            $descW = 760;
            $cellW = 135;

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

            // ===== Panel inferior =====
            $panelY = 780;
            imagefilledrectangle($image, 35, $panelY, $width - 35, $height - 35, $white);
            imagerectangle($image, 35, $panelY, $width - 35, $height - 35, $border);

            $this->drawChartTextBold($image, 22, 50, $panelY + 32, $text, 'Atención (%)');

            $slots = [
                [75, 900],
                [900, 900],
                [75, 1185],
                [900, 1185],
                [75, 1470],
                [900, 1470],
                [75, 1755],
                [900, 1755],
                [75, 2040],
                [900, 2040],
            ];

            foreach ($domains as $i => $domain) {
                if (! isset($slots[$i])) {
                    break;
                }

                [$baseX, $baseY] = $slots[$i];

                $this->drawDomainMiniChart(
                    $image,
                    $baseX,
                    $baseY,
                    620,
                    210,
                    (string) $domain['name'],
                    $domain['distribution'] ?? [],
                    $totalEvaluations,
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

        private function drawDomainMiniChart(
            $image,
            int $x,
            int $y,
            int $w,
            int $h,
            string $title,
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

            $this->drawChartTextBold($image, 16, $x + 8, $y - 28, $textColor, $line1);

            if ($line2 !== '') {
                $this->drawChartTextBold($image, 16, $x + 8, $y - 4, $textColor, $line2);
            }

            $chartX = $x + 12;
            $chartY = $y + 8;
            $chartW = $w;
            $chartH = $h;

            imagerectangle($image, $chartX, $chartY, $chartX + $chartW, $chartY + $chartH, $borderColor);

            for ($i = 0; $i <= 5; $i++) {
                $gy = $chartY + $chartH - (int)(($chartH / 5) * $i);
                imageline($image, $chartX, $gy, $chartX + $chartW, $gy, $borderColor);

                $axisLabel = (string) ($i * 20);
                $this->drawChartText($image, 10, $chartX - 28, $gy + 4, $mutedColor, $axisLabel);
            }

            $colors = [$blue, $green, $yellow, $orange, $red];
            $keys = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

            $barW = 82;
            $gap = 22;
            $startX = $chartX + 36;
            $maxH = $chartH - 28;

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

            $columns = 3;
            $rows = (int) ceil(count($dimensions) / $columns);

            $width = 1720;
            $tableX = 35;
            $tableY = 90;
            $rowH = 56;
            $descW = 900;
            $cellW = 115;

            $tableHeight = $rowH * (count($dimensions) + 1);
            $panelY = $tableY + $tableHeight + 45;
            $chartBlockHeight = ($rows * 315);
            $height = $panelY + $chartBlockHeight + 120;

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
            $baseX = 75;
            $baseY = $panelY + 120;
            $colGap = 540;
            $rowGap = 300;

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
                    430,
                    190,
                    (string) $dimension['name'],
                    $dimension['distribution'] ?? [],
                    $totalEvaluations,
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

    private function drawDimensionMiniChart(
            $image,
            int $x,
            int $y,
            int $w,
            int $h,
            string $title,
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
            $wrapped = explode("\n", wordwrap($title, 30, "\n", true));
            $line1 = $wrapped[0] ?? '';
            $line2 = $wrapped[1] ?? '';
            $line3 = $wrapped[2] ?? '';

            $this->drawChartTextBold($image, 14, $x + 8, $y - 30, $textColor, $line1);

            if ($line2 !== '') {
                $this->drawChartTextBold($image, 14, $x + 8, $y - 10, $textColor, $line2);
            }

            if ($line3 !== '') {
                $this->drawChartTextBold($image, 14, $x + 8, $y + 10, $textColor, $line3);
            }

            $chartX = $x + 10;
            $chartY = $y + 24;
            $chartW = $w;
            $chartH = $h;

            imagerectangle($image, $chartX, $chartY, $chartX + $chartW, $chartY + $chartH, $borderColor);

            for ($i = 0; $i <= 5; $i++) {
                $gy = $chartY + $chartH - (int)(($chartH / 5) * $i);
                imageline($image, $chartX, $gy, $chartX + $chartW, $gy, $borderColor);

                $axisLabel = (string) ($i * 20);
                $this->drawChartText($image, 9, $chartX - 24, $gy + 4, $mutedColor, $axisLabel);
            }

            $colors = [$blue, $green, $yellow, $orange, $red];
            $keys = ['nulo', 'bajo', 'medio', 'alto', 'muy_alto'];

            $barW = 52;
            $gap = 16;
            $startX = $chartX + 28;
            $maxH = $chartH - 24;

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

    private function generateQuestionGlobalDashboardChart(array $summary, string $outputPath): ?string
        {
            if (! function_exists('imagecreatetruecolor')) {
                return null;
            }

            $chartDir = dirname($outputPath);

            if (! is_dir($chartDir)) {
                mkdir($chartDir, 0755, true);
            }

            $blueHex = '3B82F6';
            $greenHex = '16A34A';
            $yellowHex = 'F8FF03';
            $orangeHex = 'F59E0B';
            $redHex = 'EF4444';

            $rows = [];
            foreach ($summary['categories'] as $category) {
                foreach ($category['domains'] as $domain) {
                    foreach ($domain['dimensions'] as $dimension) {
                        $rows[] = [
                            'category' => $category['name'],
                            'category_score' => $category['score'],
                            'domain' => $domain['name'],
                            'domain_score' => $domain['score'],
                            'dimension' => $dimension['name'],
                            'dimension_score' => $dimension['score'],
                            'items' => $dimension['items'],
                            'note' => $dimension['note'] ?? null,
                        ];
                    }
                }
            }

            $rowCount = count($rows);
            $rowH = 54;
            $headerH = 66;

            $width = 1900;
            $height = 220 + ($rowCount * $rowH) + 260;

            $image = imagecreatetruecolor($width, $height);

            if (function_exists('imageantialias')) {
                imageantialias($image, true);
            }

            $white = imagecolorallocate($image, 255, 255, 255);
            $panel = imagecolorallocate($image, 248, 250, 252);
            $border = imagecolorallocate($image, 60, 60, 60);
            $text = imagecolorallocate($image, 17, 17, 17);
            $gray = imagecolorallocate($image, 217, 217, 217);

            [$br, $bg, $bb] = $this->hexToRgb($blueHex);
            [$gr, $gg, $gb] = $this->hexToRgb($greenHex);
            [$yr, $yg, $yb] = $this->hexToRgb($yellowHex);
            [$or, $og, $ob] = $this->hexToRgb($orangeHex);
            [$rr, $rg, $rb] = $this->hexToRgb($redHex);

            $blue = imagecolorallocate($image, $br, $bg, $bb);
            $green = imagecolorallocate($image, $gr, $gg, $gb);
            $yellow = imagecolorallocate($image, $yr, $yg, $yb);
            $orange = imagecolorallocate($image, $or, $og, $ob);
            $red = imagecolorallocate($image, $rr, $rg, $rb);

            imagefill($image, 0, 0, $white);

            imagefilledrectangle($image, 20, 20, $width - 20, $height - 20, $panel);
            imagerectangle($image, 20, 20, $width - 20, $height - 20, $border);

            $this->drawChartTextBold($image, 20, 36, 46, $text, 'a) Promedio General por Pregunta');

            $xCat = 40;
            $wCatName = 190;
            $wCatScore = 70;

            $xDom = $xCat + $wCatName + $wCatScore;
            $wDomName = 230;
            $wDomScore = 70;

            $xDim = $xDom + $wDomName + $wDomScore;
            $wDimName = 560;
            $wDimScore = 70;

            $xItems = $xDim + $wDimName + $wDimScore;
            $itemBoxW = 56;
            $itemGap = 6;

            $tableY = 90;

            // encabezados
            imagefilledrectangle($image, $xCat, $tableY, $xCat + $wCatName + $wCatScore, $tableY + $headerH, $gray);
            imagerectangle($image, $xCat, $tableY, $xCat + $wCatName + $wCatScore, $tableY + $headerH, $border);
            $this->drawChartTextCenteredBold($image, 16, $xCat, $tableY, $xCat + $wCatName + $wCatScore, $tableY + $headerH, $text, 'Categorías');

            imagefilledrectangle($image, $xDom, $tableY, $xDom + $wDomName + $wDomScore, $tableY + $headerH, $gray);
            imagerectangle($image, $xDom, $tableY, $xDom + $wDomName + $wDomScore, $tableY + $headerH, $border);
            $this->drawChartTextCenteredBold($image, 16, $xDom, $tableY, $xDom + $wDomName + $wDomScore, $tableY + $headerH, $text, 'Dominios');

            imagefilledrectangle($image, $xDim, $tableY, $xDim + $wDimName + $wDimScore, $tableY + $headerH, $gray);
            imagerectangle($image, $xDim, $tableY, $xDim + $wDimName + $wDimScore, $tableY + $headerH, $border);
            $this->drawChartTextCenteredBold($image, 16, $xDim, $tableY, $xDim + $wDimName + $wDimScore, $tableY + $headerH, $text, 'Factores de Riesgo Psicosocial');

            imagefilledrectangle($image, $xItems, $tableY, $width - 60, $tableY + $headerH, $gray);
            imagerectangle($image, $xItems, $tableY, $width - 60, $tableY + $headerH, $border);
            $this->drawChartTextCenteredBold($image, 16, $xItems, $tableY, $width - 60, $tableY + $headerH, $text, 'Preguntas (items)');

            $currentY = $tableY + $headerH;

            $rowIndex = 0;
            foreach ($summary['categories'] as $category) {
                $categoryRows = 0;
                foreach ($category['domains'] as $domain) {
                    $categoryRows += count($domain['dimensions']);
                }

                $categoryHeight = $categoryRows * $rowH;
                $categoryLevel = $this->classifyNom035Score('categories', $category['name'], (int) $category['score']);
                $categoryHex = $this->getRiskHexByLevel($categoryLevel['key']);
                [$cr, $cg, $cb] = $this->hexToRgb($categoryHex);
                $categoryColor = imagecolorallocate($image, $cr, $cg, $cb);

                imagefilledrectangle($image, $xCat, $currentY + ($rowIndex * $rowH), $xCat + $wCatName, $currentY + ($rowIndex * $rowH) + $categoryHeight, $white);
                imagerectangle($image, $xCat, $currentY + ($rowIndex * $rowH), $xCat + $wCatName, $currentY + ($rowIndex * $rowH) + $categoryHeight, $border);

                imagefilledrectangle($image, $xCat + $wCatName, $currentY + ($rowIndex * $rowH), $xCat + $wCatName + $wCatScore, $currentY + ($rowIndex * $rowH) + $categoryHeight, $categoryColor);
                imagerectangle($image, $xCat + $wCatName, $currentY + ($rowIndex * $rowH), $xCat + $wCatName + $wCatScore, $currentY + ($rowIndex * $rowH) + $categoryHeight, $border);

                $this->drawChartTextCenteredBold(
                    $image,
                    14,
                    $xCat,
                    $currentY + ($rowIndex * $rowH),
                    $xCat + $wCatName,
                    $currentY + ($rowIndex * $rowH) + $categoryHeight,
                    $text,
                    $category['name']
                );

                $this->drawChartTextCenteredBold(
                    $image,
                    14,
                    $xCat + $wCatName,
                    $currentY + ($rowIndex * $rowH),
                    $xCat + $wCatName + $wCatScore,
                    $currentY + ($rowIndex * $rowH) + $categoryHeight,
                    $categoryHex === $yellowHex ? $text : $white,
                    (string) $category['score']
                );

                $domainRowIndex = $rowIndex;

                foreach ($category['domains'] as $domain) {
                    $domainRows = count($domain['dimensions']);
                    $domainHeight = $domainRows * $rowH;
                    $domainLevel = $this->classifyNom035Score('domains', $domain['name'], (int) $domain['score']);
                    $domainHex = $this->getRiskHexByLevel($domainLevel['key']);
                    [$dr, $dg, $db] = $this->hexToRgb($domainHex);
                    $domainColor = imagecolorallocate($image, $dr, $dg, $db);

                    imagefilledrectangle($image, $xDom, $currentY + ($domainRowIndex * $rowH), $xDom + $wDomName, $currentY + ($domainRowIndex * $rowH) + $domainHeight, $white);
                    imagerectangle($image, $xDom, $currentY + ($domainRowIndex * $rowH), $xDom + $wDomName, $currentY + ($domainRowIndex * $rowH) + $domainHeight, $border);

                    imagefilledrectangle($image, $xDom + $wDomName, $currentY + ($domainRowIndex * $rowH), $xDom + $wDomName + $wDomScore, $currentY + ($domainRowIndex * $rowH) + $domainHeight, $domainColor);
                    imagerectangle($image, $xDom + $wDomName, $currentY + ($domainRowIndex * $rowH), $xDom + $wDomName + $wDomScore, $currentY + ($domainRowIndex * $rowH) + $domainHeight, $border);

                    $this->drawChartTextCenteredBold(
                        $image,
                        14,
                        $xDom,
                        $currentY + ($domainRowIndex * $rowH),
                        $xDom + $wDomName,
                        $currentY + ($domainRowIndex * $rowH) + $domainHeight,
                        $text,
                        $domain['name']
                    );

                    $this->drawChartTextCenteredBold(
                        $image,
                        14,
                        $xDom + $wDomName,
                        $currentY + ($domainRowIndex * $rowH),
                        $xDom + $wDomName + $wDomScore,
                        $currentY + ($domainRowIndex * $rowH) + $domainHeight,
                        $domainHex === $yellowHex ? $text : $white,
                        (string) $domain['score']
                    );

                    foreach ($domain['dimensions'] as $dimension) {
                        $y1 = $currentY + ($rowIndex * $rowH);
                        $y2 = $y1 + $rowH;

                        $dimensionLevel = $this->classifyNom035Score('dimensions', $dimension['name'], (int) $dimension['score']);
                        $dimensionHex = $this->getRiskHexByLevel($dimensionLevel['key']);
                        [$ir, $ig, $ib] = $this->hexToRgb($dimensionHex);
                        $dimensionColor = imagecolorallocate($image, $ir, $ig, $ib);

                        imagefilledrectangle($image, $xDim, $y1, $xDim + $wDimName, $y2, $white);
                        imagerectangle($image, $xDim, $y1, $xDim + $wDimName, $y2, $border);

                        imagefilledrectangle($image, $xDim + $wDimName, $y1, $xDim + $wDimName + $wDimScore, $y2, $dimensionColor);
                        imagerectangle($image, $xDim + $wDimName, $y1, $xDim + $wDimName + $wDimScore, $y2, $border);

                        $this->drawChartText($image, 12, $xDim + 10, $y1 + 34, $text, $dimension['name']);
                        $this->drawChartTextCenteredBold(
                            $image,
                            13,
                            $xDim + $wDimName,
                            $y1,
                            $xDim + $wDimName + $wDimScore,
                            $y2,
                            $dimensionHex === $yellowHex ? $text : $white,
                            (string) $dimension['score']
                        );

                        $itemX = $xItems + 10;
                        foreach ($dimension['items'] as $item) {
                            $itemHex = $this->getQuestionValueHex((int) $item['score']);
                            [$qr, $qg, $qb] = $this->hexToRgb($itemHex);
                            $itemColor = imagecolorallocate($image, $qr, $qg, $qb);

                            imagefilledrectangle($image, $itemX, $y1 + 6, $itemX + $itemBoxW, $y2 - 6, $itemColor);
                            imagerectangle($image, $itemX, $y1 + 6, $itemX + $itemBoxW, $y2 - 6, $border);

                            $itemTextColor = $itemHex === $yellowHex ? $text : $white;
                            $this->drawChartTextCenteredBold(
                                $image,
                                12,
                                $itemX,
                                $y1 + 6,
                                $itemX + $itemBoxW,
                                $y2 - 6,
                                $itemTextColor,
                                (string) $item['number']
                            );

                            $itemX += $itemBoxW + $itemGap;
                        }

                        if (! empty($dimension['note'])) {
                            imagefilledrectangle($image, $itemX + 8, $y1 + 8, $itemX + 150, $y2 - 8, $gray);
                            imagerectangle($image, $itemX + 8, $y1 + 8, $itemX + 150, $y2 - 8, $border);
                            $this->drawChartText($image, 12, $itemX + 18, $y1 + 34, $text, $dimension['note']);
                        }

                        $rowIndex++;
                    }

                    $domainRowIndex += $domainRows;
                }
            }

            $bottomY = $currentY + ($rowCount * $rowH) + 60;

            $globalLevel = $this->classifyNom035Score('global', null, (int) $summary['final_total']);
            $globalHex = $this->getRiskHexByLevel($globalLevel['key']);
            [$fr, $fg, $fb] = $this->hexToRgb($globalHex);
            $finalColor = imagecolorallocate($image, $fr, $fg, $fb);

            imagefilledrectangle($image, 60, $bottomY, 650, $bottomY + 90, $finalColor);
            imagerectangle($image, 60, $bottomY, 650, $bottomY + 90, $border);
            $finalTextColor = $globalHex === $yellowHex ? $text : $white;
            $this->drawChartTextCenteredBold(
                $image,
                16,
                60,
                $bottomY,
                650,
                $bottomY + 90,
                $finalTextColor,
                'Calificación Final Total'
            );
            $this->drawChartTextCenteredBold(
                $image,
                15,
                60,
                $bottomY + 30,
                650,
                $bottomY + 90,
                $finalTextColor,
                $summary['final_total'] . ' / 288 - ' . number_format($summary['final_percentage'], 2) . ' %'
            );

            imagefilledrectangle($image, 700, $bottomY, 1260, $bottomY + 90, $gray);
            imagerectangle($image, 700, $bottomY, 1260, $bottomY + 90, $border);
            $this->drawChartTextCenteredBold(
                $image,
                16,
                700,
                $bottomY,
                1260,
                $bottomY + 90,
                $text,
                $summary['participants'] . ' Participantes'
            );

            $legendY = $bottomY + 130;
            $this->drawChartText($image, 13, 60, $legendY - 14, $text, 'Nivel de riesgo');

            $riskLegend = [
                ['Nulo', $blue],
                ['Bajo', $green],
                ['Medio', $yellow],
                ['Alto', $orange],
                ['Muy Alto', $red],
            ];

            $legendX = 60;
            foreach ($riskLegend as $i => [$label, $fill]) {
                $x1 = $legendX + ($i * 165);
                $x2 = $x1 + 155;

                imagefilledrectangle($image, $x1, $legendY, $x2, $legendY + 40, $fill);
                imagerectangle($image, $x1, $legendY, $x2, $legendY + 40, $border);

                $textColor = $label === 'Medio' ? $text : $white;
                $this->drawChartTextCenteredBold($image, 13, $x1, $legendY, $x2, $legendY + 40, $textColor, $label);
            }

            $valueLegendX = 1320;
            $this->drawChartText($image, 13, $valueLegendX, $legendY - 14, $text, 'Valor de la pregunta según el color');

            $valueLegend = [
                [0, $blue],
                [1, $green],
                [2, $yellow],
                [3, $orange],
                [4, $red],
            ];

            foreach ($valueLegend as $i => [$value, $fill]) {
                $x1 = $valueLegendX + ($i * 58);
                $x2 = $x1 + 52;

                imagefilledrectangle($image, $x1, $legendY, $x2, $legendY + 40, $fill);
                imagerectangle($image, $x1, $legendY, $x2, $legendY + 40, $border);

                $textColor = $value === 2 ? $text : $white;
                $this->drawChartTextCenteredBold($image, 13, $x1, $legendY, $x2, $legendY + 40, $textColor, (string) $value);
            }

            imagefilledrectangle($image, $valueLegendX, $legendY + 60, $valueLegendX + 88, $legendY + 100, $gray);
            imagerectangle($image, $valueLegendX, $legendY + 60, $valueLegendX + 88, $legendY + 100, $border);
            $this->drawChartTextCenteredBold($image, 12, $valueLegendX, $legendY + 60, $valueLegendX + 88, $legendY + 100, $text, '*a');

            $this->drawChartText($image, 12, $valueLegendX + 105, $legendY + 87, $text, 'Servicio a clientes o usuarios');

            imagefilledrectangle($image, $valueLegendX, $legendY + 115, $valueLegendX + 88, $legendY + 155, $gray);
            imagerectangle($image, $valueLegendX, $legendY + 115, $valueLegendX + 88, $legendY + 155, $border);
            $this->drawChartTextCenteredBold($image, 12, $valueLegendX, $legendY + 115, $valueLegendX + 88, $legendY + 155, $text, '*b');

            $this->drawChartText($image, 12, $valueLegendX + 105, $legendY + 142, $text, 'Soy jefe de otros trabajadores');

            imagepng($image, $outputPath);
            imagedestroy($image);

            return $outputPath;
        }

    private function renderHorizontalBarChart(
        string $title,
        array $labels,
        array $values,
        string $outputPath,
        ?float $forcedMaxValue = null,
        string $suffix = '',
        ?array $hexColors = null
    ): ?string {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $count = count($labels);

        if ($count === 0) {
            return null;
        }

        $chartDir = dirname($outputPath);

        if (! is_dir($chartDir)) {
            mkdir($chartDir, 0755, true);
        }

        $width = 1280;
        $rowHeight = 72;
        $topPadding = 130;
        $bottomPadding = 80;
        $leftPadding = 430;
        $rightPadding = 130;
        $height = $topPadding + ($count * $rowHeight) + $bottomPadding;

        $image = imagecreatetruecolor($width, $height);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $pageBg = imagecolorallocate($image, 245, 247, 251);
        $panelBg = imagecolorallocate($image, 255, 255, 255);
        $panelBorder = imagecolorallocate($image, 226, 232, 240);
        $titleColor = imagecolorallocate($image, 15, 23, 42);
        $textColor = imagecolorallocate($image, 51, 65, 85);
        $mutedColor = imagecolorallocate($image, 100, 116, 139);
        $gridColor = imagecolorallocate($image, 226, 232, 240);
        $trackColor = imagecolorallocate($image, 241, 245, 249);
        $defaultBarColor = imagecolorallocate($image, 37, 99, 235);

        imagefill($image, 0, 0, $pageBg);

        imagefilledrectangle($image, 18, 18, $width - 18, $height - 18, $panelBg);
        imagerectangle($image, 18, 18, $width - 18, $height - 18, $panelBorder);

        $this->drawChartText($image, 18, 36, 46, $titleColor, $title);

        $subtitle = $forcedMaxValue === 100
            ? 'Promedio porcentual por apartado'
            : 'Distribución acumulada por nivel';

        $this->drawChartText($image, 11, 36, 74, $mutedColor, $subtitle);

        $maxValue = $forcedMaxValue ?? max($values);
        $maxValue = $maxValue > 0 ? $maxValue : 1;

        $barAreaWidth = $width - $leftPadding - $rightPadding;
        $tickCount = 4;

        for ($i = 0; $i <= $tickCount; $i++) {
            $x = $leftPadding + (int) round(($i / $tickCount) * $barAreaWidth);

            imageline($image, $x, $topPadding - 8, $x, $height - $bottomPadding + 8, $gridColor);

            $tickValue = ($maxValue / $tickCount) * $i;
            $tickLabel = rtrim(rtrim(number_format($tickValue, 0, '.', ''), '0'), '.');
            if ($tickLabel === '') {
                $tickLabel = '0';
            }

            $this->drawChartText($image, 9, max($x - 8, $leftPadding - 8), $height - $bottomPadding + 28, $mutedColor, $tickLabel . $suffix);
        }

        $palette = [
            '2563EB',
            '14B8A6',
            'EAB308',
            'F97316',
            '8B5CF6',
            '06B6D4',
            '10B981',
            'EF4444',
            '6366F1',
            '84CC16',
        ];

        for ($i = 0; $i < $count; $i++) {
            $rowY = $topPadding + ($i * $rowHeight);
            $barTop = $rowY + 18;
            $barBottom = $barTop + 24;

            $wrapped = explode("\n", wordwrap((string) $labels[$i], 26, "\n", true));
            $labelLine1 = mb_substr($wrapped[0] ?? '', 0, 34);
            $labelLine2 = mb_substr($wrapped[1] ?? '', 0, 34);

            $this->drawChartText($image, 10, 36, $barTop + 4, $textColor, $labelLine1);

            if ($labelLine2 !== '') {
                $this->drawChartText($image, 9, 36, $barTop + 22, $mutedColor, $labelLine2);
            }

            imagefilledrectangle($image, $leftPadding, $barTop, $width - $rightPadding, $barBottom, $trackColor);
            imagerectangle($image, $leftPadding, $barTop, $width - $rightPadding, $barBottom, $gridColor);

            $value = (float) $values[$i];
            $barWidth = (int) round(($value / $maxValue) * $barAreaWidth);

            if (is_array($hexColors) && isset($hexColors[$i])) {
                [$r, $g, $b] = $this->hexToRgb($hexColors[$i]);
            } else {
                [$r, $g, $b] = $this->hexToRgb($palette[$i % count($palette)]);
            }

            $barColor = imagecolorallocate($image, $r, $g, $b);

            imagefilledrectangle(
                $image,
                $leftPadding,
                $barTop,
                $leftPadding + max($barWidth, 2),
                $barBottom,
                $barColor
            );

            $displayValue = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
            $valueX = min($leftPadding + $barWidth + 12, $width - 100);

            $this->drawChartText($image, 9, $valueX, $barTop + 16, $mutedColor, $displayValue . $suffix);
        }

        imagepng($image, $outputPath);
        imagedestroy($image);

        return $outputPath;
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

    private function chartText(string $text): string
        {
            return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
        }

    private function addInfoRow($table, string $label, ?string $value): void
        {
            $table->addRow();

            $table->addCell(2600, ['bgColor' => 'D9D9D9'])->addText(
                $this->safeValue($label),
                ['bold' => true, 'size' => 10, 'color' => '111827'],
                ['spaceAfter' => 0]
            );

            $table->addCell(7000)->addText(
                $this->safeValue($value),
                ['size' => 10, 'color' => '111827'],
                ['spaceAfter' => 0]
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