<?php

namespace App\Exports;

use App\Models\Asset;
use App\Models\Organization;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetsTemplateExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Organization $organization, protected bool $includeExisting = true) {}

    public function collection()
    {
        if ($this->includeExisting) {
            return Asset::where('organization_id', $this->organization->id)
                ->where('asset_category', 'extintor')
                ->orderBy('consecutive_number')
                ->get();
        }

        return collect([]);
    }

    public function headings(): array
    {
        return [
            'Número Consecutivo',
            'Número de Serie',
            'Ubicación',
            'Capacidad',
            'Tipo de Extintor',
            'Clase de Fuego',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->consecutive_number ?? '',
            $asset->serial_number ?? '',
            $asset->location ?? '',
            $asset->capacity ?? '',
            $asset->asset_type ?? '',
            $asset->fire_class ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
