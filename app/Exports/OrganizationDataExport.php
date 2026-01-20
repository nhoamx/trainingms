<?php

namespace App\Exports;

use App\Models\Organization;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrganizationDataExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Organization $organization) {}

    public function collection()
    {
        // Cargar la organización con sus direcciones
        $this->organization->load(['addresses' => function ($query) {
            $query->orderBy('type');
        }]);

        // Retornamos una colección con solo la organización
        return collect([$this->organization]);
    }

    public function headings(): array
    {
        return [
            'Nombre comercial',
            'Razón social',
            'RFC',
            'Registro patronal',
            'Actividad económica',
            'Número de trabajadores',
            'Hombres',
            'Mujeres',
            // Dirección Fiscal
            'Calle',
            'Número',
            'C.P.',
            'Colonia/ Parque industrial',
            'Estado',
            'Municipio',
            // Dirección Física
            'Calle',
            'Número',
            'C.P.',
            'Colonia/ Parque industrial',
            'Estado',
            'Municipio',
        ];
    }

    public function map($organization): array
    {
        // Obtener direcciones por tipo
        $fiscalAddress = $organization->addresses->firstWhere('type', 'fiscal');
        $fisicaAddress = $organization->addresses->firstWhere('type', 'fisica');

        // Separar calle_numero en calle y número para fiscal
        $fiscalCalle = '';
        $fiscalNumero = '';
        if ($fiscalAddress && $fiscalAddress->calle_numero) {
            $parts = $this->splitCalleNumero($fiscalAddress->calle_numero);
            $fiscalCalle = $parts['calle'];
            $fiscalNumero = $parts['numero'];
        }

        // Separar calle_numero en calle y número para física
        $fisicaCalle = '';
        $fisicaNumero = '';
        if ($fisicaAddress && $fisicaAddress->calle_numero) {
            $parts = $this->splitCalleNumero($fisicaAddress->calle_numero);
            $fisicaCalle = $parts['calle'];
            $fisicaNumero = $parts['numero'];
        }

        return [
            // Datos básicos de organización
            $organization->name,
            $organization->razon_social,
            $organization->rfc,
            $organization->registro_patronal,
            $organization->actividad_principal,
            $organization->total_trabajadores,
            $organization->total_hombres,
            $organization->total_mujeres,

            // Dirección Fiscal (cols 9-14)
            $fiscalCalle,
            $fiscalNumero,
            $fiscalAddress?->codigo_postal ?? '',
            $fiscalAddress?->colonia ?? '',
            $fiscalAddress?->estado ?? '',
            $fiscalAddress?->municipio ?? '',

            // Dirección Física (cols 15-20)
            $fisicaCalle,
            $fisicaNumero,
            $fisicaAddress?->codigo_postal ?? '',
            $fisicaAddress?->colonia ?? '',
            $fisicaAddress?->estado ?? '',
            $fisicaAddress?->municipio ?? '',
        ];
    }

    /**
     * Intenta separar calle_numero en calle y número
     * Si no hay número claro, retorna todo en calle
     */
    protected function splitCalleNumero(string $calleNumero): array
    {
        // Intentar encontrar el último número en la cadena
        if (preg_match('/^(.+?)\s+([0-9]+[A-Za-z]?)\s*$/', $calleNumero, $matches)) {
            return [
                'calle' => trim($matches[1]),
                'numero' => trim($matches[2]),
            ];
        }

        // Si no se puede separar, retornar todo como calle
        return [
            'calle' => $calleNumero,
            'numero' => '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6366F1'], // Indigo-500
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Dirección Fiscal columns (9-14) - Green background
            'I1:N1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'], // Green-500
                ],
            ],
            // Dirección Física columns (15-20) - Blue background
            'O1:T1' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '3B82F6'], // Blue-500
                ],
            ],
        ];
    }
}
