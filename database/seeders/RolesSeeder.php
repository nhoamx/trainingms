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
        // Crear dos roles, Admin y Company
        $roles = [
            ['name' => 'admin'],
            ['name' => 'organization'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate($role);
        }
    }
}
