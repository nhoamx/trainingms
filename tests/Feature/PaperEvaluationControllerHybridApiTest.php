<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaperEvaluationControllerHybridApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_update_hybrid_updates_referencia_iii_answers_successfully(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $referenciaIIIData = [
            '1' => 'Siempre',
            '2' => 'Casi siempre',
            '3' => 'Algunas veces',
        ];

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode($referenciaIIIData),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Evaluación completada exitosamente',
            'is_complete' => true,
        ]);

        $evaluation->refresh();
        $this->assertEquals($referenciaIIIData, $evaluation->referencia_iii_answers);
        $this->assertEquals('completed', $evaluation->processing_status);
        $this->assertNotNull($evaluation->processed_at);
    }

    public function test_update_hybrid_updates_referencia_i_answers_successfully(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $referenciaIData = [
            '1' => 'Sí',
            '2' => 'No',
            '3' => 'Sí',
        ];

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_i' => json_encode($referenciaIData),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Evaluación completada exitosamente',
            'is_complete' => true,
        ]);

        $evaluation->refresh();
        $this->assertEquals($referenciaIData, $evaluation->referencia_i_answers);
        $this->assertEquals('completed', $evaluation->processing_status);
        $this->assertNotNull($evaluation->processed_at);
    }

    public function test_update_hybrid_updates_both_referencia_iii_and_i_successfully(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $referenciaIIIData = ['1' => 'Siempre', '2' => 'Nunca'];
        $referenciaIData = ['1' => 'Sí', '2' => 'No'];

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode($referenciaIIIData),
            'referencia_i' => json_encode($referenciaIData),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_complete' => true,
        ]);

        $evaluation->refresh();
        $this->assertEquals($referenciaIIIData, $evaluation->referencia_iii_answers);
        $this->assertEquals($referenciaIData, $evaluation->referencia_i_answers);
        $this->assertEquals('completed', $evaluation->processing_status);
    }

    public function test_update_hybrid_updates_referencia_iii_conditional_answers(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
            'referencia_iii_conditional' => null,
        ]);

        $referenciaIIIData = ['1' => 'Siempre'];
        $conditionalData = ['47' => 'Siempre', '48' => 'Casi siempre'];

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode($referenciaIIIData),
            'referencia_iii_conditional' => json_encode($conditionalData),
        ]);

        $response->assertStatus(200);

        $evaluation->refresh();
        $this->assertEquals($referenciaIIIData, $evaluation->referencia_iii_answers);
        $this->assertEquals($conditionalData, $evaluation->referencia_iii_conditional);
    }

    public function test_update_hybrid_returns_403_when_source_is_not_hybrid(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'paper',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Siempre']),
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Esta evaluación no es de tipo híbrida',
        ]);
    }

    public function test_update_hybrid_returns_403_when_source_is_online(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'online',
            'processing_status' => 'pending',
            'referencia_iii_answers' => null,
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Siempre']),
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Esta evaluación no es de tipo híbrida',
        ]);
    }

    public function test_update_hybrid_returns_409_when_already_completed(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'completed',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => ['1' => 'Siempre'],
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Nunca']),
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'message' => 'Esta evaluación ya ha sido procesada',
        ]);
    }

    public function test_update_hybrid_returns_409_when_processing_status_is_processing(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'processing',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Siempre']),
        ]);

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'message' => 'Esta evaluación ya ha sido procesada',
        ]);
    }

    public function test_update_hybrid_sets_processing_status_to_completed(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $this->assertEquals('pending', $evaluation->processing_status);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Siempre']),
        ]);

        $response->assertStatus(200);

        $evaluation->refresh();
        $this->assertEquals('completed', $evaluation->processing_status);
    }

    public function test_update_hybrid_returns_is_complete_true_in_response(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Siempre']),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'is_complete' => true,
        ]);
    }

    public function test_update_hybrid_validates_referencia_iii_is_valid_json(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => 'invalid-json-string',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['referencia_iii']);
    }

    public function test_update_hybrid_validates_referencia_i_is_valid_json(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_i_answers' => null,
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_i' => 'not-valid-json',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['referencia_i']);
    }

    public function test_update_hybrid_stores_submission_metadata_in_raw_data(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
            'raw_data' => ['initial_key' => 'initial_value'],
        ]);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Siempre']),
        ]);

        $response->assertStatus(200);

        $evaluation->refresh();
        $this->assertNotNull($evaluation->raw_data);
        $this->assertArrayHasKey('online_completed_at', $evaluation->raw_data);
        $this->assertArrayHasKey('submission_ip', $evaluation->raw_data);
        $this->assertArrayHasKey('user_agent', $evaluation->raw_data);
        $this->assertArrayHasKey('initial_key', $evaluation->raw_data);
        $this->assertEquals('initial_value', $evaluation->raw_data['initial_key']);
    }

    public function test_update_hybrid_sets_processed_at_timestamp(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
            'processed_at' => null,
        ]);

        $this->assertNull($evaluation->processed_at);

        $response = $this->putJson(route('hybrid.update', $evaluation), [
            'referencia_iii' => json_encode(['1' => 'Siempre']),
        ]);

        $response->assertStatus(200);

        $evaluation->refresh();
        $this->assertNotNull($evaluation->processed_at);
    }
}
