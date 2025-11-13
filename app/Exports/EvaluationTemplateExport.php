<?php

namespace App\Exports;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EvaluationTemplateExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Organization $organization) {}

    /**
     * Obtener todas las evaluaciones de la organización agrupadas por folio personal
     */
    public function collection()
    {
        // Get all completed evaluations for the organization
        return PaperEvaluation::where('organization_id', $this->organization->id)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->orderBy('personal_folio')
            ->get()
            ->groupBy('personal_folio')
            ->map(function ($evaluations) {
                // Get the first evaluation for personal_folio and evaluee_name
                $firstEvaluation = $evaluations->first();

                // Try to get demographic data from Referencia V
                $referenciaV = $evaluations->firstWhere('evaluation_type', 'referencia_v');
                $demographicData = $referenciaV?->demographic_data ?? [];

                // Determine if it's paper or online format
                $isPaperFormat = ! isset($demographicData['datos_laborales']);

                // Extract Puesto and Area based on format
                $puesto = '';
                $area = '';

                if ($isPaperFormat) {
                    // Paper format: direct fields (might be arrays with fila1, fila2)
                    $puestoData = $demographicData['ocupacion'] ?? '';
                    $areaData = $demographicData['departamento'] ?? '';

                    // If it's an array, extract fila1
                    $puesto = is_array($puestoData) ? ($puestoData['fila1'] ?? '') : $puestoData;
                    $area = is_array($areaData) ? ($areaData['fila1'] ?? '') : $areaData;
                } else {
                    // Online format: nested in datos_laborales (should be strings)
                    $puesto = $demographicData['datos_laborales']['ocupacion_puesto'] ?? '';
                    $area = $demographicData['datos_laborales']['departamento_seccion_area'] ?? '';
                }

                return [
                    'personal_folio' => $firstEvaluation->personal_folio,
                    'evaluee_name' => $firstEvaluation->evaluee_name ?? '',
                    'puesto' => $puesto,
                    'area' => $area,
                ];
            })
            ->values();
    }

    /**
     * Cabeceras del archivo Excel
     */
    public function headings(): array
    {
        return [
            'Folio Personal',
            'Nombre',
            'Puesto',
            'Area',
        ];
    }

    /**
     * Mapear los datos a las columnas
     */
    public function map($evaluation): array
    {
        return [
            $evaluation['personal_folio'],
            $evaluation['evaluee_name'],
            $evaluation['puesto'],
            $evaluation['area'],
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
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'E2E8F0',
                    ],
                ],
            ],
        ];
    }
}
