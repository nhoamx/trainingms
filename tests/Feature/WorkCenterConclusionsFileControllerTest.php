<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use App\Models\WorkCenterConclusionsFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkCenterConclusionsFileControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_upload_conclusions_file(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create('report.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post(
            route('work-centers.clima.conclusions-files.store', $workCenter),
            [
                'slot' => 1,
                'title' => 'Informe Final',
                'color' => 'teal',
                'conclusions_file' => $file,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('work_center_conclusions_files', [
            'work_center_id' => $workCenter->id,
            'slot' => 1,
            'title' => 'Informe Final',
            'color' => 'teal',
        ]);
    }

    public function test_super_admin_can_upload_conclusions_file(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['super-admin']);

        $file = UploadedFile::fake()->create('report.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->post(
            route('work-centers.clima.conclusions-files.store', $workCenter),
            [
                'slot' => 2,
                'title' => 'Resumen Ejecutivo',
                'color' => 'blue',
                'conclusions_file' => $file,
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('work_center_conclusions_files', [
            'work_center_id' => $workCenter->id,
            'slot' => 2,
            'title' => 'Resumen Ejecutivo',
        ]);
    }

    public function test_work_center_user_cannot_upload_conclusions_file(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $file = UploadedFile::fake()->create('report.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->post(
            route('work-centers.clima.conclusions-files.store', $workCenter),
            [
                'slot' => 1,
                'title' => 'No permitido',
                'color' => 'teal',
                'conclusions_file' => $file,
            ]
        );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('work_center_conclusions_files', [
            'work_center_id' => $workCenter->id,
            'title' => 'No permitido',
        ]);
    }

    public function test_admin_can_toggle_publish_conclusions_file(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $fileRecord = WorkCenterConclusionsFile::create([
            'work_center_id' => $workCenter->id,
            'slot' => 1,
            'title' => 'Test File',
            'color' => 'teal',
            'disk' => 'public',
            'path' => 'test/path.pdf',
            'original_filename' => 'test.pdf',
            'file_size' => 1024,
            'is_published' => false,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->patch(
            route('work-centers.clima.conclusions-files.toggle-publish', [
                'workCenter' => $workCenter->id,
                'file' => $fileRecord->id,
            ])
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('work_center_conclusions_files', [
            'id' => $fileRecord->id,
            'is_published' => true,
        ]);
    }

    public function test_admin_can_destroy_conclusions_file(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $fileRecord = WorkCenterConclusionsFile::create([
            'work_center_id' => $workCenter->id,
            'slot' => 1,
            'title' => 'To Delete',
            'color' => 'teal',
            'disk' => 'public',
            'path' => 'test/to-delete.pdf',
            'original_filename' => 'to-delete.pdf',
            'file_size' => 512,
            'is_published' => false,
        ]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->delete(
            route('work-centers.clima.conclusions-files.destroy', [
                'workCenter' => $workCenter->id,
                'file' => $fileRecord->id,
            ])
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('work_center_conclusions_files', [
            'id' => $fileRecord->id,
        ]);
    }

    public function test_validation_requires_valid_slot(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create('report.pdf', 200, 'application/pdf');

        $response = $this->actingAs($user)->post(
            route('work-centers.clima.conclusions-files.store', $workCenter),
            [
                'slot' => 5,
                'title' => 'Bad Slot',
                'color' => 'teal',
                'conclusions_file' => $file,
            ]
        );

        $response->assertSessionHasErrors('slot');
    }
}
