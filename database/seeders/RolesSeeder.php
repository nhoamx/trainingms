<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles del sistema
        $roles = [
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'organization', 'guard_name' => 'web'],
            ['name' => 'work_center_user', 'guard_name' => 'web'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
