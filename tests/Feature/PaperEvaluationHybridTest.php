<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaperEvaluationHybridTest extends TestCase
{
    use DatabaseTransactions;

    public function test_is_complete_returns_true_when_hybrid_has_demographic_and_referencia_iii(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => ['edad' => '25-30', 'sexo' => 'M'],
            'referencia_iii_answers' => ['q1' => 0, 'q2' => 1],
            'referencia_i_answers' => null,
        ]);

        $this->assertTrue($evaluation->isComplete());
    }

    public function test_is_complete_returns_true_when_hybrid_has_demographic_and_referencia_i(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => ['edad' => '25-30', 'sexo' => 'M'],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => ['q1' => true, 'q2' => false],
        ]);

        $this->assertTrue($evaluation->isComplete());
    }

    public function test_is_complete_returns_false_when_hybrid_missing_demographic_data(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => null,
            'referencia_iii_answers' => ['q1' => 0, 'q2' => 1],
        ]);

        $this->assertFalse($evaluation->isComplete());
    }

    public function test_is_complete_returns_false_when_hybrid_missing_online_answers(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => ['edad' => '25-30', 'sexo' => 'M'],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $this->assertFalse($evaluation->isComplete());
    }

    public function test_is_partially_complete_returns_true_when_only_demographic_data(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => ['edad' => '25-30', 'sexo' => 'M'],
            'referencia_iii_answers' => null,
            'referencia_i_answers' => null,
        ]);

        $this->assertTrue($evaluation->isPartiallyComplete());
    }

    public function test_is_partially_complete_returns_true_when_only_online_answers(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => null,
            'referencia_iii_answers' => ['q1' => 0, 'q2' => 1],
        ]);

        $this->assertTrue($evaluation->isPartiallyComplete());
    }

    public function test_is_partially_complete_returns_false_when_both_parts_complete(): void
    {
        $organization = Organization::factory()->create();

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'source' => 'hybrid',
            'demographic_data' => ['edad' => '25-30', 'sexo' => 'M'],
            'referencia_iii_answers' => ['q1' => 0, 'q2' => 1],
        ]);

        $this->assertFalse($evaluation->isPartiallyComplete());
    }
}
