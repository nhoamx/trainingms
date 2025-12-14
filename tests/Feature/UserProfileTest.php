<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test showing the profile page
     */
    public function test_user_can_view_profile_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Show')
            ->has('user')
        );
    }

    /**
     * Test updating profile with valid data
     */
    public function test_user_can_update_profile_with_valid_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => null,
        ]);

        $response->assertSessionHas('success', 'Perfil actualizado exitosamente.');

        $user->refresh();
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
    }

    /**
     * Test updating profile with password
     */
    public function test_user_can_update_profile_with_new_password(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $newPassword = 'newPassword123';

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertSessionHas('success', 'Perfil actualizado exitosamente.');

        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($newPassword, $user->password));
    }

    /**
     * Test validation fails with invalid email
     */
    public function test_user_cannot_update_profile_with_invalid_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => null,
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test validation fails when name is missing
     */
    public function test_user_cannot_update_profile_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => '',
            'email' => 'john@example.com',
            'password' => null,
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test validation fails with short password
     */
    public function test_user_cannot_update_profile_with_short_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test validation fails when passwords don't match
     */
    public function test_user_cannot_update_profile_with_mismatched_passwords(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'newPassword123',
            'password_confirmation' => 'differentPassword456',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Test unauthenticated user cannot access profile
     */
    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->get(route('profile'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test unauthenticated user cannot update profile
     */
    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->post(route('profile.update'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Test validation fails with duplicate email
     */
    public function test_user_cannot_update_profile_with_duplicate_email(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $response = $this->actingAs($user1)->post(route('profile.update'), [
            'name' => 'User One',
            'email' => 'user2@example.com', // Another user's email
            'password' => null,
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test user can update profile with same email
     */
    public function test_user_can_update_profile_with_same_email(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'John Smith',
            'email' => 'john@example.com', // Same email
            'password' => null,
        ]);

        $response->assertSessionHas('success', 'Perfil actualizado exitosamente.');

        $user->refresh();
        $this->assertEquals('John Smith', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }
}
