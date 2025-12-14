<?php

namespace App\Exports;

use App\Models\PaperEvaluation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LikertAnswersExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles
{
    protected string $organizationId;

    protected string $organizationName;

    public function __construct(string $organizationId, string $organizationName)
    {
        $this->organizationId = $organizationId;
        $this->organizationName = $organizationName;
    }

    /**
     * @return \Illuminate\Support\Collection<int, PaperEvaluation>
     */
    public function collection(): \Illuminate\Support\Collection
    {
        return PaperEvaluation::with(['demographicData', 'customFields'])
            ->where('organization_id', $this->organizationId)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->orderBy('folio')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headings = [
            'Folio',
            'Nombre Organización',
            'Género',
            'Tipo de Contrato',
            'Puesto',
            'Área',
            'Turno',
            'Número',
            'Código de Línea',
            'Líder de Línea',
            'Supervisor',
            'Superintendente',
            'Gerente',
        ];

        // Add 23 question columns
        for ($i = 1; $i <= 23; $i++) {
            $headings[] = "P{$i}";
        }

        return $headings;
    }

    /**
     * @param  PaperEvaluation  $evaluation
     * @return array<int, mixed>
     */
    public function map($evaluation): array
    {
        $likertData = $evaluation->likert_answers ?? [];
        $customFields = $evaluation->customFields->keyBy('field_key');
        $demographicData = $evaluation->demographicData;

        // Extract demographics from DemographicData model
        $genero = $demographicData?->gender ?? '';
        $tipoContrato = $demographicData?->contract_type ?? '';
        $turno = $demographicData?->work_schedule ?? '';
        $puesto = $demographicData?->position ?? '';
        $area = $demographicData?->department ?? '';

        // Get custom field values
        $numero = $customFields->get('numero')?->value ?? '';
        $linea = $customFields->get('linea')?->value ?? '';
        $liderLinea = $customFields->get('lider_de_linea')?->value ?? '';
        $supervisor = $customFields->get('supervisor')?->value ?? '';
        $superintendente = $customFields->get('superintendente')?->value ?? '';
        $gerente = $customFields->get('gerente')?->value ?? '';

        $row = [
            $evaluation->folio,
            $this->organizationName,
            $genero,
            $tipoContrato,
            $puesto,
            $area,
            $turno,
            $numero,
            $linea,
            $liderLinea,
            $supervisor,
            $superintendente,
            $gerente,
        ];

        // Add 23 question answers
        $questions = $likertData['questions'] ?? [];
        for ($i = 1; $i <= 23; $i++) {
            $row[] = $questions[(string) $i] ?? '';
        }

        return $row;
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
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D9488'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
