<?php

namespace App\Exports;

use App\Models\Organization;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepartmentAreasExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Organization $organization) {}

    /**
     * Obtener todos los departamentos de la organización
     */
    public function collection()
    {
        return $this->organization->departmentAreas()
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
            'Nombre del Departamento',
        ];
    }

    /**
     * Mapear los datos a las columnas
     */
    public function map($area): array
    {
        return [
            $area->identifier,
            $area->name,
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
                    'startColor' => ['rgb' => '059669'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
