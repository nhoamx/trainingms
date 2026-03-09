<?php

namespace Tests\Feature;

use App\Models\DemographicData;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedNom035SyntheticDataCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_command_seeds_required_nom035_distribution(): void
    {
        $organization = Organization::factory()->create([
            'folio_organization' => '77',
        ]);
        $workCenter = WorkCenter::factory()->create([
            'organization_id' => $organization->id,
            'code' => '03',
        ]);

        $exitCode = Artisan::call('nom035:seed-synthetic', [
            '--organization' => $organization->id,
            '--work-center' => $workCenter->id,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());

        $refIII = PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_iii')
            ->get();

        $refI = PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->get();

        $cisneros = PaperEvaluation::query()
            ->where('organization_id', $organization->id)
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'cisneros')
            ->get();

        $this->assertCount(300, $refIII);
        $this->assertCount(54, $refI);
        $this->assertCount(45, $cisneros);

        $atsCount = $refIII->filter(function (PaperEvaluation $evaluation): bool {
            $answers = $evaluation->citsats_s1 ?? [];

            if (! is_array($answers)) {
                return false;
            }

            return collect($answers)->contains(fn ($value) => strtoupper((string) $value) === 'SI');
        })->count();

        $this->assertSame(54, $atsCount);
        $this->assertCount($atsCount, $refI);

        $violenceDomain = config('nom035_risk_levels.domains.Violencia.levels');
        $highAndVeryHigh = $refIII->filter(function (PaperEvaluation $evaluation) use ($violenceDomain): bool {
            $answers = $evaluation->referencia_iii_answers ?? [];
            $answerValues = config('answer_values');
            $score = 0;

            foreach (range(57, 64) as $question) {
                $answer = $answers[(string) $question] ?? $answers[$question] ?? null;
                if (! is_string($answer)) {
                    continue;
                }

                $questionKey = str_pad((string) $question, 2, '0', STR_PAD_LEFT);
                $group = in_array($questionKey, $answerValues['group1']['questions'], true) ? 'group1' : 'group2';
                $score += $answerValues[$group]['values'][$answer] ?? 0;
            }

            $isHigh = $score >= $violenceDomain['alto']['min'] && $score <= $violenceDomain['alto']['max'];
            $isVeryHigh = $score >= $violenceDomain['muy_alto']['min'];

            return $isHigh || $isVeryHigh;
        })->count();

        $this->assertSame(135, $highAndVeryHigh);

        $globalLevels = config('nom035_risk_levels.global.levels');
        $answerValues = config('answer_values');
        $globalDistribution = [
            'nulo' => 0,
            'bajo' => 0,
            'medio' => 0,
            'alto' => 0,
            'muy_alto' => 0,
        ];

        foreach ($refIII as $evaluation) {
            $answers = $evaluation->referencia_iii_answers ?? [];
            $score = 0;

            foreach (range(1, 64) as $question) {
                $answer = $answers[(string) $question] ?? $answers[$question] ?? null;

                if (! is_string($answer)) {
                    continue;
                }

                $questionKey = str_pad((string) $question, 2, '0', STR_PAD_LEFT);
                $group = in_array($questionKey, $answerValues['group1']['questions'], true) ? 'group1' : 'group2';
                $score += $answerValues[$group]['values'][$answer] ?? 0;
            }

            foreach ($globalLevels as $level => $range) {
                if ($score >= $range['min'] && $score <= $range['max']) {
                    $globalDistribution[$level]++;
                    break;
                }
            }
        }

        $this->assertSame(60, $globalDistribution['nulo']);
        $this->assertSame(60, $globalDistribution['bajo']);
        $this->assertSame(45, $globalDistribution['medio']);
        $this->assertSame(90, $globalDistribution['alto']);
        $this->assertSame(45, $globalDistribution['muy_alto']);

        $veryHighPersonalFolios = $refIII->filter(function (PaperEvaluation $evaluation): bool {
            $answers = $evaluation->referencia_iii_answers ?? [];
            $answerValues = config('answer_values');
            $score = 0;

            foreach (range(57, 64) as $question) {
                $answer = $answers[(string) $question] ?? $answers[$question] ?? null;
                if (! is_string($answer)) {
                    continue;
                }

                $questionKey = str_pad((string) $question, 2, '0', STR_PAD_LEFT);
                $group = in_array($questionKey, $answerValues['group1']['questions'], true) ? 'group1' : 'group2';
                $score += $answerValues[$group]['values'][$answer] ?? 0;
            }

            return $score >= config('nom035_risk_levels.domains.Violencia.levels.muy_alto.min');
        })->pluck('personal_folio')->unique()->values();

        $cisnerosPersonalFolios = $cisneros->pluck('personal_folio')->unique()->values();
        $this->assertCount(45, $cisnerosPersonalFolios);
        $this->assertSame([], $cisnerosPersonalFolios->diff($veryHighPersonalFolios)->all());

        $refIIIDs = $refIII->pluck('id');
        $demographics = DemographicData::query()->whereIn('paper_evaluation_id', $refIIIDs)->get();

        $this->assertCount(300, $demographics);
        $this->assertSame(150, $demographics->where('gender', 'Masculino')->count());
        $this->assertSame(150, $demographics->where('gender', 'Femenino')->count());

        foreach ($demographics as $demographic) {
            $age = (int) $demographic->age;
            $this->assertGreaterThanOrEqual(18, $age);
            $this->assertLessThanOrEqual(60, $age);
        }

        $organization->refresh();
        $this->assertSame(300, $organization->total_trabajadores);
        $this->assertSame(150, $organization->total_hombres);
        $this->assertSame(150, $organization->total_mujeres);
    }
}
