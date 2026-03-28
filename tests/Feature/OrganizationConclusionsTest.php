<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationClimaSection;
use App\Models\OrganizationConclusionsFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationConclusionsTest extends TestCase
{
    use DatabaseTransactions;

    private User $adminUser;

    private User $organizationUser;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->adminUser = User::factory()->create();
        $this->adminUser->syncRoles(['admin']);
        $this->adminUser->update(['organization_id' => $this->organization->id]);

        $this->organizationUser = User::factory()->create();
        $this->organizationUser->syncRoles(['organization']);
        $this->organizationUser->update(['organization_id' => $this->organization->id]);
    }

    // ── OrganizationClimaSectionController ───────────────────────────────────

    public function test_admin_can_upsert_conclusions_section_as_draft(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.conclusions.sections.upsert', $this->organization), [
                'section_key' => 'conclusions_config',
                'content' => '{"objective":"Test objective"}',
                'status' => 'draft',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('organization_clima_sections', [
            'organization_id' => $this->organization->id,
            'section_key' => 'conclusions_config',
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_upsert_conclusions_section_as_published(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.conclusions.sections.upsert', $this->organization), [
                'section_key' => 'conclusions_config',
                'content' => '{"objective":"Published objective"}',
                'status' => 'published',
            ]);

        $response->assertRedirect();

        $section = OrganizationClimaSection::query()
            ->where('organization_id', $this->organization->id)
            ->where('section_key', 'conclusions_config')
            ->first();

        $this->assertNotNull($section);
        $this->assertSame('published', $section->status);
        $this->assertNotNull($section->published_at);
    }

    public function test_upsert_updates_existing_section(): void
    {
        OrganizationClimaSection::factory()->create([
            'organization_id' => $this->organization->id,
            'section_key' => 'conclusions_config',
            'content' => '{"old":"data"}',
            'status' => 'draft',
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('organization.conclusions.sections.upsert', $this->organization), [
                'section_key' => 'conclusions_config',
                'content' => '{"new":"data"}',
                'status' => 'published',
            ]);

        $this->assertDatabaseCount('organization_clima_sections', 1);
        $this->assertDatabaseHas('organization_clima_sections', [
            'organization_id' => $this->organization->id,
            'section_key' => 'conclusions_config',
            'status' => 'published',
        ]);
    }

    public function test_organization_user_cannot_upsert_section(): void
    {
        $response = $this->actingAs($this->organizationUser)
            ->post(route('organization.conclusions.sections.upsert', $this->organization), [
                'section_key' => 'conclusions_config',
                'content' => 'test',
                'status' => 'draft',
            ]);

        $response->assertForbidden();
    }

    public function test_upsert_validates_section_key(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.conclusions.sections.upsert', $this->organization), [
                'section_key' => 'invalid_key',
                'content' => 'test',
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('section_key');
    }

    // ── OrganizationConclusionsFileController ─────────────────────────────────

    public function test_admin_can_upload_conclusions_file(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminUser)
            ->post(route('organization.conclusions.files.store', $this->organization), [
                'slot' => 1,
                'title' => 'Programa de Intervención',
                'color' => 'teal',
                'conclusions_file' => UploadedFile::fake()->create('programa.pdf', 500, 'application/pdf'),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('organization_conclusions_files', [
            'organization_id' => $this->organization->id,
            'slot' => 1,
            'title' => 'Programa de Intervención',
            'color' => 'teal',
        ]);
    }

    public function test_uploading_replaces_existing_file_in_same_slot(): void
    {
        Storage::fake('public');

        $existingFile = OrganizationConclusionsFile::factory()->create([
            'organization_id' => $this->organization->id,
            'slot' => 1,
            'disk' => 'public',
            'path' => "{$this->organization->id}/conclusions/slot_1.pdf",
        ]);

        Storage::disk('public')->put($existingFile->path, 'old content');

        $this->actingAs($this->adminUser)
            ->post(route('organization.conclusions.files.store', $this->organization), [
                'slot' => 1,
                'title' => 'New Title',
                'color' => 'blue',
                'conclusions_file' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
            ]);

        $this->assertDatabaseMissing('organization_conclusions_files', ['id' => $existingFile->id]);
        $this->assertDatabaseHas('organization_conclusions_files', [
            'organization_id' => $this->organization->id,
            'slot' => 1,
            'title' => 'New Title',
        ]);
    }

    public function test_organization_user_cannot_upload_file(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->organizationUser)
            ->post(route('organization.conclusions.files.store', $this->organization), [
                'slot' => 1,
                'title' => 'Test',
                'color' => 'teal',
                'conclusions_file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_toggle_publish_file(): void
    {
        $file = OrganizationConclusionsFile::factory()->create([
            'organization_id' => $this->organization->id,
            'is_published' => false,
        ]);

        $this->actingAs($this->adminUser)
            ->patch(route('organization.conclusions.files.toggle-publish', [$this->organization, $file]));

        $this->assertDatabaseHas('organization_conclusions_files', [
            'id' => $file->id,
            'is_published' => true,
        ]);
    }

    public function test_organization_user_cannot_toggle_publish(): void
    {
        $file = OrganizationConclusionsFile::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->organizationUser)
            ->patch(route('organization.conclusions.files.toggle-publish', [$this->organization, $file]));

        $response->assertForbidden();
    }

    public function test_admin_can_delete_conclusions_file(): void
    {
        Storage::fake('public');

        $file = OrganizationConclusionsFile::factory()->create([
            'organization_id' => $this->organization->id,
            'disk' => 'public',
            'path' => "{$this->organization->id}/conclusions/slot_1.pdf",
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('organization.conclusions.files.destroy', [$this->organization, $file]));

        $this->assertDatabaseMissing('organization_conclusions_files', ['id' => $file->id]);
    }

    public function test_organization_user_cannot_delete_file(): void
    {
        $file = OrganizationConclusionsFile::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($this->organizationUser)
            ->delete(route('organization.conclusions.files.destroy', [$this->organization, $file]));

        $response->assertForbidden();
    }

    public function test_cannot_toggle_publish_file_from_different_organization(): void
    {
        $otherOrganization = Organization::factory()->create();
        $file = OrganizationConclusionsFile::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patch(route('organization.conclusions.files.toggle-publish', [$this->organization, $file]));

        $response->assertForbidden();
    }

    public function test_dashboard_includes_conclusions_content_for_admin(): void
    {
        $section = OrganizationClimaSection::factory()->create([
            'organization_id' => $this->organization->id,
            'section_key' => 'conclusions_config',
            'content' => '{"objective":"Test"}',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('organization.dashboard', $this->organization));

        $response->assertInertia(fn ($page) => $page
            ->where('canManageConclusions', true)
            ->has('conclusionsContent')
            ->has('conclusionsContent.section')
            ->has('conclusionsContent.files')
        );
    }

    public function test_dashboard_conclusions_can_manage_is_false_for_organization_role(): void
    {
        $response = $this->actingAs($this->organizationUser)
            ->get(route('organization.dashboard', $this->organization));

        $response->assertInertia(fn ($page) => $page
            ->where('canManageConclusions', false)
        );
    }
}
