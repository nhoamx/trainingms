<?php

namespace Tests\Feature\Unit\Services;

use App\Models\PaperEvaluation;
use App\Services\PaperEvaluationScoreService;
use Tests\TestCase;

class PaperEvaluationScoreServiceTest extends TestCase
{
    public function test_it_includes_customer_service_questions_from_raw_data_when_condition_is_missing(): void
    {
        $service = app(PaperEvaluationScoreService::class);

        $evaluation = new PaperEvaluation([
            'evaluation_type' => 'referencia_iii',
            'referencia_iii_answers' => [
                '06' => 'A',
                '12' => 'A',
            ],
            'referencia_iii_conditional' => null,
            'raw_data' => [
                'referencia_iii' => [
                    '65' => 'E',
                    '66' => 'A',
                    '67' => 'E',
                    '68' => 'C',
                ],
            ],
        ]);

        $results = $service->getDetailedResults($evaluation);
        $itemNumbers = collect($results)->pluck('item_numero')->values()->all();

        $this->assertContains(65, $itemNumbers);
        $this->assertContains(66, $itemNumbers);
        $this->assertContains(67, $itemNumbers);
        $this->assertContains(68, $itemNumbers);

        $item65 = collect($results)->firstWhere('item_numero', 65);
        $this->assertNotNull($item65);
        $this->assertSame('Atiendo clientes o usuarios muy enojados', $item65['item']);
    }

    public function test_it_includes_management_questions_from_raw_data_when_condition_is_missing(): void
    {
        $service = app(PaperEvaluationScoreService::class);

        $evaluation = new PaperEvaluation([
            'evaluation_type' => 'referencia_iii',
            'referencia_iii_answers' => [
                '42' => 'A',
                '43' => 'A',
            ],
            'referencia_iii_conditional' => null,
            'raw_data' => [
                'referencia_iii' => [
                    '69' => 'D',
                    '70' => 'E',
                    '71' => 'C',
                    '72' => 'B',
                ],
            ],
        ]);

        $results = $service->getDetailedResults($evaluation);
        $itemNumbers = collect($results)->pluck('item_numero')->values()->all();

        $this->assertContains(69, $itemNumbers);
        $this->assertContains(70, $itemNumbers);
        $this->assertContains(71, $itemNumbers);
        $this->assertContains(72, $itemNumbers);

        $item69 = collect($results)->firstWhere('item_numero', 69);
        $this->assertNotNull($item69);
        $this->assertSame('Comunican tarde los asuntos de trabajo', $item69['item']);

        $scores = $service->calculateReferenciaIIIScores($evaluation);
        $this->assertGreaterThan(0, $scores['total_score']);
    }

    public function test_it_excludes_management_questions_when_condition_is_no_even_if_raw_data_exists(): void
    {
        $service = app(PaperEvaluationScoreService::class);

        $evaluation = new PaperEvaluation([
            'evaluation_type' => 'referencia_iii',
            'referencia_iii_answers' => [
                '42' => 'A',
            ],
            'referencia_iii_conditional' => [
                'management' => [
                    'condition' => 'NO',
                    'questions' => [],
                ],
            ],
            'raw_data' => [
                'referencia_iii' => [
                    '69' => 'D',
                    '70' => 'E',
                    '71' => 'C',
                    '72' => 'B',
                ],
            ],
        ]);

        $results = $service->getDetailedResults($evaluation);
        $itemNumbers = collect($results)->pluck('item_numero')->values()->all();

        $this->assertNotContains(69, $itemNumbers);
        $this->assertNotContains(70, $itemNumbers);
        $this->assertNotContains(71, $itemNumbers);
        $this->assertNotContains(72, $itemNumbers);

        $scores = $service->calculateReferenciaIIIScores($evaluation);
        $dimensionKey = 'Liderazgo y relaciones en el trabajo|Relaciones en el trabajo|Deficiente relación con los colaboradores que supervisa';
        $this->assertSame(0, $scores['dimensions'][$dimensionKey]['score']);
    }
}
