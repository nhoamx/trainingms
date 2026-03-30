<?php

namespace App\Exports;

use App\Models\WorkCenter;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkCenterCommentsTemplateExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(protected WorkCenter $workCenter) {}

    /**
     * @return Collection<int, array{folio: string, comentario: string, factor: string}>
     */
    public function collection(): Collection
    {
        return collect([
            [
                'folio' => '00001',
                'comentario' => 'Comentario 1',
                'factor' => 'Entorno Laboral Seguro',
            ],
            [
                'folio' => '00001',
                'comentario' => 'Comentario 2',
                'factor' => 'Entorno Laboral Seguro',
            ],
            [
                'folio' => '00002',
                'comentario' => 'Comentario 1',
                'factor' => 'Participación de los Empleados',
            ],
            [
                'folio' => '00003',
                'comentario' => 'Comentario 1',
                'factor' => 'Reconocimiento y Recompensa',
            ],
            [
                'folio' => '00003',
                'comentario' => 'Comentario 2',
                'factor' => 'Reconocimiento y Recompensa',
            ],
            [
                'folio' => '00003',
                'comentario' => 'Comentario 3',
                'factor' => 'Reconocimiento y Recompensa',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Folio',
            'Comentario',
            'Factor',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setCellValue('E1', 'Instrucciones');
        $sheet->setCellValue('E2', '1) Usa una fila por comentario.');
        $sheet->setCellValue('E3', '2) Puedes repetir el mismo folio varias veces.');
        $sheet->setCellValue('E4', '3) Folio, Comentario y Factor son obligatorios.');
        $sheet->setCellValue('E5', '4) No necesitas incluir todos los folios.');
        $sheet->setCellValue('E6', '5) Conserva exactamente los encabezados de columnas.');

        return [
            1 => ['font' => ['bold' => true]],
            'E1:E6' => ['font' => ['bold' => true]],
        ];
    }
}
