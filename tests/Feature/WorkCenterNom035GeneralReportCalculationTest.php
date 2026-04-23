<?php

namespace Tests\Feature;

use App\Models\EvaluationAnswer;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\WorkCenter\WorkCenterNom035CalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterNom035GeneralReportCalculationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_general_report_uses_sum_of_normalized_item_scores_for_nom_levels(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'referencia_iii_answers' => $this->buildAnswers('A'),
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'referencia_iii_answers' => $this->buildAnswers('C'),
        ]);

        $service = new WorkCenterNom035CalculationService;
        $report = $service->getGeneralDetailedReport($workCenter);

        $questionOneRow = collect($report['rows'])
            ->first(fn (array $row): bool => $row['item_numero'] === 1);

        $this->assertNotNull($questionOneRow);
        $this->assertSame(1.0, $questionOneRow['puntaje']);

        $this->assertSame('Condiciones peligrosas e inseguras', $questionOneRow['dimension']['nombre']);
        $this->assertSame(4, $questionOneRow['dimension']['score']);
        $this->assertSame('medio', $questionOneRow['dimension']['nivel_riesgo']);

        $this->assertSame('Condiciones en el ambiente de trabajo', $questionOneRow['dominio']['nombre']);
        $this->assertSame(11, $questionOneRow['dominio']['score']);
        $this->assertSame('alto', $questionOneRow['dominio']['nivel_riesgo']);

        $this->assertSame('Ambiente de trabajo', $questionOneRow['categoria']['nombre']);
        $this->assertSame(11, $questionOneRow['categoria']['score']);
        $this->assertSame('alto', $questionOneRow['categoria']['nivel_riesgo']);

        $this->assertSame(288, $report['max_score']);
        $this->assertSame(122, $report['total_score']);

        foreach ($report['rows'] as $row) {
            $this->assertGreaterThanOrEqual(0, $row['puntaje']);
            $this->assertLessThanOrEqual(4, $row['puntaje']);
        }
    }

    public function test_general_report_includes_customer_service_conditional_questions_when_enabled(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'referencia_iii_answers' => $this->buildAnswers('A'),
            'referencia_iii_conditional' => [
                'customer_service' => [
                    'condition' => 'SI',
                    'questions' => [
                        65 => 'D',
                        66 => 'E',
                        67 => 'D',
                        68 => 'E',
                    ],
                ],
                'management' => [
                    'condition' => 'NO',
                    'questions' => [
                        69 => null,
                        70 => null,
                        71 => null,
                        72 => null,
                    ],
                ],
            ],
        ]);

        $service = new WorkCenterNom035CalculationService;
        $report = $service->getGeneralDetailedReport($workCenter);

        $question65Row = collect($report['rows'])
            ->first(fn (array $row): bool => $row['item_numero'] === 65);

        $this->assertNotNull($question65Row);
        $this->assertSame(1.0, $question65Row['puntaje']);
        $this->assertSame('Cargas psicológicas emocionales', $question65Row['dimension']['nombre']);
        $this->assertSame(2, $question65Row['dimension']['score']);
    }

    public function test_question_statistics_defaults_missing_conditional_answers_to_option_e(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'referencia_iii_answers' => $this->buildAnswers('C'),
            'referencia_iii_conditional' => [
                'customer_service' => [
                    'condition' => 'SI',
                    'questions' => [
                        65 => 'A',
                        66 => 'B',
                        67 => 'C',
                        68 => 'D',
                    ],
                ],
                'management' => [
                    'condition' => 'NO',
                    'questions' => [
                        69 => null,
                        70 => null,
                        71 => null,
                        72 => null,
                    ],
                ],
            ],
        ]);

        PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            'referencia_iii_answers' => $this->buildAnswers('C'),
            'referencia_iii_conditional' => [
                'customer_service' => [
                    'condition' => 'NO',
                    'questions' => [
                        65 => null,
                        66 => null,
                        67 => null,
                        68 => null,
                    ],
                ],
                'management' => [
                    'condition' => 'NO',
                    'questions' => [
                        69 => null,
                        70 => null,
                        71 => null,
                        72 => null,
                    ],
                ],
            ],
        ]);

        $service = new WorkCenterNom035CalculationService;
        $stats = $service->calculateQuestionStatistics($workCenter);

        $questionStats = json_decode(json_encode($stats['questions']), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(2, $questionStats[65]['totalResponses']);
        $this->assertSame(1, $questionStats[65]['responses']['siempre']);
        $this->assertSame(1, $questionStats[65]['responses']['nunca']);

        $this->assertSame(2, $questionStats[69]['totalResponses']);
        $this->assertSame(2, $questionStats[69]['responses']['nunca']);
    }

    /**
     * When evaluation_answers rows exist for an evaluation, the service must use those rows
     * as the authoritative source instead of the legacy JSON columns.  This prevents the
     * dashboard distribution from diverging from the executive-report distribution.
     *
     * Scenario: JSON columns have conditional questions 65-72 enabled (condition = 'SI'),
     * but evaluation_answers was populated with questions 1-64 only (condition was actually NO).
     * Expected: score is calculated from questions 1-64 only (= 116 for all-A answers).
     * If JSON were used instead, the score would be 148 (116 + 8 × 4 for questions 65-72).
     */
    public function test_calculation_uses_evaluation_answers_table_when_available(): void
    {
        $organization = Organization::factory()->create();
        $workCenter = WorkCenter::factory()->create(['organization_id' => $organization->id]);

        $evaluation = PaperEvaluation::factory()->create([
            'organization_id' => $organization->id,
            'work_center_id' => $workCenter->id,
            'processing_status' => 'completed',
            'evaluation_type' => 'referencia_iii',
            'source' => 'paper',
            // JSON columns: conditional says 65-72 are enabled (all A)
            'referencia_iii_answers' => $this->buildAnswers('A'),
            'referencia_iii_conditional' => [
                'customer_service' => [
                    'condition' => 'SI',
                    'questions' => [65 => 'A', 66 => 'A', 67 => 'A', 68 => 'A'],
                ],
                'management' => [
                    'condition' => 'SI',
                    'questions' => [69 => 'A', 70 => 'A', 71 => 'A', 72 => 'A'],
                ],
            ],
        ]);

        // evaluation_answers has only questions 1-64 (conditions were NO when processed)
        foreach (range(1, 64) as $q) {
            EvaluationAnswer::create([
                'paper_evaluation_id' => $evaluation->id,
                'instrument' => 'referencia_iii',
                'question_key' => (string) $q,
                'answer_value' => 'A',
            ]);
        }

        $service = new WorkCenterNom035CalculationService;
        $stats = $service->calculateGlobalStatistics($workCenter);

        // Group-2 questions in 1-64 answered A (score=4): q02,03,05-22,29,54,58-64 = 29 questions → 116
        // If JSON were used: +8 conditional questions (all A, group-2) → 148
        $this->assertSame(1, $stats['global']['total_evaluations']);
        $this->assertSame(116, (int) $stats['global']['average_score']);
    }

    /**
     * @return array<int, string>
     */
    private function buildAnswers(string $answer): array
    {
        $answers = [];

        for ($questionNumber = 1; $questionNumber <= 64; $questionNumber++) {
            $answers[$questionNumber] = $answer;
        }

        return $answers;
    }
}
