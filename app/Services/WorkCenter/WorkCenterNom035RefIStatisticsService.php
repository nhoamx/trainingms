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
            $answers = $evaluation->referencia_i_answers ?? [];

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
}
