<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkCenterConstitutiveActControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_assigned_work_center_user_can_upload_submitted_constitutive_act(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $file = UploadedFile::fake()->create('acta-centro.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('work-centers.constitutive-act.upload-submitted', $workCenter), [
                'constitutive_act_submitted' => $file,
            ]);

        $response->assertRedirect();

        $workCenter->refresh();

        $this->assertNotNull($workCenter->constitutive_act_submitted_path);
        $this->assertTrue(Storage::disk('public')->exists($workCenter->constitutive_act_submitted_path));
    }

    public function test_admin_cannot_upload_submitted_constitutive_act(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create('acta-centro.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('work-centers.constitutive-act.upload-submitted', $workCenter), [
                'constitutive_act_submitted' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_upload_admin_constitutive_act(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create('acta-admin.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('work-centers.constitutive-act.upload-admin', $workCenter), [
                'constitutive_act_admin' => $file,
            ]);

        $response->assertRedirect();

        $workCenter->refresh();

        $this->assertNotNull($workCenter->constitutive_act_admin_path);
        $this->assertTrue(Storage::disk('public')->exists($workCenter->constitutive_act_admin_path));
    }

    public function test_work_center_user_cannot_upload_admin_constitutive_act(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $file = UploadedFile::fake()->create('acta-admin.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('work-centers.constitutive-act.upload-admin', $workCenter), [
                'constitutive_act_admin' => $file,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_download_submitted_constitutive_act(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $path = Storage::disk('public')->putFile(
            "work-centers/{$workCenter->id}/constitutive-act/submitted",
            UploadedFile::fake()->create('acta-centro.pdf', 200, 'application/pdf')
        );

        $workCenter->update([
            'constitutive_act_submitted_path' => $path,
            'constitutive_act_submitted_at' => now(),
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.constitutive-act.download-submitted', $workCenter));

        $response->assertOk();
    }

    public function test_assigned_work_center_user_can_download_admin_constitutive_act(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $path = Storage::disk('public')->putFile(
            "work-centers/{$workCenter->id}/constitutive-act/admin",
            UploadedFile::fake()->create('acta-admin.pdf', 200, 'application/pdf')
        );

        $workCenter->update([
            'constitutive_act_admin_path' => $path,
            'constitutive_act_admin_at' => now(),
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $response = $this->actingAs($user)
            ->get(route('work-centers.constitutive-act.download-admin', $workCenter));

        $response->assertOk();
    }
}
