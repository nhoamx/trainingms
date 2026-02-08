<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserDisableTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test que un usuario desactivado no puede hacer login
     */
    public function test_disabled_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_disabled' => true,
        ]);

        $response = $this->post(route('loginPost'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Tu cuenta ha sido desactivada. Contacta al administrador.']);
        $this->assertGuest();
    }

    /**
     * Test que un usuario activo puede hacer login
     */
    public function test_active_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_disabled' => false,
        ]);

        $response = $this->post(route('loginPost'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    /**
     * Test que el middleware logout a usuario desactivado
     */
    public function test_middleware_logs_out_disabled_user(): void
    {
        $user = User::factory()->create([
            'is_disabled' => false,
        ]);

        $this->actingAs($user);

        // Desactivar el usuario
        $user->update(['is_disabled' => true]);

        // Intentar acceder a una ruta protegida
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Tu cuenta ha sido desactivada. Contacta al administrador.']);
        $this->assertGuest();
    }

    /**
     * Test que el campo is_disabled se castea correctamente a boolean
     */
    public function test_is_disabled_casts_to_boolean(): void
    {
        $user = User::factory()->create([
            'is_disabled' => true,
        ]);

        $this->assertIsBool($user->is_disabled);
        $this->assertTrue($user->is_disabled);

        $user->update(['is_disabled' => false]);
        $user->refresh();

        $this->assertIsBool($user->is_disabled);
        $this->assertFalse($user->is_disabled);
    }
}
