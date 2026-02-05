<?php

namespace App\Services;

use App\Enums\WorkCenterType;
use App\Models\Organization;
use App\Models\WorkCenter;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Create organization with primary work center
     *
     * @param  array  $data  Organization data from form
     * @param  mixed  $logoFile  Uploaded logo file (nullable)
     */
    public function createWithWorkCenter(array $data, $logoFile = null): Organization
    {
        return DB::transaction(function () use ($data, $logoFile) {
            // Generate folio if not provided
            if (empty($data['folio_organization'])) {
                $data['folio_organization'] = rand(100, 999);
            }

            // Handle logo separately
            unset($data['logo']);

            // Create organization
            $organization = new Organization;
            $organization->fill($data);

            if ($logoFile) {
                $logoPath = $logoFile->store('organizations', 'public');
                $organization->logo = $logoPath;
            }

            $organization->save();

            // Create primary work center automatically
            $this->createPrimaryWorkCenter($organization);

            return $organization->fresh(['workCenters']);
        });
    }

    /**
     * Update organization and sync primary work center data
     *
     * @param  array  $data  Updated organization data from form
     * @param  mixed  $logoFile  Uploaded logo file (nullable)
     */
    public function updateWithWorkCenter(Organization $organization, array $data, $logoFile = null): Organization
    {
        return DB::transaction(function () use ($organization, $data, $logoFile) {
            // Handle logo separately
            unset($data['logo']);

            $organization->fill($data);

            if ($logoFile) {
                if ($organization->logo && \Storage::disk('public')->exists($organization->logo)) {
                    \Storage::disk('public')->delete($organization->logo);
                }
                $logoPath = $logoFile->store('organizations', 'public');
                $organization->logo = $logoPath;
            }

            $organization->save();

            // Sync primary work center data with organization
            $this->syncPrimaryWorkCenterData($organization);

            return $organization->fresh(['workCenters']);
        });
    }

    /**
     * Create primary work center for organization
     */
    protected function createPrimaryWorkCenter(Organization $organization): WorkCenter
    {
        return WorkCenter::create([
            'organization_id' => $organization->id,
            'code' => '0001',
            'name' => $organization->name,
            'type' => WorkCenterType::Headquarters->value,
            'is_primary' => true,
            // Copy fiscal data
            'legal_name' => $organization->razon_social,
            'tax_id' => $organization->rfc,
            'employer_registration' => $organization->registro_patronal,
            // Copy address
            'street_address' => $organization->calle_numero,
            'neighborhood' => $organization->colonia,
            'postal_code' => $organization->codigo_postal,
            'municipality' => $organization->municipio,
            'state' => $organization->estado,
            // Copy contact
            'phone' => $organization->contacto_movil,
            'email' => $organization->contacto_email,
        ]);
    }

    /**
     * Sync primary work center data with organization data
     * Updates fiscal, address, and contact information
     */
    protected function syncPrimaryWorkCenterData(Organization $organization): void
    {
        $primaryCenter = $organization->workCenters()
            ->where('is_primary', true)
            ->first();

        // If no primary work center exists, create it
        if (! $primaryCenter) {
            $this->createPrimaryWorkCenter($organization);

            return;
        }

        // Update primary work center with organization data
        $primaryCenter->update([
            'name' => $organization->name,
            // Fiscal data
            'legal_name' => $organization->razon_social,
            'tax_id' => $organization->rfc,
            'employer_registration' => $organization->registro_patronal,
            // Address
            'street_address' => $organization->calle_numero,
            'neighborhood' => $organization->colonia,
            'postal_code' => $organization->codigo_postal,
            'municipality' => $organization->municipio,
            'state' => $organization->estado,
            // Contact
            'phone' => $organization->contacto_movil,
            'email' => $organization->contacto_email,
        ]);
    }
}
