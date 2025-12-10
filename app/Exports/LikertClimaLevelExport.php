<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LikertClimaLevelExport implements FromArray, WithHeadings, WithStrictNullComparison, WithStyles
{
    /**
     * @param  array<int, array<int, mixed>>  $data
     * @param  array<int, string>  $customFieldHeaders
     */
    public function __construct(
        protected array $data,
        protected array $customFieldHeaders = []
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
            'Nivel de Clima Laboral',
            'Puntaje Total',
            'Género',
            'Tipo de Contrato',
            'Puesto',
            'Área',
            'Turno',
        ];

        return array_merge($baseHeadings, $this->customFieldHeaders);
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
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
