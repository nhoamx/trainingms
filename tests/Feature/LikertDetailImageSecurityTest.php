<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LikertDetailImageSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected Organization $organization;

    protected PaperEvaluation $evaluation;

    protected User $adminUser;

    protected User $organizationUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'folio_organization' => '999',
        ]);

        $this->evaluation = PaperEvaluation::factory()->likert()->create([
            'organization_id' => $this->organization->id,
            'organization_code' => '999',
            'personal_folio' => '0001',
            'folio' => '059990001',
            'processing_status' => 'completed',
        ]);

        $this->adminUser = User::factory()->create([
            'email' => 'test-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->adminUser->assignRole('admin');

        $this->organizationUser = User::factory()->create([
            'email' => 'test-org@example.com',
            'password' => Hash::make('password'),
            'organization_id' => $this->organization->id,
        ]);
        $this->organizationUser->assignRole('organization');

        Storage::disk('public')->put('folios/059990001.png', 'fake-image-content');
    }

    protected function tearDown(): void
    {
        Storage::disk('public')->delete('folios/059990001.png');

        parent::tearDown();
    }

    /**
     * Admin users should see the scanned_image_url.
     */
    public function test_admin_can_see_scanned_image_url(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.results.likert', [
                'organization' => $this->organization->id,
                'personalFolio' => '0001',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/LikertDetail')
            ->where('isAdmin', true)
            ->has('evaluation.scanned_image_url')
            ->where('evaluation.scanned_image_url', fn ($url) => str_contains($url, '059990001.png'))
        );
    }

    /**
     * Organization users should NOT see the scanned_image_url.
     */
    public function test_organization_user_cannot_see_scanned_image_url(): void
    {
        $response = $this->actingAs($this->organizationUser)
            ->get(route('organization.results.likert', [
                'organization' => $this->organization->id,
                'personalFolio' => '0001',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/LikertDetail')
            ->where('isAdmin', false)
            ->where('evaluation.scanned_image_url', null)
        );
    }

    /**
     * Super Admin users should see the scanned_image_url.
     */
    public function test_super_admin_can_see_scanned_image_url(): void
    {
        $superAdmin = User::where('email', 'alfredo@nhoamx.com')->first();

        if (! $superAdmin) {
            $this->markTestSkipped('Super admin user not found in database');
        }

        $response = $this->actingAs($superAdmin)
            ->get(route('organization.results.likert', [
                'organization' => $this->organization->id,
                'personalFolio' => '0001',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/LikertDetail')
            ->where('isAdmin', true)
            ->has('evaluation.scanned_image_url')
        );
    }

    /**
     * Admin users should not receive scanned_image_url if the image is missing.
     */
    public function test_admin_cannot_see_scanned_image_url_when_file_is_missing(): void
    {
        Storage::disk('public')->delete('folios/059990001.png');

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.results.likert', [
                'organization' => $this->organization->id,
                'personalFolio' => '0001',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Results/LikertDetail')
            ->where('isAdmin', true)
            ->where('evaluation.scanned_image_url', null)
        );
    }
}
