<?php

namespace Tests\Feature;

use App\Models\FolioBatch;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HybridEvaluationE2ETest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_hybrid_evaluation_flow_from_creation_to_completion(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization',
        ]);

        // Step 1: Create hybrid FolioBatch
        $batch = FolioBatch::create([
            'organization_id' => $organization->id,
            'name' => 'Hybrid Batch E2E Test',
            'description' => 'End-to-end test batch',
            'start_number' => 1,
            'end_number' => 10,
            'quantity' => 10,
            'type' => FolioBatch::TYPE_HIBRIDO,
        ]);

        $this->assertEquals('hibrido', $batch->type);

        // Step 2: Simulate OMR processing creating PaperEvaluation with demographic data
        $evaluation = PaperEvaluation::create([
            'folio' => '010011234',
            'evaluation_type_code' => '01',
            'organization_code' => '001',
            'personal_folio' => '1234',
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_i',
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => [
                'sexo' => 'Masculino',
                'edad' => '30',
                'estado_civil' => 'Casado',
                'nivel_estudios' => 'Licenciatura',
                'ocupacion_puesto' => ['fila1' => 'Analista'],
                'departamento_seccion_area' => ['fila1' => 'IT'],
            ],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $this->assertDatabaseHas('paper_evaluations', [
            'id' => $evaluation->id,
            'folio' => '010011234',
            'source' => 'hybrid',
            'processing_status' => 'pending',
        ]);

        // Step 3: Access hybrid evaluation form via GET /h/{folio}
        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Hibrido/Take')
            ->where('evaluationId', $evaluation->id)
            ->where('folio', '010011234')
            ->where('organizationName', 'Test Organization')
        );

        // Step 4: Submit online portion via PUT /hybrid-evaluations/{paperEvaluation}
        $referenciaIIIData = [
            '1' => 'Siempre',
            '2' => 'Casi siempre',
            '3' => 'Algunas veces',
        ];

        $referenciaIData = [
            '1' => 'Sí',
            '2' => 'No',
            '3' => 'Sí',
        ];

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode($referenciaIIIData),
            'referencia_i' => json_encode($referenciaIData),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Evaluación completada exitosamente',
            'is_complete' => true,
        ]);

        // Step 5: Verify completion in database
        $evaluation->refresh();
        $this->assertEquals('completed', $evaluation->processing_status);
        $this->assertEquals($referenciaIIIData, $evaluation->referencia_iii_answers);
        $this->assertEquals($referenciaIData, $evaluation->referencia_i_answers);
        $this->assertTrue($evaluation->isComplete());
        $this->assertNotNull($evaluation->processed_at);

        // Step 6: Verify cannot access form again (410 status)
        $response = $this->get(route('hybrid.show', $evaluation->folio));
        $response->assertStatus(410);

        // Step 7: Verify cannot submit again (409 status)
        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Nunca']),
        ]);
        $response->assertStatus(409);
    }

    public function test_partial_completion_online_first_then_omr_merge(): void
    {
        $organization = Organization::factory()->create();

        // Step 1: Create evaluation with only online portion completed
        $evaluation = PaperEvaluation::create([
            'folio' => '020021234',
            'evaluation_type_code' => '02',
            'organization_code' => '002',
            'personal_folio' => '1234',
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'hybrid',
            'processing_status' => 'completed',
            'demographic_data' => null,
            'referencia_iii_answers' => [
                '1' => 'Siempre',
                '2' => 'Casi siempre',
            ],
            'referencia_i_answers' => null,
            'processed_at' => now(),
        ]);

        $this->assertTrue($evaluation->isPartiallyComplete());
        $this->assertFalse($evaluation->isComplete());

        // Step 2: Simulate OMR processing adding demographic data later
        $evaluation->update([
            'demographic_data' => [
                'sexo' => 'Femenino',
                'edad' => '28',
                'estado_civil' => 'Soltera',
            ],
        ]);

        $evaluation->refresh();
        $this->assertTrue($evaluation->isComplete());
        $this->assertNotNull($evaluation->demographic_data);
        $this->assertNotNull($evaluation->referencia_iii_answers);
    }

    public function test_omr_processing_after_online_completion_does_not_overwrite_online_data(): void
    {
        $organization = Organization::factory()->create();

        // Step 1: Create evaluation with online portion completed
        $originalOnlineAnswers = [
            '1' => 'Siempre',
            '2' => 'Casi siempre',
            '3' => 'Algunas veces',
        ];

        $evaluation = PaperEvaluation::create([
            'folio' => '030031234',
            'evaluation_type_code' => '03',
            'organization_code' => '003',
            'personal_folio' => '1234',
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'hybrid',
            'processing_status' => 'completed',
            'demographic_data' => null,
            'referencia_iii_answers' => $originalOnlineAnswers,
            'referencia_i_answers' => ['1' => 'Sí', '2' => 'No'],
            'processed_at' => now()->subHours(2),
        ]);

        // Step 2: Simulate OMR processing (should only add demographic data)
        $newDemographicData = [
            'sexo' => 'Masculino',
            'edad' => '35',
            'estado_civil' => 'Casado',
        ];

        $evaluation->update([
            'demographic_data' => $newDemographicData,
        ]);

        // Step 3: Verify online answers were NOT overwritten
        $evaluation->refresh();
        $this->assertEquals($originalOnlineAnswers, $evaluation->referencia_iii_answers);
        $this->assertEquals(['1' => 'Sí', '2' => 'No'], $evaluation->referencia_i_answers);
        $this->assertEquals($newDemographicData, $evaluation->demographic_data);
        $this->assertTrue($evaluation->isComplete());
    }

    public function test_hybrid_evaluation_with_only_demographic_data_is_partially_complete(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::create([
            'folio' => '040041234',
            'evaluation_type_code' => '04',
            'organization_code' => '004',
            'personal_folio' => '1234',
            'organization_id' => $organization->id,
            'evaluation_type' => 'cisneros',
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => [
                'sexo' => 'Masculino',
                'edad' => '30',
            ],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $this->assertTrue($evaluation->isPartiallyComplete());
        $this->assertFalse($evaluation->isComplete());

        // Can still access form to complete online portion
        $response = $this->get(route('hybrid.show', $evaluation->folio));
        $response->assertStatus(200);
    }

    public function test_hybrid_evaluation_with_only_online_answers_is_partially_complete(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::create([
            'folio' => '050051234',
            'evaluation_type_code' => '05',
            'organization_code' => '005',
            'personal_folio' => '1234',
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'hybrid',
            'processing_status' => 'completed',
            'demographic_data' => null,
            'referencia_iii_answers' => [
                '1' => 'Siempre',
                '2' => 'Nunca',
            ],
            'referencia_i_answers' => null,
            'processed_at' => now(),
        ]);

        $this->assertTrue($evaluation->isPartiallyComplete());
        $this->assertFalse($evaluation->isComplete());

        // Cannot access form again since online portion is completed
        $response = $this->get(route('hybrid.show', $evaluation->folio));
        $response->assertStatus(410);
    }

    public function test_hybrid_batch_creation_generates_multiple_folios(): void
    {
        $organization = Organization::factory()->create();

        $batch = FolioBatch::create([
            'organization_id' => $organization->id,
            'name' => 'Multiple Folios Hybrid Test',
            'description' => 'Test multiple folio generation',
            'start_number' => 1,
            'end_number' => 5,
            'quantity' => 5,
            'type' => FolioBatch::TYPE_HIBRIDO,
        ]);

        $this->assertEquals(5, $batch->quantity);
        $this->assertTrue($batch->isHibrido());
    }

    public function test_omr_job_creates_hybrid_paper_evaluation_with_demographic_data(): void
    {
        Queue::fake();

        $organization = Organization::factory()->create();

        // Simulate OCR output data
        $omrData = [
            'folio' => '060061234',
            'evaluation_type_code' => '01',
            'organization_code' => '006',
            'personal_folio' => '1234',
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => [
                'sexo' => 'Femenino',
                'edad' => '25',
                'estado_civil' => 'Soltera',
            ],
        ];

        // Create the evaluation as OMR processor would
        $evaluation = PaperEvaluation::create([
            'folio' => $omrData['folio'],
            'evaluation_type_code' => $omrData['evaluation_type_code'],
            'organization_code' => $omrData['organization_code'],
            'personal_folio' => $omrData['personal_folio'],
            'organization_id' => $omrData['organization_id'],
            'evaluation_type' => 'referencia_i',
            'source' => $omrData['source'],
            'processing_status' => 'pending',
            'demographic_data' => $omrData['demographic_data'],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $this->assertDatabaseHas('paper_evaluations', [
            'folio' => '060061234',
            'source' => 'hybrid',
            'processing_status' => 'pending',
        ]);

        $this->assertEquals('hybrid', $evaluation->source);
        $this->assertNotNull($evaluation->demographic_data);
        $this->assertNull($evaluation->referencia_iii_answers);
        $this->assertTrue($evaluation->isPartiallyComplete());
        $this->assertFalse($evaluation->isComplete());
    }

    public function test_complete_hybrid_evaluation_flow_with_conditional_answers(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::create([
            'folio' => '070071234',
            'evaluation_type_code' => '02',
            'organization_code' => '007',
            'personal_folio' => '1234',
            'organization_id' => $organization->id,
            'evaluation_type' => 'referencia_iii',
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => [
                'sexo' => 'Masculino',
                'edad' => '40',
            ],
            'referencia_iii_answers' => null,
            'referencia_iii_conditional' => null,
        ]);

        $referenciaIIIData = ['1' => 'Siempre', '2' => 'Casi siempre'];
        $conditionalData = ['47' => 'Siempre', '48' => 'Nunca'];

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode($referenciaIIIData),
            'referencia_iii_conditional' => json_encode($conditionalData),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_complete' => true,
        ]);

        $evaluation->refresh();
        $this->assertEquals($referenciaIIIData, $evaluation->referencia_iii_answers);
        $this->assertEquals($conditionalData, $evaluation->referencia_iii_conditional);
        $this->assertEquals('completed', $evaluation->processing_status);
        $this->assertTrue($evaluation->isComplete());
    }
}
