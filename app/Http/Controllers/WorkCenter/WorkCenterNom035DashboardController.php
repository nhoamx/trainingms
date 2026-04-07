<?php

namespace App\Http\Controllers\WorkCenter;

use App\Http\Controllers\Controller;
use App\Models\OrganizationAnalysisBlock;
use App\Models\PaperEvaluation;
use App\Models\WorkCenter;
use App\Services\OrganizationReportCacheService;
use App\Services\WorkCenter\WorkCenterNom035CalculationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard Controller para NOM-035 Referencia III
 *
 * Este controlador maneja específicamente el dashboard de Referencia III
 * que incluye análisis de dominios, categorías y dimensiones de factores de riesgo psicosocial.
 *
 * Para el dashboard de Referencia I (ATS), ver: WorkCenterNom035RefIDashboardController
 */
class WorkCenterNom035DashboardController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected WorkCenterNom035CalculationService $calculationService,
        protected OrganizationReportCacheService $cacheService
    ) {}

    /**
     * Muestra el dashboard NOM-035 para un centro de trabajo
     */
    public function show(Request $request, WorkCenter $workCenter): Response
    {
        $this->authorize('viewWorkCenterDashboard', $workCenter);

        $workCenter->load('organization');
        $source = $this->resolveSourceFilter($request);

        $dashboardData = $this->buildDashboardData($workCenter);

        $domainStatistics = Cache::rememberForever(
            $this->cacheService->getWcNom035DomainsCacheKey($workCenter->id, $source),
            fn () => $this->calculationService->calculateDomainStatistics($workCenter, $source)
        );

        $categoryStatistics = Cache::rememberForever(
            $this->cacheService->getWcNom035CategoriesCacheKey($workCenter->id, $source),
            fn () => $this->calculationService->calculateCategoryStatistics($workCenter, $source)
        );

        $dimensionStatistics = Cache::rememberForever(
            $this->cacheService->getWcNom035DimensionsCacheKey($workCenter->id, $source),
            fn () => $this->calculationService->calculateDimensionStatistics($workCenter, $source)
        );

        $questionStatistics = Cache::rememberForever(
            $this->cacheService->getWcNom035QuestionsCacheKey($workCenter->id, $source),
            fn () => $this->calculationService->calculateQuestionStatistics($workCenter, $source)
        );

        $blockStatistics = Cache::rememberForever(
            $this->cacheService->getWcNom035BlocksCacheKey($workCenter->id, $source),
            fn () => $this->calculationService->calculateBlockStatistics($workCenter, $source)
        );

        $globalStatistics = Cache::rememberForever(
            $this->cacheService->getWcNom035GlobalCacheKey($workCenter->id, $source),
            fn () => $this->calculationService->calculateGlobalStatistics($workCenter, $source)
        );

        $analysisData = $this->calculationService->getEvaluationsWithDemographicsAndScores($workCenter, $source);

        $demographicFilters = array_filter([
            'genero' => $request->string('genero')->toString(),
            'puesto' => $request->string('puesto')->toString(),
            'area' => $request->string('area')->toString(),
            'turno' => $request->string('turno')->toString(),
        ], static fn (string $value): bool => trim($value) !== '');

        $generalReport = $demographicFilters === []
            ? Cache::rememberForever(
                $this->cacheService->getWcNom035GeneralReportCacheKey($workCenter->id, $source),
                fn () => $this->calculationService->getGeneralDetailedReport($workCenter, null, $source)
            )
            : $this->calculationService->getGeneralDetailedReport($workCenter, $demographicFilters, $source);

        $violenceLaborStatistics = Cache::rememberForever(
            $this->cacheService->getWcNom035ViolenceCacheKey($workCenter->id, $source),
            fn () => $this->calculationService->calculateViolenceLaborStatistics($workCenter, $source)
        );

        $evaluations = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->whereIn('evaluation_type', ['referencia_i', 'referencia_iii', 'referencia_v', 'cisneros'])
            ->where('processing_status', 'completed')
            ->with(['demographicData', 'comments'])
            ->when(
                in_array($source, ['online', 'paper'], true),
                fn ($query) => $query->where('source', $source),
                fn ($query) => $query->whereIn('source', ['paper', 'online'])
            )
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'evaluation_type' => $evaluation->evaluation_type,
                    'personal_folio' => $evaluation->personal_folio,
                    'source' => $evaluation->source,
                    'demographicData' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                    ] : null,
                ];
            });

        $availableEvaluationTypes = $this->getAvailableEvaluationTypes($workCenter, $source);

        $analysisBlocks = OrganizationAnalysisBlock::query()
            ->where('organization_id', $workCenter->organization_id)
            ->orderBy('instrument_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('instrument_type')
            ->map(fn ($blocks) => $blocks->map(fn (OrganizationAnalysisBlock $block) => [
                'id' => $block->id,
                'instrument_type' => $block->instrument_type,
                'title' => $block->title,
                'content_html' => $block->content_html,
                'sort_order' => $block->sort_order,
            ])->values())
            ->all();

        return Inertia::render('WorkCenters/Nom035RefIIIDashboard', [
            'title' => 'NOM-035-STPS-2018 - '.$workCenter->name,
            'selectedSource' => $source,
            'dashboardData' => $dashboardData,
            'domainStatistics' => $domainStatistics,
            'categoryStatistics' => $categoryStatistics,
            'dimensionStatistics' => $dimensionStatistics,
            'questionStatistics' => $questionStatistics,
            'blockStatistics' => $blockStatistics,
            'globalStatistics' => $globalStatistics,
            'analysisData' => $analysisData,
            'generalReport' => $generalReport,
            'violenceLaborStatistics' => $violenceLaborStatistics,
            'evaluations' => $evaluations,
            'availableEvaluationTypes' => $availableEvaluationTypes,
            'analysisBlocks' => [
                'referencia_i' => $analysisBlocks['referencia_i'] ?? [],
                'referencia_iii' => $analysisBlocks['referencia_iii'] ?? [],
            ],
            'canManageAnalysisBlocks' => request()->user()?->hasRole(['admin', 'super-admin']) ?? false,
            'preventionActions' => $workCenter->preventionActions()
                ->where('instrument_type', 'referencia_iii')
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($action) => [
                    'id' => $action->id,
                    'instrument_type' => $action->instrument_type,
                    'title' => $action->title,
                    'description' => $action->description,
                    'responsible' => $action->responsible,
                    'status' => $action->status,
                    'due_date' => $action->due_date?->format('Y-m-d'),
                    'sort_order' => $action->sort_order,
                    'updated_at' => $action->updated_at?->format('Y-m-d H:i'),
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Construye los datos del dashboard a partir del WorkCenter
     */
    private function buildDashboardData(WorkCenter $workCenter): array
    {
        $organization = $workCenter->organization;

        return [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'logo' => $organization->logo ? asset('storage/'.$organization->logo) : null,
            ],
            'work_center' => [
                'id' => $workCenter->id,
                'name' => $workCenter->name,
                'code' => $workCenter->code,
            ],
            'company_data' => [
                'general' => [
                    'name' => $workCenter->name,
                    'razon_social' => $workCenter->legal_name,
                    'rfc' => $workCenter->tax_id,
                    'registro_patronal' => $workCenter->employer_registration,
                    'actividad_principal' => $organization->actividad_principal,
                    'folio_organization' => $organization->folio_organization,
                ],
                'address' => [
                    'calle_numero' => $workCenter->street_address,
                    'colonia' => $workCenter->neighborhood,
                    'codigo_postal' => $workCenter->postal_code,
                    'municipio' => $workCenter->municipality,
                    'estado' => $workCenter->state,
                ],
                'contact' => [
                    'nombre' => $workCenter->contact_name,
                    'puesto' => $workCenter->contact_position,
                    'email' => $workCenter->contact_email,
                    'movil' => $workCenter->contact_phone,
                ],
                'responsible' => [
                    'nombre' => $workCenter->responsible_name,
                    'puesto' => $workCenter->responsible_position,
                    'email' => $workCenter->responsible_email,
                    'movil' => $workCenter->responsible_phone,
                ],
                'workforce' => [
                    'total_trabajadores' => $workCenter->total_workers,
                    'total_hombres' => $workCenter->total_men,
                    'total_mujeres' => $workCenter->total_women,
                ],
                'sample' => [
                    'muestra_aplicada' => $workCenter->sample_applied,
                    'muestra_hombres' => $workCenter->sample_men,
                    'muestra_mujeres' => $workCenter->sample_women,
                    'justificacion_muestra' => $workCenter->sample_justification,
                ],
                'evaluation_date' => $workCenter->application_date,
                'committee' => [
                    'comite_integrantes' => $workCenter->committee_members,
                    'comite_hombres' => $workCenter->committee_men,
                    'comite_mujeres' => $workCenter->committee_women,
                ],
            ],
            'demographic_summary' => [],
            'demographic_details' => [
                'genders' => [],
                'contract_types' => [],
                'positions' => [],
                'areas' => [],
                'shifts' => [],
                'total_evaluations' => 0,
            ],
        ];
    }

    /**
     * Get available evaluation types for a work center based on actual data
     *
     * @return array<int, array{key: string, label: string, description: string, badge: string, color: string, icon: string}>
     */
    private function getAvailableEvaluationTypes(WorkCenter $workCenter, ?string $source = null): array
    {
        $evaluationTypes = PaperEvaluation::query()
            ->where('work_center_id', $workCenter->id)
            ->where('processing_status', 'completed')
            ->when(
                in_array($source, ['online', 'paper'], true),
                fn ($query) => $query->where('source', $source),
                fn ($query) => $query->whereIn('source', ['paper', 'online'])
            )
            ->distinct()
            ->pluck('evaluation_type')
            ->toArray();

        $typeConfigurations = [
            'referencia_i' => [
                'key' => 'referencia_i',
                'label' => 'Referencia I (ATS)',
                'description' => 'Identificación de trabajadores con ATS (Acontecimiento Traumáticos Severos)',
                'badge' => 'Evaluación ATS',
                'color' => 'red',
                'icon' => 'DocumentTextIcon',
            ],
            'referencia_iii' => [
                'key' => 'referencia_iii',
                'label' => 'Referencia III',
                'description' => 'Identificación de factores de riesgo psicosocial en el trabajo',
                'badge' => 'Factores de Riesgo',
                'color' => 'amber',
                'icon' => 'AdjustmentsHorizontalIcon',
            ],
            'referencia_v' => [
                'key' => 'referencia_v',
                'label' => 'Referencia V',
                'description' => 'Datos demográficos y características del entorno de trabajo',
                'badge' => 'Datos Demográficos',
                'color' => 'green',
                'icon' => 'UserGroupIcon',
            ],
            'cisneros' => [
                'key' => 'cisneros',
                'label' => 'Escala Cisneros',
                'description' => 'Evaluación de violencia laboral, acoso y mobbing',
                'badge' => 'Violencia Laboral',
                'color' => 'orange',
                'icon' => 'ShieldCheckIcon',
            ],
        ];

        $availableTypes = [];
        foreach ($evaluationTypes as $type) {
            if (isset($typeConfigurations[$type])) {
                $availableTypes[] = $typeConfigurations[$type];
            }
        }

        return $availableTypes;
    }

    private function resolveSourceFilter(Request $request): ?string
    {
        $source = $request->query('source');

        if (! is_string($source) || ! in_array($source, ['online', 'paper', 'all'], true)) {
            return null;
        }

        return $source === 'all' ? null : $source;
    }
}
