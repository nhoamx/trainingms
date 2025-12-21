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
        protected LikertScoreService $likertScoreService
    ) {}

    /**
     * Muestra el dashboard de la organización
     */
    public function show(Organization $organization): Response
    {
        $this->authorize('viewOrganizationDashboard', $organization);

        // Caliza organization ID
        $calizaOrganizationId = 'a0315c7c-d7a2-4969-b51e-d126fa6da1af';

        // Check if this is Caliza organization (NOM-035 report)
        if ($organization->id === $calizaOrganizationId) {
            return $this->showCalizaDashboard($organization);
        }

        // Default: Likert/Clima Laboral dashboard
        return $this->showLikertDashboard($organization);
    }

    /**
     * Muestra el dashboard de Caliza (NOM-035)
     */
    protected function showCalizaDashboard(Organization $organization): Response
    {
        $data = $this->organizationDataService->getDashboardData($organization, 'nom035');

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
            'evaluations' => $evaluations,
        ]);
    }

    /**
     * Muestra el dashboard de Likert/Clima Laboral
     */
    protected function showLikertDashboard(Organization $organization): Response
    {
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
}
