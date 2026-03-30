<?php

namespace App\Http\Controllers;

use App\Exports\ClimaLaboralCompactExport;
use App\Exports\EvaluationCommentsTemplateExport;
use App\Exports\EvaluationTemplateExport;
use App\Exports\GapFoliosExport;
use App\Jobs\ProcessBulkCommentsImport;
use App\Jobs\ProcessBulkEvaluationImport;
use App\Models\BulkImportJob;
use App\Models\Category;
use App\Models\DemographicData;
use App\Models\Evaluation;
use App\Models\EvaluationCustomField;
use App\Models\FolioBatch;
use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Models\Question;
use App\Models\User;
use App\Services\LikertScoreService;
use App\Services\OrganizationReportCacheService;
use App\Services\PaperEvaluationScoreService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ResultsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected PaperEvaluationScoreService $scoreService,
        protected LikertScoreService $likertScoreService,
        protected OrganizationReportCacheService $cacheService
    ) {}

    /**
     * Show Likert report for an organization
     */
    public function showLikertReport(Organization $organization, Request $request)
    {
        $user = $request->user();

        // Get cached report data or compute it
        $reportData = $this->cacheService->rememberLikertReport($organization->id, function () use ($organization) {
            return $this->computeLikertReportData($organization);
        });

        // If no evaluations, return empty report
        if (empty($reportData['evaluations'])) {
            return Inertia::render('Reports/LikertOrganizationReport', [
                'organizationId' => $organization->id,
                'organizationName' => $organization->name,
                'title' => 'Reporte Clima Laboral',
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
                'totalScore' => null,
                'factors' => [],
                'isAdmin' => $user && $user->hasRole('admin'),
                'isSuperAdmin' => $user && $user->hasRole('super-admin'),
            ]);
        }

        // Load configuration for static props
        $config = config('likert-value');

        return Inertia::render('Reports/LikertOrganizationReport', [
            'organizationId' => $organization->id,
            'organizationName' => $organization->name,
            'title' => 'Clima Laboral - '.$organization->name,
            'evaluations' => $reportData['evaluations'],
            'demographics' => $reportData['demographics'],
            'customFieldFilters' => $reportData['customFieldFilters'],
            'puestosMap' => $config['puestos'],
            'areasMap' => $config['areas'],
            'dimensions' => $reportData['dimensions'],
            'climaLaboralDistribution' => $reportData['climaLaboralDistribution'],
            'totalPeople' => $reportData['totalPeople'],
            'factors' => $reportData['factors'],
            'isAdmin' => $user && $user->hasRole('admin'),
            'isAdmin' => $user && $user->hasRole('admin'),
            'isSuperAdmin' => $user && $user->hasRole('super-admin'),
        ]);
    }

    /**
     * Compute Likert report data (extracted for caching)
     *
     * @return array<string, mixed>
     */
    private function computeLikertReportData(Organization $organization): array
    {
        // Optimized query: select only needed columns and eager load relationships efficiently
        $likertEvaluations = PaperEvaluation::where('organization_id', $organization->id)
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

        // Get custom field filters from eager loaded data
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

        // Store answers separately for dimension calculations
        $answersForCalculation = [];

        // Process all evaluations
        $evaluationsData = [];
        foreach ($likertEvaluations as $evaluation) {
            $questions = $evaluation->likert_answers['questions'] ?? [];

            // Get demographic data from DemographicData model (with fallback to likert_answers)
            // Now using eager loaded demographicData to avoid N+1 queries
            $demographics = $this->likertScoreService->getDemographicDataFromLoaded($evaluation);

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

            // Compute scores using pre-loaded data
            $scores = $this->likertScoreService->calculateLikertScoresFromData($questions, $config);

            // Get custom fields from eager loaded data
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

        // Calculate distribution of people by Clima Laboral level
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
     * Export Likert evaluations by selected clima laboral levels
     */
    public function exportLikertByClimaLevel(Organization $organization, Request $request)
    {
        $this->authorize('view-organization-results', $organization);

        $user = $request->user();

        // Only admin and super-admin can download
        if (! $this->isAdminOrSuperAdmin($user)) {
            abort(403, 'Solo administradores pueden descargar este reporte');
        }

        $selectedLevels = $request->input('levels', []);

        if (empty($selectedLevels)) {
            return response()->json(['error' => 'Debe seleccionar al menos un nivel'], 422);
        }

        // Get cached report data
        $reportData = $this->cacheService->rememberLikertReport($organization->id, function () use ($organization) {
            return $this->computeLikertReportData($organization);
        });

        $evaluations = $reportData['evaluations'] ?? [];
        $customFieldFilters = $reportData['customFieldFilters'] ?? [];

        // Get custom field headers (ordered by key)
        $customFieldHeaders = [];
        $customFieldKeys = [];
        foreach ($customFieldFilters as $fieldKey => $fieldData) {
            $customFieldKeys[] = $fieldKey;
            $customFieldHeaders[] = $fieldData['label'] ?? $fieldKey;
        }

        // Filter evaluations by selected levels
        $filteredData = [];
        foreach ($evaluations as $evaluation) {
            $interpretation = $evaluation['scores']['interpretation'] ?? null;

            if (in_array($interpretation, $selectedLevels, true)) {
                $row = [
                    $evaluation['personal_folio'] ?? $evaluation['folio'] ?? '',
                    $evaluation['evaluee_name'] ?? 'Sin nombre',
                    $interpretation ?? 'Sin clasificar',
                    $evaluation['scores']['total_score'] ?? '',
                    $evaluation['demographics']['genero'] ?? '',
                    $evaluation['demographics']['tipo_contrato'] ?? '',
                    $evaluation['demographics']['puesto'] ?? '',
                    $evaluation['demographics']['area'] ?? '',
                    $evaluation['demographics']['turno'] ?? '',
                ];

                // Add custom fields values
                foreach ($customFieldKeys as $fieldKey) {
                    $customFieldValue = $evaluation['customFields'][$fieldKey]['value'] ?? '';
                    $row[] = $customFieldValue;
                }

                $filteredData[] = $row;
            }
        }

        if (empty($filteredData)) {
            return response()->json(['error' => 'No se encontraron evaluaciones con los niveles seleccionados'], 404);
        }

        $filename = 'clima_laboral_'.str_replace(' ', '_', $organization->name).'_'.now()->format('Y-m-d').'.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LikertClimaLevelExport($filteredData, $customFieldHeaders),
            $filename
        );
    }

    /**
     * Export all Clima Laboral evaluations in a single compact sheet
     */
    public function exportClimaLaboralCompact(Organization $organization, Request $request)
    {
        $this->authorize('view-organization-results', $organization);

        // Get cached report data
        $reportData = $this->cacheService->rememberLikertReport($organization->id, function () use ($organization) {
            return $this->computeLikertReportData($organization);
        });

        $evaluations = $reportData['evaluations'] ?? [];

        if (empty($evaluations)) {
            return response()->json(['error' => 'No se encontraron evaluaciones de clima laboral'], 404);
        }

        // Compute custom field keys once (derived from heading labels)
        $lineaKey = EvaluationCustomField::labelToKey('Línea');
        $gerentePlantaKey = EvaluationCustomField::labelToKey('Gerente de Planta');
        $gerenteProduccionKey = EvaluationCustomField::labelToKey('Gerente de Producción');
        $gerenteRhKey = EvaluationCustomField::labelToKey('Gerente de RH');
        $supervisorKey = EvaluationCustomField::labelToKey('Supervisor');
        $comentariosKey = EvaluationCustomField::labelToKey('Comentarios Adicionales');

        // Map evaluations to compact format
        $exportData = [];
        foreach ($evaluations as $evaluation) {
            $answers = $evaluation['answers'] ?? [];
            $customFields = $evaluation['customFields'] ?? [];
            $exportData[] = [
                $evaluation['personal_folio'] ?? $evaluation['folio'] ?? '',
                $evaluation['evaluee_name'] ?? 'Sin nombre',
                $evaluation['scores']['total_score'] ?? '',
                $evaluation['scores']['interpretation'] ?? 'Sin clasificar',
                $evaluation['demographics']['genero'] ?? '',
                $evaluation['demographics']['tipo_contrato'] ?? '',
                $evaluation['demographics']['area'] ?? '',
                $evaluation['demographics']['puesto'] ?? '',
                $evaluation['demographics']['turno'] ?? '',
                $customFields[$lineaKey]['value'] ?? '',
                $customFields[$gerentePlantaKey]['value'] ?? '',
                $customFields[$gerenteProduccionKey]['value'] ?? '',
                $customFields[$gerenteRhKey]['value'] ?? '',
                $customFields[$supervisorKey]['value'] ?? '',
                $customFields[$comentariosKey]['value'] ?? '',
                $answers['1'] ?? '',
                $answers['2'] ?? '',
                $answers['3'] ?? '',
                $answers['4'] ?? '',
                $answers['5'] ?? '',
                $answers['6'] ?? '',
                $answers['7'] ?? '',
                $answers['8'] ?? '',
                $answers['9'] ?? '',
                $answers['10'] ?? '',
                $answers['11'] ?? '',
                $answers['12'] ?? '',
                $answers['13'] ?? '',
                $answers['14'] ?? '',
                $answers['15'] ?? '',
                $answers['16'] ?? '',
                $answers['17'] ?? '',
                $answers['18'] ?? '',
                $answers['19'] ?? '',
                $answers['20'] ?? '',
                $answers['21'] ?? '',
                $answers['22'] ?? '',
                $answers['23'] ?? '',
            ];
        }

        $filename = 'clima_laboral_'.str_replace(' ', '_', $organization->name).'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new ClimaLaboralCompactExport($exportData),
            $filename
        );
    }

    /**
     * Get available export options for climate export (demographics, custom fields, factors)
     */
    public function getClimaExportOptions(Organization $organization, Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view-organization-results', $organization);

        $user = $request->user();

        if (! $this->isAdminOrSuperAdmin($user)) {
            abort(403, 'Solo administradores pueden acceder a esta función');
        }

        // Get evaluation types available for this organization
        $evaluationTypes = PaperEvaluation::where('organization_id', $organization->id)
            ->where('processing_status', 'completed')
            ->distinct()
            ->pluck('evaluation_type')
            ->toArray();

        // For now, focus on likert evaluations
        if (! in_array('likert', $evaluationTypes)) {
            return response()->json([
                'evaluationTypes' => $evaluationTypes,
                'demographics' => [],
                'customFields' => [],
                'factors' => [],
            ]);
        }

        // Get cached report data
        $reportData = $this->cacheService->rememberLikertReport($organization->id, function () use ($organization) {
            return $this->computeLikertReportData($organization);
        });

        $demographics = $reportData['demographics'] ?? [];
        $customFieldFilters = $reportData['customFieldFilters'] ?? [];

        // Get unique factors from comments
        $factors = $reportData['factors'] ?? [];

        // If no factors from comments, use config defaults
        if (empty($factors)) {
            $factors = array_keys(config('likert-value.niveles', []));
        }

        // Build demographic options
        $demographicOptions = [];

        if (! empty($demographics['generos'])) {
            $demographicOptions[] = [
                'key' => 'genero',
                'label' => 'Género',
                'values' => $demographics['generos'],
            ];
        }

        if (! empty($demographics['turnos'])) {
            $demographicOptions[] = [
                'key' => 'turno',
                'label' => 'Turno',
                'values' => $demographics['turnos'],
            ];
        }

        if (! empty($demographics['tipos_contrato'])) {
            $demographicOptions[] = [
                'key' => 'tipo_contrato',
                'label' => 'Tipo de Contrato',
                'values' => $demographics['tipos_contrato'],
            ];
        }

        if (! empty($demographics['puestos'])) {
            $demographicOptions[] = [
                'key' => 'puesto',
                'label' => 'Puesto',
                'values' => $demographics['puestos'],
            ];
        }

        if (! empty($demographics['areas'])) {
            $demographicOptions[] = [
                'key' => 'area',
                'label' => 'Área',
                'values' => $demographics['areas'],
            ];
        }

        // Build custom field options
        $customFieldOptions = [];
        foreach ($customFieldFilters as $fieldKey => $fieldData) {
            $customFieldOptions[] = [
                'key' => $fieldKey,
                'label' => $fieldData['label'] ?? $fieldKey,
                'values' => $fieldData['values'] ?? [],
            ];
        }

        return response()->json([
            'evaluationTypes' => $evaluationTypes,
            'demographics' => $demographicOptions,
            'customFields' => $customFieldOptions,
            'factors' => $factors,
        ]);
    }

    /**
     * Export multi-sheet Excel with demographic/custom field combinations
     */
    public function exportClimaMultiSheet(Organization $organization, Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $this->authorize('view-organization-results', $organization);

        $user = $request->user();

        if (! $this->isAdminOrSuperAdmin($user)) {
            abort(403, 'Solo administradores pueden descargar este reporte');
        }

        $combinations = $request->input('combinations', []);
        $selectedFactors = $request->input('factors', []);

        if (empty($combinations)) {
            return response()->json(['error' => 'Debe agregar al menos una combinación'], 422);
        }

        if (count($combinations) > 4) {
            return response()->json(['error' => 'Máximo 4 combinaciones permitidas'], 422);
        }

        // Get cached report data
        $reportData = $this->cacheService->rememberLikertReport($organization->id, function () use ($organization) {
            return $this->computeLikertReportData($organization);
        });

        $evaluations = $reportData['evaluations'] ?? [];
        $customFieldFilters = $reportData['customFieldFilters'] ?? [];

        if (empty($evaluations)) {
            return response()->json(['error' => 'No hay evaluaciones disponibles para exportar'], 404);
        }

        // Get custom field headers
        $customFieldHeaders = [];
        $customFieldKeys = [];
        foreach ($customFieldFilters as $fieldKey => $fieldData) {
            $customFieldKeys[] = $fieldKey;
            $customFieldHeaders[] = $fieldData['label'] ?? $fieldKey;
        }

        // If no factors selected, use all from config
        if (empty($selectedFactors)) {
            $selectedFactors = array_keys(config('likert-value.niveles', []));
        }

        // Build sheets for each combination
        $sheets = [];
        foreach ($combinations as $combination) {
            $sheetData = $this->buildSheetDataForCombination(
                $evaluations,
                $combination,
                $customFieldKeys,
                $selectedFactors
            );

            $sheetTitle = $this->buildSheetTitle($combination);

            $sheets[] = new \App\Exports\LikertCombinationSheetExport(
                $sheetData,
                $sheetTitle,
                $customFieldHeaders,
                $selectedFactors
            );
        }

        $filename = 'clima_combinaciones_'.str_replace(' ', '_', $organization->name).'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new \App\Exports\MultiSheetLikertExport($sheets),
            $filename
        );
    }

    /**
     * Build sheet data for a specific combination filter
     *
     * @param  array<int, array<string, mixed>>  $evaluations
     * @param  array<string, mixed>  $combination
     * @param  array<int, string>  $customFieldKeys
     * @param  array<int, string>  $selectedFactors
     * @return array<int, array<int, mixed>>
     */
    private function buildSheetDataForCombination(
        array $evaluations,
        array $combination,
        array $customFieldKeys,
        array $selectedFactors
    ): array {
        $filters = $combination['filters'] ?? [];
        $filteredData = [];

        foreach ($evaluations as $evaluation) {
            // Apply filters
            if (! $this->evaluationMatchesFilters($evaluation, $filters)) {
                continue;
            }

            // Build row
            $row = [
                $evaluation['personal_folio'] ?? $evaluation['folio'] ?? '',
                $evaluation['evaluee_name'] ?? 'Sin nombre',
                $evaluation['demographics']['genero'] ?? '',
                $evaluation['demographics']['tipo_contrato'] ?? '',
                $evaluation['demographics']['puesto'] ?? '',
                $evaluation['demographics']['area'] ?? '',
                $evaluation['demographics']['turno'] ?? '',
            ];

            // Add custom fields
            foreach ($customFieldKeys as $fieldKey) {
                $row[] = $evaluation['customFields'][$fieldKey]['value'] ?? '';
            }

            // Add factor scores and levels
            $dimensionScores = $evaluation['scores']['dimensions'] ?? [];
            $valorNiveles = config('likert-value.valorNiveles', []);

            foreach ($selectedFactors as $factor) {
                $factorScore = $dimensionScores[$factor]['score'] ?? 0;
                $row[] = $factorScore ?: '';

                // Calculate factor level based on score and config ranges
                $factorLevel = $this->getFactorLevel($factorScore, $factor, $valorNiveles);
                $row[] = $factorLevel;
            }

            // Add all comments (not filtered by factor)
            $comments = $evaluation['comments'] ?? [];
            $allComments = [];
            foreach ($comments as $comment) {
                $allComments[] = $comment['factor'].': '.$comment['comment'];
            }
            $row[] = implode(' | ', $allComments);

            $filteredData[] = $row;
        }

        return $filteredData;
    }

    /**
     * Get factor level interpretation based on score
     */
    private function getFactorLevel(float|int $score, string $factor, array $valorNiveles): string
    {
        if (empty($score) || $score == 0) {
            return '';
        }

        $ranges = $valorNiveles[$factor] ?? [];

        foreach ($ranges as $level => $range) {
            $min = $range['min'] ?? 0;
            $max = $range['max'] ?? 0;

            if ($score >= $min && $score <= $max) {
                return $level;
            }
        }

        return '';
    }

    /**
     * Check if evaluation matches all filters
     *
     * @param  array<string, mixed>  $evaluation
     * @param  array<string, mixed>  $filters
     */
    private function evaluationMatchesFilters(array $evaluation, array $filters): bool
    {
        foreach ($filters as $filter) {
            $type = $filter['type'] ?? '';
            $key = $filter['key'] ?? '';
            $value = $filter['value'] ?? '';

            if ($type === 'demographic') {
                $evalValue = $evaluation['demographics'][$key] ?? null;
                if ($evalValue !== $value) {
                    return false;
                }
            } elseif ($type === 'customField') {
                $evalValue = $evaluation['customFields'][$key]['value'] ?? null;
                if ($evalValue !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Build sheet title from combination filters (short format)
     *
     * @param  array<string, mixed>  $combination
     */
    private function buildSheetTitle(array $combination): string
    {
        $filters = $combination['filters'] ?? [];
        $parts = [];

        foreach ($filters as $filter) {
            $value = $filter['value'] ?? '';
            if ($value) {
                $parts[] = $value;
            }
        }

        if (empty($parts)) {
            return 'Todos';
        }

        return implode('+', $parts);
    }

    public function organizationResults(Organization $organization, Request $request)
    {
        // Si se proporciona un folio específico, buscar esa evaluación
        if ($request->has('folio')) {
            $evaluation = $organization->evaluations()
                ->where('folio', $request->folio)
                ->first();
        } else {
            // Si no se proporciona folio, usar la última evaluación
            $evaluation = $organization->evaluations()->latest()->first();
        }

        if (! $evaluation) {
            return response()->json(['error' => 'No evaluation found for this organization'], 404);
        }

        $results = Category::with(['domains.dimensions' => function ($query) use ($evaluation) {
            $query->withSum(['answers' => function ($query) use ($evaluation) {
                $query->where('evaluation_id', $evaluation->id);
            }], 'score');
        }])->get()->map(function ($category) {
            $categoryScore = 0;

            $domains = $category->domains->map(function ($domain) use (&$categoryScore) {
                $domainScore = 0;

                $dimensions = $domain->dimensions->map(function ($dimension) use (&$domainScore) {
                    $score = $dimension->answers_sum_score ?? 0;
                    $domainScore += $score;

                    return [
                        'id' => $dimension->id,
                        'name' => $dimension->name,
                        'score' => $score,
                    ];
                });

                $categoryScore += $domainScore;

                return [
                    'id' => $domain->id,
                    'name' => $domain->name,
                    'score' => $domainScore,
                    'dimensions' => $dimensions,
                ];
            });

            return [
                'id' => $category->id,
                'name' => $category->name,
                'score' => $categoryScore,
                'domains' => $domains,
            ];
        });

        return response()->json([
            'organization' => $organization->name,
            'evaluation_id' => $evaluation->id,
            'folio' => $evaluation->folio,
            'created_at' => $evaluation->created_at,
            'results' => $results,
        ]);
    }

    public function listResults(Organization $organization, Request $request)
    {
        $this->authorize('view-organization-results', $organization);

        // Get cached list results data or compute it
        $cachedData = $this->cacheService->rememberListResults($organization->id, function () use ($organization) {
            return $this->computeListResultsData($organization);
        });

        // Get cached missing folios or compute it
        $missingFolios = $this->cacheService->rememberMissingFolios($organization->id, function () use ($organization) {
            return $this->calculateMissingFolios($organization);
        });

        $user = $request->user();

        return Inertia::render('Results/List', [
            'organization' => $organization->only('id', 'name'),
            'evaluationGroups' => $cachedData['evaluationGroups'],
            'missingFolios' => $missingFolios,
            'summary' => $cachedData['summary'],
            'isAdmin' => $user && $user->hasRole('admin'),
            'isSuperAdmin' => $user && $user->hasRole('super-admin'),
        ]);
    }

    /**
     * Compute list results data (extracted for caching)
     *
     * @return array<string, mixed>
     */
    private function computeListResultsData(Organization $organization): array
    {
        $evaluationGroups = PaperEvaluation::where('organization_id', $organization->id)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->with('demographicData')
            ->orderBy('personal_folio')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('personal_folio')
            ->map(function ($evaluations, $personalFolio) {
                $evaluationTypes = $evaluations->pluck('evaluation_type')->unique()->values();
                $source = $evaluations->first()->source;

                $referenciaIII = $evaluations->firstWhere('evaluation_type', 'referencia_iii');
                $likert = $evaluations->firstWhere('evaluation_type', 'likert');

                $totalScore = 0;
                $evalueeNameFromRef3 = null;

                if ($referenciaIII) {
                    $scores = $this->scoreService->calculateReferenciaIIIScores($referenciaIII);
                    $totalScore = $scores['total_score'];
                    $evalueeNameFromRef3 = $referenciaIII->evaluee_name;
                } elseif ($likert) {
                    $likertScores = $this->likertScoreService->calculateLikertScores($likert);
                    $totalScore = $likertScores['total_score'];
                    $evalueeNameFromRef3 = $likert->evaluee_name;
                }

                $hasReferenciaIII = $evaluations->contains('evaluation_type', 'referencia_iii');
                $hasReferenciaV = $evaluations->contains('evaluation_type', 'referencia_v');
                $hasLikert = $evaluations->contains('evaluation_type', 'likert');

                $missingData = $this->checkMissingDataForListResults($evaluations, $hasLikert, $hasReferenciaIII, $hasReferenciaV, $likert);
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
     * Check for missing data in evaluation group (extracted for reuse)
     *
     * @return array<int, string>
     */
    private function checkMissingDataForListResults($evaluations, bool $hasLikert, bool $hasReferenciaIII, bool $hasReferenciaV, $likert): array
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
     * Download missing gap folios as CSV
     */
    public function downloadGapFolios(Organization $organization, Request $request)
    {
        $this->authorize('view-organization-results', $organization);

        $user = $request->user();

        // Only admin and super-admin can download gap folios
        if (! $this->isAdminOrSuperAdmin($user)) {
            abort(403, 'Solo administradores pueden descargar la lista de folios faltantes');
        }

        // Calculate missing folios using helper method
        $missingFolios = $this->calculateMissingFolios($organization);

        $filename = 'folios_faltantes_'.$organization->name.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new GapFoliosExport($missingFolios), $filename);
    }

    public function showDetailedResults(Organization $organization, string $personalFolio)
    {
        $this->authorize('view-organization-results', $organization);

        // Get all evaluations for this personal folio (include both paper and online sources)
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', $personalFolio)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->get();

        if ($evaluations->isEmpty()) {
            abort(404, 'No se encontraron evaluaciones para este folio personal');
        }

        // Get individual evaluations by type
        $referenciaI = $evaluations->firstWhere('evaluation_type', 'referencia_i');
        $referenciaIII = $evaluations->firstWhere('evaluation_type', 'referencia_iii');
        $referenciaV = $evaluations->firstWhere('evaluation_type', 'referencia_v');
        $cisneros = $evaluations->firstWhere('evaluation_type', 'cisneros');
        $likert = $evaluations->firstWhere('evaluation_type', 'likert');

        // Calculate scores for Referencia III
        $results = [];
        $totalScore = 0;

        if ($referenciaIII) {
            $detailedResults = $this->scoreService->getDetailedResults($referenciaIII);
            $scores = $this->scoreService->calculateReferenciaIIIScores($referenciaIII);
            $totalScore = $scores['total_score'];
            $results = $detailedResults;
        }

        // Format Guide I results
        $guideIResults = null;
        if ($referenciaI) {
            $questions = config('guide_i_questions');
            $answers = $referenciaI->referencia_i_answers ?? [];
            $mappedAnswers = [];
            foreach ($answers as $key => $value) {
                $label = $questions[$key] ?? $key;
                $mappedAnswers[$label] = $value;
            }
            $guideIResults = [
                'id' => $referenciaI->id,
                'folio' => $referenciaI->folio,
                'created_at' => $referenciaI->created_at->format('Y-m-d H:i:s'),
                'answers' => $mappedAnswers,
            ];
        }

        // Format Guide III results
        $guideIIIResults = null;
        if ($referenciaIII) {
            $questions = config('referencia_iii.general');
            $conditionalSections = config('referencia_iii.conditional_sections');
            $acontecimientos = config('referencia_iii.acontecimientos_traumaticos');
            $answers = $referenciaIII->referencia_iii_answers ?? [];
            $mappedAnswers = [];
            foreach ($answers as $key => $value) {
                $num = (int) ltrim($key, '0');
                $label = $questions[$num] ?? $key;
                $mappedAnswers[$label] = $value;
            }
            // Condicionales: incluir la condición y mapear cada pregunta
            $conditional = $referenciaIII->referencia_iii_conditional ?? [];
            $mappedConditional = [];
            foreach ($conditionalSections as $sectionKey => $section) {
                if (isset($conditional[$sectionKey])) {
                    $sectionData = $conditional[$sectionKey];
                    $conditionValue = $sectionData['condition'] ?? null;
                    $questionsData = $sectionData['questions'] ?? [];
                    $sectionLabel = $section['condition'];
                    $mappedQuestions = [];
                    foreach ($questionsData as $qKey => $qValue) {
                        $qNum = (int) ltrim($qKey, '0');
                        $qLabel = $section['questions'][$qNum] ?? $qKey;
                        $mappedQuestions[$qLabel] = $qValue;
                    }
                    $mappedConditional[] = [
                        'section' => $sectionLabel,
                        'condition' => $conditionValue,
                        'questions' => $mappedQuestions,
                    ];
                }
            }
            // CITSATS: usar el bloque de acontecimientos_traumaticos
            $citsats = $referenciaIII->citsats_s1 ?? [];
            $mappedCitsats = [];
            if (! empty($citsats)) {
                $citsatsQuestions = $acontecimientos['questions'] ?? [];
                foreach ($citsats as $key => $value) {
                    $num = (int) ltrim($key, '0');
                    $label = $citsatsQuestions[$num] ?? $key;
                    $mappedCitsats[$label] = $value;
                }
            }
            $guideIIIResults = [
                'id' => $referenciaIII->id,
                'folio' => $referenciaIII->folio,
                'created_at' => $referenciaIII->created_at->format('Y-m-d H:i:s'),
                'answers' => $mappedAnswers,
                'conditional' => $mappedConditional,
                'citsats_s1' => $mappedCitsats,
            ];
        }

        // Format Guide V results
        $guideVResults = null;
        if ($referenciaV) {
            $demographic = $referenciaV->demographic_data ?? [];
            $labels = [
                'sexo' => 'Sexo',
                'edad' => 'Edad',
                'estado_civil' => 'Estado Civil',
                'nivel_estudios' => 'Nivel de Estudios',
                'ocupacion_puesto' => 'Ocupación/Puesto',
                'departamento_seccion_area' => 'Departamento/Sección/Área',
                'tipo_puesto' => 'Tipo de Puesto',
                'tipo_contratacion' => 'Tipo de Contratación',
                'tipo_personal' => 'Tipo de Personal',
                'tipo_jornada' => 'Tipo de Jornada',
                'rotacion_turnos' => 'Rotación de Turnos',
                'tiempo_puesto_actual' => 'Tiempo en el Puesto Actual',
                'tiempo_experiencia_laboral' => 'Tiempo de Experiencia Laboral',
            ];
            $configV = config('referencia_v');
            $mappedDemographic = [];
            foreach ($demographic as $key => $value) {
                $label = $labels[$key] ?? $key;
                $displayValue = '';
                // Edad: puede venir como array { decenas, unidades }
                if ($key === 'edad' && is_array($value) && isset($value['decenas'], $value['unidades'])) {
                    $displayValue = $value['decenas'].$value['unidades'];
                }
                // Sexo
                elseif ($key === 'sexo' && is_string($value)) {
                    $displayValue = strtolower($value) === 'femenino' ? 'Femenino' : (strtolower($value) === 'masculino' ? 'Masculino' : ucfirst($value));
                }
                // Estado civil
                elseif ($key === 'estado_civil' && is_string($value)) {
                    $map = ['union_libre' => 'Unión libre', 'casado' => 'Casado', 'soltero' => 'Soltero', 'divorciado' => 'Divorciado', 'viudo' => 'Viudo'];
                    $displayValue = $map[$value] ?? ucfirst($value);
                }
                // Nivel de estudios
                elseif ($key === 'nivel_estudios' && is_array($value)) {
                    foreach ($value as $nivel => $datos) {
                        if (is_array($datos) && ! empty($datos['seleccionado'])) {
                            $labelNivel = ucfirst(str_replace('_', ' ', $nivel));
                            if (! empty($datos['completado'])) {
                                $labelNivel .= $datos['completado'] === 'completo' ? ' (Terminada)' : ' (Incompleta)';
                            }
                            $displayValue = $labelNivel;
                            break;
                        }
                    }
                }
                // Ocupación/Puesto y Departamento
                elseif (($key === 'ocupacion_puesto' || $key === 'departamento_seccion_area' || $key === 'ocupacion' || $key === 'departamento') && is_array($value)) {
                    $vals = array_filter(array_values($value), fn ($v) => ! is_null($v) && $v !== '');
                    $displayValue = $vals ? implode(' ', $vals) : 'Sin respuesta';
                }
                // Tipo de puesto, contratación, personal, jornada, rotación, etc.
                elseif (in_array($key, ['tipo_puesto', 'tipo_contratacion', 'tipo_personal', 'tipo_jornada', 'rotacion_turnos'])) {
                    $displayValue = is_string($value) ? ucwords(str_replace(['_', '-'], [' ', ' '], $value)) : '';
                }
                // Experiencia laboral y tiempo en el puesto actual - usar mapeo correcto
                elseif (in_array($key, ['tiempo_puesto_actual', 'experiencia_laboral', 'tiempo_experiencia_laboral'])) {
                    if (is_string($value)) {
                        // Reemplazar guiones bajos y "anos" por "años"
                        $displayValue = str_replace('_', ' ', $value);
                        $displayValue = str_replace('anos', 'años', $displayValue);
                        // Capitalizar correctamente
                        $displayValue = ucfirst($displayValue);
                        // Reemplazar "a" por "a" en rangos (Entre 5 a 9 años)
                        $displayValue = preg_replace('/\s+a\s+(\d)/', ' a $1', $displayValue);
                    } else {
                        $displayValue = '';
                    }
                }
                // Si no, mostrar como string
                else {
                    $displayValue = is_array($value) ? json_encode($value) : (string) $value;
                }
                $mappedDemographic[$label] = $displayValue;
            }
            $guideVResults = [
                'id' => $referenciaV->id,
                'folio' => $referenciaV->folio,
                'created_at' => $referenciaV->created_at->format('Y-m-d H:i:s'),
                'demographic_data' => $mappedDemographic,
                'raw_demographic_data' => $demographic, // Para edición
            ];
        }

        // Format Cisneros results
        $cisnerosResults = null;
        if ($cisneros) {
            $cisnerosResults = [
                'id' => $cisneros->id,
                'folio' => $cisneros->folio,
                'created_at' => $cisneros->created_at->format('Y-m-d H:i:s'),
                'answers' => $cisneros->cisneros_answers ?? [],
            ];
        }

        // Format Likert results (Clima Laboral)
        $likertResults = null;
        if ($likert) {
            $scores = $this->likertScoreService->calculateLikertScores($likert);
            $demographic = $this->likertScoreService->getDemographicData($likert);

            $likertResults = [
                'id' => $likert->id,
                'folio' => $likert->folio,
                'created_at' => $likert->created_at->format('Y-m-d H:i:s'),
                'scores' => $scores,
                'demographic' => $demographic,
            ];
        }

        return Inertia::render('Results/Detail', [
            'organization' => $organization->only('id', 'name'),
            'personalFolio' => $personalFolio,
            'evaluation' => [
                'id' => $referenciaIII?->id ?? $evaluations->first()->id,
                'folio' => $referenciaIII?->folio ?? $evaluations->first()->folio,
                'evaluee_name' => $referenciaIII?->evaluee_name ?? $evaluations->first()->evaluee_name,
                'created_at' => $referenciaIII?->created_at->format('Y-m-d H:i:s') ?? $evaluations->first()->created_at->format('Y-m-d H:i:s'),
                'personal_folio' => $personalFolio,
                'has_guide_i' => (bool) $referenciaI,
                'has_guide_iii' => (bool) $referenciaIII,
                'has_guide_v' => (bool) $referenciaV,
                'has_cisneros' => (bool) $cisneros,
                'has_likert' => (bool) $likert,
            ],
            'totalScore' => $totalScore,
            'results' => $results,
            'guideIResults' => $guideIResults,
            'guideVResults' => $guideVResults,
            'guideIIIResults' => $guideIIIResults,
            'cisnerosResults' => $cisnerosResults,
            'likertResults' => $likertResults,
            'isAdmin' => auth()->user()->hasRole(['admin', 'super-admin']),
            'occupationPositions' => $organization->occupationPositions()->get(['id', 'name'])->toArray(),
            'departmentAreas' => $organization->departmentAreas()->get(['id', 'name'])->toArray(),
        ]);
    }

    /**
     * Actualiza la respuesta de una pregunta de la Guía de Referencia V.
     *
     * @param  string  $question
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateGuideVQuestion(Request $request, Evaluation $evaluation, Question $question)
    {
        // Validar que la evaluación sea de la guía V
        if ($evaluation->reference_guide !== 'V') {
            return response()->json(['error' => 'La evaluación no pertenece a la Guía de Referencia V'], 400);
        }

        // Validar que la pregunta pertenezca a esta evaluación
        if ($question->evaluation_id !== $evaluation->id) {
            return response()->json(['error' => 'La pregunta no pertenece a esta evaluación'], 400);
        }

        // Validar datos de entrada
        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        // Actualizar la respuesta
        $question->update([
            'answer' => $validated['answer'],
        ]);

        return redirect()->back()->with([
            'success' => true,
            'message' => 'Respuesta actualizada correctamente',
            'question' => $question->only('id', 'question', 'answer'),
        ]);
    }

    public function showLikertDetails(Organization $organization, string $personalFolio)
    {
        $this->authorize('view-organization-results', $organization);

        // Get Likert evaluation for this personal folio
        $likert = PaperEvaluation::with('customFields')
            ->where('organization_id', $organization->id)
            ->where('personal_folio', $personalFolio)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->firstOrFail();

        // Calculate scores and get demographic data
        $scores = $this->likertScoreService->calculateLikertScores($likert);
        $demographic = $this->likertScoreService->getDemographicData($likert);

        // Get questions configuration
        $questionsText = config('likert-value.preguntas');
        $niveles = config('likert-value.niveles');
        $answersData = $likert->likert_answers['questions'] ?? [];

        // Map questions with their answers and values grouped by dimension
        $questionsList = [];

        foreach ($niveles as $dimension => $data) {
            $questionNumbers = $data['preguntas'];

            foreach ($questionNumbers as $questionNumber) {
                $questionText = $questionsText[$questionNumber] ?? 'Pregunta no encontrada';
                $answer = $answersData[$questionNumber] ?? null;
                $value = $this->getAnswerValue($answer);

                $questionsList[] = [
                    'number' => $questionNumber,
                    'dimension' => $dimension,
                    'text' => $questionText,
                    'answer' => $answer,
                    'value' => $value,
                ];
            }
        }

        $isAdmin = auth()->user()->hasRole(['admin', 'super-admin']);

        // Get custom fields as key-value pairs
        $customFieldLabels = config('custom_field_labels');
        $customFields = [];
        foreach ($likert->customFields as $customField) {
            $fieldKey = $customField->field_key;
            $dbLabel = $customField->key_label;

            // Use label from config if exists, otherwise use database label
            $label = $customFieldLabels[$fieldKey] ?? $dbLabel;

            $customFields[$fieldKey] = [
                'label' => $label,
                'value' => $customField->value,
            ];
        }

        return Inertia::render('Results/LikertDetail', [
            'organization' => $organization->only('id', 'name'),
            'personalFolio' => $personalFolio,
            'evaluation' => [
                'id' => $likert->id,
                'folio' => $likert->folio,
                'evaluee_name' => $likert->evaluee_name,
                'created_at' => $likert->created_at->format('Y-m-d H:i:s'),
                'personal_folio' => $personalFolio,
                'scanned_image_url' => $isAdmin ? asset('storage/folios/'.$likert->folio.'.png') : null,
            ],
            'scores' => $scores,
            'demographic' => $demographic,
            'customFields' => $customFields,
            'questions' => $questionsList,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Get numeric value for Likert answer
     */
    private function getAnswerValue(?string $answer): ?int
    {
        if ($answer === null) {
            return null;
        }

        $values = config('likert-value.valorOpciones');

        return $values[$answer] ?? null;
    }

    /**
     * Update Likert demographic data and evaluee name
     */
    public function updateLikertDemographicData(Organization $organization, string $personalFolio, Request $request)
    {
        $this->authorize('view-organization-results', $organization);

        // Get Likert evaluation
        $likert = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', $personalFolio)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->firstOrFail();

        // Update evaluee name if provided
        if ($request->filled('evaluee_name')) {
            $likert->update(['evaluee_name' => $request->input('evaluee_name')]);
        }

        // Update or create DemographicData
        $demographicData = $likert->demographicData ?? new DemographicData;

        $demographicData->fill([
            'gender' => $request->input('gender'),
            'work_schedule' => $request->input('work_schedule'),
            'contract_type' => $request->input('contract_type'),
            'position' => $request->input('position'),
            'department' => $request->input('department'),
        ]);

        if (! $demographicData->paper_evaluation_id) {
            $demographicData->paper_evaluation_id = $likert->id;
        }

        $demographicData->save();

        return response()->json([
            'success' => true,
            'message' => 'Datos demográficos actualizados correctamente',
        ]);
    }

    /**
     * Update Likert answers stored in PaperEvaluation.likert_answers JSON
     */
    public function updateLikertAnswers(Organization $organization, string $personalFolio, Request $request)
    {
        $this->authorize('view-organization-results', $organization);

        // Validate: answers must be an array of questionNumber => A|B|C|D|null
        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        // Find Likert evaluation for this person
        $likert = PaperEvaluation::where('organization_id', $organization->id)
            ->where('personal_folio', $personalFolio)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->firstOrFail();

        // Current likert_answers structure
        $likertAnswers = $likert->likert_answers ?? [];
        $questions = $likertAnswers['questions'] ?? [];

        // Sanitize and apply updates (only allow A/B/C/D or null/empty)
        foreach ($validated['answers'] as $q => $val) {
            $qNum = (string) (int) $q; // normalize keys to string numbers
            if ($val === null || $val === '') {
                $questions[$qNum] = null;

                continue;
            }
            $v = strtoupper(trim((string) $val));
            if (in_array($v, ['A', 'B', 'C', 'D'], true)) {
                $questions[$qNum] = $v;
            }
        }

        // Persist back preserving other fields in likert_answers
        $likertAnswers['questions'] = $questions;
        $likert->likert_answers = $likertAnswers;
        $likert->save();

        // Optionally return recalculated scores for instant UI feedback
        $scores = $this->likertScoreService->calculateLikertScores($likert);

        return response()->json([
            'success' => true,
            'message' => 'Respuestas actualizadas correctamente',
            'scores' => $scores,
        ]);
    }

    /**
     * Actualiza la respuesta de una pregunta de la Guía de Referencia III.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGuideIIIQuestion(Request $request, Evaluation $evaluation, Question $question)
    {
        // Validar que la evaluación sea de la guía III
        if ($evaluation->reference_guide !== 'III') {
            return response()->json(['error' => 'La evaluación no pertenece a la Guía de Referencia III'], 400);
        }

        // Validar que la pregunta pertenezca a esta evaluación
        if ($question->evaluation_id !== $evaluation->id) {
            return response()->json(['error' => 'La pregunta no pertenece a esta evaluación'], 400);
        }

        // Validar datos de entrada
        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        // Actualizar la respuesta
        $question->update([
            'answer' => $validated['answer'],
        ]);

        return redirect()->back()->with([
            'success' => true,
            'message' => 'Respuesta actualizada correctamente',
            'question' => $question->only('id', 'question', 'answer'),
        ]);
    }

    /**
     * Descargar plantilla de Excel para actualización masiva
     */
    public function downloadTemplate(Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        $filename = 'plantilla_actualizacion_'.$organization->name.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new EvaluationTemplateExport($organization),
            $filename
        );
    }

    /**
     * Procesar archivo de actualización masiva (dispatch to background job)
     */
    public function bulkUpdate(Request $request, Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
            'source' => 'nullable|in:paper,online',
        ]);

        $file = $request->file('file');
        $source = $request->input('source'); // null means both

        Log::info('=== BULK UPDATE REQUEST RECEIVED ===', [
            'organization_id' => $organization->id,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        try {
            // Store file temporarily
            $filePath = $file->store('bulk-imports', 'local');

            // Create bulk import job record
            $bulkImportJob = BulkImportJob::create([
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'source' => $source,
                'status' => 'pending',
            ]);

            // Dispatch job to queue
            ProcessBulkEvaluationImport::dispatch($bulkImportJob);

            Log::info('Bulk import job dispatched', [
                'bulk_import_job_id' => $bulkImportJob->id,
                'file_path' => $filePath,
            ]);

            return back()->with([
                'success' => true,
                'message' => 'El archivo se está procesando en segundo plano. Recibirás una notificación cuando termine.',
                'bulk_import_job_id' => $bulkImportJob->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk update dispatch exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'success' => false,
                'message' => 'Error al iniciar el procesamiento: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Download template for bulk comments import
     */
    public function bulkCommentsTemplate(Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        $filename = 'plantilla_comentarios_'.$organization->name.'_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new EvaluationCommentsTemplateExport($organization),
            $filename
        );
    }

    /**
     * Process bulk comments import file
     */
    public function bulkCommentsUpdate(Request $request, Organization $organization)
    {
        $this->authorize('view-organization-results', $organization);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');

        Log::info('=== BULK COMMENTS IMPORT REQUEST RECEIVED ===', [
            'organization_id' => $organization->id,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);

        try {
            // Store file temporarily
            $filePath = $file->store('bulk-imports', 'local');

            // Create bulk import job record
            $bulkImportJob = BulkImportJob::create([
                'organization_id' => $organization->id,
                'user_id' => $request->user()->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'source' => null, // Comments are only for Likert evaluations
                'status' => 'pending',
            ]);

            // Dispatch job to queue
            ProcessBulkCommentsImport::dispatch($bulkImportJob);

            Log::info('Bulk comments import job dispatched', [
                'bulk_import_job_id' => $bulkImportJob->id,
                'file_path' => $filePath,
            ]);

            return back()->with([
                'success' => true,
                'message' => 'El archivo de comentarios se está procesando en segundo plano. Recibirás una notificación cuando termine.',
                'bulk_import_job_id' => $bulkImportJob->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk comments import dispatch exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'success' => false,
                'message' => 'Error al iniciar el procesamiento: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Get status of a bulk import job
     */
    public function bulkImportStatus(BulkImportJob $bulkImportJob)
    {
        // Verify user owns this job
        if ($bulkImportJob->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->json([
            'id' => $bulkImportJob->id,
            'status' => $bulkImportJob->status,
            'total_rows' => $bulkImportJob->total_rows,
            'processed_rows' => $bulkImportJob->processed_rows,
            'updated_count' => $bulkImportJob->updated_count,
            'skipped_count' => $bulkImportJob->skipped_count,
            'progress_percentage' => $bulkImportJob->getProgressPercentage(),
            'errors' => $bulkImportJob->errors ?? [],
            'error_message' => $bulkImportJob->error_message,
            'file_name' => $bulkImportJob->file_name,
        ]);
    }

    /**
     * Check if user is admin or super-admin
     */
    private function isAdminOrSuperAdmin(?User $user): bool
    {
        return $user && $user->hasRole(['admin', 'super-admin']);
    }

    /**
     * Delete (cancel) a paper evaluation permanently
     * This removes the database record and associated image file
     */
    public function destroyEvaluation(Organization $organization, PaperEvaluation $evaluation): \Illuminate\Http\RedirectResponse
    {
        // Verify the evaluation belongs to this organization
        if ($evaluation->organization_id !== $organization->id) {
            return back()->with('error', 'La evaluación no pertenece a esta organización.');
        }

        $folio = $evaluation->folio;

        // Delete associated image file if exists
        $imagePath = storage_path("app/public/folios/{$folio}.png");
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Also delete backup image if exists
        $backupPath = storage_path("app/public/folios/{$folio}_v1.png");
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }

        // Force delete the evaluation (cascade will handle demographic_data and custom_fields)
        $evaluation->forceDelete();

        return back()->with('success', "Evaluación {$folio} eliminada correctamente.");
    }

    /**
     * Calculate missing folios based on gaps in uploaded evaluations
     */
    private function calculateMissingFolios(Organization $organization): array
    {
        // Get all paper evaluations for the organization (unique personal folios)
        $evaluationGroups = PaperEvaluation::where('organization_id', $organization->id)
            ->whereIn('source', ['paper', 'online'])
            ->where('processing_status', 'completed')
            ->get()
            ->groupBy('personal_folio');

        $existingFolios = $evaluationGroups->keys()->map(fn ($f) => (int) $f)->unique()->sort()->values()->toArray();

        $missingFolios = [];

        // Only calculate gaps if there are at least 2 evaluations
        if (count($existingFolios) >= 2) {
            $minFolio = min($existingFolios);
            $maxFolio = max($existingFolios);

            // Convert to associative array for O(1) lookups
            $existingFoliosLookup = array_flip($existingFolios);

            // Find gaps in the sequence between min and max
            $gaps = [];
            for ($i = $minFolio + 1; $i < $maxFolio; $i++) {
                if (! isset($existingFoliosLookup[$i])) {
                    $gaps[] = $i;
                }
            }

            // Group gaps by folio batch to maintain batch context
            $folioBatches = FolioBatch::where('organization_id', $organization->id)->get();

            if (! empty($gaps)) {
                // Pre-build batch lookup structure for O(batches + gaps) complexity
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

                // Convert batch map to array with counts
                foreach ($batchMap as $batchData) {
                    $missingFolios[] = [
                        'batch_name' => $batchData['batch_name'],
                        'batch_type' => $batchData['batch_type'],
                        'folios' => $batchData['folios'],
                        'count' => count($batchData['folios']),
                    ];
                }

                // Add ungrouped gaps if any
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
