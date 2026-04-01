<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\WorkCenter;
use App\Models\WorkCenterClimaEvidence;
use App\Models\WorkCenterClimaReport;
use App\Models\WorkCenterClimaSection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkCenterClimaContentManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_upsert_clima_section(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);

        $response = $this->actingAs($admin)->post(route('work-centers.clima.sections.upsert', $workCenter), [
            'section_key' => 'recommendations',
            'content' => 'Plan de intervención por área y turno.',
            'status' => 'published',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('work_center_clima_sections', [
            'work_center_id' => $workCenter->id,
            'section_key' => 'recommendations',
            'status' => 'published',
        ]);
    }

    public function test_organization_user_cannot_upsert_clima_section(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $organizationUser = User::factory()->create(['organization_id' => $organization->id]);
        $organizationUser->syncRoles(['organization']);

        $response = $this->actingAs($organizationUser)->post(route('work-centers.clima.sections.upsert', $workCenter), [
            'section_key' => 'foda',
            'content' => 'No debe poder guardar.',
            'status' => 'draft',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('work_center_clima_sections', [
            'work_center_id' => $workCenter->id,
            'section_key' => 'foda',
        ]);
    }

    public function test_admin_can_upload_report_and_evidence(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);

        $report = UploadedFile::fake()->create('clima-report.pdf', 500, 'application/pdf');
        $evidence = UploadedFile::fake()->image('evidencia.jpg');

        $this->actingAs($admin)->post(route('work-centers.clima.reports.store', $workCenter), [
            'title' => 'Reporte Clima Marzo',
            'language' => 'es',
            'report_file' => $report,
            'is_published' => true,
            'is_active' => true,
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('work-centers.clima.evidences.store', $workCenter), [
            'title' => 'Foto taller',
            'description' => 'Evidencia de sesión de trabajo.',
            'evidence_file' => $evidence,
            'is_published' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('work_center_clima_reports', [
            'work_center_id' => $workCenter->id,
            'title' => 'Reporte Clima Marzo',
            'is_published' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('work_center_clima_evidences', [
            'work_center_id' => $workCenter->id,
            'title' => 'Foto taller',
            'is_published' => true,
        ]);
    }

    public function test_dashboard_shows_draft_sections_for_organization_user(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        WorkCenterClimaSection::query()->create([
            'work_center_id' => $workCenter->id,
            'section_key' => 'recommendations',
            'content' => 'Borrador interno',
            'status' => 'draft',
        ]);

        $organizationUser = User::factory()->create(['organization_id' => $organization->id]);
        $organizationUser->syncRoles(['organization']);

        $response = $this->actingAs($organizationUser)
            ->get(route('work-centers.dashboard.clima-laboral', $workCenter));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('canManageClima', false)
            ->where('climaContent.sections.recommendations.content', 'Borrador interno')
        );

    }

    public function test_work_center_user_can_download_draft_report_and_evidence(): void
    {
        Storage::fake('public');

        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        Storage::disk('public')->put('reports/draft-report.pdf', 'draft report content');
        Storage::disk('public')->put('evidences/draft-evidence.jpg', 'draft evidence content');

        $report = WorkCenterClimaReport::query()->create([
            'work_center_id' => $workCenter->id,
            'title' => 'Reporte Borrador',
            'language' => 'es',
            'storage_path' => 'reports/draft-report.pdf',
            'original_filename' => 'draft-report.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1200,
            'is_published' => false,
            'is_active' => false,
        ]);

        $evidence = WorkCenterClimaEvidence::query()->create([
            'work_center_id' => $workCenter->id,
            'title' => 'Evidencia Borrador',
            'description' => 'Solo borrador',
            'storage_path' => 'evidences/draft-evidence.jpg',
            'original_filename' => 'draft-evidence.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 900,
            'is_published' => false,
        ]);

        $workCenterUser = User::factory()->create();
        $workCenterUser->syncRoles(['work_center_user']);
        $workCenterUser->workCenters()->attach($workCenter);

        $this->actingAs($workCenterUser)
            ->get(route('work-centers.clima.reports.download', [$workCenter, $report]))
            ->assertOk();

        $this->actingAs($workCenterUser)
            ->get(route('work-centers.clima.evidences.download', [$workCenter, $evidence]))
            ->assertOk();
    }
}
