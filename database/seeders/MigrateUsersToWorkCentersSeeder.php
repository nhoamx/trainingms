<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MigrateUsersToWorkCentersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Migra usuarios existentes al sistema de work centers:
     * - Admins y organization: No necesitan pivot (acceso por rol)
     * - Usuarios sin rol claro: Se convierten a work_center_user
     * - Se asigna el primary work center de su organización
     */
    public function run(): void
    {
        $usersWithOrg = User::whereNotNull('organization_id')
            ->with('organization.workCenters')
            ->get();

        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($usersWithOrg as $user) {
            // Skip admins y organization roles (no necesitan pivot)
            if ($user->hasRole(['admin', 'organization'])) {
                $this->command->info("  Skipped: {$user->name} (role: {$user->role_name})");
                $skippedCount++;

                continue;
            }

            // Si el usuario no tiene rol específico, asignar work_center_user
            if (! $user->hasRole('work_center_user')) {
                $user->assignRole('work_center_user');
                $this->command->info("  Assigned work_center_user role to: {$user->name}");
            }

            // Buscar el primary work center de su organización
            $primaryCenter = $user->organization->workCenters()
                ->where('is_primary', true)
                ->first();

            if ($primaryCenter) {
                $user->workCenters()->syncWithoutDetaching([$primaryCenter->id]);
                $this->command->info("  ✓ Migrated: {$user->name} → {$primaryCenter->name}");
                $migratedCount++;
            } else {
                $this->command->warn("  ✗ No primary work center found for: {$user->name} (Org: {$user->organization->name})");
            }
        }

        $this->command->newLine();
        $this->command->info('Migration Summary:');
        $this->command->info("  Total users: {$usersWithOrg->count()}");
        $this->command->info("  Migrated: {$migratedCount}");
        $this->command->info("  Skipped (admin/organization): {$skippedCount}");
    }
}
