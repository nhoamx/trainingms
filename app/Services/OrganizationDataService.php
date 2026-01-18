<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;

class OrganizationDataService
{
    /**
     * Obtiene los datos de empresa de una organización
     *
     * @param  Organization  $organization  La organización
     * @return array Datos de empresa formateados
     */
    public function getCompanyData(Organization $organization): array
    {
        return [
            'logo' => $organization->logo ? asset('storage/'.$organization->logo) : null,
            'general' => [
                'name' => $organization->name,
                'razon_social' => $organization->razon_social,
                'rfc' => $organization->rfc,
                'registro_patronal' => $organization->registro_patronal,
                'actividad_principal' => $organization->actividad_principal,
                'folio_organization' => $organization->folio_organization,
            ],
            'address' => [
                'calle_numero' => $organization->calle_numero,
                'colonia' => $organization->colonia,
                'codigo_postal' => $organization->codigo_postal,
                'municipio' => $organization->municipio,
                'estado' => $organization->estado,
            ],
            'contact' => [
                'nombre' => $organization->contacto_nombre,
                'puesto' => $organization->contacto_puesto,
                'email' => $organization->contacto_email,
                'movil' => $organization->contacto_movil,
            ],
            'responsible' => [
                'nombre' => $organization->responsable_nombre,
                'puesto' => $organization->responsable_puesto,
                'email' => $organization->responsable_email,
                'movil' => $organization->responsable_movil,
            ],
            'workforce' => [
                'total_trabajadores' => $organization->total_trabajadores,
                'total_hombres' => $organization->total_hombres,
                'total_mujeres' => $organization->total_mujeres,
            ],
            'sample' => [
                'muestra_aplicada' => $organization->muestra_aplicada,
                'muestra_hombres' => $organization->muestra_hombres,
                'muestra_mujeres' => $organization->muestra_mujeres,
                'justificacion_muestra' => $organization->justificacion_muestra,
            ],
            'evaluation_date' => $organization->fecha_aplicacion,
            'committee' => [
                'comite_integrantes' => $organization->comite_integrantes,
                'comite_hombres' => $organization->comite_hombres,
                'comite_mujeres' => $organization->comite_mujeres,
            ],
        ];
    }

    /**
     * Obtiene un resumen de datos demográficos de la organización
     *
     * @param  Organization  $organization  La organización
     * @return array Resumen de datos demográficos con conteos por categoría
     */
    public function getDemographicSummary(Organization $organization): array
    {
        // Obtener todos los datos demográficos de la organización con eager loading
        $demographicData = DemographicData::whereHas('paperEvaluation', function ($query) use ($organization) {
            $query->where('organization_id', $organization->id);
        })
            ->with('paperEvaluation')
            ->get();

        $summary = [
            'total_records' => $demographicData->count(),
            'gender' => $this->countByField($demographicData, 'gender'),
            'age' => $this->countByField($demographicData, 'age'),
            'marital_status' => $this->countByField($demographicData, 'marital_status'),
            'education_level' => $this->countByField($demographicData, 'education_level'),
            'position_type' => $this->countByField($demographicData, 'position_type'),
            'contract_type' => $this->countByField($demographicData, 'contract_type'),
            'personnel_type' => $this->countByField($demographicData, 'personnel_type'),
            'work_schedule' => $this->countByField($demographicData, 'work_schedule'),
        ];

        return $summary;
    }

    /**
     * Obtiene datos demográficos detallados para filtrado
     *
     * @param  Organization  $organization  La organización
     * @param  string|null  $evaluationType  Tipo de evaluación a filtrar (null = todos)
     * @return array Datos demográficos detallados para filtrado
     */
    public function getDemographicDetails(Organization $organization, ?string $evaluationType = 'likert'): array
    {
        // Determinar qué tipos de evaluación incluir
        $query = PaperEvaluation::where('organization_id', $organization->id)
            ->where('processing_status', 'completed')
            ->with('demographicData');

        if ($evaluationType === 'likert') {
            // Solo evaluaciones Likert (Clima Laboral)
            $query->where('evaluation_type', 'likert');
        } elseif ($evaluationType === 'nom035') {
            // Evaluaciones NOM-035 (Referencia I, III, Cisneros)
            $query->whereIn('evaluation_type', ['referencia_i', 'referencia_iii', 'cisneros']);
        }
        // Si es null, obtener todas las evaluaciones completadas

        $evaluations = $query->get();

        // Extraer valores únicos de cada campo demográfico
        $genders = [];
        $contractTypes = [];
        $positions = [];
        $areas = [];
        $shifts = [];

        foreach ($evaluations as $evaluation) {
            $demo = $evaluation->demographicData;
            if ($demo) {
                if ($demo->gender) {
                    $genders[$demo->gender] = true;
                }
                if ($demo->contract_type) {
                    $contractTypes[$demo->contract_type] = true;
                }
                if ($demo->position) {
                    $positions[$demo->position] = true;
                }
                if ($demo->department) {
                    $areas[$demo->department] = true;
                }
                if ($demo->work_schedule) {
                    $shifts[$demo->work_schedule] = true;
                }
            }
        }

        return [
            'genders' => array_keys($genders),
            'contract_types' => array_keys($contractTypes),
            'positions' => array_keys($positions),
            'areas' => array_keys($areas),
            'shifts' => array_keys($shifts),
            'total_evaluations' => $evaluations->count(),
        ];
    }

    /**
     * Obtiene todos los datos del dashboard de la organización
     *
     * @param  Organization  $organization  La organización
     * @param  string|null  $evaluationType  Tipo de evaluación para datos demográficos ('likert', 'nom035', null=todos)
     * @return array Datos consolidados del dashboard
     */
    public function getDashboardData(Organization $organization, ?string $evaluationType = 'likert'): array
    {
        $data = [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo ? asset('storage/'.$organization->logo) : null,
            ],
            'company_data' => $this->getCompanyData($organization),
            'demographic_summary' => $this->getDemographicSummary($organization),
            'demographic_details' => $this->getDemographicDetails($organization, $evaluationType),
        ];

        // Solo para NOM-002: incluir inventario de activos (extintores)
        if ($evaluationType === 'nom002') {
            $data['assets'] = $this->getAssetsData($organization);
        }

        return $data;
    }

    /**
     * Obtiene el inventario de activos (extintores) con su estado y enlace al reporte/inspección
     */
    protected function getAssetsData(Organization $organization): array
    {
        $assets = $organization->assets()
            ->where('asset_category', 'extintor')
            ->with(['inspections' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderByRaw('CAST(consecutive_number AS UNSIGNED)')
            ->get();

        return $assets->map(function (Asset $asset) {
            $latestInspection = $asset->inspections->first();

            return [
                'id' => $asset->id,
                'location' => $asset->location ?? 'Sin ubicación',
                'consecutive_number' => $asset->consecutive_number,
                'type' => $asset->asset_type ?? $asset->fire_class ?? 'Extintor',
                'status' => $latestInspection ? 'Completado' : 'Pendiente',
                'reportUrl' => route('assets.inspect', $asset),
            ];
        })->toArray();
    }

    /**
     * Cuenta los registros agrupados por un campo específico
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $collection  Colección de datos demográficos
     * @param  string  $field  Campo a agrupar
     * @return array Conteos agrupados
     */
    private function countByField($collection, string $field): array
    {
        return $collection->groupBy($field)
            ->map(fn ($group) => $group->count())
            ->toArray();
    }
}
