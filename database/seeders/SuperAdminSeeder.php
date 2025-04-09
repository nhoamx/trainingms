<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol de Super Admin
        $role = Role::firstOrCreate(['name' => 'super-admin']);

        $admin = Role::firstOrCreate(['name' => 'admin']);

        // Crear un usuario con rol de Super Admin
        $user = User::firstOrCreate(
            ['email' => 'alfredo@nhoamx.com'], // Cambiar por el correo deseado
            [
                'name' => 'Super Admin',
                'password' => bcrypt('chencho9130'), // Cambiar por una contraseña segura
            ]
        );

        // Asignar el rol al usuario
        $user->assignRole($role);

        $adminUser = User::firstOrCreate(
            ['email' => 'jaime@trainingyms.com'],
            [
                'name' => 'Jaime',
                'password' => bcrypt('password'), // Cambiar por una contraseña segura
            ]
        );
        $adminUser->assignRole($admin);
    }
}
