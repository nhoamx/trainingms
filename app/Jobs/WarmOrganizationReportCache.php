<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\LikertScoreService;
use App\Services\OrganizationReportCacheService;
use App\Services\PaperEvaluationScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to pre-warm organization report caches after invalidation.
 *
 * This job runs in the background after evaluation data changes to ensure
 * the next user request hits a warm cache instead of waiting for computation.
 */
class WarmOrganizationReportCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300;

    public function __construct(
        public Organization $organization
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        OrganizationReportCacheService $cacheService,
        LikertScoreService $likertScoreService,
        PaperEvaluationScoreService $scoreService
    ): void {
        $orgId = $this->organization->id;

        // Log::info("Warming report cache for organization {$orgId}");

        // Warm Likert report cache if organization has Likert evaluations
        $hasLikertEvaluations = PaperEvaluation::where('organization_id', $orgId)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->exists();

        if ($hasLikertEvaluations) {
            $this->warmLikertReportCache($cacheService, $likertScoreService);
        }

        // Warm list results cache
        $this->warmListResultsCache($cacheService, $scoreService, $likertScoreService);

        Log::info("Cache warming completed for organization {$orgId}");
    }

    /**
     * Warm the Likert report cache
     */
    private function warmLikertReportCache(
        OrganizationReportCacheService $cacheService,
        LikertScoreService $likertScoreService
    ): void {
        $cacheService->rememberLikertReport($this->organization->id, function () use ($likertScoreService) {
            return $this->computeLikertReportData($likertScoreService);
        });
    }

    /**
     * Warm the list results cache
     */
    private function warmListResultsCache(
        OrganizationReportCacheService $cacheService,
        PaperEvaluationScoreService $scoreService,
        LikertScoreService $likertScoreService
    ): void {
        $cacheService->rememberListResults($this->organization->id, function () use ($scoreService, $likertScoreService) {
            return $this->computeListResultsData($scoreService, $likertScoreService);
        });

        $cacheService->rememberMissingFolios($this->organization->id, function () {
            return $this->computeMissingFoliosData();
        });
    }

    /**
     * Compute Likert report data (same logic as controller)
     */
    private function computeLikertReportData(LikertScoreService $likertScoreService): array
    {
        // Optimized query: select only needed columns and eager load relationships efficiently
        $likertEvaluations = PaperEvaluation::where('organization_id', $this->organization->id)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->select(['id', 'folio', 'personal_folio', 'evaluee_name', 'likert_answers', 'organization_id'])
            ->with([
                'comments:id,paper_evaluation_id,factor,comment',
                'customFields:id,paper_evaluation_id,field_key,key_label,value',
                'demographicData:id,paper_evaluation_id,gender,work_schedule,contract_type,position,department',
            ])
            ->get();

        if ($likertEvaluations->isEmpty()) {
            return [
                'evaluations' => [],
                'demographics' => [
                    'generos' => [],
                    'tipos_contrato' => [],
                    'puestos' => [],
                    'areas' => [],
                    'turnos' => [],
                ],
                'customFieldFilters' => [],
                'dimensions' => [],
                'climaLaboralDistribution' => [
                    'Totalmente de Acuerdo' => 0,
                    'De Acuerdo' => 0,
                    'Desacuerdo' => 0,
                    'Totalmente Desacuerdo' => 0,
                ],
                'totalPeople' => 0,
                'factors' => [],
            ];
        }

        // Get custom field filters
        $customFieldLabels = config('custom_field_labels');
        $customFieldValues = $likertEvaluations
            ->flatMap(fn ($eval) => $eval->customFields)
            ->groupBy('field_key')
            ->map(function ($fields) use ($customFieldLabels) {
                $fieldKey = $fields->first()->field_key;
                $dbLabel = $fields->first()->key_label;

                // Use label from config if exists, otherwise use database label
                $label = $customFieldLabels[$fieldKey] ?? $dbLabel;

                return [
                    'label' => $label,
                    'values' => $fields->pluck('value')->unique()->filter()->values()->all(),
                ];
            })
            ->all();

        // Load configuration
        $config = config('likert-value');
        $niveles = $config['niveles'];
        $preguntas = $config['preguntas'];
        $valorOpciones = $config['valorOpciones'];

        // Collect unique demographic values
        $generos = [];
        $tiposContrato = [];
        $puestos = [];
        $areas = [];
        $turnos = [];

        // Store answers for dimension calculations
        $answersForCalculation = [];

        // Process all evaluations
        $evaluationsData = [];
        foreach ($likertEvaluations as $evaluation) {
            $questions = $evaluation->likert_answers['questions'] ?? [];

            // Get demographic data using optimized method with eager-loaded data
            $demographics = $likertScoreService->getDemographicDataFromLoaded($evaluation);

            // Collect demographic values
            if (isset($demographics['genero'])) {
                $generos[$demographics['genero']] = true;
            }
            if (isset($demographics['tipo_contrato'])) {
                $tiposContrato[$demographics['tipo_contrato']] = true;
            }
            if (isset($demographics['puesto'])) {
                $puestos[$demographics['puesto']] = true;
            }
            if (isset($demographics['area'])) {
                $areas[$demographics['area']] = true;
            }
            if (isset($demographics['turno'])) {
                $turnos[$demographics['turno']] = true;
            }

            // Compute scores using optimized method
            $scores = $likertScoreService->calculateLikertScoresFromData($questions, $config);

            // Get custom fields
            $evaluationCustomFields = [];
            foreach ($evaluation->customFields as $customField) {
                $fieldKey = $customField->field_key;
                $dbLabel = $customField->key_label;

                // Use label from config if exists, otherwise use database label
                $label = $customFieldLabels[$fieldKey] ?? $dbLabel;

                $evaluationCustomFields[$fieldKey] = [
                    'label' => $label,
                    'value' => $customField->value,
                ];
            }

            // Build evaluation data
            $evaluationsData[] = [
                'id' => $evaluation->id,
                'folio' => $evaluation->folio,
                'personal_folio' => $evaluation->personal_folio,
                'evaluee_name' => $evaluation->evaluee_name,
                'demographics' => [
                    'genero' => $demographics['genero'] ?? null,
                    'tipo_contrato' => $demographics['tipo_contrato'] ?? null,
                    'puesto' => $demographics['puesto'] ?? null,
                    'area' => $demographics['area'] ?? null,
                    'turno' => $demographics['turno'] ?? null,
                ],
                'customFields' => $evaluationCustomFields,
                'scores' => $scores,
                'answers' => $questions,
                'comments' => $evaluation->comments->map(function ($comment) {
                    return [
                        'factor' => $comment->factor,
                        'comment' => $comment->comment,
                    ];
                })->all(),
            ];

            $answersForCalculation[$evaluation->id] = $questions;
        }

        // Extract unique factors from comments (dynamic, not hardcoded)
        $factors = $likertEvaluations
            ->flatMap(fn ($eval) => $eval->comments)
            ->pluck('factor')
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->all();

        // Calculate distribution
        $climaLaboralDistribution = [
            'Totalmente de Acuerdo' => 0,
            'De Acuerdo' => 0,
            'Desacuerdo' => 0,
            'Totalmente Desacuerdo' => 0,
        ];

        foreach ($evaluationsData as $evalData) {
            $interpretation = $evalData['scores']['interpretation'];
            if ($interpretation) {
                $climaLaboralDistribution[$interpretation] = ($climaLaboralDistribution[$interpretation] ?? 0) + 1;
            }
        }

        // Build dimension summaries
        $dimensionSummaries = [];
        foreach ($niveles as $dimensionName => $dimensionConfig) {
            $questionNumbers = $dimensionConfig['preguntas'];
            $questionCount = count($questionNumbers);
            $questionScores = [];

            $dimensionDistribution = [
                'Totalmente de Acuerdo' => 0,
                'De Acuerdo' => 0,
                'Desacuerdo' => 0,
                'Totalmente Desacuerdo' => 0,
            ];

            foreach ($evaluationsData as $evalData) {
                $personScore = 0;
                $evalAnswers = $answersForCalculation[$evalData['id']] ?? [];
                foreach ($questionNumbers as $qNum) {
                    $answer = $evalAnswers[$qNum] ?? null;
                    if ($answer) {
                        $personScore += $valorOpciones[$answer] ?? 0;
                    }
                }

                $interpretation = $this->getInterpretation($personScore, $config['valorNiveles'][$dimensionName]);
                if ($interpretation) {
                    $dimensionDistribution[$interpretation] = ($dimensionDistribution[$interpretation] ?? 0) + 1;
                }
            }

            foreach ($questionNumbers as $qNum) {
                $qScore = 0;
                $qCount = 0;
                foreach ($evaluationsData as $evalData) {
                    $evalAnswers = $answersForCalculation[$evalData['id']] ?? [];
                    $answer = $evalAnswers[$qNum] ?? null;
                    if ($answer) {
                        $qScore += $valorOpciones[$answer] ?? 0;
                        $qCount++;
                    }
                }
                $avgScore = $qCount > 0 ? $qScore / $qCount : 0;
                $questionScores[$qNum] = [
                    'question' => $preguntas[$qNum] ?? "Pregunta {$qNum}",
                    'score' => round($avgScore, 2),
                ];
            }

            $dimensionSummaries[$dimensionName] = [
                'name' => $dimensionName,
                'distribution' => $dimensionDistribution,
                'questionCount' => $questionCount,
                'questions' => $questionScores,
            ];
        }

        return [
            'evaluations' => $evaluationsData,
            'demographics' => [
                'generos' => array_keys($generos),
                'tipos_contrato' => array_keys($tiposContrato),
                'puestos' => array_keys($puestos),
                'areas' => array_keys($areas),
                'turnos' => array_keys($turnos),
            ],
            'customFieldFilters' => $customFieldValues,
            'dimensions' => $dimensionSummaries,
            'climaLaboralDistribution' => $climaLaboralDistribution,
            'totalPeople' => count($evaluationsData),
            'factors' => $factors,
        ];
    }

    /**
     * Get interpretation label for a score
     */
    private function getInterpretation(float $score, array $ranges): string
    {
        foreach ($ranges as $label => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return $label;
            }
        }

        return 'Sin interpretación';
    }

    /**
     * Compute list results data (same logic as controller)
     */
    private function computeListResultsData(
        PaperEvaluationScoreService $scoreService,
        LikertScoreService $likertScoreService
    ): array {
        $evaluationGroups = PaperEvaluation::where('organization_id', $this->organization->id)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->with('demographicData')
            ->orderBy('personal_folio')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('personal_folio')
            ->map(function ($evaluations, $personalFolio) use ($scoreService, $likertScoreService) {
                $evaluationTypes = $evaluations->pluck('evaluation_type')->unique()->values();
                $source = $evaluations->first()->source;

                $referenciaIII = $evaluations->firstWhere('evaluation_type', 'referencia_iii');
                $likert = $evaluations->firstWhere('evaluation_type', 'likert');

                $totalScore = 0;
                $evalueeNameFromRef3 = null;

                if ($referenciaIII) {
                    $scores = $scoreService->calculateReferenciaIIIScores($referenciaIII);
                    $totalScore = $scores['total_score'];
                    $evalueeNameFromRef3 = $referenciaIII->evaluee_name;
                } elseif ($likert) {
                    $likertScores = $likertScoreService->calculateLikertScores($likert);
                    $totalScore = $likertScores['total_score'];
                    $evalueeNameFromRef3 = $likert->evaluee_name;
                }

                $hasReferenciaIII = $evaluations->contains('evaluation_type', 'referencia_iii');
                $hasReferenciaV = $evaluations->contains('evaluation_type', 'referencia_v');
                $hasLikert = $evaluations->contains('evaluation_type', 'likert');

                $missingData = $this->checkMissingData($evaluations, $hasLikert, $hasReferenciaIII, $hasReferenciaV, $likert);
                $referenciaV = $evaluations->firstWhere('evaluation_type', 'referencia_v');

                return [
                    'personal_folio' => $personalFolio,
                    'evaluation_types' => $evaluationTypes,
                    'evaluee_name' => $evalueeNameFromRef3 ?? $evaluations->first()->evaluee_name,
                    'source' => $source,
                    'total_score' => $totalScore,
                    'created_at' => $evaluations->first()->created_at->format('Y-m-d H:i:s'),
                    'has_referencia_iii' => $hasReferenciaIII,
                    'has_referencia_v' => $hasReferenciaV,
                    'has_likert' => $hasLikert,
                    'missing_data' => $missingData,
                    'demographic_data' => $referenciaV?->demographic_data,
                    'likert_demographic_data' => $likert?->demographicData ? [
                        'gender' => $likert->demographicData->gender,
                        'age' => $likert->demographicData->age,
                        'marital_status' => $likert->demographicData->marital_status,
                        'education_level' => $likert->demographicData->education_level,
                        'position' => $likert->demographicData->position,
                        'department' => $likert->demographicData->department,
                        'position_type' => $likert->demographicData->position_type,
                        'contract_type' => $likert->demographicData->contract_type,
                        'personnel_type' => $likert->demographicData->personnel_type,
                        'work_schedule' => $likert->demographicData->work_schedule,
                        'shift_rotation' => $likert->demographicData->shift_rotation,
                        'time_in_current_position' => $likert->demographicData->time_in_current_position,
                        'work_experience' => $likert->demographicData->work_experience,
                    ] : null,
                    'evaluations' => $evaluations->map(function ($eval) {
                        return [
                            'id' => $eval->id,
                            'folio' => $eval->folio,
                            'evaluation_type' => $eval->evaluation_type,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        // Calculate summary statistics
        $totalEvaluations = count($evaluationGroups);

        $missingReferenciaIII = collect($evaluationGroups)->filter(function ($group) {
            return ! $group['has_referencia_iii'] && ! ($group['has_likert'] && ! $group['has_referencia_v']);
        })->count();

        $missingReferenciaV = collect($evaluationGroups)->filter(function ($group) {
            return ! $group['has_referencia_v'] && ! ($group['has_likert'] && ! $group['has_referencia_iii']);
        })->count();

        $withMissingData = collect($evaluationGroups)->filter(fn ($group) => ! empty($group['missing_data']))->count();

        return [
            'evaluationGroups' => $evaluationGroups,
            'summary' => [
                'total_evaluations' => $totalEvaluations,
                'missing_referencia_iii' => $missingReferenciaIII,
                'missing_referencia_v' => $missingReferenciaV,
                'with_missing_data' => $withMissingData,
            ],
        ];
    }

    /**
     * Check for missing data in evaluation group
     */
    private function checkMissingData($evaluations, bool $hasLikert, bool $hasReferenciaIII, bool $hasReferenciaV, $likert): array
    {
        $missingData = [];
        $referenciaV = $evaluations->firstWhere('evaluation_type', 'referencia_v');

        if ($hasLikert && ! $hasReferenciaIII && ! $hasReferenciaV) {
            $likertData = $likert->likert_answers ?? [];
            $questions = $likertData['questions'] ?? [];
            $unansweredQuestions = [];

            for ($i = 1; $i <= 23; $i++) {
                if (! isset($questions[(string) $i]) || empty($questions[(string) $i])) {
                    $unansweredQuestions[] = $i;
                }
            }

            if (count($unansweredQuestions) > 0) {
                if (count($unansweredQuestions) <= 5) {
                    $missingData[] = 'Preguntas sin responder: '.implode(', ', $unansweredQuestions);
                } else {
                    $missingData[] = count($unansweredQuestions).' preguntas sin responder';
                }
            }

            $demoModel = $likert->demographicData;
            $checks = [
                ['label' => 'Género', 'model' => $demoModel?->gender, 'json' => $likertData['genero'] ?? null],
                ['label' => 'Turno', 'model' => $demoModel?->work_schedule, 'json' => $likertData['turno'] ?? null],
                ['label' => 'Tipo de Contrato', 'model' => $demoModel?->contract_type, 'json' => $likertData['tipo_contrato'] ?? null],
                ['label' => 'Puesto', 'model' => $demoModel?->position, 'json' => $likertData['puestos'] ?? null],
                ['label' => 'Área', 'model' => $demoModel?->department, 'json' => $likertData['areas'] ?? null],
            ];

            foreach ($checks as $c) {
                $modelVal = is_string($c['model'] ?? null) ? trim($c['model']) : ($c['model'] ?? null);
                $jsonVal = is_string($c['json'] ?? null) ? trim($c['json']) : ($c['json'] ?? null);
                if (($modelVal === null || $modelVal === '') && ($jsonVal === null || $jsonVal === '')) {
                    $missingData[] = $c['label'];
                }
            }
        } elseif ($referenciaV) {
            if (! $referenciaV->demographic_data || empty($referenciaV->demographic_data)) {
                $missingData = ['Todos los datos demográficos'];
            } else {
                $data = $referenciaV->demographic_data;
                $isPaperFormat = ! isset($data['datos_laborales']);

                if ($isPaperFormat) {
                    $paperFields = [
                        'edad' => 'Edad',
                        'sexo' => 'Género',
                        'estado_civil' => 'Estado Civil',
                        'ocupacion' => 'Puesto/Ocupación',
                        'departamento' => 'Departamento',
                        'tipo_puesto' => 'Tipo de Puesto',
                        'tipo_contratacion' => 'Tipo de Contratación',
                        'tipo_jornada' => 'Tipo de Jornada',
                        'tiempo_puesto_actual' => 'Experiencia en Puesto Actual',
                    ];

                    foreach ($paperFields as $field => $label) {
                        $value = $data[$field] ?? null;
                        if ($value === null || $value === '') {
                            $missingData[] = $label;
                        } elseif (is_array($value)) {
                            $allEmpty = true;
                            foreach ($value as $subValue) {
                                if ($subValue !== null && $subValue !== '') {
                                    $allEmpty = false;
                                    break;
                                }
                            }
                            if ($allEmpty) {
                                $missingData[] = $label;
                            }
                        }
                    }
                } else {
                    $basicFields = [
                        'edad' => 'Edad',
                        'sexo' => 'Género',
                        'estado_civil' => 'Estado Civil',
                        'nivel_estudios' => 'Nivel de Estudios',
                    ];

                    foreach ($basicFields as $field => $label) {
                        if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                            $missingData[] = $label;
                        }
                    }

                    if (! isset($data['datos_laborales']) || empty($data['datos_laborales'])) {
                        $missingData[] = 'Todos los Datos Laborales';
                    } else {
                        $laborData = $data['datos_laborales'];
                        $laborFields = [
                            'ocupacion_puesto' => 'Puesto',
                            'tipo_puesto' => 'Tipo de Puesto',
                            'tipo_contratacion' => 'Tipo de Contratación',
                            'tipo_jornada' => 'Tipo de Jornada',
                            'departamento_seccion_area' => 'Área/Departamento',
                        ];

                        foreach ($laborFields as $field => $label) {
                            if (! isset($laborData[$field]) || $laborData[$field] === null || $laborData[$field] === '') {
                                $missingData[] = $label;
                            }
                        }

                        if (isset($laborData['experiencia'])) {
                            if (empty($laborData['experiencia']['tiempo_puesto_actual'])) {
                                $missingData[] = 'Experiencia en Puesto Actual';
                            }
                        } else {
                            $missingData[] = 'Experiencia en Puesto Actual';
                        }
                    }
                }
            }
        }

        return $missingData;
    }

    /**
     * Compute missing folios data
     */
    private function computeMissingFoliosData(): array
    {
        $evaluationGroups = PaperEvaluation::where('organization_id', $this->organization->id)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->get()
            ->groupBy('personal_folio');

        $existingFolios = $evaluationGroups->keys()->map(fn ($f) => (int) $f)->unique()->sort()->values()->toArray();

        $missingFolios = [];

        if (count($existingFolios) >= 2) {
            $minFolio = min($existingFolios);
            $maxFolio = max($existingFolios);
            $existingFoliosLookup = array_flip($existingFolios);

            $gaps = [];
            for ($i = $minFolio + 1; $i < $maxFolio; $i++) {
                if (! isset($existingFoliosLookup[$i])) {
                    $gaps[] = $i;
                }
            }

            $folioBatches = \App\Models\FolioBatch::where('organization_id', $this->organization->id)->get();

            if (! empty($gaps)) {
                $batchLookup = [];
                foreach ($folioBatches as $batch) {
                    $batchLookup[] = [
                        'id' => $batch->id,
                        'name' => $batch->name,
                        'type' => $batch->type,
                        'start' => $batch->start_number,
                        'end' => $batch->end_number,
                    ];
                }

                $batchMap = [];
                $ungroupedGaps = [];

                foreach ($gaps as $gap) {
                    $assignedToBatch = false;

                    foreach ($batchLookup as $batch) {
                        if ($gap >= $batch['start'] && $gap <= $batch['end']) {
                            if (! isset($batchMap[$batch['id']])) {
                                $batchMap[$batch['id']] = [
                                    'batch_name' => $batch['name'],
                                    'batch_type' => $batch['type'],
                                    'folios' => [],
                                ];
                            }
                            $batchMap[$batch['id']]['folios'][] = str_pad($gap, 4, '0', STR_PAD_LEFT);
                            $assignedToBatch = true;
                            break;
                        }
                    }

                    if (! $assignedToBatch) {
                        $ungroupedGaps[] = str_pad($gap, 4, '0', STR_PAD_LEFT);
                    }
                }

                foreach ($batchMap as $batchData) {
                    $missingFolios[] = [
                        'batch_name' => $batchData['batch_name'],
                        'batch_type' => $batchData['batch_type'],
                        'folios' => $batchData['folios'],
                        'count' => count($batchData['folios']),
                    ];
                }

                if (! empty($ungroupedGaps)) {
                    $missingFolios[] = [
                        'batch_name' => 'Sin lote asignado',
                        'batch_type' => 'presencial',
                        'folios' => $ungroupedGaps,
                        'count' => count($ungroupedGaps),
                    ];
                }
            }
        }

        return $missingFolios;
    }
}
