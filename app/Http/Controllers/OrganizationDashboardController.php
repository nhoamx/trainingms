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

        $data = $this->organizationDataService->getDashboardData($organization);

        // Obtener evaluaciones con datos demográficos y calcular score
        // Solo incluir evaluaciones Likert completadas (igual que en LikertOrganizationReport)
        $config = config('likert-value');

        $evaluations = PaperEvaluation::where('organization_id', $organization->id)
            ->where('evaluation_type', 'likert')
            ->where('processing_status', 'completed')
            ->with('demographicData')
            ->get()
            ->map(function ($evaluation) use ($config) {
                $questions = $evaluation->likert_answers['questions'] ?? [];
                $scores = $this->likertScoreService->calculateLikertScoresFromData($questions, $config);

                return [
                    'id' => $evaluation->id,
                    'demographicData' => $evaluation->demographicData ? [
                        'gender' => $evaluation->demographicData->gender,
                        'contract_type' => $evaluation->demographicData->contract_type,
                        'position' => $evaluation->demographicData->position,
                        'department' => $evaluation->demographicData->department,
                        'work_schedule' => $evaluation->demographicData->work_schedule,
                    ] : null,
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
