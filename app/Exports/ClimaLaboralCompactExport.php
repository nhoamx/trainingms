<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClimaLaboralCompactExport implements FromArray, WithHeadings, WithStrictNullComparison, WithStyles
{
    /**
     * @param  array<int, array<int, mixed>>  $data
     */
    public function __construct(
        protected array $data
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
        return [
            'Folio',
            'Nombre del Evaluado',
            'Puntaje',
            'Nivel',
            'Género',
            'Contratación',
            'Área',
            'Puesto',
            'Turno',
            'Línea',
            'Gerente',
            'Gerente de Producción',
            'Gerente de RH',
            'Supervisor',
            'Comentarios Adicionales',
            'Factor de Comentarios',
            'P1',
            'P2',
            'P3',
            'P4',
            'P5',
            'P6',
            'P7',
            'P8',
            'P9',
            'P10',
            'P11',
            'P12',
            'P13',
            'P14',
            'P15',
            'P16',
            'P17',
            'P18',
            'P19',
            'P20',
            'P21',
            'P22',
            'P23',
        ];
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
