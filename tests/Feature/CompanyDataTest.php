<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyDataTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected User $organizationUser;

    protected User $adminUser;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $organizationRole = Role::firstOrCreate(['name' => 'organization']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Company',
            'razon_social' => 'Test Company S.A. de C.V.',
            'rfc' => 'TCO123456789',
        ]);

        // Create organization user
        $this->organizationUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $this->organizationUser->assignRole($organizationRole);

        // Create admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        Storage::fake('public');
    }

    /** @test */
    public function organization_user_can_view_company_data_form(): void
    {
        $response = $this->actingAs($this->organizationUser)
            ->get(route('company-data.edit', $this->organization));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Organizations/CompanyData')
            ->has('organization')
        );
    }

    /** @test */
    public function organization_user_cannot_update_company_data(): void
    {
        $updateData = [
            'name' => 'Updated Company Name',
            'razon_social' => 'Updated Razon Social',
            'rfc' => 'UPD123456789',
            'total_trabajadores' => 100,
            'total_hombres' => 60,
            'total_mujeres' => 40,
        ];

        $response = $this->actingAs($this->organizationUser)
            ->post(route('company-data.update', $this->organization), $updateData);

        $response->assertForbidden();
    }

    /** @test */
    public function organization_user_cannot_upload_policy_draft(): void
    {
        $file = UploadedFile::fake()->create('policy-draft.pdf', 1024);

        $response = $this->actingAs($this->organizationUser)
            ->post(route('company-data.policy.upload-draft', $this->organization), [
                'policy_draft' => $file,
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_upload_approved_policy(): void
    {
        $file = UploadedFile::fake()->create('policy-approved.pdf', 1024);

        $response = $this->actingAs($this->adminUser)
            ->post(route('company-data.policy.upload-approved', $this->organization), [
                'policy_approved' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash.type', 'success');

        $this->organization->refresh();
        $this->assertNotNull($this->organization->policy_approved_path);
        $this->assertNotNull($this->organization->policy_approved_at);

        Storage::disk('public')->assertExists($this->organization->policy_approved_path);
    }

    /** @test */
    public function organization_user_cannot_upload_approved_policy(): void
    {
        $file = UploadedFile::fake()->create('policy-approved.pdf', 1024);

        $response = $this->actingAs($this->organizationUser)
            ->post(route('company-data.policy.upload-approved', $this->organization), [
                'policy_approved' => $file,
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function organization_user_can_download_policy_draft(): void
    {
        $file = UploadedFile::fake()->create('policy-draft.pdf', 1024);
        $path = $file->store('policies/'.$this->organization->id.'/drafts', 'public');

        $this->organization->update(['policy_draft_path' => $path]);

        $response = $this->actingAs($this->organizationUser)
            ->get(route('company-data.policy.download-draft', $this->organization));

        $response->assertOk();
        $response->assertDownload();
    }

    /** @test */
    public function organization_user_can_download_approved_policy(): void
    {
        $file = UploadedFile::fake()->create('policy-approved.pdf', 1024);
        $path = $file->store('policies/'.$this->organization->id.'/approved', 'public');

        $this->organization->update([
            'policy_approved_path' => $path,
            'policy_approved_at' => now(),
        ]);

        $response = $this->actingAs($this->organizationUser)
            ->get(route('company-data.policy.download-approved', $this->organization));

        $response->assertOk();
        $response->assertDownload();
    }

    /** @test */
    public function organization_user_cannot_access_other_organization_data(): void
    {
        $otherOrganization = Organization::factory()->create();

        $response = $this->actingAs($this->organizationUser)
            ->get(route('company-data.edit', $otherOrganization));

        $response->assertForbidden();
    }

    /** @test */
    public function admin_validation_requires_name_field(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('company-data.update', $this->organization), [
                'name' => '',
                'razon_social' => 'Test',
            ]);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function admin_validation_requires_valid_email_formats(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('company-data.update', $this->organization), [
                'name' => 'Test Company',
                'contacto_email' => 'invalid-email',
                'responsable_email' => 'also-invalid',
            ]);

        $response->assertSessionHasErrors(['contacto_email', 'responsable_email']);
    }

    /** @test */
    public function admin_policy_upload_requires_valid_file_type(): void
    {
        $file = UploadedFile::fake()->create('policy.txt', 1024);

        $response = $this->actingAs($this->adminUser)
            ->post(route('company-data.policy.upload-draft', $this->organization), [
                'policy_draft' => $file,
            ]);

        $response->assertSessionHasErrors('policy_draft');
    }
}
