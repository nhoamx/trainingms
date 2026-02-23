<?php

namespace App\Services\WorkCenter;

use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use Illuminate\Support\Collection;

/**
 * Servicio para calcular estadísticas de Referencia I (ATS - Acontecimientos Traumáticos Severos)
 *
 * Referencia I evalúa la presencia de PTSD mediante 14 preguntas Sí/No
 * A diferencia de Ref III, no tiene dominios/categorías/dimensiones,
 * solo identifica trabajadores que contestaron y sus características demográficas.
 */
class WorkCenterNom035RefIStatisticsService
{
    /**
     * Build analysis payload for stages (Analizar/Participantes) with demographic filters.
     */
    public function getStagesAnalysisData(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('referencia_i_answers')
            ->with(['demographicData:id,paper_evaluation_id,gender,position,department,work_schedule'])
            ->select(['id', 'folio', 'personal_folio', 'evaluee_name', 'referencia_i_answers'])
            ->get();

        $availableDemographics = [
            'generos' => [],
            'puestos' => [],
            'areas' => [],
            'turnos' => [],
        ];

        $evaluationsData = $evaluations->map(function (PaperEvaluation $evaluation) use (&$availableDemographics) {
            $normalizedAnswers = $this->normalizeRefIAnswers($evaluation->referencia_i_answers ?? []);

            $demographics = [
                'genero' => $evaluation->demographicData->gender ?? 'No especificado',
                'puesto' => $evaluation->demographicData->position ?? 'No especificado',
                'area' => $evaluation->demographicData->department ?? 'No especificado',
                'turno' => $evaluation->demographicData->work_schedule ?? 'No especificado',
            ];

            foreach ($demographics as $key => $value) {
                $target = match ($key) {
                    'genero' => 'generos',
                    'puesto' => 'puestos',
                    'area' => 'areas',
                    default => 'turnos',
                };

                if (! in_array($value, $availableDemographics[$target], true)) {
                    $availableDemographics[$target][] = $value;
                }
            }

            $yesCount = $this->countYesAnswers($normalizedAnswers);
            $riskLevel = $this->getRiskLevelFromYesCount($yesCount);

            return [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'personal_folio' => $evaluation->personal_folio,
                'evaluee_name' => $evaluation->evaluee_name,
                'demographics' => $demographics,
                'answers' => $normalizedAnswers,
                'yes_count' => $yesCount,
                'total_score' => $yesCount,
                'risk_level' => $riskLevel,
            ];
        })->values()->all();

        sort($availableDemographics['generos']);
        sort($availableDemographics['puestos']);
        sort($availableDemographics['areas']);
        sort($availableDemographics['turnos']);

        return [
            'evaluations' => $evaluationsData,
            'demographics' => $availableDemographics,
            'colors' => config('nom035_risk_levels.colors'),
            'labels' => config('nom035_risk_levels.labels'),
        ];
    }

    /**
     * Build question-level stats for Identificar > Preguntas (Ref I).
     */
    public function getQuestionStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('referencia_i_answers')
            ->get(['referencia_i_answers']);

        $questionsConfig = config('guide_i_questions');
        $questions = [];

        foreach ($questionsConfig as $questionKey => $questionText) {
            $questionNumber = (int) str_replace('pregunta_', '', $questionKey);
            $yesCount = 0;
            $noCount = 0;

            foreach ($evaluations as $evaluation) {
                $answer = $this->getAnswerByQuestionKey($evaluation->referencia_i_answers ?? [], $questionKey);

                if ($answer === null) {
                    continue;
                }

                if ($this->isAffirmativeAnswer($answer)) {
                    $yesCount++;
                } else {
                    $noCount++;
                }
            }

            $totalResponses = $yesCount + $noCount;

            $questions[] = [
                'key' => $questionKey,
                'number' => $questionNumber,
                'text' => $questionText,
                'yes_count' => $yesCount,
                'no_count' => $noCount,
                'total_responses' => $totalResponses,
                'yes_percentage' => $totalResponses > 0 ? round(($yesCount / $totalResponses) * 100, 2) : 0,
            ];
        }

        usort($questions, fn (array $left, array $right) => $left['number'] <=> $right['number']);

        return [
            'questions' => $questions,
            'total_evaluations' => $evaluations->count(),
        ];
    }

    /**
     * Build block-level stats for Identificar > Bloques (Ref I).
     */
    public function getBlockStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('referencia_i_answers')
            ->get(['referencia_i_answers']);

        $questionToKeyMap = [];
        foreach (config('guide_i_questions') as $questionKey => $questionText) {
            $questionToKeyMap[$questionText] = $questionKey;
        }

        $blocks = [];
        foreach (config('referencia_i') as $blockName => $questions) {
            $questionKeys = [];
            $questionNumbers = [];

            foreach ($questions as $questionText) {
                if (! isset($questionToKeyMap[$questionText])) {
                    continue;
                }

                $key = $questionToKeyMap[$questionText];
                $questionKeys[] = $key;
                $questionNumbers[] = (int) str_replace('pregunta_', '', $key);
            }

            $yesCount = 0;
            $noCount = 0;

            foreach ($evaluations as $evaluation) {
                $normalizedAnswers = $this->normalizeRefIAnswers($evaluation->referencia_i_answers ?? []);

                foreach ($questionKeys as $questionKey) {
                    $answer = $normalizedAnswers[$questionKey] ?? null;

                    if ($answer === null) {
                        continue;
                    }

                    if ($this->isAffirmativeAnswer($answer)) {
                        $yesCount++;
                    } else {
                        $noCount++;
                    }
                }
            }

            $totalResponses = $yesCount + $noCount;

            sort($questionNumbers);
            $blocks[] = [
                'name' => $blockName,
                'question_numbers' => $questionNumbers,
                'question_count' => count($questionNumbers),
                'yes_count' => $yesCount,
                'no_count' => $noCount,
                'total_responses' => $totalResponses,
                'yes_percentage' => $totalResponses > 0 ? round(($yesCount / $totalResponses) * 100, 2) : 0,
            ];
        }

        return [
            'blocks' => $blocks,
            'total_evaluations' => $evaluations->count(),
        ];
    }

    /**
     * Build ATS panorama stats based on citsats_s1 answers (questions 1-6).
     */
    public function getAtsPanoramaStatistics(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('citsats_s1')
            ->get(['citsats_s1']);

        $questions = config('referencia_iii.acontecimientos_traumaticos.questions', []);
        $items = [];
        $withTraumaticEventCount = 0;
        $withoutTraumaticEventCount = 0;

        foreach ($evaluations as $evaluation) {
            $answers = is_array($evaluation->citsats_s1) ? $evaluation->citsats_s1 : [];
            $hasAnyYes = false;

            for ($index = 1; $index <= 6; $index++) {
                $answer = $answers[(string) $index] ?? $answers[$index] ?? null;

                if ($answer === null) {
                    continue;
                }

                if ($this->isAffirmativeAnswer($answer)) {
                    $hasAnyYes = true;
                    break;
                }
            }

            if ($hasAnyYes) {
                $withTraumaticEventCount++;
            } else {
                $withoutTraumaticEventCount++;
            }
        }

        for ($index = 1; $index <= 6; $index++) {
            $yesCount = 0;
            $noCount = 0;

            foreach ($evaluations as $evaluation) {
                $answers = is_array($evaluation->citsats_s1) ? $evaluation->citsats_s1 : [];
                $answer = $answers[(string) $index] ?? $answers[$index] ?? null;

                if ($answer === null) {
                    continue;
                }

                if ($this->isAffirmativeAnswer($answer)) {
                    $yesCount++;
                } else {
                    $noCount++;
                }
            }

            $items[] = [
                'index' => $index,
                'label' => $questions[$index - 1] ?? 'ATS '.$index,
                'yes_count' => $yesCount,
                'no_count' => $noCount,
                'total_responses' => $yesCount + $noCount,
            ];
        }

        return [
            'items' => $items,
            'total_evaluations' => $evaluations->count(),
            'with_traumatic_event_count' => $withTraumaticEventCount,
            'without_traumatic_event_count' => $withoutTraumaticEventCount,
        ];
    }

    /**
     * Build participant-level data for traumatic events table in panorama.
     */
    public function getAcontecimientoParticipants(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('citsats_s1')
            ->with([
                'demographicData:id,paper_evaluation_id,gender,age,marital_status,education_level,position,department,position_type,contract_type,personnel_type,work_schedule,shift_rotation,time_in_current_position,work_experience',
            ])
            ->select(['id', 'personal_folio', 'evaluee_name', 'citsats_s1'])
            ->get();

        $participants = $evaluations->map(function (PaperEvaluation $evaluation) {
            $citsats = is_array($evaluation->citsats_s1) ? $evaluation->citsats_s1 : [];

            $events = [];
            $hasAnyEvent = false;

            for ($index = 1; $index <= 6; $index++) {
                $answer = $citsats[(string) $index] ?? $citsats[$index] ?? null;
                $isSelected = $answer !== null && $this->isAffirmativeAnswer($answer);

                $events[(string) $index] = $isSelected;

                if ($isSelected) {
                    $hasAnyEvent = true;
                }
            }

            $demographics = $evaluation->demographicData;

            return [
                'id' => $evaluation->id,
                'personal_folio' => $evaluation->personal_folio,
                'name' => $evaluation->evaluee_name ?? 'No especificado',
                'has_any_event' => $hasAnyEvent,
                'events' => $events,
                'demographics' => [
                    'genero' => $demographics->gender ?? 'No especificado',
                    'edad' => $demographics->age ?? 'No especificado',
                    'estado_civil' => $demographics->marital_status ?? 'No especificado',
                    'estudios' => $demographics->education_level ?? 'No especificado',
                    'puesto' => $demographics->position ?? 'No especificado',
                    'area' => $demographics->department ?? 'No especificado',
                    'tipo_puesto' => $demographics->position_type ?? 'No especificado',
                    'tipo_contratacion' => $demographics->contract_type ?? 'No especificado',
                    'tipo_personal' => $demographics->personnel_type ?? 'No especificado',
                    'turno' => $demographics->work_schedule ?? 'No especificado',
                    'rotacion_turnos' => $demographics->shift_rotation ?? 'No especificado',
                    'tiempo_puesto_actual' => $demographics->time_in_current_position ?? 'No especificado',
                    'tiempo_experiencia_laboral_total' => $demographics->work_experience ?? 'No especificado',
                ],
            ];
        })->values()->all();

        return [
            'participants' => $participants,
            'total' => count($participants),
        ];
    }

    /**
     * Obtener lista de participantes que contestaron Referencia I
     */
    public function getParticipantsList(WorkCenter $workCenter): Collection
    {
        return PaperEvaluation::where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('referencia_i_answers')
            ->with(['demographicData', 'comments'])
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'personal_folio' => $evaluation->personal_folio,
                    'folio' => $evaluation->folio?->folio_number ?? 'N/A',
                    'evaluation_type' => $evaluation->evaluation_type,
                    'created_at' => $evaluation->created_at?->format('Y-m-d H:i:s'),
                    'demographics' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'age_range' => $evaluation->demographicData->age_range,
                        'civil_status' => $evaluation->demographicData->civil_status,
                        'education_level' => $evaluation->demographicData->education_level,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                        'position_type' => $evaluation->demographicData->position_type,
                        'years_in_position' => $evaluation->demographicData->years_in_position,
                        'daily_hours' => $evaluation->demographicData->daily_hours,
                    ] : null,
                    'answers' => $evaluation->referencia_i_answers,
                    'comments_count' => $evaluation->comments->count(),
                ];
            });
    }

    /**
     * Obtener estadísticas agregadas de Referencia I
     */
    public function getAggregatedStats(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('referencia_i_answers')
            ->with('demographicData')
            ->get();

        if ($evaluations->isEmpty()) {
            return [
                'total_participants' => 0,
                'total_questions' => 14,
                'demographic_distribution' => [],
                'answer_distribution' => [],
                'questions_config' => config('guide_i_questions'),
            ];
        }

        return [
            'total_participants' => $evaluations->count(),
            'total_questions' => 14,
            'demographic_distribution' => $this->getDemographicDistribution($evaluations),
            'answer_distribution' => $this->getAnswerDistribution($evaluations),
            'questions_config' => config('guide_i_questions'),
        ];
    }

    /**
     * Calcular distribución demográfica de participantes Ref I
     */
    public function getDemographicDistribution(Collection $evaluations): array
    {
        $demographics = [
            'by_gender' => [],
            'by_age_range' => [],
            'by_department' => [],
            'by_position' => [],
            'by_work_schedule' => [],
            'by_contract_type' => [],
        ];

        foreach ($evaluations as $evaluation) {
            if (! $evaluation->demographicData) {
                continue;
            }

            $demo = $evaluation->demographicData;

            // Por género
            $gender = $demo->gender ?? 'No especificado';
            $demographics['by_gender'][$gender] = ($demographics['by_gender'][$gender] ?? 0) + 1;

            // Por rango de edad
            $ageRange = $demo->age_range ?? 'No especificado';
            $demographics['by_age_range'][$ageRange] = ($demographics['by_age_range'][$ageRange] ?? 0) + 1;

            // Por departamento
            $department = $demo->department ?? 'No especificado';
            $demographics['by_department'][$department] = ($demographics['by_department'][$department] ?? 0) + 1;

            // Por puesto
            $position = $demo->position ?? 'No especificado';
            $demographics['by_position'][$position] = ($demographics['by_position'][$position] ?? 0) + 1;

            // Por turno
            $schedule = $demo->work_schedule ?? 'No especificado';
            $demographics['by_work_schedule'][$schedule] = ($demographics['by_work_schedule'][$schedule] ?? 0) + 1;

            // Por tipo de contrato
            $contract = $demo->contract_type ?? 'No especificado';
            $demographics['by_contract_type'][$contract] = ($demographics['by_contract_type'][$contract] ?? 0) + 1;
        }

        return $demographics;
    }

    /**
     * Calcular distribución de respuestas (Sí/No) por pregunta
     *
     * @return array<string, array{yes: int, no: int, total: int, percentage_yes: float}>
     */
    private function getAnswerDistribution(Collection $evaluations): array
    {
        $questions = config('guide_i_questions');
        $distribution = [];

        foreach ($questions as $questionKey => $questionText) {
            $distribution[$questionKey] = [
                'question_text' => $questionText,
                'yes_count' => 0,
                'no_count' => 0,
                'total_responses' => 0,
                'percentage_yes' => 0.0,
            ];
        }

        foreach ($evaluations as $evaluation) {
            $answers = $this->normalizeRefIAnswers($evaluation->referencia_i_answers ?? []);

            foreach ($questions as $questionKey => $questionText) {
                if (! isset($answers[$questionKey])) {
                    continue;
                }

                $answer = $answers[$questionKey];
                $distribution[$questionKey]['total_responses']++;

                if ($answer === true || $answer === 'true' || $answer === 1 || $answer === '1' || strtolower($answer) === 'sí' || strtolower($answer) === 'si') {
                    $distribution[$questionKey]['yes_count']++;
                } else {
                    $distribution[$questionKey]['no_count']++;
                }
            }
        }

        // Calcular porcentajes
        foreach ($distribution as $questionKey => &$stats) {
            if ($stats['total_responses'] > 0) {
                $stats['percentage_yes'] = round(($stats['yes_count'] / $stats['total_responses']) * 100, 2);
            }
        }

        return $distribution;
    }

    /**
     * Obtener resumen ejecutivo de Ref I
     */
    public function getExecutiveSummary(WorkCenter $workCenter): array
    {
        $evaluations = PaperEvaluation::where('work_center_id', $workCenter->id)
            ->where('evaluation_type', 'referencia_i')
            ->where('processing_status', 'completed')
            ->whereNotNull('referencia_i_answers')
            ->count();

        return [
            'total_participants' => $evaluations,
            'evaluation_type' => 'Referencia I (ATS)',
            'description' => 'Identificación de trabajadores expuestos a acontecimientos traumáticos severos',
            'total_questions' => 14,
        ];
    }

    private function countYesAnswers(array $answers): int
    {
        $count = 0;

        foreach ($answers as $answer) {
            if ($this->isAffirmativeAnswer($answer)) {
                $count++;
            }
        }

        return $count;
    }

    private function isAffirmativeAnswer(mixed $answer): bool
    {
        if (is_string($answer)) {
            $normalizedAnswer = mb_strtolower(trim($answer));

            return in_array($normalizedAnswer, ['sí', 'si', 'true', '1'], true);
        }

        return in_array($answer, [true, 1], true);
    }

    private function getRiskLevelFromYesCount(int $yesCount): string
    {
        if ($yesCount === 0) {
            return 'nulo';
        }

        if ($yesCount <= 2) {
            return 'bajo';
        }

        if ($yesCount <= 4) {
            return 'medio';
        }

        if ($yesCount <= 6) {
            return 'alto';
        }

        return 'muy_alto';
    }

    /**
     * Normalize Ref I answers to canonical keys: pregunta_1 ... pregunta_14
     *
     * @param  array<string|int, mixed>  $answers
     * @return array<string, mixed>
     */
    private function normalizeRefIAnswers(array $answers): array
    {
        $normalized = [];

        foreach ($answers as $rawKey => $value) {
            $key = (string) $rawKey;

            if (preg_match('/^pregunta_(\d+)$/', $key, $matches) === 1) {
                $normalized['pregunta_'.((int) $matches[1])] = $value;

                continue;
            }

            if (preg_match('/^(\d+)$/', $key, $matches) === 1) {
                $normalized['pregunta_'.((int) $matches[1])] = $value;

                continue;
            }
        }

        return $normalized;
    }

    /**
     * Resolve an answer by canonical key, supporting legacy numeric keys.
     *
     * @param  array<string|int, mixed>  $answers
     */
    private function getAnswerByQuestionKey(array $answers, string $questionKey): mixed
    {
        $normalized = $this->normalizeRefIAnswers($answers);

        return $normalized[$questionKey] ?? null;
    }
}
