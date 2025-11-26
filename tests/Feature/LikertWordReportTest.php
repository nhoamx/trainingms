<?php

namespace Tests\Feature;

use App\Jobs\GenerateWordReport;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\User;
use App\Services\LikertChartImageService;
use App\Services\LikertScoreService;
use App\Services\ReportPdfService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LikertWordReportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_likert_word_report_route_exists(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->getJson("/reportes/word/likert/{$this->organization->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'report_id',
                'message',
            ]);
    }

    public function test_likert_word_report_requires_admin_role(): void
    {
        $regularUser = User::factory()->create([
            'organization_id' => $this->organization->id,
        ]);
        $regularUser->assignRole('organization');

        $response = $this->actingAs($regularUser)
            ->getJson("/reportes/word/likert/{$this->organization->id}");

        $response->assertStatus(403);
    }

    public function test_likert_word_report_creates_report_generation_record(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->getJson("/reportes/word/likert/{$this->organization->id}");

        $this->assertDatabaseHas('report_generations', [
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'report_type' => 'likert',
            'format' => 'docx',
            'status' => 'pending',
        ]);
    }

    public function test_likert_word_report_dispatches_job(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->getJson("/reportes/word/likert/{$this->organization->id}");

        Queue::assertPushed(GenerateWordReport::class, function ($job) {
            return $job->reportGeneration->report_type === 'likert';
        });
    }

    public function test_report_pdf_service_returns_likert_data(): void
    {
        // Create Likert evaluations
        PaperEvaluation::factory()
            ->likert()
            ->count(3)
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $service = app(ReportPdfService::class);
        $data = $service->getLikertReportWordData($this->organization->id);

        $this->assertArrayHasKey('evaluations', $data);
        $this->assertArrayHasKey('demographics', $data);
        $this->assertArrayHasKey('dimensions', $data);
        $this->assertArrayHasKey('climaLaboralDistribution', $data);
        $this->assertArrayHasKey('totalPeople', $data);
        $this->assertArrayHasKey('puestosMap', $data);
        $this->assertArrayHasKey('areasMap', $data);

        $this->assertCount(3, $data['evaluations']);
        $this->assertEquals(3, $data['totalPeople']);
    }

    public function test_report_pdf_service_returns_empty_data_for_no_likert_evaluations(): void
    {
        $service = app(ReportPdfService::class);
        $data = $service->getLikertReportWordData($this->organization->id);

        $this->assertEmpty($data['evaluations']);
        $this->assertEquals(0, $data['totalPeople']);
    }

    public function test_likert_score_service_calculates_scores_correctly(): void
    {
        $evaluation = PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
                'likert_answers' => [
                    'questions' => array_fill_keys(range(1, 23), 'A'), // All "Totalmente de Acuerdo"
                ],
            ]);

        $service = app(LikertScoreService::class);
        $scores = $service->calculateLikertScores($evaluation);

        $this->assertArrayHasKey('dimensions', $scores);
        $this->assertArrayHasKey('total_score', $scores);
        $this->assertArrayHasKey('interpretation', $scores);

        // All A's means 4 points each = 23 * 4 = 92 total
        $this->assertEquals(92, $scores['total_score']);
        $this->assertEquals('Totalmente de Acuerdo', $scores['interpretation']);
    }

    public function test_likert_data_includes_clima_laboral_distribution(): void
    {
        // Create evaluations with known answers for predictable distribution
        PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
                'likert_answers' => [
                    'questions' => array_fill_keys(range(1, 23), 'A'),
                ],
            ]);

        PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
                'likert_answers' => [
                    'questions' => array_fill_keys(range(1, 23), 'D'),
                ],
            ]);

        $service = app(ReportPdfService::class);
        $data = $service->getLikertReportWordData($this->organization->id);

        $distribution = $data['climaLaboralDistribution'];

        $this->assertArrayHasKey('Totalmente de Acuerdo', $distribution);
        $this->assertArrayHasKey('De Acuerdo', $distribution);
        $this->assertArrayHasKey('Desacuerdo', $distribution);
        $this->assertArrayHasKey('Totalmente Desacuerdo', $distribution);

        // One person with all A's should be "Totalmente de Acuerdo"
        // One person with all D's should be "Totalmente Desacuerdo"
        $this->assertEquals(1, $distribution['Totalmente de Acuerdo']);
        $this->assertEquals(1, $distribution['Totalmente Desacuerdo']);
    }

    public function test_likert_chart_image_service_can_be_instantiated(): void
    {
        $service = app(LikertChartImageService::class);
        $this->assertInstanceOf(LikertChartImageService::class, $service);
    }

    public function test_likert_data_includes_all_ten_dimensions(): void
    {
        PaperEvaluation::factory()
            ->likert()
            ->create([
                'organization_id' => $this->organization->id,
            ]);

        $service = app(ReportPdfService::class);
        $data = $service->getLikertReportWordData($this->organization->id);

        $expectedDimensions = [
            'Entorno Laboral Seguro',
            'Seguridad Laboral',
            'Compensación Justa',
            'Comunicación Abierta',
            'Participación de los Empleados',
            'Reconocimiento y Recompensa',
            'Capacitación y Desarrollo',
            'Equilibrio entre Vida Laboral y Personal',
            'Avance Profesional',
            'Apoyo al Empleado',
        ];

        foreach ($expectedDimensions as $dimension) {
            $this->assertArrayHasKey($dimension, $data['dimensions']);
        }
    }

    public function test_likert_word_report_returns_organization_not_found_for_invalid_id(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->getJson('/reportes/word/likert/invalid-organization-id');

        $response->assertStatus(404);
    }
}
