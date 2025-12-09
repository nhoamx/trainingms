<?php

namespace App\Services;

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
            'logo' => $organization->logo,
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
     * @return array Datos demográficos detallados para filtrado
     */
    public function getDemographicDetails(Organization $organization): array
    {
        // Obtener todas las evaluaciones de la organización con datos demográficos
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->with('demographicData')
            ->get();

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
     * @return array Datos consolidados del dashboard
     */
    public function getDashboardData(Organization $organization): array
    {
        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo,
            ],
            'company_data' => $this->getCompanyData($organization),
            'demographic_summary' => $this->getDemographicSummary($organization),
            'demographic_details' => $this->getDemographicDetails($organization),
        ];
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
