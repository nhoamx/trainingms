<?php

namespace Tests\Feature;

use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProcessPaperEvaluationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the paper_evaluations table is empty before each test to avoid
        // interference from other tests or pre-seeded data.
        PaperEvaluation::query()->delete();
    }

    public function test_can_parse_folio_correctly(): void
    {
        $folio = '019530001';
        $parsed = PaperEvaluation::parseFolio($folio);

        $this->assertEquals('019530001', $parsed['folio']);
        $this->assertEquals('01', $parsed['evaluation_type_code']);
        $this->assertEquals('953', $parsed['organization_code']);
        $this->assertEquals('0001', $parsed['personal_folio']);
        $this->assertEquals('referencia_i', $parsed['evaluation_type']);
    }

    public function test_can_create_paper_evaluation_with_parsed_folio(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '953',
        ]);

        $folio = '019530001';
        $parsed = PaperEvaluation::parseFolio($folio);

        $evaluation = PaperEvaluation::create([
            ...$parsed,
            'organization_id' => $organization->id,
            'source' => 'paper',
            'processing_status' => 'completed',
            'referencia_i_answers' => ['1' => 'SI', '2' => 'NO'],
        ]);

        $this->assertDatabaseHas('paper_evaluations', [
            'folio' => $folio,
            'evaluation_type' => 'referencia_i',
            'organization_id' => $organization->id,
        ]);
    }

    public function test_can_get_evaluation_type_from_code(): void
    {
        $this->assertEquals('referencia_i', PaperEvaluation::getEvaluationTypeFromCode('01'));
        $this->assertEquals('referencia_iii', PaperEvaluation::getEvaluationTypeFromCode('02'));
        $this->assertEquals('referencia_v', PaperEvaluation::getEvaluationTypeFromCode('03'));
        $this->assertEquals('cisneros', PaperEvaluation::getEvaluationTypeFromCode('04'));
    }

    public function test_can_mark_evaluation_as_completed(): void
    {
        $evaluation = PaperEvaluation::factory()->pending()->create();

        $this->assertEquals('pending', $evaluation->processing_status);
        $this->assertNull($evaluation->processed_at);

        $evaluation->markAsCompleted();

        $this->assertEquals('completed', $evaluation->fresh()->processing_status);
        $this->assertNotNull($evaluation->fresh()->processed_at);
    }

    public function test_can_mark_evaluation_as_failed(): void
    {
        $evaluation = PaperEvaluation::factory()->pending()->create();

        $evaluation->markAsFailed('Test error message');

        $this->assertEquals('failed', $evaluation->fresh()->processing_status);
        $this->assertEquals('Test error message', $evaluation->fresh()->processing_error);
        $this->assertEquals(1, $evaluation->fresh()->retry_count);
    }

    public function test_can_filter_by_evaluation_type(): void
    {
        PaperEvaluation::factory()->referenciaI()->create();
        PaperEvaluation::factory()->referenciaIII()->create();
        PaperEvaluation::factory()->referenciaV()->create();

        $referenciaI = PaperEvaluation::ofType('referencia_i')->get();
        $referenciaIII = PaperEvaluation::ofType('referencia_iii')->get();

        $this->assertCount(1, $referenciaI);
        $this->assertCount(1, $referenciaIII);
    }

    public function test_can_filter_by_source(): void
    {
        PaperEvaluation::factory()->create(['source' => 'paper']);
        PaperEvaluation::factory()->online()->create();

        $paperEvaluations = PaperEvaluation::fromSource('paper')->get();
        $onlineEvaluations = PaperEvaluation::fromSource('online')->get();

        $this->assertCount(1, $paperEvaluations);
        $this->assertCount(1, $onlineEvaluations);
    }

    public function test_same_folio_can_exist_across_different_sources(): void
    {
        PaperEvaluation::factory()->create([
            'folio' => '02010900012',
            'source' => 'online',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'organization_code' => '01',
            'work_center_code' => '09',
            'personal_folio' => '00012',
        ]);

        PaperEvaluation::factory()->create([
            'folio' => '02010900012',
            'source' => 'paper',
            'evaluation_type' => 'referencia_iii',
            'evaluation_type_code' => '02',
            'organization_code' => '01',
            'work_center_code' => '09',
            'personal_folio' => '00012',
        ]);

        $this->assertSame(2, PaperEvaluation::query()->where('folio', '02010900012')->count());
        $this->assertSame(1, PaperEvaluation::query()->where('folio', '02010900012')->where('source', 'online')->count());
        $this->assertSame(1, PaperEvaluation::query()->where('folio', '02010900012')->where('source', 'paper')->count());
    }

    public function test_is_folio_available_is_checked_per_source(): void
    {
        $evaluation = PaperEvaluation::factory()->create([
            'folio' => '02010900013',
            'source' => 'online',
        ]);

        $this->assertFalse(PaperEvaluation::isFolioAvailable('02010900013', 'online'));
        $this->assertTrue(PaperEvaluation::isFolioAvailable('02010900013', 'paper'));
        $this->assertTrue(PaperEvaluation::isFolioAvailable('02010900013', 'online', $evaluation->id));
    }

    public function test_can_filter_by_status(): void
    {
        PaperEvaluation::factory()->create(['processing_status' => 'completed']);
        PaperEvaluation::factory()->failed()->create();
        PaperEvaluation::factory()->pending()->create();

        $completed = PaperEvaluation::completed()->get();
        $failed = PaperEvaluation::failed()->get();

        $this->assertCount(1, $completed);
        $this->assertCount(1, $failed);
    }

    public function test_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertInstanceOf(Organization::class, $evaluation->organization);
        $this->assertEquals($organization->id, $evaluation->organization->id);
    }

    public function test_stores_json_data_correctly(): void
    {
        $demographicData = [
            'sexo' => 'masculino',
            'edad' => ['decenas' => '3', 'unidades' => '5'],
        ];

        $evaluation = PaperEvaluation::factory()->create([
            'demographic_data' => $demographicData,
        ]);

        $this->assertEquals($demographicData, $evaluation->fresh()->demographic_data);
    }

    public function test_likert_data_structure_is_stored_correctly(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '301',
        ]);

        $folio = '053010001';
        $likertData = [
            'questions' => [
                '1' => 'A',
                '2' => 'A',
                '3' => 'A',
            ],
            'areas' => 1,
            'turno' => 'nocturno',
            'genero' => 'masculino',
            'puestos' => 1,
            'tipo_contrato' => 'confianza',
        ];

        $evaluation = PaperEvaluation::create([
            'folio' => $folio,
            'evaluation_type_code' => '05',
            'organization_code' => '301',
            'personal_folio' => '0001',
            'organization_id' => $organization->id,
            'evaluation_type' => 'likert',
            'source' => 'paper',
            'processing_status' => 'completed',
            'likert_answers' => $likertData,
        ]);

        // Verify the likert data structure is stored correctly
        $this->assertEquals($likertData, $evaluation->fresh()->likert_answers);
        $this->assertIsArray($evaluation->fresh()->likert_answers['questions']);
        $this->assertEquals('masculino', $evaluation->fresh()->likert_answers['genero']);
        $this->assertEquals('nocturno', $evaluation->fresh()->likert_answers['turno']);
        $this->assertEquals('confianza', $evaluation->fresh()->likert_answers['tipo_contrato']);
        $this->assertEquals(1, $evaluation->fresh()->likert_answers['areas']);
        $this->assertEquals(1, $evaluation->fresh()->likert_answers['puestos']);
    }

    public function test_job_calls_ocr_service_and_persists_evaluation(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '953',
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        \Illuminate\Support\Facades\Http::fake([
            config('services.ocr.url').'/process' => \Illuminate\Support\Facades\Http::response([
                'results' => [
                    [
                        'folio' => '019530001',
                        'answers' => ['1' => 'SI', '2' => 'NO'],
                        'marked_image_base64' => null,
                    ],
                ],
            ], 200),
        ]);

        \Illuminate\Support\Facades\Event::fake();

        $job = new \App\Jobs\ProcessPaperEvaluation(
            $tmpFile,
            null,
            null,
            1,
            1,
            'test.pdf'
        );

        $job->handle();

        $this->assertDatabaseHas('paper_evaluations', [
            'folio' => '019530001',
            'evaluation_type' => 'referencia_i',
            'processing_status' => 'completed',
        ]);

        @unlink($tmpFile);
    }

    public function test_job_assigns_work_center_from_folio_code_when_available(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '95',
        ]);

        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'code' => '0002',
            'name' => 'Centro 02',
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        \Illuminate\Support\Facades\Http::fake([
            config('services.ocr.url').'/process' => \Illuminate\Support\Facades\Http::response([
                'results' => [
                    [
                        'folio' => '02950200001',
                        'answers' => ['referencia_iii' => ['pregunta_1' => 'SI']],
                        'marked_image_base64' => null,
                    ],
                ],
            ], 200),
        ]);

        \Illuminate\Support\Facades\Event::fake();

        $job = new \App\Jobs\ProcessPaperEvaluation(
            $tmpFile,
            null,
            null,
            1,
            1,
            'test.pdf'
        );

        $job->handle();

        $this->assertDatabaseHas('paper_evaluations', [
            'folio' => '02950200001',
            'source' => 'paper',
            'organization_id' => $organization->id,
            'work_center_code' => '02',
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
        ]);

        @unlink($tmpFile);
    }

    public function test_job_throws_exception_when_ocr_service_fails(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        \Illuminate\Support\Facades\Http::fake([
            config('services.ocr.url').'/process' => \Illuminate\Support\Facades\Http::response([
                'error' => 'Error interno del servidor',
                'detail' => 'Fallo al procesar el PDF',
            ], 500),
        ]);

        \Illuminate\Support\Facades\Event::fake();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/OCR service returned error/');

        $job = new \App\Jobs\ProcessPaperEvaluation(
            $tmpFile,
            null,
            null,
            1,
            1,
            'test.pdf'
        );

        $job->handle();

        @unlink($tmpFile);
    }

    public function test_job_normalizes_cisneros_answers_to_canonical_json_structure(): void
    {
        Organization::factory()->create([
            'folio_organization' => '953',
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        \Illuminate\Support\Facades\Http::fake([
            config('services.ocr.url').'/process' => \Illuminate\Support\Facades\Http::response([
                'results' => [
                    [
                        'folio' => '049530001',
                        'answers' => [
                            'cisneros' => [
                                'persona1' => 'b',
                                'frecuencia1' => '5',
                                'frecuencia3' => '0',
                                'pregunta44' => 'sí',
                            ],
                        ],
                        'marked_image_base64' => null,
                    ],
                ],
            ], 200),
        ]);

        \Illuminate\Support\Facades\Event::fake();

        $job = new \App\Jobs\ProcessPaperEvaluation(
            $tmpFile,
            null,
            null,
            1,
            1,
            'test-cisneros.pdf'
        );

        $job->handle();

        $evaluation = PaperEvaluation::where('folio', '049530001')->first();
        $this->assertNotNull($evaluation);
        $this->assertEquals([
            '1' => ['persona' => 'B', 'frecuencia' => 5],
            '3' => ['persona' => null, 'frecuencia' => 0],
            '44' => true,
        ], $evaluation->cisneros_answers);

        @unlink($tmpFile);
    }

    public function test_job_extracts_citsats_from_flat_referencia_iii_raw_data(): void
    {
        Organization::factory()->create([
            'folio_organization' => '953',
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        \Illuminate\Support\Facades\Http::fake([
            config('services.ocr.url').'/process' => \Illuminate\Support\Facades\Http::response([
                'results' => [
                    [
                        'folio' => '029530001',
                        'answers' => [
                            '75' => ['mapping_section' => 'ats', 'value' => 'NO'],
                            '76' => ['mapping_section' => 'ats', 'value' => 'SI'],
                            '77' => ['mapping_section' => 'ats', 'value' => 'NO'],
                            '78' => ['mapping_section' => 'ats', 'value' => 'NO'],
                            '79' => ['mapping_section' => 'ats', 'value' => 'SI'],
                            '80' => ['mapping_section' => 'ats', 'value' => 'NO'],
                        ],
                        'marked_image_base64' => null,
                    ],
                ],
            ], 200),
        ]);

        \Illuminate\Support\Facades\Event::fake();

        $job = new \App\Jobs\ProcessPaperEvaluation(
            $tmpFile,
            null,
            null,
            1,
            1,
            'test-refiii-flat.pdf'
        );

        $job->handle();

        $evaluation = PaperEvaluation::where('folio', '029530001')->first();
        $this->assertNotNull($evaluation);
        $this->assertEquals([
            '1' => 'NO',
            '2' => 'SI',
            '3' => 'NO',
            '4' => 'NO',
            '5' => 'SI',
            '6' => 'NO',
        ], $evaluation->citsats_s1);

        @unlink($tmpFile);
    }

    public function test_job_propagates_referencia_v_demographics_to_related_ref_i_record(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '95',
        ]);

        WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'code' => '0002',
            'name' => 'Centro 02',
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        \Illuminate\Support\Facades\Http::fake([
            config('services.ocr.url').'/process' => \Illuminate\Support\Facades\Http::response([
                'results' => [
                    [
                        'folio' => '01950200001',
                        'answers' => [
                            '1' => ['value' => 'SI'],
                            '2' => ['value' => 'NO'],
                        ],
                        'marked_image_base64' => null,
                    ],
                    [
                        'folio' => '03950200001',
                        'answers' => [
                            'sexo' => 'masculino',
                            'edad' => ['decenas' => 3, 'unidades' => 0],
                            'estado_civil' => 'soltero',
                            'nivel_estudios' => 'licenciatura',
                            'ocupacion_puesto' => 'Analista',
                            'departamento_seccion_area' => 'Operaciones',
                            'tipo_puesto' => 'operativo',
                            'tipo_contratacion' => 'tiempo_indeterminado',
                            'tipo_personal' => 'sindicalizado',
                            'tipo_jornada' => 'diurno',
                            'rotacion_turnos' => 'no',
                            'tiempo_puesto_actual' => 'entre_6_meses_y_1_anio',
                            'tiempo_experiencia_laboral' => 'entre_1_y_4_anios',
                        ],
                        'marked_image_base64' => null,
                    ],
                ],
            ], 200),
        ]);

        \Illuminate\Support\Facades\Event::fake();

        $job = new \App\Jobs\ProcessPaperEvaluation(
            $tmpFile,
            null,
            null,
            1,
            1,
            'test.pdf'
        );

        $job->handle();

        $refI = PaperEvaluation::query()
            ->where('folio', '01950200001')
            ->where('source', 'paper')
            ->firstOrFail();

        $this->assertNotNull($refI->fresh()->demographic_data);
        $this->assertNotNull($refI->fresh()->demographicData);
        $this->assertInstanceOf(DemographicData::class, $refI->fresh()->demographicData);
        $this->assertEquals('Masculino', $refI->fresh()->demographicData->gender);
        $this->assertEquals('Operaciones', $refI->fresh()->demographicData->department);

        @unlink($tmpFile);
    }

    public function test_job_syncs_demographics_for_ref_i_when_referencia_v_already_exists(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '95',
        ]);

        WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'code' => '0002',
            'name' => 'Centro 02',
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_').'.pdf';
        file_put_contents($tmpFile, '%PDF-1.4 fake content');

        \Illuminate\Support\Facades\Http::fake([
            config('services.ocr.url').'/process' => \Illuminate\Support\Facades\Http::response([
                'results' => [
                    [
                        'folio' => '03950200002',
                        'answers' => [
                            'sexo' => 'femenino',
                            'edad' => ['decenas' => 2, 'unidades' => 8],
                            'estado_civil' => 'casado',
                            'nivel_estudios' => 'licenciatura',
                            'ocupacion_puesto' => 'Coordinador',
                            'departamento_seccion_area' => 'Administracion',
                            'tipo_puesto' => 'supervision',
                            'tipo_contratacion' => 'tiempo_indeterminado',
                            'tipo_personal' => 'confianza',
                            'tipo_jornada' => 'diurno',
                            'rotacion_turnos' => 'no',
                            'tiempo_puesto_actual' => 'entre_1_y_4_anios',
                            'tiempo_experiencia_laboral' => 'entre_5_y_10_anios',
                        ],
                        'marked_image_base64' => null,
                    ],
                    [
                        'folio' => '01950200002',
                        'answers' => [
                            '1' => ['value' => 'NO'],
                            '2' => ['value' => 'NO'],
                        ],
                        'marked_image_base64' => null,
                    ],
                ],
            ], 200),
        ]);

        \Illuminate\Support\Facades\Event::fake();

        $job = new \App\Jobs\ProcessPaperEvaluation(
            $tmpFile,
            null,
            null,
            1,
            1,
            'test.pdf'
        );

        $job->handle();

        $refI = PaperEvaluation::query()
            ->where('folio', '01950200002')
            ->where('source', 'paper')
            ->firstOrFail();

        $this->assertNotNull($refI->fresh()->demographic_data);
        $this->assertNotNull($refI->fresh()->demographicData);
        $this->assertEquals('Femenino', $refI->fresh()->demographicData->gender);
        $this->assertEquals('Administracion', $refI->fresh()->demographicData->department);

        @unlink($tmpFile);
    }
}
