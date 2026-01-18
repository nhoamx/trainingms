<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewOrganizationResults(User $user, Organization $organization): bool
    {
        // Los administradores siempre pueden ver los resultados
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Los usuarios de organización solo pueden ver sus propios resultados
        return $user->hasRole('organization') && $user->organization_id === $organization->id;
    }

    /**
     * Determina si el usuario puede ver el dashboard de la organización
     */
    public function viewOrganizationDashboard(User $user, Organization $organization): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }
        
        // Solo usuarios con rol 'organization' pueden ver su propio dashboard
        return $user->hasRole('organization') && $user->organization_id === $organization->id;
    }

    // Alias para mantener compatibilidad con el nombre usando guiones
    public function __call($method, $args)
    {
        if ($method === 'view-organization-results') {
            return $this->viewOrganizationResults(...$args);
        }
        if ($method === 'view-organization-dashboard') {
            return $this->viewOrganizationDashboard(...$args);
        }
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
