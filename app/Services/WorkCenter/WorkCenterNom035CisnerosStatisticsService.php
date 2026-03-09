<?php

namespace App\Services\WorkCenter;

use App\Models\PaperEvaluation;
use App\Models\WorkCenter;

class WorkCenterNom035CisnerosStatisticsService
{
    /**
     * Build all Cisneros dashboard data for a work center.
     *
     * @return array{
     *   summary: array{total_evaluations: int, victim_yes: int, victim_no: int, victim_unknown: int, victim_yes_percentage: float},
     *   authors_chart: array<int, array{key: string, label: string, count: int, color: string}>,
     *   frequency_chart: array<int, array{key: string, label: string, count: int, color: string}>,
     *   responses_table: array<int, array{folio: string, personal_folio: string, question_number: int, question_text: string, author_code: string|null, author_label: string|null, frequency_value: int|null, frequency_label: string|null, victim_last_6_months: bool|null, victim_last_6_months_label: string}>
     * }
     */
    public function getDashboardData(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'cisneros')
            ->where('processing_status', 'completed')
            ->whereNotNull('cisneros_answers')
            ->select(['id', 'folio', 'personal_folio', 'cisneros_answers'])
            ->get();

        $authorsMap = [
            'A' => 'Jefas/jefes o personas supervisoras',
            'B' => 'Personas companeras de trabajo',
            'C' => 'Personas subordinadas',
        ];

        $authorsColors = [
            'A' => '#2563EB',
            'B' => '#10B981',
            'C' => '#F59E0B',
        ];

        $frequencyMap = [
            0 => 'Nunca',
            1 => 'Pocas veces al ano o menos',
            2 => 'Una vez al mes o menos',
            3 => 'Algunas veces al mes',
            4 => 'Una vez a la semana',
            5 => 'Varias veces a la semana',
            6 => 'Todos los dias',
        ];

        $frequencyColors = [
            0 => '#0EA5E9',
            1 => '#22C55E',
            2 => '#84CC16',
            3 => '#EAB308',
            4 => '#F97316',
            5 => '#EF4444',
            6 => '#A855F7',
        ];

        $authorDistribution = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
        ];

        $frequencyDistribution = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
        ];

        $responsesTable = [];
        $victimYes = 0;
        $victimNo = 0;
        $victimUnknown = 0;

        $questions = config('escala_cisneros', []);

        foreach ($evaluations as $evaluation) {
            $answers = is_array($evaluation->cisneros_answers) ? $evaluation->cisneros_answers : [];
            $victimInLastSixMonths = $this->normalizeVictimAnswer($answers['44'] ?? null);

            if ($victimInLastSixMonths === true) {
                $victimYes++;
            } elseif ($victimInLastSixMonths === false) {
                $victimNo++;
            } else {
                $victimUnknown++;
            }

            for ($questionNumber = 1; $questionNumber <= 43; $questionNumber++) {
                $questionKey = (string) $questionNumber;
                $questionAnswer = $answers[$questionKey] ?? null;

                if (! is_array($questionAnswer)) {
                    continue;
                }

                $authorCode = $this->normalizeAuthorCode($questionAnswer['persona'] ?? null);
                $frequencyValue = $this->normalizeFrequencyValue($questionAnswer['frecuencia'] ?? null);

                if ($authorCode === null && $frequencyValue === null) {
                    continue;
                }

                if ($authorCode !== null) {
                    $authorDistribution[$authorCode]++;
                }

                if ($frequencyValue !== null) {
                    $frequencyDistribution[$frequencyValue]++;
                }

                $responsesTable[] = [
                    'folio' => $evaluation->folio,
                    'personal_folio' => $evaluation->personal_folio,
                    'question_number' => $questionNumber,
                    'question_text' => $questions[$questionNumber] ?? ('Pregunta '.$questionNumber),
                    'author_code' => $authorCode,
                    'author_label' => $authorCode !== null ? $authorsMap[$authorCode] : null,
                    'frequency_value' => $frequencyValue,
                    'frequency_label' => $frequencyValue !== null ? $frequencyMap[$frequencyValue] : null,
                    'victim_last_6_months' => $victimInLastSixMonths,
                    'victim_last_6_months_label' => $victimInLastSixMonths === null
                        ? 'Sin respuesta'
                        : ($victimInLastSixMonths ? 'SI' : 'NO'),
                ];
            }
        }

        usort($responsesTable, function (array $left, array $right): int {
            $folioComparison = strcmp($left['folio'], $right['folio']);
            if ($folioComparison !== 0) {
                return $folioComparison;
            }

            return $left['question_number'] <=> $right['question_number'];
        });

        $authorsChart = [];
        foreach ($authorDistribution as $key => $count) {
            $authorsChart[] = [
                'key' => $key,
                'label' => $authorsMap[$key],
                'count' => $count,
                'color' => $authorsColors[$key],
            ];
        }

        $frequencyChart = [];
        foreach ($frequencyDistribution as $key => $count) {
            $frequencyChart[] = [
                'key' => (string) $key,
                'label' => $frequencyMap[$key],
                'count' => $count,
                'color' => $frequencyColors[$key],
            ];
        }

        $evaluationsWithVictimAnswer = $victimYes + $victimNo;

        return [
            'summary' => [
                'total_evaluations' => $evaluations->count(),
                'victim_yes' => $victimYes,
                'victim_no' => $victimNo,
                'victim_unknown' => $victimUnknown,
                'victim_yes_percentage' => $evaluationsWithVictimAnswer > 0
                    ? round(($victimYes / $evaluationsWithVictimAnswer) * 100, 2)
                    : 0.0,
            ],
            'authors_chart' => $authorsChart,
            'frequency_chart' => $frequencyChart,
            'responses_table' => $responsesTable,
        ];
    }

    private function normalizeAuthorCode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = strtoupper(trim($value));

        return in_array($normalized, ['A', 'B', 'C'], true) ? $normalized : null;
    }

    private function normalizeFrequencyValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return ($value >= 0 && $value <= 6) ? $value : null;
        }

        if (is_string($value) && is_numeric($value)) {
            $parsed = (int) $value;

            return ($parsed >= 0 && $parsed <= 6) ? $parsed : null;
        }

        return null;
    }

    private function normalizeVictimAnswer(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1 ? true : ($value === 0 ? false : null);
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
        ]);
        $normalized = strtoupper($normalized);

        if (in_array($normalized, ['SI', 'S', 'YES', 'Y', 'TRUE', '1'], true)) {
            return true;
        }

        if (in_array($normalized, ['NO', 'N', 'FALSE', '0'], true)) {
            return false;
        }

        return null;
    }
}
