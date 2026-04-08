<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkCenterResponsesSheetExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @var array<int, string>
     */
    private const ATS_KEYS = ['1', '2', '3', '4', '5', '6'];

    /**
     * @var array<int, string>
     */
    private const REFERENCIA_I_KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'];

    /**
     * @var array<int, string>
     */
    private const REFERENCIA_V_KEYS = [
        'edad',
        'sexo',
        'estado_civil',
        'nivel_estudios',
        'tiempo_puesto_actual',
        'tiempo_experiencia_laboral',
        'tipo_de_puesto',
        'tipo_jornada',
        'tipo_personal',
        'rotacion_turnos',
        'ocupacion_puesto',
        'tipo_contratacion',
        'departamento_seccion_area',
    ];

    /**
     * @param  array{id: string, code: string, name: string, is_primary: bool, evaluations: array<int, array{folio: string, personal_folio: string, evaluee_name: string, source: string, referencia_iii: mixed, referencia_i_acontecimientos_traumaticos: mixed, referencia_i: mixed, referencia_v: mixed}>}  $workCenter
     */
    public function __construct(
        private readonly array $workCenter,
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $referenceIiiHeadings = array_map(
            fn (int $questionNumber): string => 'Guia III - Pregunta '.$questionNumber,
            range(1, 72)
        );

        $atsHeadings = array_map(
            fn (string $key): string => 'Acontecimiento traumatico '.$key,
            self::ATS_KEYS
        );

        $referenceIHeadings = array_map(
            fn (string $key): string => 'Guia I - Pregunta '.$key,
            self::REFERENCIA_I_KEYS
        );

        $referenceVHeadings = [
            'Edad',
            'Sexo',
            'Estado civil',
            'Nivel de estudios',
            'Tiempo en puesto actual',
            'Tiempo de experiencia laboral',
            'Tipo de puesto',
            'Tipo de jornada',
            'Tipo de personal',
            'Rotacion de turnos',
            'Ocupacion del puesto',
            'Tipo de contratacion',
            'Departamento / Seccion / Area',
        ];

        return array_merge([
            'Folio personal',
            'Folios de evaluacion',
            'Nombre evaluado',
            'Origen',
        ], $referenceVHeadings, $referenceIiiHeadings, $atsHeadings, $referenceIHeadings);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $groupedEvaluations = collect($this->workCenter['evaluations'] ?? [])
            ->groupBy(fn (array $evaluation): string => $this->buildPersonSourceKey($evaluation))
            ->map(fn ($group): array => $this->mergePersonEvaluations($group->all()))
            ->sortBy([
                ['source', 'asc'],
                ['personal_folio', 'asc'],
                ['folios_label', 'asc'],
            ])
            ->values();

        return $groupedEvaluations
            ->map(function (array $evaluation): array {
                $referenceIii = is_array($evaluation['referencia_iii'] ?? null) ? $evaluation['referencia_iii'] : [];
                $ats = is_array($evaluation['referencia_i_acontecimientos_traumaticos'] ?? null)
                    ? $evaluation['referencia_i_acontecimientos_traumaticos']
                    : [];
                $referenceI = is_array($evaluation['referencia_i'] ?? null) ? $evaluation['referencia_i'] : [];
                $referenceV = is_array($evaluation['referencia_v'] ?? null) ? $evaluation['referencia_v'] : [];
                $referenceIiiValues = [];
                $atsValues = [];
                $referenceIValues = [];
                $referenceVValues = [];

                foreach (range(1, 72) as $questionNumber) {
                    $prefixedKey = 'pregunta_'.$questionNumber;
                    $numericKey = (string) $questionNumber;

                    $referenceIiiValues[] = $referenceIii[$prefixedKey] ?? $referenceIii[$numericKey] ?? '';
                }

                foreach (self::ATS_KEYS as $key) {
                    $atsValues[] = $ats[$key] ?? '';
                }

                foreach (self::REFERENCIA_I_KEYS as $key) {
                    $referenceIValues[] = $referenceI[$key] ?? '';
                }

                foreach (self::REFERENCIA_V_KEYS as $key) {
                    $referenceVValues[] = $referenceV[$key] ?? '';
                }

                return array_merge([
                    $evaluation['personal_folio'] ?? '',
                    $evaluation['folios_label'] ?? '',
                    $evaluation['evaluee_name'] ?? '',
                    $this->mapSourceLabel((string) ($evaluation['source'] ?? '')),
                ], $referenceVValues, $referenceIiiValues, $atsValues, $referenceIValues);
            })
            ->all();
    }

    public function title(): string
    {
        $code = trim((string) ($this->workCenter['code'] ?? ''));
        $name = trim((string) ($this->workCenter['name'] ?? ''));

        $title = trim($code !== '' ? $code.' - '.$name : $name);
        $title = preg_replace('/[\[\]\*\?\/\\:]/', '', $title) ?? '';

        if (mb_strlen($title) > 31) {
            $title = mb_substr($title, 0, 31);
        }

        return $title !== '' ? $title : 'Centro';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = max(1, $sheet->getHighestRow());

        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$lastColumn}1");

        $headerRowRange = "A1:{$lastColumn}1";
        $sheet->getStyle($headerRowRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        // Header color blocks by section: base, Referencia V, Guia III, ATS, Guia I.
        $this->applyHeaderSectionColor($sheet, 1, 4, '1D4ED8');
        $this->applyHeaderSectionColor($sheet, 5, 17, '047857');
        $this->applyHeaderSectionColor($sheet, 18, 89, '2563EB');
        $this->applyHeaderSectionColor($sheet, 90, 95, 'B45309');
        $this->applyHeaderSectionColor($sheet, 96, 109, '7C3AED');

        if ($lastRow >= 2) {
            $contentRange = "A2:{$lastColumn}{$lastRow}";
            $sheet->getStyle($contentRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);

            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
                }
            }
        }

        return [];
    }

    private function applyHeaderSectionColor(Worksheet $sheet, int $startIndex, int $endIndex, string $color): void
    {
        $startColumn = Coordinate::stringFromColumnIndex($startIndex);
        $endColumn = Coordinate::stringFromColumnIndex($endIndex);

        $sheet->getStyle("{$startColumn}1:{$endColumn}1")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB($color);
    }

    /**
     * @param  array{folio?: mixed, personal_folio?: mixed, source?: mixed}  $evaluation
     */
    private function buildPersonSourceKey(array $evaluation): string
    {
        $personalFolio = trim((string) ($evaluation['personal_folio'] ?? ''));
        $folio = trim((string) ($evaluation['folio'] ?? ''));
        $source = trim((string) ($evaluation['source'] ?? ''));

        return ($personalFolio !== '' ? $personalFolio : $folio).'|'.$source;
    }

    /**
     * @param  array<int, array<string, mixed>>  $evaluations
     * @return array{personal_folio: string, folios_label: string, evaluee_name: string, source: string, referencia_iii: array<string, mixed>, referencia_i_acontecimientos_traumaticos: array<string, mixed>, referencia_i: array<string, mixed>, referencia_v: array<string, mixed>}
     */
    private function mergePersonEvaluations(array $evaluations): array
    {
        $personalFolio = '';
        $evalueeName = '';
        $source = '';
        $referenciaIii = [];
        $ats = [];
        $referenciaI = [];
        $referenciaV = [];
        $folios = [];

        foreach ($evaluations as $evaluation) {
            $folio = trim((string) ($evaluation['folio'] ?? ''));
            if ($folio !== '') {
                $folios[] = $folio;
            }

            if ($personalFolio === '') {
                $personalFolio = trim((string) ($evaluation['personal_folio'] ?? ''));
            }

            if ($evalueeName === '') {
                $evalueeName = trim((string) ($evaluation['evaluee_name'] ?? ''));
            }

            if ($source === '') {
                $source = trim((string) ($evaluation['source'] ?? ''));
            }

            $referenciaIii = $this->mergeNonEmptyValues(
                $referenciaIii,
                is_array($evaluation['referencia_iii'] ?? null) ? $evaluation['referencia_iii'] : []
            );

            $ats = $this->mergeNonEmptyValues(
                $ats,
                is_array($evaluation['referencia_i_acontecimientos_traumaticos'] ?? null) ? $evaluation['referencia_i_acontecimientos_traumaticos'] : []
            );

            $referenciaI = $this->mergeNonEmptyValues(
                $referenciaI,
                is_array($evaluation['referencia_i'] ?? null) ? $evaluation['referencia_i'] : []
            );

            $referenciaV = $this->mergeNonEmptyValues(
                $referenciaV,
                is_array($evaluation['referencia_v'] ?? null) ? $evaluation['referencia_v'] : []
            );
        }

        $folios = array_values(array_unique($folios));
        sort($folios);

        if ($personalFolio === '' && count($folios) > 0) {
            $personalFolio = $folios[0];
        }

        return [
            'personal_folio' => $personalFolio,
            'folios_label' => implode(', ', $folios),
            'evaluee_name' => $evalueeName,
            'source' => $source,
            'referencia_iii' => $referenciaIii,
            'referencia_i_acontecimientos_traumaticos' => $ats,
            'referencia_i' => $referenciaI,
            'referencia_v' => $referenciaV,
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeNonEmptyValues(array $current, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            $alreadyHasValue = array_key_exists($key, $current) && $current[$key] !== '' && $current[$key] !== null;

            if ($alreadyHasValue) {
                continue;
            }

            if ($value === '' || $value === null) {
                continue;
            }

            $current[$key] = $value;
        }

        return $current;
    }

    private function mapSourceLabel(string $source): string
    {
        return match (mb_strtolower(trim($source))) {
            'paper' => 'Presencial',
            'online' => 'En línea',
            default => $source,
        };
    }
}
