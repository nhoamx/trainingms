<?php

namespace App\Exports;

use App\Models\Organization;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OccupationPositionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Organization $organization) {}

    /**
     * Obtener todos los puestos de la organización
     */
    public function collection()
    {
        return $this->organization->occupationPositions()
            ->orderBy('identifier')
            ->get();
    }

    /**
     * Cabeceras del archivo Excel
     */
    public function headings(): array
    {
        return [
            'Identificador',
            'Nombre del Puesto',
        ];
    }

    /**
     * Mapear los datos a las columnas
     */
    public function map($position): array
    {
        return [
            $position->identifier,
            $position->name,
        ];
    }

    /**
     * Estilos para la hoja de Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la fila de encabezados
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
