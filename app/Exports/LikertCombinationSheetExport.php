<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LikertCombinationSheetExport implements FromArray, WithHeadings, WithStrictNullComparison, WithStyles, WithTitle
{
    /**
     * @param  array<int, array<int, mixed>>  $data
     * @param  array<int, string>  $customFieldHeaders
     * @param  array<int, string>  $factorHeaders
     */
    public function __construct(
        protected array $data,
        protected string $sheetTitle,
        protected array $customFieldHeaders = [],
        protected array $factorHeaders = []
    ) {}

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->data;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $baseHeadings = [
            'Folio',
            'Nombre',
            'Género',
            'Tipo de Contrato',
            'Puesto',
            'Área',
            'Turno',
        ];

        // Add custom field headers
        $headings = array_merge($baseHeadings, $this->customFieldHeaders);

        // Add factor headers with score and level columns
        foreach ($this->factorHeaders as $factor) {
            $headings[] = $factor.' (Puntaje)';
            $headings[] = $factor.' (Nivel)';
        }

        // Add comments column
        $headings[] = 'Comentarios';

        return $headings;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Sheet title (max 31 chars for Excel)
     */
    public function title(): string
    {
        // Excel sheet names max 31 chars
        $title = $this->sheetTitle;
        if (strlen($title) > 31) {
            $title = substr($title, 0, 28).'...';
        }

        // Remove invalid characters for sheet names
        $title = preg_replace('/[\[\]\*\?\/\\\\:]/', '', $title);

        return $title ?: 'Hoja';
    }
}
