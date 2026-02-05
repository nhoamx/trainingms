<?php

namespace App\Exports;

use App\Models\Organization;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkCentersExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Organization $organization) {}

    /**
     * Obtener todos los centros de trabajo de la organización (excepto el primario)
     */
    public function collection()
    {
        return $this->organization->workCenters()
            ->where('is_primary', false)
            ->orderBy('code')
            ->get();
    }

    /**
     * Cabeceras del archivo Excel
     */
    public function headings(): array
    {
        return [
            'Código',
            'Nombre',
            'Tipo',
            'Razón Social',
            'RFC',
            'Registro Patronal',
            'Calle y Número',
            'Colonia',
            'Código Postal',
            'Municipio',
            'Estado',
            'Teléfono',
            'Email',
            'Notas',
        ];
    }

    /**
     * Mapear los datos a las columnas
     */
    public function map($workCenter): array
    {
        // Mapear el enum al nombre en español
        $typeLabel = match ($workCenter->type->value) {
            'headquarters' => 'Matriz',
            'plant' => 'Planta',
            'branch' => 'Sucursal',
            'warehouse' => 'Almacén',
            'office' => 'Oficina',
            'other' => 'Otro',
            default => $workCenter->type->value,
        };

        return [
            $workCenter->code,
            $workCenter->name,
            $typeLabel,
            $workCenter->legal_name,
            $workCenter->tax_id,
            $workCenter->employer_registration,
            $workCenter->street_address,
            $workCenter->neighborhood,
            $workCenter->postal_code,
            $workCenter->municipality,
            $workCenter->state,
            $workCenter->phone,
            $workCenter->email,
            $workCenter->notes,
        ];
    }

    /**
     * Estilos para la hoja de Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(10); // Código
        $sheet->getColumnDimension('B')->setWidth(30); // Nombre
        $sheet->getColumnDimension('C')->setWidth(15); // Tipo
        $sheet->getColumnDimension('D')->setWidth(30); // Razón Social
        $sheet->getColumnDimension('E')->setWidth(15); // RFC
        $sheet->getColumnDimension('F')->setWidth(20); // Registro Patronal
        $sheet->getColumnDimension('G')->setWidth(30); // Calle y Número
        $sheet->getColumnDimension('H')->setWidth(20); // Colonia
        $sheet->getColumnDimension('I')->setWidth(12); // Código Postal
        $sheet->getColumnDimension('J')->setWidth(20); // Municipio
        $sheet->getColumnDimension('K')->setWidth(20); // Estado
        $sheet->getColumnDimension('L')->setWidth(15); // Teléfono
        $sheet->getColumnDimension('M')->setWidth(25); // Email
        $sheet->getColumnDimension('N')->setWidth(40); // Notas

        return [
            // Estilo para la fila de encabezados
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'], // Verde (color de centros de trabajo)
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
