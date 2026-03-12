<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkCenterMetricsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  array<string, array{evaluated_people_count: int, men_count: int, women_count: int, requires_clinical_attention_count: int}>  $metrics
     */
    public function __construct(
        private readonly Collection $workCenters,
        private readonly array $metrics,
    ) {}

    public function collection(): Collection
    {
        return $this->workCenters;
    }

    public function headings(): array
    {
        return [
            'Centro de trabajo',
            'Total de personas evaluadas',
            'Total de hombres',
            'Total de mujeres',
            'Total de personas que requieren atencion medica',
        ];
    }

    public function map($workCenter): array
    {
        $metric = $this->metrics[$workCenter->id] ?? [
            'evaluated_people_count' => 0,
            'men_count' => 0,
            'women_count' => 0,
            'requires_clinical_attention_count' => 0,
        ];

        return [
            $workCenter->name,
            $metric['evaluated_people_count'],
            $metric['men_count'],
            $metric['women_count'],
            $metric['requires_clinical_attention_count'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2563EB'],
                ],
            ],
        ];
    }
}
