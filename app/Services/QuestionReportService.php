<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class QuestionReportService
{
    /**
     * Obtiene datos para reportes de preguntas de la guía III filtrados por datos demográficos de la guía V
     */
    public function getFilteredReportData(Organization $organization, array $filters = [])
    {
        // Obtener todos los personal_ids con datos demográficos (guía V)
        $demographicPersonalIds = Question::where('reference_guide', 'V')
            ->whereHas('evaluation', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->distinct('evaluation_id')
            ->pluck('evaluation_id')
            ->toArray();

        // Obtener las evaluaciones de guía III que tienen datos demográficos correspondientes
        $guideIIIEvaluations = DB::table('evaluations as e1')
            ->join('evaluations as e2', function ($join) {
                $join->on('e1.personal_id', '=', 'e2.personal_id')
                     ->where('e2.reference_guide', '=', 'V');
            })
            ->where('e1.reference_guide', 'III')
            ->where('e1.organization_id', $organization->id)
            ->whereIn('e2.id', $demographicPersonalIds)
            ->select('e1.id')
            ->distinct()
            ->pluck('e1.id')
            ->toArray();

        // Aplicar filtros demográficos
        if (!empty($filters)) {
            $guideIIIEvaluations = $this->applyDemographicFilters($guideIIIEvaluations, $filters);
        }

        // Obtener datos de preguntas de la guía III para las evaluaciones filtradas
        $questions = Question::whereIn('evaluation_id', $guideIIIEvaluations)
            ->where('reference_guide', 'III')
            ->get();

        // Generar reportes
        return [
            'estadoCivil' => $this->generateEstadoCivilReport($guideIIIEvaluations, $organization),
            'edadDistribution' => $this->generateAgeDistributionReport($guideIIIEvaluations, $organization),
            'questionStatistics' => $this->generateQuestionStatisticsReport($questions),
            'categoryScores' => $this->generateCategoryScoresReport($guideIIIEvaluations),
            'answersDistribution' => $this->generateAnswersDistributionReport($questions),
            'nivelAcademico' => $this->generateAcademicLevelReport($guideIIIEvaluations, $organization),
            'questionDetail' => $this->generateQuestionDetailReport($guideIIIEvaluations, $organization),
        ];
    }

    /**
     * Aplica filtros demográficos a las evaluaciones
     */
    private function applyDemographicFilters(array $evaluationIds, array $filters)
    {
        $query = DB::table('evaluations as e1')
            ->join('evaluations as e2', function ($join) {
                $join->on('e1.personal_id', '=', 'e2.personal_id')
                     ->where('e2.reference_guide', '=', 'V');
            })
            ->join('questions as q', 'q.evaluation_id', '=', 'e2.id')
            ->whereIn('e1.id', $evaluationIds)
            ->where('q.reference_guide', 'V');

        // Aplicar filtros individuales
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            $query->where(function ($query) use ($key, $value) {
                // Si es un array, usar whereIn
                if (is_array($value)) {
                    $query->where('q.question', $key)
                          ->whereIn('q.answer', $value);
                } else {
                    $query->where('q.question', $key)
                          ->where('q.answer', $value);
                }
            });
        }

        return $query->select('e1.id')
            ->distinct()
            ->pluck('e1.id')
            ->toArray();
    }

    /**
     * Genera reporte de distribución por estado civil
     */
    private function generateEstadoCivilReport(array $evaluationIds, Organization $organization)
    {
        $estadoCivilCounts = DB::table('evaluations as e1')
            ->join('evaluations as e2', function ($join) {
                $join->on('e1.personal_id', '=', 'e2.personal_id')
                     ->where('e2.reference_guide', '=', 'V');
            })
            ->join('questions as q', 'q.evaluation_id', '=', 'e2.id')
            ->whereIn('e1.id', $evaluationIds)
            ->where('q.question', 'estado_civil')
            ->groupBy('q.answer')
            ->select('q.answer as estado_civil', DB::raw('count(*) as total'))
            ->get();

        // Conteo de estados civiles para análisis de niveles
        $estadoCivilAnalysis = [];

        foreach ($estadoCivilCounts as $item) {
            $answers = Question::whereIn('evaluation_id', $evaluationIds)
                ->where('reference_guide', 'III')
                ->whereHas('evaluation', function ($query) use ($organization, $item) {
                    $query->where('organization_id', $organization->id)
                          ->whereHas('questions', function ($q) use ($item) {
                              $q->where('question', 'estado_civil')
                                ->where('answer', $item->estado_civil)
                                ->where('reference_guide', 'V');
                          });
                })
                ->get();

            // Análisis de niveles (Nulo, Bajo, Medio, Alto, Muy Alto)
            $niveles = ['Nulo' => 0, 'Bajo' => 0, 'Medio' => 0, 'Alto' => 0, 'Muy Alto' => 0];
            $totalResponses = count($answers);

            foreach ($answers as $answer) {
                $value = (int) $answer->value;

                if ($value === 0) $niveles['Nulo']++;
                elseif ($value <= 1) $niveles['Bajo']++;
                elseif ($value <= 2) $niveles['Medio']++;
                elseif ($value <= 3) $niveles['Alto']++;
                else $niveles['Muy Alto']++;
            }

            // Calcular porcentajes
            $nuBa = $niveles['Nulo'] + $niveles['Bajo'];
            $meAlMa = $niveles['Medio'] + $niveles['Alto'] + $niveles['Muy Alto'];

            // Calcular calificación final (CF)
            $cf = 0;
            if ($totalResponses > 0) {
                $cf = ($niveles['Bajo'] * 1 + $niveles['Medio'] * 2 + $niveles['Alto'] * 3 + $niveles['Muy Alto'] * 4) / $totalResponses * 25;
            }

            $estadoCivilAnalysis[] = [
                'estado_civil' => $this->formatEstadoCivil($item->estado_civil),
                'total' => $item->total,
                'niveles' => $niveles,
                'nu_ba' => $nuBa,
                'me_al_ma' => $meAlMa,
                'cf' => round($cf)
            ];
        }

        return $estadoCivilAnalysis;
    }

    /**
     * Genera reporte de distribución por edad
     */
    private function generateAgeDistributionReport(array $evaluationIds, Organization $organization)
    {
        // Obtener datos de edad
        $ageData = DB::table('evaluations as e1')
            ->join('evaluations as e2', function ($join) {
                $join->on('e1.personal_id', '=', 'e2.personal_id')
                     ->where('e2.reference_guide', '=', 'V');
            })
            ->join('questions as q1', 'q1.evaluation_id', '=', 'e2.id')
            ->join('questions as q2', function ($join) {
                $join->on('q2.evaluation_id', '=', 'e2.id')
                     ->where('q2.question', '=', 'edad_d2');
            })
            ->whereIn('e1.id', $evaluationIds)
            ->where('q1.question', 'edad_d1')
            ->select('e1.id', 'q1.answer as edad_d1', 'q2.answer as edad_d2')
            ->get();

        // Agrupar por rangos de edad
        $ageRanges = [
            '18-25' => ['count' => 0, 'analysis' => []],
            '26-35' => ['count' => 0, 'analysis' => []],
            '36-45' => ['count' => 0, 'analysis' => []],
            '46-55' => ['count' => 0, 'analysis' => []],
            '56+' => ['count' => 0, 'analysis' => []]
        ];

        foreach ($ageData as $item) {
            $age = (int)($item->edad_d1 . $item->edad_d2);
            $range = '56+';

            if ($age <= 25) $range = '18-25';
            elseif ($age <= 35) $range = '26-35';
            elseif ($age <= 45) $range = '36-45';
            elseif ($age <= 55) $range = '46-55';

            $ageRanges[$range]['count']++;
            $ageRanges[$range]['evaluations'][] = $item->id;
        }

        // Para cada rango de edad, analizar las respuestas
        foreach ($ageRanges as $range => &$data) {
            if ($data['count'] === 0) continue;

            $evalIds = $data['evaluations'] ?? [];
            $answers = Question::whereIn('evaluation_id', $evalIds)
                ->where('reference_guide', 'III')
                ->get();

            // Análisis de niveles
            $niveles = ['Nulo' => 0, 'Bajo' => 0, 'Medio' => 0, 'Alto' => 0, 'Muy Alto' => 0];
            $totalResponses = count($answers);

            foreach ($answers as $answer) {
                $value = (int) $answer->value;

                if ($value === 0) $niveles['Nulo']++;
                elseif ($value <= 1) $niveles['Bajo']++;
                elseif ($value <= 2) $niveles['Medio']++;
                elseif ($value <= 3) $niveles['Alto']++;
                else $niveles['Muy Alto']++;
            }

            // Calcular porcentajes
            $nuBa = $niveles['Nulo'] + $niveles['Bajo'];
            $meAlMa = $niveles['Medio'] + $niveles['Alto'] + $niveles['Muy Alto'];

            // Calcular calificación final
            $cf = 0;
            if ($totalResponses > 0) {
                $cf = ($niveles['Bajo'] * 1 + $niveles['Medio'] * 2 + $niveles['Alto'] * 3 + $niveles['Muy Alto'] * 4) / $totalResponses * 25;
            }

            $data['analysis'] = [
                'niveles' => $niveles,
                'nu_ba' => $nuBa,
                'me_al_ma' => $meAlMa,
                'cf' => round($cf)
            ];

            unset($data['evaluations']);
        }

        return $ageRanges;
    }

    /**
     * Genera reporte de nivel académico con análisis
     */
    private function generateAcademicLevelReport(array $evaluationIds, Organization $organization)
    {
        $nivelAcademicoCounts = DB::table('evaluations as e1')
            ->join('evaluations as e2', function ($join) {
                $join->on('e1.personal_id', '=', 'e2.personal_id')
                     ->where('e2.reference_guide', '=', 'V');
            })
            ->join('questions as q', 'q.evaluation_id', '=', 'e2.id')
            ->whereIn('e1.id', $evaluationIds)
            ->where('q.question', 'ultimo_nivel_estudio')
            ->groupBy('q.answer')
            ->select('q.answer as nivel', DB::raw('count(*) as total'))
            ->get();

        // Conteo de niveles académicos para análisis
        $nivelAcademicoAnalysis = [];

        foreach ($nivelAcademicoCounts as $item) {
            $answers = Question::whereIn('evaluation_id', $evaluationIds)
                ->where('reference_guide', 'III')
                ->whereHas('evaluation', function ($query) use ($organization, $item) {
                    $query->where('organization_id', $organization->id)
                          ->whereHas('questions', function ($q) use ($item) {
                              $q->where('question', 'ultimo_nivel_estudio')
                                ->where('answer', $item->nivel)
                                ->where('reference_guide', 'V');
                          });
                })
                ->get();

            // Análisis de niveles (Nulo, Bajo, Medio, Alto, Muy Alto)
            $niveles = ['Nulo' => 0, 'Bajo' => 0, 'Medio' => 0, 'Alto' => 0, 'Muy Alto' => 0];
            $totalResponses = count($answers);

            foreach ($answers as $answer) {
                $value = (int) $answer->value;

                if ($value === 0) $niveles['Nulo']++;
                elseif ($value <= 1) $niveles['Bajo']++;
                elseif ($value <= 2) $niveles['Medio']++;
                elseif ($value <= 3) $niveles['Alto']++;
                else $niveles['Muy Alto']++;
            }

            // Calcular porcentajes
            $nuBa = $niveles['Nulo'] + $niveles['Bajo'];
            $meAlMa = $niveles['Medio'] + $niveles['Alto'] + $niveles['Muy Alto'];

            // Calcular calificación final (CF)
            $cf = 0;
            if ($totalResponses > 0) {
                $cf = ($niveles['Bajo'] * 1 + $niveles['Medio'] * 2 + $niveles['Alto'] * 3 + $niveles['Muy Alto'] * 4) / $totalResponses * 25;
            }

            $nivelAcademicoAnalysis[] = [
                'nivel' => $this->formatNivelAcademico($item->nivel),
                'total' => $item->total,
                'niveles' => $niveles,
                'nu_ba' => $nuBa,
                'me_al_ma' => $meAlMa,
                'cf' => round($cf)
            ];
        }

        return $nivelAcademicoAnalysis;
    }

    /**
     * Genera estadísticas generales de preguntas
     */
    private function generateQuestionStatisticsReport($questions)
    {
        $statistics = [];

        // Agrupar por pregunta
        $groupedQuestions = $questions->groupBy('question');

        foreach ($groupedQuestions as $questionNumber => $questionSet) {
            $answerCounts = [];

            // Contar respuestas
            foreach ($questionSet as $question) {
                if (!isset($answerCounts[$question->answer])) {
                    $answerCounts[$question->answer] = 0;
                }
                $answerCounts[$question->answer]++;
            }

            $statistics[$questionNumber] = [
                'question' => $questionNumber,
                'answers' => $answerCounts,
                'total' => $questionSet->count()
            ];
        }

        return $statistics;
    }

    /**
     * Genera reporte de puntajes por categoría
     */
    private function generateCategoryScoresReport(array $evaluationIds)
    {
        $categoryScores = DB::table('questions')
            ->join('categories', 'questions.category_id', '=', 'categories.id')
            ->whereIn('questions.evaluation_id', $evaluationIds)
            ->where('questions.reference_guide', 'III')
            ->groupBy('categories.id', 'categories.name')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(CAST(questions.value AS DECIMAL(10, 2))) as score'),
                DB::raw('COUNT(questions.id) as question_count')
            )
            ->get()
            ->map(function ($item) {
                $avgScore = $item->question_count > 0 ? $item->score / $item->question_count : 0;
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'score' => $item->score,
                    'question_count' => $item->question_count,
                    'avg_score' => $avgScore,
                    'level' => $this->getScoreLevel($avgScore)
                ];
            });

        return $categoryScores;
    }

    /**
     * Genera reporte de distribución de respuestas
     */
    private function generateAnswersDistributionReport($questions)
    {
        $responseDistribution = [
            'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0
        ];

        foreach ($questions as $question) {
            if (isset($responseDistribution[$question->answer])) {
                $responseDistribution[$question->answer]++;
            }
        }

        return $responseDistribution;
    }

    /**
     * Formatea el nombre del estado civil para mostrarlo en el reporte
     */
    private function formatEstadoCivil($estadoCivil)
    {
        $estados = [
            'casado' => 'Casado',
            'soltero' => 'Soltero',
            'union_libre' => 'Unión Libre',
            'divorciado' => 'Divorciado',
            'viudo' => 'Viudo',
            'otro' => 'Otro'
        ];

        return $estados[$estadoCivil] ?? $estadoCivil;
    }

    /**
     * Formatea el nombre del nivel académico para mostrarlo en el reporte
     */
    private function formatNivelAcademico($nivel)
    {
        $niveles = [
            'primaria_incompleta' => 'Primaria Incompleta',
            'primaria_terminada' => 'Primaria Terminada',
            'secundaria_incompleta' => 'Secundaria Incompleta',
            'secundaria_terminada' => 'Secundaria Terminada',
            'preparatoria_incompleta' => 'Preparatoria Incompleta',
            'preparatoria_terminada' => 'Preparatoria Terminada',
            'tecnico_superior_incompleto' => 'Técnico Superior Incompleto',
            'tecnico_superior_terminado' => 'Técnico Superior Terminado',
            'licenciatura_incompleta' => 'Licenciatura Incompleta',
            'licenciatura_terminada' => 'Licenciatura Terminada',
            'maestria_incompleta' => 'Maestría Incompleta',
            'maestria_terminada' => 'Maestría Terminada',
            'doctorado_incompleto' => 'Doctorado Incompleto',
            'doctorado_terminado' => 'Doctorado Terminado'
        ];

        return $niveles[$nivel] ?? $nivel;
    }

    /**
     * Obtiene el nivel de riesgo basado en el puntaje
     */
    private function getScoreLevel($score)
    {
        if ($score <= 0) return 'Nulo';
        if ($score <= 1) return 'Bajo';
        if ($score <= 2) return 'Medio';
        if ($score <= 3) return 'Alto';
        return 'Muy Alto';
    }

    /**
     * Genera un reporte detallado de las preguntas por criterio demográfico
     */
    private function generateQuestionDetailReport(array $evaluationIds, Organization $organization)
    {
        // Obtener todos los datos demográficos
        $estadoCivilData = $this->getDemographicData($evaluationIds, 'estado_civil');
        $edadData = $this->getAgeData($evaluationIds);
        $nivelAcademicoData = $this->getDemographicData($evaluationIds, 'ultimo_nivel_estudio');

        // Obtener todas las preguntas de la guía III
        $questions = Question::whereIn('evaluation_id', $evaluationIds)
            ->where('reference_guide', 'III')
            ->select('question')
            ->distinct()
            ->pluck('question')
            ->toArray();

        $result = [
            'estadoCivil' => $this->generateReportByDemographic($evaluationIds, $estadoCivilData, 'estado_civil', $questions),
            'edad' => $this->generateReportByAge($evaluationIds, $edadData, $questions),
            'nivelAcademico' => $this->generateReportByDemographic($evaluationIds, $nivelAcademicoData, 'ultimo_nivel_estudio', $questions),
        ];

        return $result;
    }

    /**
     * Obtiene datos demográficos de personas con evaluaciones
     */
    private function getDemographicData(array $evaluationIds, string $demographicField)
    {
        return DB::table('evaluations as e1')
            ->join('evaluations as e2', function ($join) {
                $join->on('e1.personal_id', '=', 'e2.personal_id')
                     ->where('e2.reference_guide', '=', 'V');
            })
            ->join('questions as q', 'q.evaluation_id', '=', 'e2.id')
            ->whereIn('e1.id', $evaluationIds)
            ->where('q.question', $demographicField)
            ->select('e1.id as evaluation_id', 'q.answer as demographic_value')
            ->get();
    }

    /**
     * Obtiene datos de edad procesados para personas con evaluaciones
     */
    private function getAgeData(array $evaluationIds)
    {
        $rawData = DB::table('evaluations as e1')
            ->join('evaluations as e2', function ($join) {
                $join->on('e1.personal_id', '=', 'e2.personal_id')
                     ->where('e2.reference_guide', '=', 'V');
            })
            ->join('questions as q1', 'q1.evaluation_id', '=', 'e2.id')
            ->join('questions as q2', function ($join) {
                $join->on('q2.evaluation_id', '=', 'e2.id')
                     ->where('q2.question', '=', 'edad_d2');
            })
            ->whereIn('e1.id', $evaluationIds)
            ->where('q1.question', 'edad_d1')
            ->select('e1.id as evaluation_id', 'q1.answer as edad_d1', 'q2.answer as edad_d2')
            ->get();

        $processedData = [];
        foreach ($rawData as $item) {
            $age = (int)($item->edad_d1 . $item->edad_d2);
            $range = '56+';

            if ($age <= 25) $range = '18-25';
            elseif ($age <= 35) $range = '26-35';
            elseif ($age <= 45) $range = '36-45';
            elseif ($age <= 55) $range = '46-55';

            $processedData[] = (object)[
                'evaluation_id' => $item->evaluation_id,
                'demographic_value' => $range
            ];
        }

        return collect($processedData);
    }

    /**
     * Genera reporte detallado por criterio demográfico
     */
    private function generateReportByDemographic(array $evaluationIds, $demographicData, string $demographicField, array $questions)
    {
        $report = [];

        // Agrupar por valor demográfico
        $groupedByDemographic = $demographicData->groupBy('demographic_value');

        foreach ($groupedByDemographic as $demographicValue => $evaluations) {
            $evaluationIds = $evaluations->pluck('evaluation_id')->toArray();

            $label = $demographicField === 'estado_civil'
                ? $this->formatEstadoCivil($demographicValue)
                : $this->formatNivelAcademico($demographicValue);

            $questionStats = [];

            // Para cada pregunta, obtener las estadísticas para este grupo demográfico
            foreach ($questions as $questionNumber) {
                $answers = Question::whereIn('evaluation_id', $evaluationIds)
                    ->where('reference_guide', 'III')
                    ->where('question', $questionNumber)
                    ->get();

                if ($answers->isEmpty()) continue;

                // Contar respuestas por nivel
                $niveles = ['Nulo' => 0, 'Bajo' => 0, 'Medio' => 0, 'Alto' => 0, 'Muy Alto' => 0];

                foreach ($answers as $answer) {
                    $value = (int) $answer->value;

                    if ($value === 0) $niveles['Nulo']++;
                    elseif ($value <= 1) $niveles['Bajo']++;
                    elseif ($value <= 2) $niveles['Medio']++;
                    elseif ($value <= 3) $niveles['Alto']++;
                    else $niveles['Muy Alto']++;
                }

                // Calcular totales
                $total = count($answers);
                $nuBa = $niveles['Nulo'] + $niveles['Bajo'];
                $meAlMa = $niveles['Medio'] + $niveles['Alto'] + $niveles['Muy Alto'];

                // Calcular CF
                $cf = 0;
                if ($total > 0) {
                    $cf = ($niveles['Bajo'] * 1 + $niveles['Medio'] * 2 + $niveles['Alto'] * 3 + $niveles['Muy Alto'] * 4) / $total * 25;
                }

                $questionStats[$questionNumber] = [
                    'niveles' => $niveles,
                    'total' => $total,
                    'nu_ba' => $nuBa,
                    'me_al_ma' => $meAlMa,
                    'cf' => round($cf)
                ];
            }

            $report[$demographicValue] = [
                'label' => $label,
                'total' => count($evaluationIds),
                'questions' => $questionStats
            ];
        }

        return $report;
    }

    /**
     * Genera reporte detallado por rango de edad
     */
    private function generateReportByAge(array $evaluationIds, $ageData, array $questions)
    {
        $report = [];

        // Definir rangos de edad
        $ageRanges = ['18-25', '26-35', '36-45', '46-55', '56+'];

        // Agrupar por rango de edad
        $groupedByAge = $ageData->groupBy('demographic_value');

        foreach ($ageRanges as $range) {
            if (!isset($groupedByAge[$range])) {
                $report[$range] = [
                    'label' => $range,
                    'total' => 0,
                    'questions' => []
                ];
                continue;
            }

            $evaluations = $groupedByAge[$range];
            $evaluationIds = $evaluations->pluck('evaluation_id')->toArray();

            $questionStats = [];

            // Para cada pregunta, obtener las estadísticas para este grupo de edad
            foreach ($questions as $questionNumber) {
                $answers = Question::whereIn('evaluation_id', $evaluationIds)
                    ->where('reference_guide', 'III')
                    ->where('question', $questionNumber)
                    ->get();

                if ($answers->isEmpty()) continue;

                // Contar respuestas por nivel
                $niveles = ['Nulo' => 0, 'Bajo' => 0, 'Medio' => 0, 'Alto' => 0, 'Muy Alto' => 0];

                foreach ($answers as $answer) {
                    $value = (int) $answer->value;

                    if ($value === 0) $niveles['Nulo']++;
                    elseif ($value <= 1) $niveles['Bajo']++;
                    elseif ($value <= 2) $niveles['Medio']++;
                    elseif ($value <= 3) $niveles['Alto']++;
                    else $niveles['Muy Alto']++;
                }

                // Calcular totales
                $total = count($answers);
                $nuBa = $niveles['Nulo'] + $niveles['Bajo'];
                $meAlMa = $niveles['Medio'] + $niveles['Alto'] + $niveles['Muy Alto'];

                // Calcular CF
                $cf = 0;
                if ($total > 0) {
                    $cf = ($niveles['Bajo'] * 1 + $niveles['Medio'] * 2 + $niveles['Alto'] * 3 + $niveles['Muy Alto'] * 4) / $total * 25;
                }

                $questionStats[$questionNumber] = [
                    'niveles' => $niveles,
                    'total' => $total,
                    'nu_ba' => $nuBa,
                    'me_al_ma' => $meAlMa,
                    'cf' => round($cf)
                ];
            }

            $report[$range] = [
                'label' => $range,
                'total' => count($evaluationIds),
                'questions' => $questionStats
            ];
        }

        return $report;
    }
}
