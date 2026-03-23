<?php

namespace Tests\Feature;

use App\Enums\WorkCenterType;
use App\Models\FolioBatch;
use App\Models\Organization;
use App\Models\PdfGenerationJob;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PdfGenerationJobDownloadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pdf_job_download_uses_batch_filename_format(): void
    {
        /** @var User $user */
        $user = User::factory()->createOne();

        $organization = Organization::factory()->create([
            'name' => 'Organizacion Demo',
        ]);

        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Centro Norte',
            'code' => '0001',
            'type' => WorkCenterType::Plant->value,
        ]);

        $batch = FolioBatch::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'start_number' => 1,
            'end_number' => 100,
            'quantity' => 100,
        ]);

        $relativeFilePath = 'public/pdfs/test-download-file.pdf';
        $fullPath = storage_path('app/'.$relativeFilePath);

        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        file_put_contents($fullPath, 'test-content');

        $pdfJob = PdfGenerationJob::create([
            'organization_id' => $organization->id,
            'folio_batch_id' => $batch->id,
            'guide_type' => 'likert-planta-3',
            'total_folios' => 1,
            'processed_folios' => 1,
            'status' => 'completed',
            'file_paths' => [$relativeFilePath],
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('api.pdf-jobs.download', $pdfJob));

        $response->assertOk();
        $response->assertDownload('organizacion-demo-centro-norte-1-100.pdf');

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
