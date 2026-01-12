<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\PaperEvaluation;
use App\Services\LikertScoreService;
use App\Services\OrganizationDataService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationDashboardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Constructor
     */
    public function __construct(
        protected OrganizationDataService $organizationDataService,
        protected LikertScoreService $likertScoreService,
        protected \App\Services\Nom035DomainCalculationService $domainCalculationService
    ) {}

    /**
     * Muestra el dashboard de la organización
     *
     * Determina dinámicamente qué tipo(s) de evaluación tiene la organización
     * y redirige al dashboard correspondiente o muestra un selector si hay múltiples
     */
    public function show(Organization $organization): Response
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        // Get evaluation types for this organization
        $evaluationTypes = $this->getEvaluationTypesForOrganization($organization);

        // If only one evaluation type (or no config, defaults to Likert), show that dashboard
        if (count($evaluationTypes) === 1) {
            $evaluationType = reset($evaluationTypes);

            return $this->showDashboardForType($organization, $evaluationType);
        }

        // If multiple evaluation types, show selection landing page
        return $this->showEvaluationTypeSelector($organization, $evaluationTypes);
    }

    /**
     * Get evaluation types configured for the organization
     *
     * @return array Array of evaluation type configurations
     */
    protected function getEvaluationTypesForOrganization(Organization $organization): array
    {
        $evaluationTypes = config('evaluation_types', []);
        $organizationTypes = [];

        foreach ($evaluationTypes as $typeKey => $typeConfig) {
            if (in_array($organization->id, $typeConfig['organizations'] ?? [])) {
                $organizationTypes[$typeKey] = $typeConfig;
            }
        }

        // If no evaluation types are configured, return Likert as default
        if (empty($organizationTypes)) {
            return [
                'clima_laboral' => $evaluationTypes['clima_laboral'] ?? [],
            ];
        }

        return $organizationTypes;
    }

    /**
     * Show the evaluation type selector landing page
     */
    protected function showEvaluationTypeSelector(Organization $organization, array $evaluationTypes): Response
    {
        return Inertia::render('Organizations/EvaluationTypeSelector', [
            'title' => 'Seleccionar Tipo de Evaluación',
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'evaluationTypes' => array_map(function ($type) use ($organization) {
                return [
                    'id' => $type['id'],
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'route' => route($type['route'], $organization->id),
                ];
            }, $evaluationTypes),
        ]);
    }

    /**
     * Show dashboard for a specific evaluation type
     */
    protected function showDashboardForType(Organization $organization, array $evaluationType): Response
    {
        $typeId = $evaluationType['id'];

        return match ($typeId) {
            'nom_035' => $this->showCalizaDashboard($organization),
            'clima_laboral' => $this->showLikertDashboard($organization),
            'nom_002' => $this->showNom002Dashboard($organization),
            default => $this->showLikertDashboard($organization),
        };
    }

    /**
     * Muestra el dashboard de Caliza (NOM-035)
     * Public method for route binding
     */
    public function showCalizaDashboard(Organization $organization): Response
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        $data = $this->organizationDataService->getDashboardData($organization, 'nom035');

        // Calcular estadísticas por dominio y categoría
        $domainStatistics = $this->domainCalculationService->calculateDomainStatistics($organization);
        $categoryStatistics = $this->domainCalculationService->calculateCategoryStatistics($organization);

        // Obtener evaluaciones NOM-035 completadas con datos demográficos
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->whereIn('evaluation_type', ['referencia_i', 'referencia_iii', 'cisneros'])
            ->where('processing_status', 'completed')
            ->with(['demographicData', 'comments'])
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'evaluation_type' => $evaluation->evaluation_type,
                    'personal_folio' => $evaluation->personal_folio,
                    'demographicData' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                    ] : null,
                ];
            });

        return Inertia::render('Organizations/CalizaDashboard', [
            'title' => 'NOM-035-STPS-2018',
            'dashboardData' => $data,
            'domainStatistics' => $domainStatistics,
            'categoryStatistics' => $categoryStatistics,
            'evaluations' => $evaluations,
        ]);
    }

    /**
     * Muestra el dashboard de Likert/Clima Laboral
     * Public method for route binding
     */
    public function showLikertDashboard(Organization $organization): Response
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        $data = $this->organizationDataService->getDashboardData($organization, 'likert');

        // Obtener evaluaciones con datos demográficos y calcular score
        // Solo incluir evaluaciones Likert completadas (igual que en LikertOrganizationReport)
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->with(['demographicData', 'comments'])
            ->get()
            ->map(function ($evaluation) {
                $scores = $this->likertScoreService->calculateLikertScores($evaluation);

                // Format dimensions for frontend consumption
                $dimensions = [];
                foreach ($scores['dimensions'] as $dimensionName => $dimensionData) {
                    $dimensions[] = [
                        'name' => $dimensionName,
                        'score' => $dimensionData['score'],
                        'interpretation' => $dimensionData['interpretation'],
                    ];
                }

                // Format comments for frontend consumption
                $comments = $evaluation->comments->map(function ($comment) {
                    return [
                        'factor' => $comment->factor,
                        'comment' => $comment->comment,
                    ];
                });

                return [
                    'id' => $evaluation->id,
                    'demographicData' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                    ] : null,
                    'dimensions' => $dimensions,
                    'comments' => $comments,
                    'total_score' => $scores['total_score'],
                    'interpretation' => $scores['interpretation'],
                ];
            });

        return Inertia::render('Organizations/Dashboard', [
            'title' => 'Clima Laboral',
            'dashboardData' => $data,
            'evaluations' => $evaluations,
        ]);
    }

    /**
     * Muestra el dashboard de NOM-002
     * Public method for route binding
     */
    public function showNom002Dashboard(Organization $organization): Response
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        $data = $this->organizationDataService->getDashboardData($organization, 'nom002');

        // Obtener evaluaciones NOM-002 completadas con datos demográficos
        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->where('evaluation_type', 'nom_002')
            ->where('processing_status', 'completed')
            ->with(['demographicData', 'comments'])
            ->get()
            ->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'evaluation_type' => $evaluation->evaluation_type,
                    'personal_folio' => $evaluation->personal_folio,
                    'demographicData' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                    ] : null,
                ];
            });

        return Inertia::render('Organizations/Nom002Dashboard', [
            'title' => 'NOM-002-STPS-2010',
            'organization' => $organization,
            'dashboardData' => $data,
            'evaluations' => $evaluations,
        ]);
    }
}
