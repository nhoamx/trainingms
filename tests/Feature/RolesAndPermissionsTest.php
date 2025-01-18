<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected $superAdminUser;
    protected $adminUser;
    protected $companyUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Obtener el usuario Super Admin existente
        $this->superAdminUser = User::where('email', 'alfredo@nhoamx.com')->first();

        // Crear usuario Admin
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole('admin');

        // Crear usuario Company
        $this->organizationUser = User::factory()->create([
            'email' => 'company@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->organizationUser->assignRole('organization');
    }

    /**
     * El rol "Company" no puede acceder a rutas de administrador.
     */
    public function test_organization_role_cannot_access_admin_routes()
    {
        // Actuar como usuario Company e intentar acceder a ruta protegida
        $response = $this->actingAs($this->organizationUser)->get(route('users.index'));

        // Verificar que el acceso sea denegado
        $response->assertForbidden();
    }

    /**
     * El Super Admin puede crear un usuario con rol "Admin".
     */
    public function test_super_admin_or_admin_can_create_user_with_admin_role()
    {
        // Actuar como Super Admin y crear usuario Admin
        $response = $this->actingAs($this->superAdminUser)->post(route('users.store'), [
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        // Verificar redirección y creación de usuario
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'admin2@example.com']);
    }

    /**
     * El Super Admin o Admin puede crear un usuario con rol "Company".
     */
    public function test_super_admin_or_admin_can_create_user_with_organization_role()
    {

        $actingUser = rand(0, 1) ? $this->superAdminUser : $this->adminUser;
        // Actuar como Super Admin y crear usuario Admin
        $response = $this->actingAs($actingUser)->post(route('users.store'), [
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'organization',
        ]);

        // Verificar redirección y creación de usuario
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'admin2@example.com']);
    }

    /**
     * El Admin no puede eliminar usuarios con rol "Super Admin".
     */
    public function test_admin_cannot_delete_super_admin()
    {
        // Actuar como Admin e intentar eliminar Super Admin
        $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $this->superAdminUser->id));

        // Verificar que el acceso sea denegado
        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['email' => 'alfredo@nhoamx.com']);
    }

    /**
     * El Super Admin puede eliminar usuarios con rol "Admin".
     */
    public function test_super_admin_can_delete_admin()
    {
        // Actuar como Super Admin y eliminar usuario Admin
        $response = $this->actingAs($this->superAdminUser)->delete(route('users.destroy', $this->adminUser));

        // Verificar redirección y eliminación de usuario
        $response->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['email' => $this->adminUser->email]);
    }

    /**
     * El Super Admin o Admin puede eliminar un usuario con rol "Company".
     */
    public function test_super_admin_or_admin_can_delete_organization_user()
    {
        // Seleccionar aleatoriamente entre Super Admin o Admin
        $actingUser = rand(0, 1) ? $this->superAdminUser : $this->adminUser;

        // Super Admin o Admin elimina usuario Company
        $response = $this->actingAs($actingUser)->delete(route('users.destroy', $this->organizationUser));

        // Verificar redirección y eliminación
        $response->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['email' => $this->organizationUser->email]);

    }

}
