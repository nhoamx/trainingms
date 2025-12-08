<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluationCommentsTemplateExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Organization $organization) {}

    /**
     * Get all Likert evaluations with their comments for the organization
     */
    public function collection()
    {
        $data = [];

        $evaluations = PaperEvaluation::where('organization_id', $this->organization->id)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->with('comments')
            ->orderBy('personal_folio')
            ->get();

        foreach ($evaluations as $evaluation) {
            if ($evaluation->comments->isEmpty()) {
                // Add one row with empty comment for evaluations without comments
                $data[] = [
                    'folio' => $evaluation->personal_folio,
                    'comentario' => '',
                    'factor' => '',
                ];
            } else {
                // Add one row per existing comment
                foreach ($evaluation->comments as $comment) {
                    $data[] = [
                        'folio' => $evaluation->personal_folio,
                        'comentario' => $comment->comment,
                        'factor' => $comment->factor,
                    ];
                }
            }
        }

        return collect($data);
    }

    /**
     * Excel file headers
     */
    public function headings(): array
    {
        return [
            'Folio',
            'Comentario',
            'Factor',
        ];
    }

    /**
     * Map data to columns
     */
    public function map($row): array
    {
        return [
            $row['folio'],
            $row['comentario'],
            $row['factor'],
        ];
    }

    /**
     * Apply styling to the worksheet
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
