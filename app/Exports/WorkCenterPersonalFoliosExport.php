<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkCenterPersonalFoliosExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly Organization $organization,
        private readonly ?string $workCenterId = null,
    ) {}

    public function collection(): Collection
    {
        $evaluations = PaperEvaluation::query()
            ->where('organization_id', $this->organization->id)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->whereNotNull('work_center_id')
            ->whereNotNull('personal_folio')
            ->with('workCenter:id,name')
            ->when($this->workCenterId !== null, function ($query): void {
                $query->where('work_center_id', $this->workCenterId);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return $evaluations
            ->groupBy(fn (PaperEvaluation $evaluation) => $evaluation->work_center_id.'|'.$evaluation->personal_folio.'|'.$evaluation->source)
            ->map(fn (Collection $group) => $group->first())
            ->values();
    }

    public function headings(): array
    {
        return [
            'Centro de trabajo',
            'ID Centro de trabajo',
            'Folio Personal',
            'Source',
            'Nombre',
        ];
    }

    public function map($evaluation): array
    {
        return [
            $evaluation->workCenter?->name ?? 'Sin centro',
            $evaluation->work_center_id,
            $evaluation->personal_folio,
            $evaluation->source,
            $evaluation->evaluee_name ?? '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0F766E'],
                ],
            ],
        ];
    }
}
