<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HybridEvaluationControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_show_renders_form_when_folio_exists_and_source_hybrid_and_answers_null(): void
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

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Hibrido/Take')
            ->has('evaluationId')
            ->has('folio')
            ->has('organizationName')
            ->has('questions')
            ->has('referencia_i_questions')
            ->where('evaluationId', $evaluation->id)
            ->where('folio', $evaluation->folio)
            ->where('organizationName', $organization->name)
        );
    }

    public function test_show_returns_404_when_folio_does_not_exist(): void
    {
        $nonExistentFolio = '99999999999';

        $response = $this->get(route('hybrid.show', $nonExistentFolio));

        $response->assertStatus(404);
    }

    public function test_show_returns_403_when_source_is_paper(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'paper',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(403);
    }

    public function test_show_returns_403_when_source_is_online(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'online',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(403);
    }

    public function test_show_returns_410_when_already_completed_with_referencia_iii_answers(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'completed',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => ['1' => 'Siempre', '2' => 'Casi siempre'],
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(410);
    }

    public function test_show_returns_410_when_already_completed_with_both_answers(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'completed',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => ['1' => 'Siempre', '2' => 'Casi siempre'],
            'referencia_i_answers' => ['1' => 'Sí', '2' => 'No'],
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(410);
    }

    public function test_show_passes_correct_props_to_inertia(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization Name',
        ]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'folio' => '010011234',
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Hibrido/Take')
            ->where('evaluationId', $evaluation->id)
            ->where('folio', '010011234')
            ->where('organizationName', 'Test Organization Name')
            ->has('questions')
            ->has('referencia_i_questions')
        );
    }

    public function test_show_uses_default_organization_name_when_organization_is_null(): void
    {
        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => null,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Hibrido/Take')
            ->where('organizationName', 'Organización')
        );
    }

    public function test_show_includes_referencia_iii_question_config(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $referenciaIIIConfig = config('referencia_iii');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Hibrido/Take')
            ->where('questions', $referenciaIIIConfig)
        );
    }

    public function test_show_includes_referencia_i_question_config(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'demographic_data' => ['sexo' => 'Masculino', 'edad' => '30'],
            'referencia_iii_answers' => null,
        ]);

        $response = $this->get(route('hybrid.show', $evaluation->folio));

        $referenciaIConfig = config('referencia_i');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Hibrido/Take')
            ->where('referencia_i_questions', $referenciaIConfig)
        );
    }

    public function test_show_finds_evaluation_with_padded_folio_from_qr_scanner(): void
    {
        // Extended folio format: [type(2)][org(3)][person(4)] = 9 digits
        // Example: 03 + 030 + 0042 = 030300042
        // QR scanners may strip leading zeros: 030300042 -> 30300042 (or more if there are more leading zeros)
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'folio' => '030300042',  // Full 9-digit format stored in DB
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        // Access with folio that may have lost leading zeros
        // 030300042 -> 30300042 (lost first 0)
        $response = $this->get(route('hybrid.show', '30300042'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Hibrido/Take')
            ->where('evaluationId', $evaluation->id)
            ->where('folio', '030300042')
        );
    }

    public function test_update_accepts_json_string_answers(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $referenciaIIIData = ['1' => 'A', '2' => 'B'];
        $conditionalData = ['condition_management' => true];
        $referenciaIData = ['0' => true, '1' => false];

        $response = $this->put(route('hybrid.update', $evaluation->id), [
            'referencia_iii' => json_encode($referenciaIIIData),
            'referencia_iii_conditional' => json_encode($conditionalData),
            'referencia_i' => json_encode($referenciaIData),
        ]);

        $response->assertRedirect();

        $evaluation->refresh();

        // Las respuestas de referencia_iii y referencia_iii_conditional se combinan
        $expectedMerged = array_merge($referenciaIIIData, $conditionalData);
        $this->assertEquals($expectedMerged, $evaluation->referencia_iii_answers);
        $this->assertEquals($referenciaIData, $evaluation->referencia_i_answers);
        $this->assertEquals('completed', $evaluation->processing_status);
    }

    public function test_update_merges_conditional_answers_with_referencia_iii(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'processing_status' => 'pending',
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $referenciaIIIData = ['1' => 'A', '2' => 'B'];
        $conditionalData = ['condition_management' => true];

        $response = $this->put(route('hybrid.update', $evaluation->id), [
            'referencia_iii' => json_encode($referenciaIIIData),
            'referencia_iii_conditional' => json_encode($conditionalData),
            'referencia_i' => json_encode([]),
        ]);

        $response->assertRedirect();

        $evaluation->refresh();

        $expectedMerged = array_merge($referenciaIIIData, $conditionalData);
        $this->assertEquals($expectedMerged, $evaluation->referencia_iii_answers);
    }
}
