<?php

namespace App\Exports;

use App\Models\PaperEvaluation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaperEvaluationsExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles
{
    protected $organizationId;

    protected $referenciaIIIQuestions;

    protected $referenciaIIIConditionalQuestions;

    protected $cisnerosQuestions;

    protected $referenciaVQuestions;

    public function __construct($organizationId)
    {
        $this->organizationId = $organizationId;

        // Load configuration files
        $this->referenciaIIIQuestions = config('referencia_iii.general', []);
        $this->referenciaIIIConditionalQuestions = config('referencia_iii.conditional', []);
        $this->cisnerosQuestions = config('escala_cisneros', []);
        $this->referenciaVQuestions = config('referencia_v.datos_laborales', []);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return PaperEvaluation::with('organization')
            ->where('organization_id', $this->organizationId)
            ->where('processing_status', 'completed')
            ->orderBy('folio')
            ->get();
    }

    /**
     * Define the headings for the Excel file
     */
    public function headings(): array
    {
        $headings = [
            // Basic folio information
            'Folio Completo',
            'Código de Compañía',
            'Folio Personal',
            'Nombre del Evaluado',
            'Tipo de Evaluación',
            'Estado de Procesamiento',
            'Fecha de Procesamiento',
        ];

        // Demographic data headings
        $headings = array_merge($headings, [
            'Sexo',
            'Edad',
            'Estado Civil',
            'Nivel de Estudios',
            'Ocupación/Puesto',
            'Departamento/Sección/Área',
            'Tipo de Puesto',
            'Tipo de Contratación',
            'Tipo de Personal',
            'Tipo de Jornada',
            'Rotación de Turnos',
            'Tiempo en Puesto Actual',
            'Tiempo de Experiencia Laboral',
        ]);

        // Referencia III - General questions
        foreach ($this->referenciaIIIQuestions as $key => $question) {
            $headings[] = "Ref III - P{$key}";
        }

        // Referencia III - Conditional questions
        foreach ($this->referenciaIIIConditionalQuestions as $key => $question) {
            $headings[] = "Ref III Opcional - P{$key}";
        }

        // CITSATS-S1 (Guide I - PTSD questions)
        $guideIQuestions = config('guide_i_questions', []);
        foreach ($guideIQuestions as $key => $question) {
            $questionNumber = str_replace('pregunta_', '', $key);
            $headings[] = "CITSATS-S1 - P{$questionNumber}";
        }

        // Escala Cisneros (Mobbing)
        foreach ($this->cisnerosQuestions as $key => $question) {
            $headings[] = "Cisneros - P{$key}";
        }

        return $headings;
    }

    /**
     * Map each row to the corresponding values
     */
    public function map($evaluation): array
    {
        $row = [
            // Basic folio information
            $evaluation->folio,
            $evaluation->organization_code,
            $evaluation->personal_folio,
            $evaluation->evaluee_name,
            $this->getEvaluationTypeName($evaluation->evaluation_type),
            $this->getStatusName($evaluation->processing_status),
            $evaluation->processed_at?->format('Y-m-d H:i:s'),
        ];

        // Demographic data
        $demographicData = $evaluation->demographic_data ?? [];
        $row = array_merge($row, [
            $demographicData['sexo'] ?? '',
            $demographicData['edad'] ?? '',
            $demographicData['estado_civil'] ?? '',
            $this->formatEducationLevel($demographicData['nivel_estudios'] ?? null),
            $demographicData['ocupacion_puesto'] ?? '',
            $demographicData['departamento_seccion_area'] ?? '',
            $demographicData['tipo_puesto'] ?? '',
            $demographicData['tipo_contratacion'] ?? '',
            $demographicData['tipo_personal'] ?? '',
            $demographicData['tipo_jornada'] ?? '',
            $demographicData['rotacion_turnos'] ?? '',
            $demographicData['tiempo_puesto_actual'] ?? '',
            $demographicData['tiempo_experiencia_laboral'] ?? '',
        ]);

        // Referencia III - General answers
        $referenciaIIIAnswers = $evaluation->referencia_iii_answers ?? [];
        foreach (array_keys($this->referenciaIIIQuestions) as $key) {
            $row[] = $referenciaIIIAnswers[$key] ?? '';
        }

        // Referencia III - Conditional answers
        $referenciaIIIConditional = $evaluation->referencia_iii_conditional ?? [];
        foreach (array_keys($this->referenciaIIIConditionalQuestions) as $key) {
            $row[] = $referenciaIIIConditional[$key] ?? '';
        }

        // CITSATS-S1 (Guide I)
        $citsatsAnswers = $evaluation->citsats_s1 ?? [];
        $guideIQuestions = config('guide_i_questions', []);
        foreach (array_keys($guideIQuestions) as $key) {
            $row[] = $citsatsAnswers[$key] ?? '';
        }

        // Escala Cisneros
        $cisnerosAnswers = $evaluation->cisneros_answers ?? [];
        foreach (array_keys($this->cisnerosQuestions) as $key) {
            $row[] = $cisnerosAnswers[$key] ?? '';
        }

        return $row;
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0070C0'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Get human-readable evaluation type name
     */
    protected function getEvaluationTypeName(?string $type): string
    {
        return match ($type) {
            'referencia_i' => 'Guía de Referencia I (PTSD)',
            'referencia_iii' => 'Guía de Referencia III',
            'referencia_v' => 'Guía de Referencia V',
            'cisneros' => 'Escala Cisneros (Mobbing)',
            default => $type ?? 'N/A',
        };
    }

    /**
     * Get human-readable status name
     */
    protected function getStatusName(?string $status): string
    {
        return match ($status) {
            'completed' => 'Completado',
            'pending' => 'Pendiente',
            'processing' => 'Procesando',
            'failed' => 'Fallido',
            default => $status ?? 'N/A',
        };
    }

    /**
     * Format education level from nested array structure
     */
    protected function formatEducationLevel($educationData): string
    {
        if (empty($educationData)) {
            return '';
        }

        if (is_string($educationData)) {
            return $educationData;
        }

        if (is_array($educationData)) {
            // Handle nested structure like ['Licenciatura' => 'Terminada']
            $level = array_key_first($educationData);
            $status = $educationData[$level];

            return $status ? "{$level} - {$status}" : $level;
        }

        return '';
    }
}
