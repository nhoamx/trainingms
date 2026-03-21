<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use App\Models\WorkCenterSensitizationVideo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkCenterSensitizationVideoControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_upload_sensitization_video(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $video = UploadedFile::fake()->create('capacitacion.mp4', 2048, 'video/mp4');

        $response = $this->actingAs($user)->post(route('work-centers.sensitization-videos.store', $workCenter), [
            'title' => 'Sensibilización inicial',
            'description' => 'Video introductorio para el personal.',
            'audience' => 'general',
            'sort_order' => 1,
            'video' => $video,
        ]);

        $response->assertRedirect();

        $record = WorkCenterSensitizationVideo::query()->where('work_center_id', $workCenter->id)->first();

        $this->assertNotNull($record);
        $this->assertSame('Sensibilización inicial', $record->title);
        Storage::disk('public')->assertExists($record->storage_path);
    }

    public function test_work_center_user_cannot_upload_sensitization_video(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create();
        $user->syncRoles(['work_center_user']);
        $user->workCenters()->attach($workCenter);

        $video = UploadedFile::fake()->create('no-autorizado.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($user)->post(route('work-centers.sensitization-videos.store', $workCenter), [
            'title' => 'No autorizado',
            'audience' => 'general',
            'video' => $video,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('work_center_sensitization_videos', [
            'work_center_id' => $workCenter->id,
            'title' => 'No autorizado',
        ]);
    }

    public function test_admin_can_delete_sensitization_video(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $video = WorkCenterSensitizationVideo::query()->create([
            'work_center_id' => $workCenter->id,
            'title' => 'Video a eliminar',
            'description' => null,
            'audience' => 'general',
            'storage_path' => "work-centers/{$workCenter->id}/sensitization-videos/eliminar.mp4",
            'original_filename' => 'eliminar.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 1024,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        Storage::disk('public')->put($video->storage_path, 'contenido de prueba');

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)->delete(route('work-centers.sensitization-videos.destroy', [$workCenter, $video]));

        $response->assertRedirect();

        $this->assertDatabaseMissing('work_center_sensitization_videos', [
            'id' => $video->id,
        ]);
        Storage::disk('public')->assertMissing($video->storage_path);
    }
}
