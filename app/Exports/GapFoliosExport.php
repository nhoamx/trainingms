<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GapFoliosExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * @param  array<int, array{batch_name: string, batch_type: string, folios: array<string>, count: int}>  $missingFolios
     */
    public function __construct(protected array $missingFolios) {}

    /**
     * Return data as array for Excel export
     *
     * @return array<int, array<string>>
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->missingFolios as $batch) {
            foreach ($batch['folios'] as $folio) {
                $rows[] = [
                    $batch['batch_name'],
                    $batch['batch_type'] === 'presencial' ? 'Presencial' : 'En línea',
                    $folio,
                ];
            }
        }

        return $rows;
    }

    /**
     * Headings for Excel file
     *
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            'Lote',
            'Tipo',
            'Folio Faltante',
        ];
    }

    /**
     * Styles for Excel worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
