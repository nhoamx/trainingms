<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /*
     * A user can view the login page
     */
    public function test_login_page_renders_correctly()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    /*
     * A user with valid credentials can login
     */
    public function test_user_with_valid_credentials_can_login()
    {
        $credentials = [
            'email' => 'alfredo@nhoamx.com',
            'password' => 'chencho9130',
        ];

        $response = $this->post('/login', $credentials);

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    /*
     * An authenticated user can logout
     */
    public function test_authenticated_user_can_logout()
    {
        $user = \App\Models\User::where('email', 'alfredo@nhoamx.com')->first();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');

        $this->assertGuest();
    }

    /*
     * A user with invalid credentials cannot login
     */
    public function test_user_with_invalid_credentials_cannot_login()
    {
        $credentials = [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->post('/login', $credentials);

        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect('/login');

        $this->assertGuest();
    }
}
