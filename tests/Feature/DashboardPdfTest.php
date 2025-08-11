<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class DashboardPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'organization']);
    }

    /** @test */
    public function authenticated_user_can_access_category_pdf_report()
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization'
        ]);
        
        $user = User::factory()->create([
            'organization_id' => $organization->id
        ]);
        $user->assignRole('organization');

        $response = $this->actingAs($user)
            ->get(route('dashboard.pdf.category'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function authenticated_user_can_access_domain_pdf_report()
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization'
        ]);
        
        $user = User::factory()->create([
            'organization_id' => $organization->id
        ]);
        $user->assignRole('organization');

        $response = $this->actingAs($user)
            ->get(route('dashboard.pdf.domain'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function authenticated_user_can_access_dimension_pdf_report()
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization'
        ]);
        
        $user = User::factory()->create([
            'organization_id' => $organization->id
        ]);
        $user->assignRole('organization');

        $response = $this->actingAs($user)
            ->get(route('dashboard.pdf.dimension'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function authenticated_user_can_access_demographic_pdf_report()
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization'
        ]);
        
        $user = User::factory()->create([
            'organization_id' => $organization->id
        ]);
        $user->assignRole('organization');

        $response = $this->actingAs($user)
            ->get(route('dashboard.pdf.demographic'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function authenticated_user_can_access_complete_pdf_report()
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization'
        ]);
        
        $user = User::factory()->create([
            'organization_id' => $organization->id
        ]);
        $user->assignRole('organization');

        $response = $this->actingAs($user)
            ->get(route('dashboard.pdf.complete'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function admin_user_can_access_pdf_reports()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->get(route('dashboard.pdf.complete'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function super_admin_user_can_access_pdf_reports()
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->get(route('dashboard.pdf.complete'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_pdf_reports()
    {
        $response = $this->get(route('dashboard.pdf.category'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('dashboard.pdf.domain'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('dashboard.pdf.dimension'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('dashboard.pdf.demographic'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('dashboard.pdf.complete'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function pdf_report_routes_exist()
    {
        $this->assertTrue(route('dashboard.pdf.category') !== null);
        $this->assertTrue(route('dashboard.pdf.domain') !== null);
        $this->assertTrue(route('dashboard.pdf.dimension') !== null);
        $this->assertTrue(route('dashboard.pdf.demographic') !== null);
        $this->assertTrue(route('dashboard.pdf.complete') !== null);
    }
}