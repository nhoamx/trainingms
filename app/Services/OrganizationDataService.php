<?php

namespace App\Services;

use App\Models\DemographicData;
use App\Models\Organization;

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
            'logo' => asset('storage/' . $organization->logo),
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
                'logo' => asset('storage/' . $organization->logo),
            ],
            'company_data' => $this->getCompanyData($organization),
            'demographic_summary' => $this->getDemographicSummary($organization),
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
