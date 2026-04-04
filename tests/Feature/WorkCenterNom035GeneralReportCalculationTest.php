<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\WorkCenter\WorkCenterNom035CalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WorkCenterNom035GeneralReportCalculationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_general_report_uses_summed_scores_for_nom_levels_and_item_average_for_display(): void
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
        $this->assertSame(8, $questionOneRow['dimension']['score']);
        $this->assertSame('medio', $questionOneRow['dimension']['nivel_riesgo']);

        $this->assertSame('Condiciones en el ambiente de trabajo', $questionOneRow['dominio']['nombre']);
        $this->assertSame(22, $questionOneRow['dominio']['score']);
        $this->assertSame('alto', $questionOneRow['dominio']['nivel_riesgo']);

        $this->assertSame('Ambiente de trabajo', $questionOneRow['categoria']['nombre']);
        $this->assertSame(22, $questionOneRow['categoria']['score']);
        $this->assertSame('alto', $questionOneRow['categoria']['nivel_riesgo']);

        $this->assertSame(576, $report['max_score']);
        $this->assertSame(244, $report['total_score']);

        foreach ($report['rows'] as $row) {
            $this->assertGreaterThanOrEqual(0, $row['puntaje']);
            $this->assertLessThanOrEqual(4, $row['puntaje']);
        }
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
