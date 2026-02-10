<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkCenter;

class WorkCenterPolicy
{
    /**
     * Determina si el usuario puede ver el dashboard de un centro de trabajo
     */
    public function viewWorkCenterDashboard(User $user, WorkCenter $workCenter): bool
    {
        if ($user->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        if ($user->hasRole('work_center_user')) {
            return $user->workCenters()->where('work_centers.id', $workCenter->id)->exists();
        }

        return $user->hasRole('organization') && $user->organization_id === $workCenter->organization_id;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'organization', 'work_center_user']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkCenter $workCenter): bool
    {
        return $this->viewWorkCenterDashboard($user, $workCenter);
    }
}
