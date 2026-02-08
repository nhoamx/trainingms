<?php

namespace App\Services;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrganizationService
{
    /**
     * Create organization with primary work center
     *
     * @param  array  $orgData  Organization data
     * @param  array  $workCenterData  Work center data
     * @param  mixed  $logoFile  Logo file
     */
    public function createWithWorkCenter(array $orgData, array $workCenterData, $logoFile = null): Organization
    {
        return DB::transaction(function () use ($orgData, $workCenterData, $logoFile) {
            // Generar folio automáticamente basado en timestamp + random
            if (empty($orgData['folio_organization'])) {
                $orgData['folio_organization'] = $this->generateFolio();
            }

            // Crear organización (solo datos corporativos)
            $organization = new Organization;
            $organization->fill($orgData);

            if ($logoFile) {
                $logoPath = $logoFile->store('organizations', 'public');
                $organization->logo = $logoPath;
            }

            $organization->save();

            // Crear centro de trabajo primario con los datos del wizard
            $this->createWorkCenter($organization, $workCenterData, true);

            return $organization->fresh(['workCenters']);
        });
    }

    /**
     * Generate unique folio for organization
     */
    protected function generateFolio(): int
    {
        // Timestamp + random para máxima unicidad
        return (int) (now()->timestamp.rand(10, 99));
    }

    /**
     * Create work center for organization
     *
     * @param  bool  $isPrimary  Is this the primary center?
     */
    public function createWorkCenter(Organization $organization, array $data, bool $isPrimary = false): WorkCenter
    {
        // Generar código de centro si no existe
        if (empty($data['code'])) {
            $data['code'] = $this->generateWorkCenterCode($organization);
        }

        // Asegurar que el tipo sea headquarters si es primario
        if ($isPrimary) {
            $data['type'] = $data['type'] ?? WorkCenterType::Headquarters->value;
            $data['is_primary'] = true;
        }

        $data['organization_id'] = $organization->id;

        return WorkCenter::create($data);
    }

    /**
     * Generate unique code for work center within organization
     */
    protected function generateWorkCenterCode(Organization $organization): string
    {
        $lastCenter = $organization->workCenters()
            ->orderBy('code', 'desc')
            ->first();

        if (! $lastCenter) {
            return '0001';
        }

        $lastCode = (int) $lastCenter->code;

        return str_pad($lastCode + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update organization (only corporate data)
     */
    public function updateOrganization(Organization $organization, array $data, $logoFile = null): Organization
    {
        return DB::transaction(function () use ($organization, $data, $logoFile) {
            $organization->fill($data);

            if ($logoFile) {
                if ($organization->logo && Storage::disk('public')->exists($organization->logo)) {
                    Storage::disk('public')->delete($organization->logo);
                }
                $logoPath = $logoFile->store('organizations', 'public');
                $organization->logo = $logoPath;
            }

            $organization->save();

            return $organization->fresh();
        });
    }

    /**
     * Update work center
     */
    public function updateWorkCenter(WorkCenter $workCenter, array $data): WorkCenter
    {
        $workCenter->fill($data);
        $workCenter->save();

        return $workCenter->fresh();
    }
}
