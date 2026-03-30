<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkCommentsImport;
use App\Jobs\ProcessBulkEvaluationImport;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkCenterClimaBulkUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_upload_bulk_update_file(): void
    {
        Bus::fake();
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create(
            'clima_export.xlsx',
            200,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $response = $this->actingAs($user)->post(
            route('work-centers.clima.bulk-update', $workCenter->id),
            ['file' => $file],
        );

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'message',
            'bulk_import_job_id',
        ]);

        $this->assertDatabaseHas('bulk_import_jobs', [
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type' => 'likert',
            'status' => 'pending',
        ]);

        Bus::assertDispatched(ProcessBulkEvaluationImport::class);
    }

    public function test_non_admin_cannot_upload_bulk_update_file(): void
    {
        Bus::fake();
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->syncRoles(['organization']);

        $file = UploadedFile::fake()->create(
            'clima_export.xlsx',
            200,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(
                route('work-centers.clima.bulk-update', $workCenter->id),
                ['file' => $file],
            );

        $response->assertForbidden();

        Bus::assertNotDispatched(ProcessBulkEvaluationImport::class);
    }

    public function test_validation_requires_xlsx_file(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(
                route('work-centers.clima.bulk-update', $workCenter->id),
                ['file' => $file],
            );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_bulk_import_job_has_correct_work_center_scope(): void
    {
        Bus::fake();
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $workCenterA = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $workCenterB = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create(
            'clima_export.xlsx',
            200,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $this->actingAs($user)->post(
            route('work-centers.clima.bulk-update', $workCenterA->id),
            ['file' => $file],
        );

        $this->assertDatabaseHas('bulk_import_jobs', [
            'work_center_id' => $workCenterA->id,
            'evaluation_type' => 'likert',
        ]);

        $this->assertDatabaseMissing('bulk_import_jobs', [
            'work_center_id' => $workCenterB->id,
        ]);
    }

    public function test_admin_can_download_comments_template(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $response = $this->actingAs($user)
            ->get(route('work-centers.clima.comments-template', $workCenter));

        $response->assertOk();
        $response->assertDownload();
    }

    public function test_admin_can_upload_bulk_comments_file(): void
    {
        Bus::fake();
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $file = UploadedFile::fake()->create(
            'comments.xlsx',
            200,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $response = $this->actingAs($user)->post(
            route('work-centers.clima.bulk-comments', $workCenter->id),
            ['file' => $file],
        );

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'message',
            'bulk_import_job_id',
        ]);

        $this->assertDatabaseHas('bulk_import_jobs', [
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'evaluation_type' => 'likert',
            'status' => 'pending',
        ]);

        Bus::assertDispatched(ProcessBulkCommentsImport::class);
    }

    public function test_non_admin_cannot_upload_bulk_comments_file(): void
    {
        Bus::fake();
        Storage::fake('local');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create();
        $user->syncRoles(['organization']);

        $file = UploadedFile::fake()->create(
            'comments.xlsx',
            200,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(
                route('work-centers.clima.bulk-comments', $workCenter->id),
                ['file' => $file],
            );

        $response->assertForbidden();

        Bus::assertNotDispatched(ProcessBulkCommentsImport::class);
    }
}
